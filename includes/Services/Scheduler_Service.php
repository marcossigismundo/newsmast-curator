<?php
namespace NewsmastCurator\Services;

use NewsmastCurator\Core\Database;
use NewsmastCurator\Repositories\Publication_Repository;
use NewsmastCurator\Repositories\Collection_Repository;
use NewsmastCurator\Repositories\Item_Repository;
use NewsmastCurator\Repositories\Mastodon_Account_Repository;

class Scheduler_Service {
    private $database;
    private $pub_repo;
    private $logger;
    private $lock_key = 'nc_scheduler_lock';

    public function __construct(Database $database) {
        $this->database = $database;
        $this->pub_repo = new Publication_Repository($database);
        $this->logger = new Logger_Service($database);
    }

    public function process_scheduled_publications() {
        if ($this->is_locked()) {
            $this->logger->info('Scheduler bloqueado por lock ativo', [
                'related_type' => 'scheduler',
            ]);
            return;
        }
        if (!$this->acquire_lock()) {
            $this->logger->warning('Scheduler não conseguiu adquirir lock', [
                'related_type' => 'scheduler',
            ]);
            return;
        }

        try {
            // Recover publications stuck in 'processing' due to crashes
            $recovered = $this->pub_repo->recover_stuck_processing(5);
            if ($recovered > 0) {
                $this->logger->warning('Publicações travadas recuperadas', [
                    'related_type' => 'scheduler',
                    'details' => sprintf('%d publicação(ões) estavam travadas em "processing" e foram reagendadas', $recovered),
                ]);
            }

            $publications = $this->pub_repo->find_pending_for_processing(5);

            $this->logger->info('Scheduler executando', [
                'related_type' => 'scheduler',
                'details' => sprintf('Encontradas %d publicações pendentes', count($publications)),
            ]);

            foreach ($publications as $pub) {
                $this->process_publication($pub);
            }

            // Refresh collection statuses based on publication results
            if (!empty($publications)) {
                $collection_repo = new Collection_Repository($this->database);
                $collection_repo->refresh_statuses();
            }
        } finally {
            $this->release_lock();
        }
    }

    private function process_publication($pub) {
        $content = $pub->get_content();
        $content_preview = mb_substr(wp_strip_all_tags($content), 0, 80);

        // Resolve qual serviço Mastodon usar
        $mastodon_service = $this->resolve_mastodon_service($pub);
        $account_label = $this->get_account_label($pub);

        $this->logger->info('Iniciando processamento de publicação', [
            'related_type' => 'publication',
            'related_id' => $pub->get_id(),
            'details' => sprintf('Item #%d | Agendada para: %s | Conta: %s', $pub->get_item_id(), $pub->get_scheduled_for(), $account_label),
            'content_preview' => $content_preview,
            'attempt' => $pub->get_attempt_count() + 1,
        ]);

        $pub->mark_as_processing();
        $this->pub_repo->update($pub);

        try {
            $media_ids = $this->upload_item_image($pub, $mastodon_service);

            if (!empty($media_ids)) {
                $this->logger->info('Imagem enviada ao Mastodon', [
                    'related_type' => 'publication',
                    'related_id' => $pub->get_id(),
                    'details' => sprintf('media_ids: %s | Conta: %s', implode(', ', $media_ids), $account_label),
                    'media_count' => count($media_ids),
                ]);
            }

            // Thread support: resolve in_reply_to_id for threaded publications
            $in_reply_to_id = $this->resolve_thread_reply_id($pub);

            // Thread predecessor not yet published — reschedule
            if ($in_reply_to_id === '__THREAD_WAIT__') {
                $pub->reschedule(2);
                $this->pub_repo->update($pub);
                return;
            }

            $result = $mastodon_service->post_status($content, $media_ids, $in_reply_to_id);

            if ($result && isset($result['id'])) {
                $pub->mark_as_published($result['id'], $result['url'] ?? '');
                $this->pub_repo->update($pub);
                $this->logger->success('Publicação realizada com sucesso', [
                    'related_type' => 'publication',
                    'related_id' => $pub->get_id(),
                    'mastodon_id' => $result['id'],
                    'mastodon_url' => $result['url'] ?? '',
                    'content_preview' => $content_preview,
                    'media_count' => count($media_ids),
                    'details' => sprintf('Mastodon ID: %s | Mídia: %d | Conta: %s', $result['id'], count($media_ids), $account_label),
                ]);
            } else {
                throw new \Exception('Resposta inválida do Mastodon: ' . wp_json_encode($result));
            }
        } catch (\Exception $e) {
            $pub->increment_attempts();

            if ($pub->can_retry()) {
                $pub->reschedule(10);
                $this->logger->warning('Falha na publicação, reagendando', [
                    'related_type' => 'publication',
                    'related_id' => $pub->get_id(),
                    'error' => $e->getMessage(),
                    'attempt' => $pub->get_attempt_count(),
                    'details' => sprintf('Tentativa %d/%d | Próxima em 10min | Conta: %s', $pub->get_attempt_count(), get_option('nc_max_attempts', 3), $account_label),
                    'content_preview' => $content_preview,
                ]);
            } else {
                $pub->mark_as_failed($e->getMessage());
                $this->logger->error('Publicação falhou definitivamente', [
                    'related_type' => 'publication',
                    'related_id' => $pub->get_id(),
                    'error' => $e->getMessage(),
                    'attempt' => $pub->get_attempt_count(),
                    'details' => sprintf('Esgotadas %d tentativas | Conta: %s', $pub->get_attempt_count(), $account_label),
                    'content_preview' => $content_preview,
                ]);
            }

            $this->pub_repo->update($pub);
        }
    }

