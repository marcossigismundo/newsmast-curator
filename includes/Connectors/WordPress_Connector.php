<?php
namespace NewsmastCurator\Connectors;

class WordPress_Connector implements Connector_Interface {
    private $api_url;
    private $config;
    private $last_error = '';

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
        $this->last_error = '';
        $per_page = $this->config['per_page'] ?? 20;
        $url = add_query_arg(['per_page' => $per_page], $this->api_url);

        $response = wp_remote_get($url, [
            'timeout' => 30,
            'sslverify' => true,
            'user-agent' => 'Mozilla/5.0 (compatible; NewsmastCurator/1.0; +WordPress)',
        ]);

        if (is_wp_error($response)) {
            $this->last_error = 'HTTP request failed: ' . $response->get_error_message();
            error_log('[NC WordPress] ' . $this->last_error . ' | URL: ' . $url);
            return [];
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            $this->last_error = "HTTP {$code} response";
            error_log('[NC WordPress] ' . $this->last_error . ' | URL: ' . $url);
            return [];
        }

        $posts = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($posts)) {
            $this->last_error = 'Invalid JSON response';
            error_log('[NC WordPress] ' . $this->last_error . ' | URL: ' . $url);
            return [];
        }

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

    public function get_last_error() {
        return $this->last_error;
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
        $message = count($items) > 0
            ? sprintf(__('%d posts encontrados', 'newsmast-curator'), count($items))
            : ($this->last_error ?: __('Nenhum post encontrado', 'newsmast-curator'));
        return [
            'success' => count($items) > 0,
            'message' => $message,
            'sample_items' => array_slice($items, 0, 3),
        ];
    }
}
