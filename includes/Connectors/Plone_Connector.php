<?php
/**
 * Conector Plone (Scraping via DOMDocument)
 *
 * Coleta notícias de sites Plone CMS (gov.br) via parsing HTML.
 *
 * @package NewsmastCurator
 * @subpackage Connectors
 */

namespace NewsmastCurator\Connectors;

class Plone_Connector implements Connector_Interface {
    private $url;
    private $config;
    private $last_error = '';

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
        $this->last_error = '';

        $response = wp_remote_get($this->url, [
            'timeout' => 30,
            'sslverify' => true,
            'user-agent' => 'Mozilla/5.0 (compatible; NewsmastCurator/1.0; +WordPress)',
            'headers' => [
                'Accept' => 'text/html,application/xhtml+xml',
                'Accept-Language' => 'pt-BR,pt;q=0.9,en;q=0.5',
            ],
        ]);

        if (is_wp_error($response)) {
            $this->last_error = 'HTTP request failed: ' . $response->get_error_message();
            error_log('[NC Plone] ' . $this->last_error . ' | URL: ' . $this->url);
            return [];
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            $this->last_error = "HTTP {$code} response";
            error_log('[NC Plone] ' . $this->last_error . ' | URL: ' . $this->url);
            return [];
        }

        $html = wp_remote_retrieve_body($response);
        if (empty($html)) {
            $this->last_error = 'Empty response body';
            error_log('[NC Plone] ' . $this->last_error . ' | URL: ' . $this->url);
            return [];
        }

        $items = $this->parse_html($html);

        if (empty($items)) {
            $this->last_error = 'No items found in HTML (check CSS selectors)';
            error_log('[NC Plone] ' . $this->last_error . ' | URL: ' . $this->url . ' | HTML length: ' . strlen($html));
        }

