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

        register_rest_route($this->namespace, '/' . $this->rest_base . '/cleanup-stats', [
            ['methods' => 'GET', 'callback' => [$this, 'get_cleanup_stats'], 'permission_callback' => [$this, 'check_permissions']],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/cleanup', [
            ['methods' => 'POST', 'callback' => [$this, 'cleanup'], 'permission_callback' => [$this, 'check_permissions']],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/uncurate', [
            ['methods' => 'POST', 'callback' => [$this, 'uncurate'], 'permission_callback' => [$this, 'check_permissions']],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/browse-tainacan', [
            ['methods' => 'POST', 'callback' => [$this, 'browse_tainacan'], 'permission_callback' => [$this, 'check_permissions']],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/import-selected', [
            ['methods' => 'POST', 'callback' => [$this, 'import_selected'], 'permission_callback' => [$this, 'check_permissions']],
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

        // Filtros de metadados Tainacan (meta_slug=value)
        $meta_filters = [];
        foreach ($request->get_params() as $key => $value) {
            if (strpos($key, 'meta_') === 0 && $value !== '') {
                $meta_slug = sanitize_key(substr($key, 5));
                $meta_filters[$meta_slug] = sanitize_text_field($value);
            }
        }
        if (!empty($meta_filters)) {
            $args['meta_filters'] = $meta_filters;
        }

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
     * Desfaz curadoria de um item
     */
    public function uncurate($request) {
        $repo = new Item_Repository($this->database);
        $result = $repo->unmark_curated($request['id']);
        return $result ? $this->prepare_response(['success' => true]) : $this->prepare_error('Failed');
    }

    /**
     * Estatísticas para limpeza
     */
    public function get_cleanup_stats($request) {
        $repo = new Item_Repository($this->database);
        return $this->prepare_response($repo->get_cleanup_stats());
    }

    /**
     * Executa limpeza de itens
     */
    public function cleanup($request) {
        $action = sanitize_text_field($request['action'] ?? '');
        $repo = new Item_Repository($this->database);
        $logger = new \NewsmastCurator\Services\Logger_Service($this->database);

        $deleted = 0;
        switch ($action) {
            case 'published':
                $deleted = $repo->delete_published_items();
                $logger->info('Limpeza: itens publicados removidos', [
                    'related_type' => 'cleanup',
                    'details' => sprintf('%d itens publicados removidos da curadoria', $deleted),
                ]);
                break;

            case 'old_uncurated':
                $days = (int) ($request['days'] ?? 30);
                $days = max(7, min(365, $days)); // limita entre 7 e 365 dias
                $deleted = $repo->delete_old_uncurated($days);
                $logger->info('Limpeza: itens antigos não curados removidos', [
                    'related_type' => 'cleanup',
                    'details' => sprintf('%d itens não curados com mais de %d dias removidos', $deleted, $days),
                ]);
                break;

            case 'all_uncurated':
                $deleted = $repo->delete_all_uncurated();
                $logger->info('Limpeza: todos os itens não curados removidos', [
                    'related_type' => 'cleanup',
                    'details' => sprintf('%d itens não curados removidos', $deleted),
                ]);
                break;

            default:
                return $this->prepare_error(
                    __('Ação de limpeza inválida', 'newsmast-curator'),
                    'invalid_action',
                    400
                );
        }

        return $this->prepare_response([
            'success' => true,
            'deleted' => $deleted,
            'stats' => $repo->get_cleanup_stats(),
        ]);
    }

    /**
     * Navega itens do Tainacan remotamente (sem importar)
     * Proxy para evitar CORS e permitir filtros avançados
     */
    public function browse_tainacan($request) {
        $source_id = (int) ($request['source_id'] ?? 0);

        $source_repo = new Source_Repository($this->database);
        $source = $source_repo->find($source_id);

        if (!$source || $source->get_connector_type() !== 'tainacan') {
            return $this->prepare_error(
                __('Fonte Tainacan não encontrada', 'newsmast-curator'),
                'invalid_source',
                400
            );
        }

        $config = array_merge(['url' => $source->get_url()], $source->get_config());
        $base_url = rtrim($config['url'], '/');
        $collection_id = $config['collection_id'] ?? '';

        $api_url = "{$base_url}/wp-json/tainacan/v2/collection/{$collection_id}/items";

        $args = [
            'perpage' => (int) ($request['per_page'] ?? 20),
            'paged' => (int) ($request['page'] ?? 1),
            'order' => sanitize_key($request['order'] ?? 'DESC'),
            'orderby' => sanitize_key($request['orderby'] ?? 'date'),
            'fetch_only' => 'title,description,thumbnail,_thumbnail_id,url,document_as_html,creation_date,author_name,metadata,id,collection_id',
        ];

        $search = sanitize_text_field($request['search'] ?? '');
        if (!empty($search)) {
            $args['search'] = $search;
        }

        // Filtros de metadados Tainacan nativos (taxquery, metaquery)
        $meta_filters = $request['meta_filters'] ?? [];
        if (!empty($meta_filters) && is_array($meta_filters)) {
            foreach ($meta_filters as $key => $value) {
                $args['metaquery[' . sanitize_key($key) . ']'] = sanitize_text_field($value);
            }
        }

        $url = add_query_arg($args, $api_url);

        $response = wp_remote_get($url, [
            'timeout' => 30,
            'sslverify' => true,
            'user-agent' => 'Mozilla/5.0 (compatible; NewsmastCurator/1.0; +WordPress)',
        ]);

        if (is_wp_error($response)) {
            return $this->prepare_error($response->get_error_message(), 'request_failed', 500);
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            return $this->prepare_error(
                sprintf(__('Tainacan retornou HTTP %d', 'newsmast-curator'), $code),
                'http_error',
                $code
            );
        }

        $total = (int) wp_remote_retrieve_header($response, 'X-WP-Total');
        $total_pages = (int) wp_remote_retrieve_header($response, 'X-WP-TotalPages');

        $data = json_decode(wp_remote_retrieve_body($response), true);
        $tainacan_items = $data['items'] ?? [];

        // Verifica quais já foram importados
        $item_repo = new Item_Repository($this->database);
        $connector = new \NewsmastCurator\Connectors\Tainacan_Connector();
        $connector->connect($config);

        $items = [];
        foreach ($tainacan_items as $item) {
            $external_id = 'tainacan_' . $item['id'];
            $already_imported = $item_repo->exists_by_external_id($source_id, $external_id);

            // Extrai thumbnail
            $image_url = null;
            if (!empty($item['thumbnail'])) {
                $sizes = ['medium_large', 'large', 'tainacan-medium-full', 'full'];
                foreach ($sizes as $size) {
                    if (!empty($item['thumbnail'][$size][0])) {
                        $image_url = $item['thumbnail'][$size][0];
                        break;
                    }
                }
                if (!$image_url) {
                    foreach ($item['thumbnail'] as $size_data) {
                        if (!empty($size_data[0])) { $image_url = $size_data[0]; break; }
                    }
                }
            }

            $title = wp_strip_all_tags($item['title'] ?? '');
            $author = null;
            $metadata = $item['metadata'] ?? [];
            foreach (['autor', 'autoria'] as $slug) {
                if (!empty($metadata[$slug]['value_as_string'])) {
                    $author = wp_strip_all_tags($metadata[$slug]['value_as_string']);
                    break;
                }
            }
            if (!$author && !empty($item['author_name'])) {
                $author = $item['author_name'];
            }

            $items[] = [
                'tainacan_id' => $item['id'],
                'external_id' => $external_id,
                'title' => $title,
                'description' => wp_trim_words(wp_strip_all_tags($item['description'] ?? ''), 30),
                'image_url' => $image_url,
                'url' => $item['url'] ?? '',
                'author' => $author,
                'creation_date' => $item['creation_date'] ?? '',
                'already_imported' => $already_imported,
            ];
        }

        return $this->prepare_response([
            'items' => $items,
            'total' => $total,
            'total_pages' => $total_pages,
            'page' => $args['paged'],
            'per_page' => $args['perpage'],
            'source_name' => $source->get_name(),
        ]);
    }

    /**
     * Importa itens selecionados do Tainacan
     */
    public function import_selected($request) {
        $source_id = (int) ($request['source_id'] ?? 0);
        $tainacan_ids = $request['tainacan_ids'] ?? [];

        if (empty($tainacan_ids) || !is_array($tainacan_ids)) {
            return $this->prepare_error(
                __('Nenhum item selecionado', 'newsmast-curator'),
                'no_items',
                400
            );
        }

        $source_repo = new Source_Repository($this->database);
        $source = $source_repo->find($source_id);

        if (!$source || $source->get_connector_type() !== 'tainacan') {
            return $this->prepare_error(
                __('Fonte Tainacan não encontrada', 'newsmast-curator'),
                'invalid_source',
                400
            );
        }

        $config = array_merge(['url' => $source->get_url()], $source->get_config());
        $base_url = rtrim($config['url'], '/');
        $collection_id = $config['collection_id'] ?? '';
        $item_repo = new Item_Repository($this->database);
        $logger = new \NewsmastCurator\Services\Logger_Service($this->database);

        $imported = 0;
        $skipped = 0;

        foreach ($tainacan_ids as $tainacan_id) {
            $tainacan_id = (int) $tainacan_id;
            $external_id = 'tainacan_' . $tainacan_id;

            // Já importado?
            if ($item_repo->exists_by_external_id($source_id, $external_id)) {
                $skipped++;
                continue;
            }

            // Busca item individual do Tainacan
            $item_url = "{$base_url}/wp-json/tainacan/v2/items/{$tainacan_id}";
            $item_url = add_query_arg([
                'fetch_only' => 'title,description,thumbnail,_thumbnail_id,url,document_as_html,creation_date,author_name,metadata,id,collection_id',
            ], $item_url);

            $response = wp_remote_get($item_url, [
                'timeout' => 15,
                'sslverify' => true,
                'user-agent' => 'Mozilla/5.0 (compatible; NewsmastCurator/1.0; +WordPress)',
            ]);

            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                continue;
            }

            $tainacan_item = json_decode(wp_remote_retrieve_body($response), true);
            if (!$tainacan_item) continue;

            // Usa o conector para extrair dados
            $connector = new \NewsmastCurator\Connectors\Tainacan_Connector();
            $connector->connect($config);

            // Monta o item manualmente
            $item = new \NewsmastCurator\Models\Item();
            $item->set_source_id($source_id);
            $item->set_external_id($external_id);

            $title = wp_strip_all_tags($tainacan_item['title'] ?? '');
            $metadata = $tainacan_item['metadata'] ?? [];
            if (empty($title)) {
                foreach (['denominacao', 'denominação'] as $slug) {
                    if (!empty($metadata[$slug]['value_as_string'])) {
                        $title = wp_strip_all_tags($metadata[$slug]['value_as_string']);
                        break;
                    }
                }
            }
            $item->set_title($title);
            $item->set_content(wp_kses_post($tainacan_item['description'] ?? ''));
            $item->set_url($tainacan_item['url'] ?? '');

            // Imagem
            $image_url = null;
            if (!empty($tainacan_item['thumbnail'])) {
                $sizes = ['medium_large', 'large', 'tainacan-medium-full', 'full'];
                foreach ($sizes as $size) {
                    if (!empty($tainacan_item['thumbnail'][$size][0])) {
                        $image_url = $tainacan_item['thumbnail'][$size][0];
                        break;
                    }
                }
                if (!$image_url) {
                    foreach ($tainacan_item['thumbnail'] as $size_data) {
                        if (!empty($size_data[0])) { $image_url = $size_data[0]; break; }
                    }
                }
            }
            $item->set_image_url($image_url);

            // Autor
            $author = null;
            foreach (['autor', 'autoria'] as $slug) {
                if (!empty($metadata[$slug]['value_as_string'])) {
                    $author = wp_strip_all_tags($metadata[$slug]['value_as_string']);
                    break;
                }
            }
            if (!$author && !empty($tainacan_item['author_name'])) {
                $author = $tainacan_item['author_name'];
            }
            $item->set_author($author);
            $item->set_metadata($metadata);
            $item->set_collection_type('manual');
            $item->generate_hash();

            if ($item_repo->insert($item)) {
                $imported++;
            }
        }

        $logger->info('Importação seletiva do Tainacan', [
            'related_type' => 'source',
            'related_id' => $source_id,
            'details' => sprintf('Importados: %d | Já existentes: %d | Total selecionados: %d', $imported, $skipped, count($tainacan_ids)),
        ]);

        return $this->prepare_response([
            'success' => true,
            'imported' => $imported,
            'skipped' => $skipped,
        ]);
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
