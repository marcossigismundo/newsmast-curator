<?php
namespace NewsmastCurator\Connectors;

class Tainacan_Connector implements Connector_Interface {
    private $api_url;
    private $config;

    public function connect($config) {
        $this->config = $config;
        $base_url = rtrim($config['url'], '/');
        $collection_id = $config['collection_id'] ?? '';
        // Tainacan v2 API uses singular /collection/ (not /collections/)
        $this->api_url = "{$base_url}/wp-json/tainacan/v2/collection/{$collection_id}/items";
        return !empty($collection_id);
    }

    public function validate_config($config) {
        return !empty($config['collection_id']) ? true : ['collection_id' => __('ID da coleção obrigatório', 'newsmast-curator')];
    }

    public function collect() {
        $per_page = $this->config['per_page'] ?? 20;
        $orderby = $this->config['orderby'] ?? 'date';
        $url = add_query_arg([
            'perpage' => $per_page,
            'order'   => 'DESC',
            'orderby' => $orderby,
        ], $this->api_url);

        $response = wp_remote_get($url, ['timeout' => 30]);
        if (is_wp_error($response)) return [];

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) return [];

        $data = json_decode(wp_remote_retrieve_body($response), true);
        $tainacan_items = $data['items'] ?? [];

        $items = [];
        foreach ($tainacan_items as $item) {
            $image_url = $this->extract_image_url($item);

            $items[] = [
                'external_id' => 'tainacan_' . $item['id'],
                'title' => wp_strip_all_tags($item['title'] ?? ''),
                'content' => wp_kses_post($item['description'] ?? ''),
                'excerpt' => $this->extract_excerpt($item),
                'url' => $item['url'] ?? '',
                'image_url' => $image_url,
                'author' => $item['author_name'] ?? null,
                'published_date' => $this->parse_tainacan_date($item['creation_date'] ?? ''),
                'metadata' => $item['metadata'] ?? [],
            ];
        }

        return $items;
    }

    /**
     * Extrai URL da imagem do item Tainacan
     */
    private function extract_image_url($item) {
        // Try document_as_html first (contains <a><img></a>)
        if (!empty($item['document_as_html'])) {
            if (preg_match('/src=["\']([^"\']+)["\']/', $item['document_as_html'], $matches)) {
                return $matches[1];
            }
            if (preg_match('/href=["\']([^"\']+)["\']/', $item['document_as_html'], $matches)) {
                return $matches[1];
            }
        }

        // Fallback: resolve _thumbnail_id via WordPress media API
        if (!empty($item['_thumbnail_id'])) {
            $base_url = rtrim($this->config['url'], '/');
            $media_url = "{$base_url}/wp-json/wp/v2/media/{$item['_thumbnail_id']}";
            $response = wp_remote_get($media_url, ['timeout' => 10]);
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $media = json_decode(wp_remote_retrieve_body($response), true);
                return $media['source_url'] ?? null;
            }
        }

        return null;
    }

    /**
     * Extrai resumo do item a partir dos metadados
     */
    private function extract_excerpt($item) {
        $metadata = $item['metadata'] ?? [];
        foreach ($metadata as $meta) {
            if (!empty($meta['value_as_string']) && isset($meta['name'])) {
                $name = strtolower($meta['name']);
                if (strpos($name, 'descri') !== false) {
                    return wp_trim_words(wp_strip_all_tags($meta['value_as_string']), 30);
                }
            }
        }

        if (!empty($item['description'])) {
            return wp_trim_words(wp_strip_all_tags($item['description']), 30);
        }

        return '';
    }

    /**
     * Converte data localizada do Tainacan para formato MySQL
     */
    private function parse_tainacan_date($date_str) {
        if (empty($date_str)) return null;

        // Try standard format first
        $timestamp = strtotime($date_str);
        if ($timestamp !== false) {
            return gmdate('Y-m-d H:i:s', $timestamp);
        }

        // Parse Portuguese localized dates: "25 de marco de 2024"
        $months = [
            'janeiro' => '01', 'fevereiro' => '02', 'marco' => '03', 'março' => '03',
            'abril' => '04', 'maio' => '05', 'junho' => '06',
            'julho' => '07', 'agosto' => '08', 'setembro' => '09',
            'outubro' => '10', 'novembro' => '11', 'dezembro' => '12',
        ];

        $date_lower = strtolower($date_str);
        if (preg_match('/(\d{1,2})\s+de\s+(\w+)\s+de\s+(\d{4})/', $date_lower, $m)) {
            $month_num = $months[$m[2]] ?? null;
            if ($month_num) {
                return sprintf('%s-%s-%02d 00:00:00', $m[3], $month_num, (int)$m[1]);
            }
        }

        return null;
    }

    public function get_config_fields() {
        return [
            'collection_id' => [
                'type' => 'number',
                'label' => __('ID da Coleção', 'newsmast-curator'),
                'required' => true,
            ],
            'per_page' => [
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