    /**
     * Resolve qual serviço Mastodon usar para a publicação
     */
    private function resolve_mastodon_service($pub) {
        $account_id = $pub->get_mastodon_account_id();

        if ($account_id) {
            try {
                return Mastodon_Service::for_account($account_id, $this->database);
            } catch (\Exception $e) {
                $this->logger->warning('Conta Mastodon específica indisponível, usando padrão', [
                    'related_type' => 'publication',
                    'related_id' => $pub->get_id(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Tenta conta padrão cadastrada, senão usa configuração legada
        return Mastodon_Service::get_default($this->database);
    }

    /**
     * Obtém label da conta para logs
     */
    private function get_account_label($pub) {
        $account_id = $pub->get_mastodon_account_id();
        if (!$account_id) {
            return 'padrão';
        }

        $repo = new Mastodon_Account_Repository($this->database);
        $account = $repo->find($account_id);
        return $account ? $account->get_name() : "#{$account_id}";
    }

    /**
     * Resolve o in_reply_to_id para publicações em thread
     *
     * @param \NewsmastCurator\Models\Publication $pub
     * @return string|null Mastodon ID do post anterior na thread
     */
    private function resolve_thread_reply_id($pub) {
        if (!$pub->is_thread() || $pub->get_thread_position() <= 0) {
            return null;
        }

        $prev = $this->pub_repo->find_last_published_in_thread(
            $pub->get_thread_id(),
            $pub->get_thread_position()
        );

        if ($prev && $prev->get_mastodon_id()) {
            $this->logger->info('Thread: encadeando como resposta', [
                'related_type' => 'publication',
                'related_id' => $pub->get_id(),
                'details' => sprintf(
                    'Thread %s | Posição %d → reply_to Mastodon ID %s (posição %d)',
                    $pub->get_thread_id(),
                    $pub->get_thread_position(),
                    $prev->get_mastodon_id(),
                    $prev->get_thread_position()
                ),
            ]);
            return $prev->get_mastodon_id();
        }

        // Previous items not yet published — skip thread for now
        if (!$this->pub_repo->are_previous_thread_items_published(
            $pub->get_thread_id(),
            $pub->get_thread_position()
        )) {
            $this->logger->warning('Thread: itens anteriores pendentes, reagendando', [
                'related_type' => 'publication',
                'related_id' => $pub->get_id(),
                'details' => sprintf(
                    'Thread %s | Posição %d aguardando predecessores',
                    $pub->get_thread_id(),
                    $pub->get_thread_position()
                ),
            ]);
            // Return a special marker to indicate rescheduling is needed
            return '__THREAD_WAIT__';
        }

        $this->logger->warning('Thread: post anterior não encontrado, publicando sem encadeamento', [
            'related_type' => 'publication',
            'related_id' => $pub->get_id(),
            'details' => sprintf('Thread %s | Posição %d', $pub->get_thread_id(), $pub->get_thread_position()),
        ]);

        return null;
    }

    private function upload_item_image($pub, $mastodon_service) {
        $item_repo = new Item_Repository($this->database);
        $item = $item_repo->find($pub->get_item_id());

        if (!$item || !$item->has_image()) {
            $this->logger->info('Publicação sem imagem associada', [
                'related_type' => 'publication',
                'related_id' => $pub->get_id(),
                'details' => 'Publicando somente texto',
            ]);
            return [];
        }

        $image_url = $item->get_image();
        if (empty($image_url)) {
            return [];
        }

        // Usa alt text da publicação (editado pelo usuário) ou gera automaticamente
        $alt_text = $pub->get_alt_text();
        if (empty($alt_text)) {
            $alt_text = $item->build_alt_text();
        }

        $this->logger->info('Preparando upload de imagem', [
            'related_type' => 'publication',
            'related_id' => $pub->get_id(),
            'details' => sprintf('URL: %s | Local ID: %s | Alt: %d chars', $image_url, $item->get_image_local_id() ?: 'N/A', mb_strlen($alt_text)),
        ]);

        $tmp_file = null;
        try {
            // If it's a local WordPress attachment, get the file path directly
            if ($item->get_image_local_id()) {
                $local_path = get_attached_file($item->get_image_local_id());
                if ($local_path && file_exists($local_path)) {
                    $media_id = $mastodon_service->upload_media($local_path, $alt_text);
                    return $media_id ? [$media_id] : [];
                }
            }

            // Download remote image to temp file
            $response = wp_remote_get($image_url, ['timeout' => 15]);
            if (is_wp_error($response)) {
                $this->logger->warning('Falha ao baixar imagem para upload', [
                    'related_type' => 'publication',
                    'related_id' => $pub->get_id(),
                    'error' => $response->get_error_message(),
                    'details' => sprintf('URL: %s', $image_url),
                ]);
                return [];
            }

            $http_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            if (empty($body)) {
                $this->logger->warning('Imagem vazia ou inacessível', [
                    'related_type' => 'publication',
                    'related_id' => $pub->get_id(),
                    'details' => sprintf('HTTP %d | URL: %s', $http_code, $image_url),
                ]);
                return [];
            }

            $ext = pathinfo(wp_parse_url($image_url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
            $tmp_file = tempnam(get_temp_dir(), 'nc_media_') . '.' . $ext;
            file_put_contents($tmp_file, $body);

            $this->logger->info('Imagem baixada, enviando ao Mastodon', [
                'related_type' => 'publication',
                'related_id' => $pub->get_id(),
                'details' => sprintf('Tamanho: %s | Tipo: .%s', size_format(strlen($body)), $ext),
            ]);

            $media_id = $mastodon_service->upload_media($tmp_file, $alt_text);
            return $media_id ? [$media_id] : [];
        } catch (\Exception $e) {
            $this->logger->warning('Falha no upload de imagem, publicando sem mídia', [
                'related_type' => 'publication',
                'related_id' => $pub->get_id(),
                'error' => $e->getMessage(),
                'details' => sprintf('URL: %s', $image_url),
            ]);
            return [];
        } finally {
            if ($tmp_file && file_exists($tmp_file)) {
                @unlink($tmp_file);
            }
        }
    }

    private function is_locked() {
        $lock = get_option($this->lock_key);
        if (!$lock) {
            return false;
        }
        // If lock has expired, clean it up so acquire_lock can work
        if ($lock <= time()) {
            delete_option($this->lock_key);
            return false;
        }
        return true;
    }

    private function acquire_lock() {
        // First try add_option (for when option doesn't exist)
        if (add_option($this->lock_key, time() + 300, '', 'no')) {
            return true;
        }
        // If add_option failed, the option might exist with expired value
        // Try to update it (atomic check via current value)
        $current = get_option($this->lock_key);
        if ($current && $current <= time()) {
            // Lock expired, take ownership
            update_option($this->lock_key, time() + 300, 'no');
            return true;
        }
        return false;
    }

    private function release_lock() {
        delete_option($this->lock_key);
    }
}
