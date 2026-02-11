<?php
/**
 * Conector Plone (Scraping)
 *
 * @package NewsmastCurator
 * @subpackage Connectors
 */

namespace NewsmastCurator\Connectors;

class Plone_Connector implements Connector_Interface {
    private $url;
    private $config;

    public function connect($config) {
        $this->config = $config;
        $this->url = $config['url'] ?? '';
        return !empty($this->url);
    }

    public function validate_config($config) {
        $errors = [];
        if (empty($config['url'])) {
            $errors['url'] = __('URL é obrigatória', 'newsmast-curator');
        }
        return empty($errors) ? true : $errors;
    }

    public function collect() {
        $response = wp_remote_get($this->url, ['timeout' => 30]);

        if (is_wp_error($response)) {
            return [];
        }

        $html = wp_remote_retrieve_body($response);
        return $this->parse_html($html);
    }

    private function parse_html($html) {
        $items = [];

        // Parsing simplificado - em produção usar DOMDocument ou biblioteca
        // Por simplicidade, retorna estrutura base
        $pattern = '/class="[^"]*newsItem[^"]*".*?<h2[^>]*>(.*?)<\/h2>.*?href="([^"]+)"/is';
        preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);

        foreach ($matches as $i => $match) {
            $items[] = [
                'external_id' => 'plone_' . md5($match[2]),
                'title' => wp_strip_all_tags($match[1]),
                'content' => '',
                'excerpt' => '',
                'url' => $this->normalize_url($match[2]),
                'image_url' => null,
                'author' => null,
                'published_date' => null,
                'metadata' => [],
            ];
        }

        return $items;
    }

    private function normalize_url($url) {
        if (strpos($url, 'http') === 0) {
            return $url;
        }
        $base = parse_url($this->url, PHP_URL_SCHEME) . '://' . parse_url($this->url, PHP_URL_HOST);
        return rtrim($base, '/') . '/' . ltrim($url, '/');
    }

    public function get_config_fields() {
        return [
            'selector' => [
                'type' => 'text',
                'label' => __('CSS Selector dos Itens', 'newsmast-curator'),
                'default' => '.newsItem',
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
