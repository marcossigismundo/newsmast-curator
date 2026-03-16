<?php
namespace NewsmastCurator\API;

use NewsmastCurator\Repositories\Item_Repository;
use NewsmastCurator\Repositories\Source_Repository;

class Items_Controller extends Base_REST_Controller {
    protected $rest_base = 'items';

    public function register_routes() {
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            ['methods' => 'GET', 'callback' => [$this, 'get_items'], 'permission_callback' => [$this, 'check_permissions']],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/curate', [
            ['methods' => 'POST', 'callback' => [$this, 'curate'], 'permission_callback' => [$this, 'check_permissions']],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/stats', [
            ['methods' => 'GET', 'callback' => [$this, 'get_stats'], 'permission_callback' => [$this, 'check_permissions']],
        ]);
    }

    public function get_items($request) {
        $repo = new Item_Repository($this->database);
        $args = [];
        if (isset($request['curated'])) $args['curated'] = (int) $request['curated'];
        if (isset($request['source_id'])) $args['source_id'] = (int) $request['source_id'];
        $args['per_page'] = $request['per_page'] ?? 20;
        $args['page'] = $request['page'] ?? 1;

        $items = $repo->find_all($args);
        $total = $repo->count($args);

        // Enriquece com dados da fonte (nome e termos de busca)
        $sources_map = $this->get_sources_map();

        $items_data = array_map(function($item) use ($sources_map) {
            $data = $item->to_api_response();
            $source = $sources_map[$item->get_source_id()] ?? null;
            $data['source_name'] = $source ? $source->get_name() : '';
            $config = $source ? $source->get_config() : [];
            $data['search_terms'] = $config['search_terms'] ?? '';
            return $data;
        }, $items);

        return $this->prepare_response([
            'items' => $items_data,
            'total' => $total,
            'pages' => ceil($total / $args['per_page']),
        ]);
    }

    public function curate($request) {
        $repo = new Item_Repository($this->database);
        $user_id = get_current_user_id();
        $result = $repo->mark_as_curated($request['id'], $user_id);
        return $result ? $this->prepare_response(['success' => true]) : $this->prepare_error('Failed');
    }

    public function get_stats($request) {
        $repo = new Item_Repository($this->database);
        return $this->prepare_response($repo->get_curated_stats());
    }

    /**
     * Carrega mapa de fontes indexado por ID
     */
    private function get_sources_map() {
        $source_repo = new Source_Repository($this->database);
        $sources = $source_repo->find_all();
        $map = [];
        foreach ($sources as $source) {
            $map[$source->get_id()] = $source;
        }
        return $map;
    }
}
