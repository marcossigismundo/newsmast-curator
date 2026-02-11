<?php
namespace NewsmastCurator\Connectors;

class Tainacan_Connector implements Connector_Interface {
    private $api_url;
    private $config;

    public function connect($config) {
        $this->config = $config;
        $base_url = rtrim($config['url'], '/');
        $collection_id = $config['collection_id'] ?? '';
        $this->api_url = "{$base_url}/wp-json/tainacan/v2/collections/{$collection_id}/items";
        return !empty($collection_id);
    }

    public function validate_config($config) {
        return !empty($config['collection_id']) ? true : ['collection_id' => __('ID da coleção obrigatório', 'newsmast-curator')];
    }

    public function collect() {
        $per_page = $this->config['items_per_page'] ?? 20;
        $url = add_query_arg(['perpage' => $per_page], $this->api_url);

        $response = wp_remote_get($url, ['timeout' => 30]);
        if (is_wp_error($response)) return [];

        $data = json_decode(wp_remote_retrieve_body($response), true);
        $tainacan_items = $data['items'] ?? [];

        $items = [];
        foreach ($tainacan_items as $item) {
            $items[] = [
                'external_id' => 'tainacan_' . $item['id'],
                'title' => wp_strip_all_tags($item['title'] ?? ''),
                'content' => wp_kses_post($item['description'] ?? ''),
                'excerpt' => '',
                'url' => $item['url'] ?? '',
                'image_url' => $item['thumbnail']['tainacan-medium'][0] ?? null,
                'author' => $item['author_name'] ?? null,
                'published_date' => $item['creation_date'] ?? null,
                'metadata' => $item['metadata'] ?? [],
            ];
        }

        return $items;
    }

    public function get_config_fields() {
        return [
            'collection_id' => [
                'type' => 'number',
                'label' => __('ID da Coleção', 'newsmast-curator'),
                'required' => true,
            ],
            'items_per_page' => [
                'type' => 'number',
                'label' => __('Itens por página', 'newsmast-curator'),
                'default' => 20,
            ],
        ];
    }

    public function test_connection() {
        $items = $this->collect();
        return [
            'success' => count($items) > 0,
            'message' => sprintf(__('%d itens encontrados', 'newsmast-curator'), count($items)),
            'sample_items' => array_slice($items, 0, 3),
        ];
    }
}
