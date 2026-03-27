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

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
            ['methods' => 'GET', 'callback' => [$this, 'get_item'], 'permission_callback' => [$this, 'check_permissions']],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/curate', [
            ['methods' => 'POST', 'callback' => [$this, 'curate'], 'permission_callback' => [$this, 'check_permissions']],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/alt-text', [
            ['methods' => 'GET', 'callback' => [$this, 'get_alt_text'], 'permission_callback' => [$this, 'check_permissions']],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/stats', [
            ['methods' => 'GET', 'callback' => [$this, 'get_stats'], 'permission_callback' => [$this, 'check_permissions']],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/authors', [
            ['methods' => 'GET', 'callback' => [$this, 'get_authors'], 'permission_callback' => [$this, 'check_permissions']],
        ]);
    }

    public function get_items($request) {
        $repo = new Item_Repository($this->database);
        $args = [];
        if (isset($request['curated'])) $args['curated'] = (int) $request['curated'];
        if (isset($request['source_id'])) $args['source_id'] = (int) $request['source_id'];
        if (!empty($request['search'])) $args['search'] = sanitize_text_field($request['search']);
        if (!empty($request['date_from'])) $args['date_from'] = sanitize_text_field($request['date_from']);
        if (!empty($request['date_to'])) $args['date_to'] = sanitize_text_field($request['date_to']);
        if (!empty($request['author'])) $args['author'] = sanitize_text_field($request['author']);
        if (isset($request['has_image']) && $request['has_image'] !== '') $args['has_image'] = (int) $request['has_image'];
        if (!empty($request['collection_type'])) $args['collection_type'] = sanitize_text_field($request['collection_type']);
        if (!empty($request['orderby'])) $args['orderby'] = sanitize_key($request['orderby']);
        if (!empty($request['order'])) $args['order'] = sanitize_key($request['order']);
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

    public function get_item($request) {
        $repo = new Item_Repository($this->database);
        $item = $repo->find($request['id']);
        if (!$item) {
            return $this->prepare_error(__('Item não encontrado', 'newsmast-curator'), 'not_found', 404);
        }
        $sources_map = $this->get_sources_map();
        $data = $item->to_api_response();
        $source = $sources_map[$item->get_source_id()] ?? null;
        $data['source_name'] = $source ? $source->get_name() : '';
        $config = $source ? $source->get_config() : [];
        $data['search_terms'] = $config['search_terms'] ?? '';
        return $this->prepare_response($data);
    }

    public function curate($request) {
        $repo = new Item_Repository($this->database);
        $user_id = get_current_user_id();
        $result = $repo->mark_as_curated($request['id'], $user_id);
        return $result ? $this->prepare_response(['success' => true]) : $this->prepare_error('Failed');
    }

    public function get_alt_text($request) {
        $repo = new Item_Repository($this->database);
        $item = $repo->find($request['id']);
        if (!$item) {
            return $this->prepare_error(__('Item não encontrado', 'newsmast-curator'), 'not_found', 404);
        }
        return $this->prepare_response([
            'alt_text' => $item->build_alt_text(),
            'char_count' => mb_strlen($item->build_alt_text()),
        ]);
    }

    public function get_stats($request) {
        $repo = new Item_Repository($this->database);
        return $this->prepare_response($repo->get_curated_stats());
    }

    public function get_authors($request) {
        $repo = new Item_Repository($this->database);
        $args = [];
        if (isset($request['curated'])) $args['curated'] = (int) $request['curated'];
        $authors = $repo->get_distinct_authors($args);
        return $this->prepare_response(['authors' => $authors]);
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
