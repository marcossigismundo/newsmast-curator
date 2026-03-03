<?php
namespace NewsmastCurator\API;

use NewsmastCurator\Repositories\Publication_Repository;
use NewsmastCurator\Repositories\Item_Repository;
use NewsmastCurator\Models\Publication;
use NewsmastCurator\Models\Item;

class Public_API_Controller extends Base_REST_Controller {
    protected $rest_base = 'public';

    public function register_routes() {
        register_rest_route($this->namespace, '/' . $this->rest_base . '/items', [
            ['methods' => 'GET', 'callback' => [$this, 'get_items'], 'permission_callback' => [$this, 'check_api_key']],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/items/(?P<id>[\d]+)', [
            ['methods' => 'GET', 'callback' => [$this, 'get_item'], 'permission_callback' => [$this, 'check_api_key']],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/schedule', [
            ['methods' => 'POST', 'callback' => [$this, 'schedule_publication'], 'permission_callback' => [$this, 'check_api_key']],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/publications', [
            ['methods' => 'GET', 'callback' => [$this, 'get_publications'], 'permission_callback' => [$this, 'check_api_key']],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/publications/(?P<id>[\d]+)', [
            ['methods' => 'GET', 'callback' => [$this, 'get_publication'], 'permission_callback' => [$this, 'check_api_key']],
        ]);
    }

    /**
     * Valida API Key via header ou query param
     */
    public function check_api_key($request) {
        if (get_option('nc_api_enabled', '0') !== '1') {
            return new \WP_Error('api_disabled', __('API pública desabilitada', 'newsmast-curator'), ['status' => 401]);
        }

        $stored_key = get_option('nc_api_key', '');
        if (empty($stored_key)) {
            return new \WP_Error('api_not_configured', __('API Key não configurada', 'newsmast-curator'), ['status' => 401]);
        }

        $provided_key = $request->get_header('X-NC-API-Key');
        if (empty($provided_key)) {
            $provided_key = $request->get_param('api_key');
        }

        if (empty($provided_key) || !hash_equals($stored_key, $provided_key)) {
            return new \WP_Error('invalid_api_key', __('API Key inválida', 'newsmast-curator'), ['status' => 403]);
        }

        return true;
    }

    /**
     * Lista itens curados disponíveis
     */
    public function get_items($request) {
        $repo = new Item_Repository($this->database);
        $args = [
            'curated' => 1,
            'per_page' => min((int) ($request['per_page'] ?? 20), 100),
            'page' => max((int) ($request['page'] ?? 1), 1),
        ];

        if (isset($request['source_id'])) {
            $args['source_id'] = (int) $request['source_id'];
        }

        $items = $repo->find_all($args);
        $total = $repo->count($args);

        return $this->prepare_response([
            'items' => array_map(function($item) {
                return [
                    'id' => $item->get_id(),
                    'title' => $item->get_title(),
                    'excerpt' => $item->get_excerpt(),
                    'url' => $item->get_url(),
                    'image_url' => $item->get_image(),
                    'has_image' => $item->has_image(),
                    'published_date' => $item->get_published_date(),
                    'formatted_content' => $item->get_formatted_content(),
                ];
            }, $items),
            'total' => $total,
            'page' => $args['page'],
            'per_page' => $args['per_page'],
        ]);
    }

    /**
     * Detalhes de um item
     */
    public function get_item($request) {
        $repo = new Item_Repository($this->database);
        $item = $repo->find((int) $request['id']);

        if (!$item) {
            return $this->prepare_error(__('Item não encontrado', 'newsmast-curator'), 'not_found', 404);
        }

        return $this->prepare_response([
            'id' => $item->get_id(),
            'title' => $item->get_title(),
            'content' => $item->get_content(),
            'excerpt' => $item->get_excerpt(),
            'url' => $item->get_url(),
            'image_url' => $item->get_image(),
            'has_image' => $item->has_image(),
            'author' => $item->get_author(),
            'published_date' => $item->get_published_date(),
            'formatted_content' => $item->get_formatted_content(),
            'curated' => $item->is_curated(),
        ]);
    }

