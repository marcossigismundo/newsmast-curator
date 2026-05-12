<?php
/**
 * REST Controller de Coleções
 *
 * @package NewsmastCurator
 * @subpackage API
 */

namespace NewsmastCurator\API;

use NewsmastCurator\Repositories\Collection_Repository;
use NewsmastCurator\Repositories\Publication_Repository;
use NewsmastCurator\Repositories\Item_Repository;
use NewsmastCurator\Models\Collection;
use NewsmastCurator\Models\Publication;
use NewsmastCurator\Models\Item;

class Collections_Controller extends Base_REST_Controller {
    protected $rest_base = 'collections';

    public function register_routes() {
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            ['methods' => 'GET', 'callback' => [$this, 'get_items'], 'permission_callback' => [$this, 'check_permissions']],
            ['methods' => 'POST', 'callback' => [$this, 'create_item'], 'permission_callback' => [$this, 'check_permissions']],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
            ['methods' => 'GET', 'callback' => [$this, 'get_item'], 'permission_callback' => [$this, 'check_permissions']],
            ['methods' => 'PUT', 'callback' => [$this, 'update_item'], 'permission_callback' => [$this, 'check_permissions']],
            ['methods' => 'DELETE', 'callback' => [$this, 'delete_item'], 'permission_callback' => [$this, 'check_permissions']],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/items', [
            ['methods' => 'GET', 'callback' => [$this, 'get_collection_items'], 'permission_callback' => [$this, 'check_permissions']],
            ['methods' => 'POST', 'callback' => [$this, 'add_collection_items'], 'permission_callback' => [$this, 'check_permissions']],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/items/(?P<item_id>[\d]+)', [
            ['methods' => 'DELETE', 'callback' => [$this, 'remove_collection_item'], 'permission_callback' => [$this, 'check_permissions']],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/reorder', [
            ['methods' => 'POST', 'callback' => [$this, 'reorder_items'], 'permission_callback' => [$this, 'check_permissions']],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/schedule', [
            ['methods' => 'POST', 'callback' => [$this, 'schedule_collection'], 'permission_callback' => [$this, 'check_permissions']],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/stats', [
            ['methods' => 'GET', 'callback' => [$this, 'get_stats'], 'permission_callback' => [$this, 'check_permissions']],
        ]);
    }

    /**
     * Lista coleções
     */
    public function get_items($request) {
        $repo = new Collection_Repository($this->database);
        $args = [];

        if (isset($request['status'])) {
            $args['status'] = sanitize_text_field($request['status']);
        }
        if (isset($request['search'])) {
            $args['search'] = sanitize_text_field($request['search']);
        }

        $args['per_page'] = (int) ($request['per_page'] ?? 20);
        $args['page'] = (int) ($request['page'] ?? 1);

        $collections = $repo->find_all($args);
        $total = $repo->count($args);

        return $this->prepare_response([
            'collections' => array_map(fn($c) => $c->to_api_response(), $collections),
            'total' => $total,
            'pages' => $args['per_page'] > 0 ? ceil($total / $args['per_page']) : 1,
        ]);
    }

    /**
     * Obtém uma coleção
     */
    public function get_item($request) {
        $repo = new Collection_Repository($this->database);
        $collection = $repo->find($request['id']);

        if (!$collection) {
            return $this->prepare_error(__('Coleção não encontrada', 'newsmast-curator'), 'not_found', 404);
        }

        return $this->prepare_response($collection->to_api_response());
    }

    /**
     * Cria nova coleção
     */
    public function create_item($request) {
        $name = isset($request['name']) ? sanitize_text_field($request['name']) : '';

        if (empty($name)) {
            return $this->prepare_error(__('Nome é obrigatório', 'newsmast-curator'), 'validation_error', 400);
        }

        $collection = new Collection();
        $collection->set_name($name);
        $collection->set_description($request['description'] ?? '');
        $collection->set_color($request['color'] ?? '#457B9D');
        $collection->set_created_by(get_current_user_id());

        $validation = $collection->validate();
        if ($validation !== true) {
            return $this->prepare_error(__('Validação falhou', 'newsmast-curator'), 'validation_error', 400);
        }

        $repo = new Collection_Repository($this->database);
        $id = $repo->insert($collection);

        if (!$id) {
            return $this->prepare_error(__('Falha ao criar coleção', 'newsmast-curator'));
        }

        return $this->prepare_response($collection->to_api_response(), 201);
    }

    /**
     * Atualiza coleção
     */
    public function update_item($request) {
        $repo = new Collection_Repository($this->database);
        $collection = $repo->find($request['id']);

        if (!$collection) {
            return $this->prepare_error(__('Coleção não encontrada', 'newsmast-curator'), 'not_found', 404);
        }

        if (isset($request['name'])) $collection->set_name($request['name']);
        if (isset($request['description'])) $collection->set_description($request['description']);
        if (isset($request['color'])) $collection->set_color($request['color']);
        if (isset($request['status'])) $collection->set_status($request['status']);

        $validation = $collection->validate();
        if ($validation !== true) {
            return $this->prepare_error(__('Validação falhou', 'newsmast-curator'), 'validation_error', 400);
        }

        $repo->update($collection);
        return $this->prepare_response($collection->to_api_response());
    }

    /**
     * Deleta coleção
     */
    public function delete_item($request) {
        $repo = new Collection_Repository($this->database);

        if (!$repo->exists($request['id'])) {
            return $this->prepare_error(__('Coleção não encontrada', 'newsmast-curator'), 'not_found', 404);
        }

        $result = $repo->delete($request['id']);
        return $result
            ? $this->prepare_response(['success' => true])
            : $this->prepare_error(__('Falha ao excluir coleção', 'newsmast-curator'));
    }

    /**
     * Lista itens de uma coleção
     */
    public function get_collection_items($request) {
        $repo = new Collection_Repository($this->database);

        if (!$repo->exists($request['id'])) {
            return $this->prepare_error(__('Coleção não encontrada', 'newsmast-curator'), 'not_found', 404);
        }

        $rows = $repo->get_collection_items($request['id']);

        $items = array_map(function($row) {
            $item = Item::from_row($row);
            $response = $item->to_api_response();
            $response['sort_order'] = (int) $row->sort_order;
            $response['added_to_collection_at'] = $row->added_to_collection_at;
            return $response;
        }, $rows);

        return $this->prepare_response($items);
    }

    /**
     * Adiciona itens a uma coleção
     */
    public function add_collection_items($request) {
        $repo = new Collection_Repository($this->database);

        if (!$repo->exists($request['id'])) {
            return $this->prepare_error(__('Coleção não encontrada', 'newsmast-curator'), 'not_found', 404);
        }

        // Suporta item_ids (array) ou item_id (single)
        $item_ids = $request['item_ids'] ?? [];
        if (empty($item_ids) && isset($request['item_id'])) {
            $item_ids = [(int) $request['item_id']];
        }

        if (empty($item_ids)) {
            return $this->prepare_error(__('Nenhum item fornecido', 'newsmast-curator'), 'validation_error', 400);
        }

        // Validar que itens existem
        $item_repo = new Item_Repository($this->database);
        $current_count = $repo->get_items_count($request['id']);
        $added = 0;

        foreach ($item_ids as $index => $item_id) {
            $item_id = (int) $item_id;
            if ($item_repo->exists($item_id)) {
                $result = $repo->add_item($request['id'], $item_id, $current_count + $index);
                if ($result) $added++;
            }
        }

        return $this->prepare_response(['success' => true, 'added' => $added]);
    }

    /**
     * Remove item de uma coleção
     */
    public function remove_collection_item($request) {
        $repo = new Collection_Repository($this->database);
        $repo->remove_item($request['id'], $request['item_id']);
        return $this->prepare_response(['success' => true]);
    }

    /**
     * Reordena itens de uma coleção
     */
    public function reorder_items($request) {
        $item_ids = $request['item_ids'] ?? [];

        if (empty($item_ids) || !is_array($item_ids)) {
            return $this->prepare_error(__('Lista de itens inválida', 'newsmast-curator'), 'validation_error', 400);
        }

        $repo = new Collection_Repository($this->database);
        $repo->reorder_items($request['id'], array_map('intval', $item_ids));
        return $this->prepare_response(['success' => true]);
    }

    /**
     * Agenda toda a coleção (cria publicações individuais com intervalo)
     */
    public function schedule_collection($request) {
        $repo = new Collection_Repository($this->database);
        $collection = $repo->find($request['id']);

        if (!$collection) {
            return $this->prepare_error(__('Coleção não encontrada', 'newsmast-curator'), 'not_found', 404);
        }

        $scheduled_for = sanitize_text_field($request['scheduled_for'] ?? '');
        $interval = (int) ($request['interval_minutes'] ?? 60);
        $as_thread = !empty($request['as_thread']);
        $mastodon_account_ids = $request['mastodon_account_ids'] ?? [];

        // Destinos: mastodon, wordpress, ou ambos
        $destinations = isset($request['destinations']) ? (array) $request['destinations'] : ['mastodon'];
        $destinations = array_intersect($destinations, ['mastodon', 'wordpress']);
        if (empty($destinations)) $destinations = ['mastodon'];

        $wp_category_id = isset($request['wp_category_id']) ? (int) $request['wp_category_id'] : 0;

        if (empty($scheduled_for)) {
            return $this->prepare_error(__('Data de agendamento é obrigatória', 'newsmast-curator'), 'validation_error', 400);
        }

        $start_timestamp = strtotime($scheduled_for);
        $now = current_time('timestamp');
        if (!$start_timestamp || $start_timestamp < ($now - 60)) {
            return $this->prepare_error(__('Data de agendamento deve ser atual ou futura (não retroativa)', 'newsmast-curator'), 'validation_error', 400);
        }

        if ($interval < 1) {
            return $this->prepare_error(__('Intervalo deve ser pelo menos 1 minuto', 'newsmast-curator'), 'validation_error', 400);
        }

        // Thread mode requires a single Mastodon account
        if ($as_thread && count($mastodon_account_ids) > 1) {
            return $this->prepare_error(
                __('Thread requer uma única conta Mastodon (todas as respostas devem ser do mesmo autor)', 'newsmast-curator'),
                'validation_error',
                400
            );
        }

        // Valida categoria WP quando destino inclui WordPress
        if (in_array('wordpress', $destinations, true)) {
            if (!$wp_category_id || !term_exists($wp_category_id, 'category')) {
                return $this->prepare_error(
                    __('Categoria WordPress é obrigatória quando o destino inclui WordPress', 'newsmast-curator'),
                    'validation_error',
                    400
                );
            }
        }

        $rows = $repo->get_collection_items($request['id']);
        if (empty($rows)) {
            return $this->prepare_error(__('Coleção não possui itens', 'newsmast-curator'), 'empty_collection', 400);
        }

        $pub_repo = new Publication_Repository($this->database);
        $created = 0;

        // Thread só faz sentido para Mastodon
        $effective_thread = $as_thread && in_array('mastodon', $destinations, true);
        $thread_id = $effective_thread ? 'thread_' . $request['id'] . '_' . time() : null;
        $effective_interval = $effective_thread ? max($interval, 1) : $interval;

        // Cria publicações Mastodon (uma por conta, com thread quando aplicável)
        if (in_array('mastodon', $destinations, true)) {
            $target_accounts = !empty($mastodon_account_ids)
                ? array_map('intval', (array) $mastodon_account_ids)
                : [null];

            foreach ($target_accounts as $account_id) {
                $account_thread_id = $thread_id && $account_id ? $thread_id . '_a' . $account_id : $thread_id;

                foreach ($rows as $index => $row) {
                    $item = Item::from_row($row);
                    $pub_time = date('Y-m-d H:i:s', $start_timestamp + ($index * $effective_interval * 60));

                    $pub = new Publication();
                    $pub->set_item_id($item->get_id());
                    $pub->set_destination_type(Publication::DESTINATION_MASTODON);
                    $pub->set_scheduled_for($pub_time);
                    $pub->set_content($item->get_formatted_content());
                    $pub->set_published_by(get_current_user_id());

                    if ($account_id) {
                        $pub->set_mastodon_account_id($account_id);
                    }

                    if ($effective_thread) {
                        $pub->set_thread_id($account_thread_id ?: $thread_id);
                        $pub->set_thread_position($index);
                    }

                    if ($pub_repo->insert($pub)) {
                        $created++;
                    }
                }
            }
        }

        // Cria publicações WordPress (uma por item, na mesma categoria)
        if (in_array('wordpress', $destinations, true)) {
            foreach ($rows as $index => $row) {
                $item = Item::from_row($row);
                $pub_time = date('Y-m-d H:i:s', $start_timestamp + ($index * $interval * 60));

                $pub = new Publication();
                $pub->set_item_id($item->get_id());
                $pub->set_destination_type(Publication::DESTINATION_WORDPRESS);
                $pub->set_wp_category_id($wp_category_id);
                $pub->set_scheduled_for($pub_time);
                $pub->set_content($item->get_formatted_content());
                $pub->set_published_by(get_current_user_id());

                if ($pub_repo->insert($pub)) {
                    $created++;
                }
            }
        }

        $collection->set_status(Collection::STATUS_SCHEDULED);
        $collection->set_scheduled_for(date('Y-m-d H:i:s', $start_timestamp));
        $collection->set_interval_minutes($effective_interval);
        $repo->update($collection);

        return $this->prepare_response([
            'success' => true,
            'publications_created' => $created,
            'total_items' => count($rows),
            'as_thread' => $effective_thread,
            'thread_id' => $thread_id,
            'destinations' => array_values($destinations),
        ]);
    }

    /**
     * Estatísticas de coleções
     */
    public function get_stats($request) {
        $repo = new Collection_Repository($this->database);
        return $this->prepare_response($repo->get_stats());
    }
}