        return $items;
    }

    public function get_last_error() {
        return $this->last_error;
    }

    /**
     * Parseia HTML da listagem de notícias usando DOMDocument + XPath
     */
    private function parse_html($html) {
        if (empty($html)) {
            return [];
        }

        $items = [];

        // Suprimir warnings de HTML malformado
        $prev = libxml_use_internal_errors(true);

        $doc = new \DOMDocument();
        $doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);

        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $xpath = new \DOMXPath($doc);

        // Seletores configuráveis com defaults para estrutura gov.br
        $item_class = $this->config['selector'] ?? 'li';
        $title_class = $this->config['title_selector'] ?? '.titulo';
        $desc_class = $this->config['description_selector'] ?? '.subtitulo-noticia';
        $image_class = $this->config['image_selector'] ?? '.newsImage';
        $date_class = $this->config['date_selector'] ?? '.data';

        // Buscar containers de itens
        $item_xpath = $this->css_to_xpath($item_class);
        $nodes = $xpath->query($item_xpath);

        if ($nodes === false || $nodes->length === 0) {
            return [];
        }

        foreach ($nodes as $node) {
            // Extrair título e URL
            $title = '';
            $url = '';
            $title_xpath = $this->css_to_xpath($title_class, './/');
            $title_nodes = $xpath->query($title_xpath, $node);

            if ($title_nodes && $title_nodes->length > 0) {
                $title_el = $title_nodes->item(0);
                // Buscar link dentro do elemento título ou no próprio elemento
                $link = null;
                if ($title_el->nodeName === 'a') {
                    $link = $title_el;
                } else {
                    $links = $xpath->query('.//a', $title_el);
                    if ($links && $links->length > 0) {
                        $link = $links->item(0);
                    }
                }

                if ($link) {
                    $title = trim($link->textContent);
                    $url = $link->getAttribute('href');
                } else {
                    $title = trim($title_el->textContent);
                }
            }

            // Sem título nem URL = não é um item válido, pular
            if (empty($title) && empty($url)) {
                continue;
            }

            $url = $this->normalize_url($url);

            // Extrair resumo/descrição
            $excerpt = '';
            $desc_xpath_str = $this->css_to_xpath($desc_class, './/');
            $desc_nodes = $xpath->query($desc_xpath_str, $node);
            if ($desc_nodes && $desc_nodes->length > 0) {
                $excerpt = trim($desc_nodes->item(0)->textContent);
            }

            // Extrair data
            $published_date = null;
            $date_xpath_str = $this->css_to_xpath($date_class, './/');
            $date_nodes = $xpath->query($date_xpath_str, $node);
            if ($date_nodes && $date_nodes->length > 0) {
                $date_text = trim($date_nodes->item(0)->textContent);
                $published_date = $this->parse_brazilian_date($date_text);
            }

            // Extrair imagem
            $image_url = null;
            $img_xpath_str = $this->css_to_xpath($image_class, './/');
            $img_nodes = $xpath->query($img_xpath_str, $node);
            if ($img_nodes && $img_nodes->length > 0) {
                $img_el = $img_nodes->item(0);
                // Se o seletor apontou para um <img>, pegar src direto
                if ($img_el->nodeName === 'img') {
                    $image_url = $img_el->getAttribute('src');
                } else {
                    // Buscar <img> dentro do elemento
                    $imgs = $xpath->query('.//img', $img_el);
                    if ($imgs && $imgs->length > 0) {
                        $image_url = $imgs->item(0)->getAttribute('src');
                    }
                }
                if ($image_url) {
                    $image_url = $this->normalize_url($image_url);
                }
            }

            // Extrair tags
            $tags = [];
            $tag_nodes = $xpath->query('.//*[contains(@class,"subject-noticia")]//a', $node);
            if ($tag_nodes && $tag_nodes->length > 0) {
                foreach ($tag_nodes as $tag_el) {
                    $tag_text = trim($tag_el->textContent);
                    if (!empty($tag_text)) {
                        $tags[] = $tag_text;
                    }
                }
            }

            $items[] = [
                'external_id'    => 'plone_' . md5($url),
                'title'          => wp_strip_all_tags($title),
                'content'        => wp_kses_post($excerpt),
                'excerpt'        => wp_trim_words(wp_strip_all_tags($excerpt), 30),
                'url'            => esc_url_raw($url),
                'image_url'      => $image_url ? esc_url_raw($image_url) : null,
                'author'         => null,
                'published_date' => $published_date,
                'metadata'       => !empty($tags) ? ['tags' => $tags] : [],
            ];
        }

        return $items;
    }

    /**
     * Converte seletor CSS simples para expressão XPath
     *
     * Suporta: tag, .classe, tag.classe
     */
    private function css_to_xpath($selector, $prefix = '//') {
        $selector = trim($selector);

        // Seletor já é um tag simples sem ponto (ex: "li", "div")
        if (strpos($selector, '.') === false) {
            return $prefix . $selector;
        }

        // Começa com ponto: .classe
        if ($selector[0] === '.') {
            $class = substr($selector, 1);
            return $prefix . '*[contains(concat(" ",normalize-space(@class)," ")," ' . $class . ' ")]';
        }

        // tag.classe
        $parts = explode('.', $selector, 2);
        $tag = $parts[0];
        $class = $parts[1];
        return $prefix . $tag . '[contains(concat(" ",normalize-space(@class)," ")," ' . $class . ' ")]';
    }

    /**
     * Parseia datas no formato brasileiro para MySQL datetime
     *
     * Aceita: dd/mm/yyyy, "23 de fevereiro de 2026"
     */
    private function parse_brazilian_date($date_str) {
        $date_str = trim($date_str);
        if (empty($date_str)) {
            return null;
        }

        // Formato dd/mm/yyyy
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $date_str, $m)) {
            return sprintf('%s-%02d-%02d 00:00:00', $m[3], (int)$m[2], (int)$m[1]);
        }

        // Formato por extenso: "23 de fevereiro de 2026"
        $months = [
            'janeiro' => 1, 'fevereiro' => 2, 'março' => 3, 'marco' => 3,
            'abril' => 4, 'maio' => 5, 'junho' => 6,
            'julho' => 7, 'agosto' => 8, 'setembro' => 9,
            'outubro' => 10, 'novembro' => 11, 'dezembro' => 12,
        ];

        $date_lower = mb_strtolower($date_str);
        if (preg_match('/(\d{1,2})\s+de\s+(\w+)\s+de\s+(\d{4})/', $date_lower, $m)) {
            $month_num = $months[$m[2]] ?? null;
            if ($month_num) {
                return sprintf('%s-%02d-%02d 00:00:00', $m[3], $month_num, (int)$m[1]);
            }
        }

        // Tentar strtotime como fallback
        $timestamp = strtotime($date_str);
        if ($timestamp !== false) {
            return gmdate('Y-m-d H:i:s', $timestamp);
        }

        return null;
    }

    /**
     * Normaliza URL relativa para absoluta
     */
    private function normalize_url($url) {
        if (empty($url)) {
            return '';
        }
        if (strpos($url, 'http') === 0) {
            return $url;
        }
        if (strpos($url, '//') === 0) {
            return parse_url($this->url, PHP_URL_SCHEME) . ':' . $url;
        }
        $base = parse_url($this->url, PHP_URL_SCHEME) . '://' . parse_url($this->url, PHP_URL_HOST);
        return rtrim($base, '/') . '/' . ltrim($url, '/');
    }

    public function get_config_fields() {
        return [
            'selector' => [
                'type' => 'text',
                'label' => __('Seletor do Container de Itens', 'newsmast-curator'),
                'default' => 'li',
            ],
            'title_selector' => [
                'type' => 'text',
                'label' => __('Seletor do Título', 'newsmast-curator'),
                'default' => '.titulo',
            ],
            'description_selector' => [
                'type' => 'text',
                'label' => __('Seletor do Resumo', 'newsmast-curator'),
                'default' => '.subtitulo-noticia',
            ],
            'image_selector' => [
                'type' => 'text',
                'label' => __('Seletor da Imagem', 'newsmast-curator'),
                'default' => '.newsImage',
            ],
            'date_selector' => [
                'type' => 'text',
                'label' => __('Seletor da Data', 'newsmast-curator'),
                'default' => '.data',
            ],
        ];
    }

    public function test_connection() {
        $items = $this->collect();
        $message = count($items) > 0
            ? sprintf(__('%d itens encontrados', 'newsmast-curator'), count($items))
            : ($this->last_error ?: __('Nenhum item encontrado', 'newsmast-curator'));
        return [
            'success' => count($items) > 0,
            'message' => $message,
            'sample_items' => array_slice($items, 0, 3),
        ];
    }
}
