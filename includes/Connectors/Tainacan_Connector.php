<?php
namespace NewsmastCurator\Connectors;

class Tainacan_Connector implements Connector_Interface {
    private $api_url;
    private $config;
    private $last_error = '';
    private $total_items = 0;

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
        $this->last_error = '';
        $this->total_items = 0;
        $per_page = $this->config['per_page'] ?? 20;
        $orderby = $this->config['orderby'] ?? 'date';

        $args = [
            'perpage' => $per_page,
            'order'   => 'DESC',
            'orderby' => $orderby,
        ];

        // Filtro de busca por termos/assunto
        $search_terms = trim($this->config['search_terms'] ?? '');
        if (!empty($search_terms)) {
            $args['search'] = $search_terms;
        }

        $url = add_query_arg($args, $this->api_url);

        $response = wp_remote_get($url, [
            'timeout' => 30,
            'sslverify' => true,
            'user-agent' => 'Mozilla/5.0 (compatible; NewsmastCurator/1.0; +WordPress)',
        ]);

        if (is_wp_error($response)) {
            $this->last_error = 'HTTP request failed: ' . $response->get_error_message();
            error_log('[NC Tainacan] ' . $this->last_error . ' | URL: ' . $url);
            return [];
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            $this->last_error = "HTTP {$code} response";
            error_log('[NC Tainacan] ' . $this->last_error . ' | URL: ' . $url);
            return [];
        }

        // Captura total de itens do header X-WP-Total
        $this->total_items = (int) wp_remote_retrieve_header($response, 'X-WP-Total');

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($data)) {
            $this->last_error = 'Invalid JSON response';
            error_log('[NC Tainacan] ' . $this->last_error . ' | URL: ' . $url);
            return [];
        }
        $tainacan_items = $data['items'] ?? [];

        $items = [];
        foreach ($tainacan_items as $item) {
            $image_url = $this->extract_image_url($item);

            // Usa denominação como título quando title está vazio (comum no Tainacan)
            $title = wp_strip_all_tags($item['title'] ?? '');
            if (empty($title)) {
                $title = $this->extract_denomination($item);
            }

            // Autor: tenta campo 'autor'/'autoria' dos metadados, fallback para author_name
            $author = $this->extract_metadata_value($item, ['autor', 'autoria']) ?: ($item['author_name'] ?? null);

            $items[] = [
                'external_id' => 'tainacan_' . $item['id'],
                'title' => $title,
                'content' => wp_kses_post($item['description'] ?? ''),
                'excerpt' => $this->extract_excerpt($item),
                'url' => $item['url'] ?? '',
                'image_url' => $image_url,
                'author' => $author,
                'published_date' => $this->parse_tainacan_date($item['creation_date'] ?? ''),
                'metadata' => $item['metadata'] ?? [],
            ];
        }

        return $items;
    }

    /**
     * Extrai valor de metadado por slug ou nome
     *
     * @param array $item Item do Tainacan
     * @param array $slugs Lista de slugs/nomes para tentar
     * @return string|null
     */
    private function extract_metadata_value($item, $slugs) {
        $metadata = $item['metadata'] ?? [];
        foreach ($slugs as $slug) {
            // Tenta pelo slug (chave do array)
            if (isset($metadata[$slug]) && !empty($metadata[$slug]['value_as_string'])) {
                return wp_strip_all_tags($metadata[$slug]['value_as_string']);
            }
        }
        // Tenta pelo nome do metadado
        foreach ($metadata as $meta) {
            if (!empty($meta['value_as_string']) && isset($meta['name'])) {
                $name = strtolower($meta['name']);
                foreach ($slugs as $slug) {
                    if ($name === strtolower($slug) || strpos($name, strtolower($slug)) !== false) {
                        return wp_strip_all_tags($meta['value_as_string']);
                    }
                }
            }
        }
        return null;
    }

    /**
     * Extrai denominação dos metadados (usado como fallback para título)
     */
    private function extract_denomination($item) {
        return $this->extract_metadata_value($item, ['denominacao', 'denominação']) ?: '';
    }

    /**
     * Extrai URL da imagem do item Tainacan
     *
     * Ordem de prioridade:
     * 1. Campo thumbnail (disponível direto na API, sem requisição extra)
     * 2. document_as_html (pode conter <img> embutido)
     * 3. _thumbnail_id via /wp/v2/media (fallback lento, requisição extra)
     */
    private function extract_image_url($item) {
        // 1. Usar campo thumbnail direto da API (preferido — sem requisição extra)
        if (!empty($item['thumbnail'])) {
            // Tenta medium_large > large > tainacan-medium-full > full
            $preferred_sizes = ['medium_large', 'large', 'tainacan-medium-full', 'tainacan-large-full', 'full'];
            foreach ($preferred_sizes as $size) {
                if (!empty($item['thumbnail'][$size][0])) {
                    return $item['thumbnail'][$size][0];
                }
            }
            // Qualquer tamanho disponível
            foreach ($item['thumbnail'] as $size_data) {
                if (!empty($size_data[0])) {
                    return $size_data[0];
                }
            }
        }

        // 2. document_as_html (pode conter <a><img></a> com URLs)
        if (!empty($item['document_as_html'])) {
            if (preg_match('/src=["\']([^"\']+)["\']/', $item['document_as_html'], $matches) && !empty($matches[1])) {
                return $matches[1];
            }
            if (preg_match('/href=["\']([^"\']+)["\']/', $item['document_as_html'], $matches) && !empty($matches[1])) {
                return $matches[1];
            }
        }

        // 3. Fallback: resolve _thumbnail_id via WordPress media API (lento)
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
            'search_terms' => [
                'type' => 'text',
                'label' => __('Termos de Busca (assunto)', 'newsmast-curator'),
                'required' => false,
                'placeholder' => __('Ex: indígena, quilombo, patrimônio imaterial...', 'newsmast-curator'),
                'help' => __('Filtra itens por palavras-chave. Deixe vazio para coletar todos os itens.', 'newsmast-curator'),
            ],
            'per_page' => [
                'type' => 'number',
                'label' => __('Itens por página', 'newsmast-curator'),
                'default' => 20,
            ],
        ];
    }

    /**
     * Retorna o total de itens encontrados na última coleta (via header X-WP-Total)
     */
    public function get_total_items() {
        return $this->total_items;
    }

    public function get_last_error() {
        return $this->last_error;
    }

    public function test_connection() {
        $items = $this->collect();
        $search = trim($this->config['search_terms'] ?? '');
        $total = $this->total_items;

        if (count($items) > 0) {
            if (!empty($search)) {
                $message = sprintf(
                    __('%d itens encontrados para "%s" (mostrando %d)', 'newsmast-curator'),
                    $total, $search, count($items)
                );
            } else {
                $message = sprintf(
                    __('%d itens encontrados no acervo (mostrando %d)', 'newsmast-curator'),
                    $total, count($items)
                );
            }
        } else {
            if (!empty($search)) {
                $message = $this->last_error ?: sprintf(
                    __('Nenhum item encontrado para "%s"', 'newsmast-curator'),
                    $search
                );
            } else {
                $message = $this->last_error ?: __('Nenhum item encontrado', 'newsmast-curator');
            }
        }

        return [
            'success' => count($items) > 0,
            'message' => $message,
            'total_items' => $total,
            'sample_items' => array_slice($items, 0, 3),
        ];
    }
}
