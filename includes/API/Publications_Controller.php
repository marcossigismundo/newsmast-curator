<?php
namespace NewsmastCurator\API;

use NewsmastCurator\Repositories\Publication_Repository;
use NewsmastCurator\Repositories\Item_Repository;
use NewsmastCurator\Models\Publication;

class Publications_Controller extends Base_REST_Controller {
    protected $rest_base = 'publications';

    public function register_routes() {
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            ['methods' => 'GET', 'callback' => [$this, 'get_items'], 'permission_callback' => [$this, 'check_permissions']],
            ['methods' => 'POST', 'callback' => [$this, 'create_item'], 'permission_callback' => [$this, 'check_permissions']],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
            ['methods' => 'DELETE', 'callback' => [$this, 'delete_item'], 'permission_callback' => [$this, 'check_permissions']],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/retry', [
            ['methods' => 'POST', 'callback' => [$this, 'retry_item'], 'permission_callback' => [$this, 'check_permissions']],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/process-now', [
            ['methods' => 'POST', 'callback' => [$this, 'process_now'], 'permission_callback' => [$this, 'check_permissions']],
        ]);
    }

    public function get_items($request) {
        $repo = new Publication_Repository($this->database);
        $args = [];
        if (isset($request['status'])) $args['status'] = $request['status'];
        $args['per_page'] = $request['per_page'] ?? 20;
        $args['page'] = $request['page'] ?? 1;

        $pubs = $repo->find_all($args);
        return $this->prepare_response(array_map(fn($p) => $p->to_api_response(), $pubs));
    }

    public function create_item($request) {
        $item_id = isset($request['item_id']) ? (int) $request['item_id'] : 0;
        $scheduled_for = isset($request['scheduled_for']) ? sanitize_text_field($request['scheduled_for']) : '';
        $content = isset($request['content']) ? sanitize_textarea_field($request['content']) : '';
        $alt_text = isset($request['alt_text']) ? sanitize_textarea_field($request['alt_text']) : '';
        $mastodon_account_ids = isset($request['mastodon_account_ids']) ? array_map('intval', (array) $request['mastodon_account_ids']) : [];
        $mastodon_account_id = isset($request['mastodon_account_id']) ? (int) $request['mastodon_account_id'] : 0;

        if (empty($item_id)) {
            return $this->prepare_error(__('Item é obrigatório', 'newsmast-curator'), 'validation_error', 400);
        }

        $item_repo = new Item_Repository($this->database);
        if (!$item_repo->exists($item_id)) {
            return $this->prepare_error(__('Item não encontrado', 'newsmast-curator'), 'not_found', 404);
        }

        if (empty($scheduled_for)) {
            return $this->prepare_error(__('Data de agendamento é obrigatória', 'newsmast-curator'), 'validation_error', 400);
        }

        $timestamp = strtotime($scheduled_for);
        // Allow same-day scheduling: accept if scheduled_for is within 60 seconds in the past
        // (to account for processing delay), but never truly retroactive
        $now = current_time('timestamp');
        if (!$timestamp || $timestamp < ($now - 60)) {
            return $this->prepare_error(__('Data de agendamento deve ser atual ou futura (não retroativa)', 'newsmast-curator'), 'validation_error', 400);
        }

        if (empty($content)) {
            return $this->prepare_error(__('Conteúdo é obrigatório', 'newsmast-curator'), 'validation_error', 400);
        }

        // Normalize datetime to MySQL format (Y-m-d H:i:s) for consistent DB comparison
        $normalized_datetime = date('Y-m-d H:i:s', $timestamp);

        $repo = new Publication_Repository($this->database);

        // Se múltiplas contas selecionadas, cria uma publicação por conta
        if (!empty($mastodon_account_ids)) {
            $created = [];
            foreach ($mastodon_account_ids as $account_id) {
                $pub = new Publication();
                $pub->set_item_id($item_id);
                $pub->set_mastodon_account_id($account_id);
                $pub->set_scheduled_for($normalized_datetime);
                $pub->set_content($content);
                $pub->set_alt_text($alt_text);
                $pub->set_published_by(get_current_user_id());

                $id = $repo->insert($pub);
                if ($id) {
                    $created[] = $pub->to_api_response();
                }
            }
            return !empty($created)
                ? $this->prepare_response($created, 201)
                : $this->prepare_error(__('Falha ao criar publicações', 'newsmast-curator'));
        }

        // Publicação única (conta única ou padrão)
        $pub = new Publication();
        $pub->set_item_id($item_id);
        if ($mastodon_account_id > 0) {
            $pub->set_mastodon_account_id($mastodon_account_id);
        }
        $pub->set_scheduled_for($normalized_datetime);
        $pub->set_content($content);
        $pub->set_alt_text($alt_text);
        $pub->set_published_by(get_current_user_id());

        $id = $repo->insert($pub);

        return $id ? $this->prepare_response($pub->to_api_response(), 201) : $this->prepare_error(__('Falha ao criar publicação', 'newsmast-curator'));
    }

    public function delete_item($request) {
        $repo = new Publication_Repository($this->database);
        $result = $repo->delete($request['id']);
        return $result ? $this->prepare_response(['success' => true]) : $this->prepare_error('Failed');
    }

    public function retry_item($request) {
        $repo = new Publication_Repository($this->database);
        $pub = $repo->find((int) $request['id']);

        if (!$pub) {
            return $this->prepare_error(__('Publicação não encontrada', 'newsmast-curator'), 'not_found', 404);
        }

        if ($pub->get_status() !== Publication::STATUS_FAILED) {
            return $this->prepare_error(__('Somente publicações com falha podem ser reagendadas', 'newsmast-curator'), 'invalid_status', 400);
        }

        $pub->set_status(Publication::STATUS_SCHEDULED);
        $pub->set_scheduled_for(date('Y-m-d H:i:s', time() + 60));
        $pub->set_attempt_count(0);
        $pub->set_last_error('');
        $repo->update($pub);

        return $this->prepare_response([
            'success' => true,
            'message' => __('Publicação reagendada', 'newsmast-curator'),
        ]);
    }

    public function process_now($request) {
        $repo = new Publication_Repository($this->database);

        // Log pre-processing state for diagnostics
        $logger = new \NewsmastCurator\Services\Logger_Service($this->database);
        $status_counts = $repo->count_by_status();
        $logger->info('process_now: Estado antes do processamento', [
            'related_type' => 'scheduler',
            'details' => sprintf('Publicações por status: %s | Hora local: %s', wp_json_encode($status_counts), current_time('mysql')),
        ]);

        $scheduler = new \NewsmastCurator\Services\Scheduler_Service($this->database);
        $scheduler->process_scheduled_publications();

        $stats = $repo->get_stats();

        return $this->prepare_response([
            'success' => true,
            'message' => __('Fila processada', 'newsmast-curator'),
            'stats' => $stats,
        ]);
    }
}
