<?php
namespace NewsmastCurator\API;

use NewsmastCurator\Repositories\Publication_Repository;
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
        $pub = new Publication();
        $pub->set_item_id($request['item_id']);
        $pub->set_scheduled_for($request['scheduled_for']);
        $pub->set_content($request['content']);
        $pub->set_published_by(get_current_user_id());

        $repo = new Publication_Repository($this->database);
        $id = $repo->insert($pub);

        return $id ? $this->prepare_response($pub->to_api_response(), 201) : $this->prepare_error('Failed');
    }

    public function delete_item($request) {
        $repo = new Publication_Repository($this->database);
        $result = $repo->delete($request['id']);
        return $result ? $this->prepare_response(['success' => true]) : $this->prepare_error('Failed');
    }
}
