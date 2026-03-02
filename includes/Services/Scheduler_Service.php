<?php
namespace NewsmastCurator\Services;

use NewsmastCurator\Core\Database;
use NewsmastCurator\Repositories\Publication_Repository;
use NewsmastCurator\Repositories\Collection_Repository;
use NewsmastCurator\Repositories\Item_Repository;

class Scheduler_Service {
    private $database;
    private $pub_repo;
    private $mastodon_service;
    private $logger;
    private $lock_key = 'nc_scheduler_lock';

    public function __construct(Database $database) {
        $this->database = $database;
        $this->pub_repo = new Publication_Repository($database);
        $this->mastodon_service = new Mastodon_Service();
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
        $pub->mark_as_processing();
        $this->pub_repo->update($pub);

        try {
            $media_ids = $this->upload_item_image($pub);
            $result = $this->mastodon_service->post_status($pub->get_content(), $media_ids);

            if ($result && isset($result['id'])) {
                $pub->mark_as_published($result['id'], $result['url'] ?? '');
                $this->pub_repo->update($pub);
                $this->logger->success('Publicação realizada', [
                    'related_type' => 'publication',
                    'related_id' => $pub->get_id(),
                ]);
            } else {
                throw new \Exception('Resposta inválida do Mastodon');
            }
        } catch (\Exception $e) {
            $pub->increment_attempts();

            if ($pub->can_retry()) {
                $pub->reschedule(10);
                $this->logger->warning('Falha na publicação, reagendando', [
                    'related_type' => 'publication',
                    'related_id' => $pub->get_id(),
                    'error' => $e->getMessage(),
                ]);
            } else {
                $pub->mark_as_failed($e->getMessage());
                $this->logger->error('Publicação falhou definitivamente', [
                    'related_type' => 'publication',
                    'related_id' => $pub->get_id(),
                    'error' => $e->getMessage(),
                ]);
            }

            $this->pub_repo->update($pub);
        }
    }

    private function upload_item_image($pub) {
        $item_repo = new Item_Repository($this->database);
        $item = $item_repo->find($pub->get_item_id());

        if (!$item || !$item->has_image()) {
            return [];
        }

        $image_url = $item->get_image();
        if (empty($image_url)) {
            return [];
        }

        $tmp_file = null;
        try {
            // If it's a local WordPress attachment, get the file path directly
            if ($item->get_image_local_id()) {
                $local_path = get_attached_file($item->get_image_local_id());
                if ($local_path && file_exists($local_path)) {
                    $media_id = $this->mastodon_service->upload_media($local_path);
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
                ]);
                return [];
            }

            $body = wp_remote_retrieve_body($response);
            if (empty($body)) {
                return [];
            }

            $ext = pathinfo(wp_parse_url($image_url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
            $tmp_file = wp_tempnam('nc_media_.' . $ext);
            file_put_contents($tmp_file, $body);

            $media_id = $this->mastodon_service->upload_media($tmp_file);
            return $media_id ? [$media_id] : [];
        } catch (\Exception $e) {
            $this->logger->warning('Falha no upload de imagem, publicando sem mídia', [
                'related_type' => 'publication',
                'related_id' => $pub->get_id(),
                'error' => $e->getMessage(),
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
