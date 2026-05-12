<?php
namespace NewsmastCurator\API;

use NewsmastCurator\Repositories\Publication_Repository;
use NewsmastCurator\Repositories\Item_Repository;
use NewsmastCurator\Models\Publication;

class Publications_Controller extends Base_REST_Controller {
    protected $rest_base = 'publications';

    public function register_routes() {
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            ['methods' => 'GET', 'callback' => [$this, 'get_items'], 'permission_callback' => [$this, 'check_permissions']],
            ['methods' => 'POST', 'callback' => [$this, 'create_item'], 'permission_callback' => [$this, 'check_permissions']],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
            ['methods' => 'DELETE', 'callback' => [$this, 'delete_item'], 'permission_callback' => [$this, 'check_permissions']],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/retry', [
            ['methods' => 'POST', 'callback' => [$this, 'retry_item'], 'permission_callback' => [$this, 'check_permissions']],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/process-now', [
            ['methods' => 'POST', 'callback' => [$this, 'process_now'], 'permission_callback' => [$this, 'check_permissions']],
        ]);

        register_rest_route($this->namespace, '/wp-categories', [
            ['methods' => 'GET', 'callback' => [$this, 'get_wp_categories'], 'permission_callback' => [$this, 'check_permissions']],
        ]);
    }

    /**
     * Lista categorias do WordPress (para seletor no modal de agendamento)
     */
    public function get_wp_categories($request) {
        $categories = \NewsmastCurator\Services\WordPress_Publisher_Service::get_categories();
        return $this->prepare_response(['categories' => $categories]);
    }

    public function get_items($request) {
        $repo = new Publication_Repository($this->database);
        $args = [];
        if (isset($request['status'])) $args['status'] = $request['status'];
        $args['per_page'] = $request['per_page'] ?? 20;
        $args['page'] = $request['page'] ?? 1;

        $pubs = $repo->find_all($args);
        return $this->prepare_response(array_map(fn($p) => $p->to_api_response(), $pubs));
    }

    public function create_item($request) {
        $item_id = isset($request['item_id']) ? (int) $request['item_id'] : 0;
        $scheduled_for = isset($request['scheduled_for']) ? sanitize_text_field($request['scheduled_for']) : '';
        $content = isset($request['content']) ? sanitize_textarea_field($request['content']) : '';
        $alt_text = isset($request['alt_text']) ? sanitize_textarea_field($request['alt_text']) : '';
        $mastodon_account_ids = isset($request['mastodon_account_ids']) ? array_map('intval', (array) $request['mastodon_account_ids']) : [];
        $mastodon_account_id = isset($request['mastodon_account_id']) ? (int) $request['mastodon_account_id'] : 0;

        // Destinos: mastodon, wordpress, ou ambos
        $destinations = isset($request['destinations']) ? (array) $request['destinations'] : ['mastodon'];
        $destinations = array_intersect($destinations, ['mastodon', 'wordpress']);
        if (empty($destinations)) $destinations = ['mastodon'];

        $wp_category_id = isset($request['wp_category_id']) ? (int) $request['wp_category_id'] : 0;

        if (empty($item_id)) {
            return $this->prepare_error(__('Item é obrigatório', 'newsmast-curator'), 'validation_error', 400);
        }

        $item_repo = new Item_Repository($this->database);
        if (!$item_repo->exists($item_id)) {
            return $this->prepare_error(__('Item não encontrado', 'newsmast-curator'), 'not_found', 404);
        }

        if (empty($scheduled_for)) {
            return $this->prepare_error(__('Data de agendamento é obrigatória', 'newsmast-curator'), 'validation_error', 400);
        }

        $timestamp = strtotime($scheduled_for);
        $now = current_time('timestamp');
        if (!$timestamp || $timestamp < ($now - 60)) {
            return $this->prepare_error(__('Data de agendamento deve ser atual ou futura (não retroativa)', 'newsmast-curator'), 'validation_error', 400);
        }

        if (empty($content)) {
            return $this->prepare_error(__('Conteúdo é obrigatório', 'newsmast-curator'), 'validation_error', 400);
        }

        // Valida categoria WP se destino inclui WordPress
        if (in_array('wordpress', $destinations, true)) {
            if (!$wp_category_id || !term_exists($wp_category_id, 'category')) {
                return $this->prepare_error(
                    __('Categoria WordPress é obrigatória quando o destino inclui WordPress', 'newsmast-curator'),
                    'validation_error',
                    400
                );
            }
        }

        $normalized_datetime = date('Y-m-d H:i:s', $timestamp);
        $repo = new Publication_Repository($this->database);
        $created = [];

        // Cria publicação Mastodon (uma por conta, se múltiplas)
        if (in_array('mastodon', $destinations, true)) {
            $accounts = !empty($mastodon_account_ids) ? $mastodon_account_ids
                : ($mastodon_account_id > 0 ? [$mastodon_account_id] : [0]);

            foreach ($accounts as $account_id) {
                $pub = new Publication();
                $pub->set_item_id($item_id);
                $pub->set_destination_type(Publication::DESTINATION_MASTODON);
                if ($account_id > 0) {
                    $pub->set_mastodon_account_id($account_id);
                }
                $pub->set_scheduled_for($normalized_datetime);
                $pub->set_content($content);
                $pub->set_alt_text($alt_text);
                $pub->set_published_by(get_current_user_id());

                if ($repo->insert($pub)) {
                    $created[] = $pub->to_api_response();
                }
            }
        }

        // Cria publicação WordPress
        if (in_array('wordpress', $destinations, true)) {
            $pub = new Publication();
            $pub->set_item_id($item_id);
            $pub->set_destination_type(Publication::DESTINATION_WORDPRESS);
            $pub->set_wp_category_id($wp_category_id);
            $pub->set_scheduled_for($normalized_datetime);
            $pub->set_content($content);
            $pub->set_published_by(get_current_user_id());

            if ($repo->insert($pub)) {
                $created[] = $pub->to_api_response();
            }
        }

        return !empty($created)
            ? $this->prepare_response(count($created) === 1 ? $created[0] : $created, 201)
            : $this->prepare_error(__('Falha ao criar publicações', 'newsmast-curator'));
    }

    public function delete_item($request) {
        $repo = new Publication_Repository($this->database);
        $result = $repo->delete($request['id']);
        return $result ? $this->prepare_response(['success' => true]) : $this->prepare_error('Failed');
    }

    public function retry_item($request) {
        $repo = new Publication_Repository($this->database);
        $pub = $repo->find((int) $request['id']);

        if (!$pub) {
            return $this->prepare_error(__('Publicação não encontrada', 'newsmast-curator'), 'not_found', 404);
        }

        if ($pub->get_status() !== Publication::STATUS_FAILED) {
            return $this->prepare_error(__('Somente publicações com falha podem ser reagendadas', 'newsmast-curator'), 'invalid_status', 400);
        }

        $pub->set_status(Publication::STATUS_SCHEDULED);
        $pub->set_scheduled_for(date('Y-m-d H:i:s', time() + 60));
        $pub->set_attempt_count(0);
        $pub->set_last_error('');
        $repo->update($pub);

        return $this->prepare_response([
            'success' => true,
            'message' => __('Publicação reagendada', 'newsmast-curator'),
        ]);
    }

    public function process_now($request) {
        $repo = new Publication_Repository($this->database);

        // Log pre-processing state for diagnostics
        $logger = new \NewsmastCurator\Services\Logger_Service($this->database);
        $status_counts = $repo->count_by_status();
        $logger->info('process_now: Estado antes do processamento', [
            'related_type' => 'scheduler',
            'details' => sprintf('Publicações por status: %s | Hora local: %s', wp_json_encode($status_counts), current_time('mysql')),
        ]);

        $scheduler = new \NewsmastCurator\Services\Scheduler_Service($this->database);
        $scheduler->process_scheduled_publications();

        $stats = $repo->get_stats();

        return $this->prepare_response([
            'success' => true,
            'message' => __('Fila processada', 'newsmast-curator'),
            'stats' => $stats,
        ]);
    }
}
