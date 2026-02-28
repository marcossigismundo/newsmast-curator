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

        $pub = new Publication();
        $pub->set_item_id($item_id);
        $pub->set_scheduled_for($normalized_datetime);
        $pub->set_content($content);
        $pub->set_published_by(get_current_user_id());

        $repo = new Publication_Repository($this->database);
        $id = $repo->insert($pub);

        return $id ? $this->prepare_response($pub->to_api_response(), 201) : $this->prepare_error(__('Falha ao criar publicação', 'newsmast-curator'));
    }

    public function delete_item($request) {
        $repo = new Publication_Repository($this->database);
        $result = $repo->delete($request['id']);
        return $result ? $this->prepare_response(['success' => true]) : $this->prepare_error('Failed');
    }

    public function process_now($request) {
        $scheduler = new \NewsmastCurator\Services\Scheduler_Service($this->database);
        $scheduler->process_scheduled_publications();

        $repo = new Publication_Repository($this->database);
        $stats = $repo->get_stats();

        return $this->prepare_response([
            'success' => true,
            'message' => __('Fila processada', 'newsmast-curator'),
            'stats' => $stats,
        ]);
    }
}