    /**
     * Agenda publicação de um item
     */
    public function schedule_publication($request) {
        $item_id = isset($request['item_id']) ? (int) $request['item_id'] : 0;
        $scheduled_for = isset($request['scheduled_for']) ? sanitize_text_field($request['scheduled_for']) : '';
        $content = isset($request['content']) ? sanitize_textarea_field($request['content']) : '';

        if (empty($item_id)) {
            return $this->prepare_error(__('item_id é obrigatório', 'newsmast-curator'), 'validation_error', 400);
        }

        $item_repo = new Item_Repository($this->database);
        $item = $item_repo->find($item_id);
        if (!$item) {
            return $this->prepare_error(__('Item não encontrado', 'newsmast-curator'), 'not_found', 404);
        }

        if (empty($scheduled_for)) {
            return $this->prepare_error(__('scheduled_for é obrigatório (formato: Y-m-d H:i:s)', 'newsmast-curator'), 'validation_error', 400);
        }

        $timestamp = strtotime($scheduled_for);
        $now = current_time('timestamp');
        if (!$timestamp || $timestamp < ($now - 60)) {
            return $this->prepare_error(__('scheduled_for deve ser uma data futura', 'newsmast-curator'), 'validation_error', 400);
        }

        // Se content não fornecido, usa o template formatado do item
        if (empty($content)) {
            $content = $item->get_formatted_content();
        }

        if (empty($content)) {
            return $this->prepare_error(__('Conteúdo não pode ser vazio', 'newsmast-curator'), 'validation_error', 400);
        }

        $pub = new Publication();
        $pub->set_item_id($item_id);
        $pub->set_scheduled_for(date('Y-m-d H:i:s', $timestamp));
        $pub->set_content($content);
        $pub->set_published_by(0); // API externa, sem usuário WordPress

        $pub_repo = new Publication_Repository($this->database);
        $id = $pub_repo->insert($pub);

        if (!$id) {
            return $this->prepare_error(__('Falha ao criar publicação', 'newsmast-curator'), 'server_error', 500);
        }

        return $this->prepare_response([
            'id' => $pub->get_id(),
            'item_id' => $pub->get_item_id(),
            'status' => $pub->get_status(),
            'scheduled_for' => $pub->get_scheduled_for(),
            'content' => $pub->get_content(),
        ], 201);
    }

    /**
     * Lista publicações
     */
    public function get_publications($request) {
        $repo = new Publication_Repository($this->database);
        $args = [
            'per_page' => min((int) ($request['per_page'] ?? 20), 100),
            'page' => max((int) ($request['page'] ?? 1), 1),
        ];

        if (isset($request['status'])) {
            $args['status'] = sanitize_text_field($request['status']);
        }

        $pubs = $repo->find_all($args);

        return $this->prepare_response([
            'publications' => array_map(function($pub) {
                return [
                    'id' => $pub->get_id(),
                    'item_id' => $pub->get_item_id(),
                    'status' => $pub->get_status(),
                    'scheduled_for' => $pub->get_scheduled_for(),
                    'content' => $pub->get_content(),
                    'mastodon_id' => $pub->get_mastodon_id(),
                    'mastodon_url' => $pub->get_mastodon_url(),
                    'attempt_count' => $pub->get_attempt_count(),
                    'last_error' => $pub->get_last_error(),
                    'published_at' => $pub->get_published_at(),
                ];
            }, $pubs),
            'page' => $args['page'],
            'per_page' => $args['per_page'],
        ]);
    }

    /**
     * Detalhes de uma publicação
     */
    public function get_publication($request) {
        $repo = new Publication_Repository($this->database);
        $pub = $repo->find((int) $request['id']);

        if (!$pub) {
            return $this->prepare_error(__('Publicação não encontrada', 'newsmast-curator'), 'not_found', 404);
        }

        return $this->prepare_response([
            'id' => $pub->get_id(),
            'item_id' => $pub->get_item_id(),
            'status' => $pub->get_status(),
            'scheduled_for' => $pub->get_scheduled_for(),
            'content' => $pub->get_content(),
            'mastodon_id' => $pub->get_mastodon_id(),
            'mastodon_url' => $pub->get_mastodon_url(),
            'attempt_count' => $pub->get_attempt_count(),
            'last_error' => $pub->get_last_error(),
            'published_at' => $pub->get_published_at(),
        ]);
    }
}
