<?php
namespace NewsmastCurator\Connectors;

class Visite_Museus_Connector implements Connector_Interface {
    private $api_url = 'https://visite.museus.gov.br/wp-json/tainacan/v2';
    private $config;

    public function connect($config) {
        $this->config = $config;
        return !empty($config['collection_slug']);
    }

    public function validate_config($config) {
        return !empty($config['collection_slug']) ? true : ['collection_slug' => __('Slug da coleção obrigatório', 'newsmast-curator')];
    }

    public function collect() {
        // Primeiro, busca ID da coleção pelo slug
        $slug = $this->config['collection_slug'];
        $collections_url = "{$this->api_url}/collections";

        $response = wp_remote_get($collections_url, ['timeout' => 30]);
        if (is_wp_error($response)) return [];

        $collections = json_decode(wp_remote_retrieve_body($response), true);
        $collection_id = null;

        foreach ($collections as $col) {
            if ($col['slug'] === $slug) {
                $collection_id = $col['id'];
                break;
            }
        }

        if (!$collection_id) return [];

        // Busca itens da coleção
        $items_url = "{$this->api_url}/collections/{$collection_id}/items";
        $per_page = $this->config['items_per_page'] ?? 20;
        $items_url = add_query_arg(['perpage' => $per_page], $items_url);

        $response = wp_remote_get($items_url, ['timeout' => 30]);
        if (is_wp_error($response)) return [];

        $data = json_decode(wp_remote_retrieve_body($response), true);
        $tainacan_items = $data['items'] ?? [];

        $items = [];
        foreach ($tainacan_items as $item) {
            $items[] = [
                'external_id' => 'visite_' . $item['id'],
                'title' => wp_strip_all_tags($item['title'] ?? ''),
                'content' => wp_kses_post($item['description'] ?? ''),
                'excerpt' => '',
                'url' => $item['url'] ?? '',
                'image_url' => $item['thumbnail']['tainacan-medium'][0] ?? null,
                'author' => null,
                'published_date' => $item['creation_date'] ?? null,
                'metadata' => $item['metadata'] ?? [],
            ];
        }

        return $items;
    }

    public function get_config_fields() {
        return [
            'collection_slug' => [
                'type' => 'text',
                'label' => __('Slug da Coleção', 'newsmast-curator'),
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
