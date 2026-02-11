<?php
namespace NewsmastCurator\Connectors;

class WordPress_Connector implements Connector_Interface {
    private $api_url;
    private $config;

    public function connect($config) {
        $this->config = $config;
        $base_url = rtrim($config['url'], '/');
        $endpoint = $config['api_endpoint'] ?? '/wp-json/wp/v2/posts';
        $this->api_url = $base_url . $endpoint;
        return true;
    }

    public function validate_config($config) {
        return !empty($config['url']) ? true : ['url' => __('URL obrigatória', 'newsmast-curator')];
    }

    public function collect() {
        $args = ['timeout' => 30];
        $per_page = $this->config['per_page'] ?? 20;
        $url = add_query_arg(['per_page' => $per_page], $this->api_url);

        $response = wp_remote_get($url, $args);
        if (is_wp_error($response)) return [];

        $posts = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($posts)) return [];

        $items = [];
        foreach ($posts as $post) {
            $items[] = [
                'external_id' => 'wp_' . $post['id'],
                'title' => wp_strip_all_tags($post['title']['rendered'] ?? ''),
                'content' => wp_kses_post($post['content']['rendered'] ?? ''),
                'excerpt' => wp_strip_all_tags($post['excerpt']['rendered'] ?? ''),
                'url' => $post['link'] ?? '',
                'image_url' => null,
                'author' => null,
                'published_date' => $post['date'] ?? null,
                'metadata' => [],
            ];
        }

        return $items;
    }

    public function get_config_fields() {
        return [
            'api_endpoint' => [
                'type' => 'text',
                'label' => __('Endpoint da API', 'newsmast-curator'),
                'default' => '/wp-json/wp/v2/posts',
            ],
            'per_page' => [
                'type' => 'number',
                'label' => __('Posts por página', 'newsmast-curator'),
                'default' => 20,
            ],
        ];
    }

    public function test_connection() {
        $items = $this->collect();
        return [
            'success' => count($items) > 0,
            'message' => sprintf(__('%d posts encontrados', 'newsmast-curator'), count($items)),
            'sample_items' => array_slice($items, 0, 3),
        ];
    }
}
