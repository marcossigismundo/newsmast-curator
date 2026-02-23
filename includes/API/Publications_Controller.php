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
        if (!$timestamp || $timestamp <= time()) {
            return $this->prepare_error(__('Data de agendamento deve ser no futuro', 'newsmast-curator'), 'validation_error', 400);
        }

        if (empty($content)) {
            return $this->prepare_error(__('Conteúdo é obrigatório', 'newsmast-curator'), 'validation_error', 400);
        }

        $pub = new Publication();
        $pub->set_item_id($item_id);
        $pub->set_scheduled_for($scheduled_for);
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
}
