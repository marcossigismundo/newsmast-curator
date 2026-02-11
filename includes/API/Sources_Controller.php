<?php
namespace NewsmastCurator\API;

use NewsmastCurator\Repositories\Source_Repository;
use NewsmastCurator\Models\Source;

class Sources_Controller extends Base_REST_Controller {
    protected $rest_base = 'sources';

    public function register_routes() {
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            ['methods' => 'GET', 'callback' => [$this, 'get_items'], 'permission_callback' => [$this, 'check_permissions']],
            ['methods' => 'POST', 'callback' => [$this, 'create_item'], 'permission_callback' => [$this, 'check_permissions']],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
            ['methods' => 'GET', 'callback' => [$this, 'get_item'], 'permission_callback' => [$this, 'check_permissions']],
            ['methods' => 'PUT', 'callback' => [$this, 'update_item'], 'permission_callback' => [$this, 'check_permissions']],
            ['methods' => 'DELETE', 'callback' => [$this, 'delete_item'], 'permission_callback' => [$this, 'check_permissions']],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/collect', [
            ['methods' => 'POST', 'callback' => [$this, 'collect'], 'permission_callback' => [$this, 'check_permissions']],
        ]);
    }

    public function get_items($request) {
        $repo = new Source_Repository($this->database);
        $sources = $repo->find_all();
        return $this->prepare_response(array_map(fn($s) => $s->to_api_response(), $sources));
    }

    public function get_item($request) {
        $repo = new Source_Repository($this->database);
        $source = $repo->find($request['id']);
        return $source ? $this->prepare_response($source->to_api_response()) : $this->prepare_error('Source not found', 'not_found', 404);
    }

    public function create_item($request) {
        $source = new Source();
        $source->set_name($request['name']);
        $source->set_connector_type($request['connector_type']);
        $source->set_url($request['url']);
        $source->set_config($request['config'] ?? []);

        $validation = $source->validate();
        if ($validation !== true) {
            return $this->prepare_error('Validation failed', 'validation_error', 400);
        }

        $repo = new Source_Repository($this->database);
        $id = $repo->insert($source);

        return $id ? $this->prepare_response($source->to_api_response(), 201) : $this->prepare_error('Failed to create');
    }

    public function update_item($request) {
        $repo = new Source_Repository($this->database);
        $source = $repo->find($request['id']);
        if (!$source) return $this->prepare_error('Not found', 'not_found', 404);

        if (isset($request['name'])) $source->set_name($request['name']);
        if (isset($request['url'])) $source->set_url($request['url']);
        if (isset($request['config'])) $source->set_config($request['config']);
        if (isset($request['status'])) $source->set_status($request['status']);

        $repo->update($source);
        return $this->prepare_response($source->to_api_response());
    }

    public function delete_item($request) {
        $repo = new Source_Repository($this->database);
        $result = $repo->delete($request['id']);
        return $result ? $this->prepare_response(['success' => true]) : $this->prepare_error('Failed to delete');
    }

    public function collect($request) {
        $service = new \NewsmastCurator\Services\Collection_Service($this->database);
        $count = $service->collect_from_source($request['id']);
        return $this->prepare_response(['success' => true, 'items_collected' => $count]);
    }
}
