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

        register_rest_route($this->namespace, '/' . $this->rest_base . '/test-tainacan', [
            ['methods' => 'POST', 'callback' => [$this, 'test_tainacan_search'], 'permission_callback' => [$this, 'check_permissions']],
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
        if (isset($request['auto_collect'])) $source->set_auto_collect($request['auto_collect']);
        if (isset($request['collect_interval'])) $source->set_collect_interval($request['collect_interval']);

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
        if (isset($request['auto_collect'])) $source->set_auto_collect($request['auto_collect']);
        if (isset($request['collect_interval'])) $source->set_collect_interval($request['collect_interval']);

        $repo->update($source);
        return $this->prepare_response($source->to_api_response());
    }

    public function delete_item($request) {
        $repo = new Source_Repository($this->database);
        $result = $repo->delete($request['id']);
        return $result ? $this->prepare_response(['success' => true]) : $this->prepare_error('Failed to delete');
    }

    /**
     * Testa busca no Tainacan via backend (evita CORS no browser)
     */
    public function test_tainacan_search($request) {
        $url = esc_url_raw($request['url'] ?? '');
        $collection_id = absint($request['collection_id'] ?? 0);
        $search_terms = sanitize_text_field($request['search_terms'] ?? '');

        if (empty($url) || empty($collection_id)) {
            return $this->prepare_error(__('URL e ID da coleção são obrigatórios', 'newsmast-curator'), 'missing_params', 400);
        }

        $connector = new \NewsmastCurator\Connectors\Tainacan_Connector();
        $connector->connect([
            'url' => $url,
            'collection_id' => $collection_id,
            'search_terms' => $search_terms,
            'per_page' => 3,
        ]);

        $result = $connector->test_connection();
        return $this->prepare_response($result);
    }

    public function collect($request) {
        try {
            $service = new \NewsmastCurator\Services\Collection_Service($this->database);
            $count = $service->collect_from_source($request['id']);

            if ($count === false) {
                return $this->prepare_error(
                    __('Falha na coleta. Verifique os logs para detalhes.', 'newsmast-curator'),
                    'collection_failed',
                    500
                );
            }

            return $this->prepare_response(['success' => true, 'items_collected' => (int) $count]);
        } catch (\Exception $e) {
            error_log('[NC Collection] Exception: ' . $e->getMessage());
            return $this->prepare_error($e->getMessage(), 'collection_error', 500);
        }
    }
}
