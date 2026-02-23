<?php
/**
 * Repository de Coleções
 *
 * @package NewsmastCurator
 * @subpackage Repositories
 */

namespace NewsmastCurator\Repositories;

use NewsmastCurator\Core\Database;
use NewsmastCurator\Models\Collection;

class Collection_Repository extends Base_Repository {
    /**
     * Nome da tabela pivot
     *
     * @var string
     */
    private $pivot_table;

    /**
     * Construtor
     *
     * @param Database $database
     */
    public function __construct(Database $database) {
        parent::__construct($database);
        $this->table_name = $database->get_table_name('collections');
        $this->pivot_table = $database->get_table_name('collection_items');
        $this->model_class = Collection::class;
    }

    /**
     * Busca todas as coleções com contagens de itens
     *
     * @param array $args
     * @return array
     */
    public function find_all($args = []) {
        $defaults = [
            'per_page' => 20,
            'page' => 1,
            'orderby' => 'id',
            'order' => 'DESC',
        ];

        $args = wp_parse_args($args, $defaults);
        $where = $this->prepare_where($args);
        $orderby = $this->prepare_order_aliased($args['orderby'], $args['order']);
        $offset = ($args['page'] - 1) * $args['per_page'];
        $limit = $this->wpdb->prepare('LIMIT %d, %d', $offset, $args['per_page']);

        $pubs_table = $this->database->get_table_name('publications');

        $sql = "SELECT c.*,
                    (SELECT COUNT(*) FROM {$this->pivot_table} ci WHERE ci.collection_id = c.id) as items_count,
                    (SELECT COUNT(*) FROM {$this->pivot_table} ci2
                     INNER JOIN {$pubs_table} p ON p.item_id = ci2.item_id
                     WHERE ci2.collection_id = c.id AND p.status = 'published') as published_count
                FROM {$this->table_name} c
                {$where} {$orderby} {$limit}";

        $rows = $this->wpdb->get_results($sql);
        return array_map([Collection::class, 'from_row'], $rows);
    }

    /**
     * Conta coleções com filtros
     *
     * @param array $args
     * @return int
     */
    public function count($args = []) {
        $where = $this->prepare_where($args);
        $sql = "SELECT COUNT(*) FROM {$this->table_name} c {$where}";
        return (int) $this->wpdb->get_var($sql);
    }

    // --- Operações na tabela pivot ---

    /**
     * Adiciona item a uma coleção
     *
     * @param int $collection_id
     * @param int $item_id
     * @param int $sort_order
     * @return int|false
     */
    public function add_item($collection_id, $item_id, $sort_order = 0) {
        return $this->wpdb->insert($this->pivot_table, [
            'collection_id' => (int) $collection_id,
            'item_id' => (int) $item_id,
            'sort_order' => (int) $sort_order,
            'added_at' => current_time('mysql'),
        ]);
    }

    /**
     * Remove item de uma coleção
     *
     * @param int $collection_id
     * @param int $item_id
     * @return int|false
     */
    public function remove_item($collection_id, $item_id) {
        return $this->wpdb->delete($this->pivot_table, [
            'collection_id' => (int) $collection_id,
            'item_id' => (int) $item_id,
        ]);
    }

    /**
     * Obtém itens de uma coleção com dados do item
     *
     * @param int $collection_id
     * @return array
     */
    public function get_collection_items($collection_id) {
        $items_table = $this->database->get_table_name('items');

        $sql = $this->wpdb->prepare(
            "SELECT i.*, ci.sort_order, ci.added_at as added_to_collection_at
             FROM {$this->pivot_table} ci
             INNER JOIN {$items_table} i ON i.id = ci.item_id
             WHERE ci.collection_id = %d
             ORDER BY ci.sort_order ASC",
            $collection_id
        );

        return $this->wpdb->get_results($sql);
    }

    /**
     * Conta itens de uma coleção
     *
     * @param int $collection_id
     * @return int
     */
    public function get_items_count($collection_id) {
        $sql = $this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->pivot_table} WHERE collection_id = %d",
            $collection_id
        );
        return (int) $this->wpdb->get_var($sql);
    }

    /**
     * Atualiza ordem de um item na coleção
     *
     * @param int $collection_id
     * @param int $item_id
     * @param int $sort_order
     * @return bool
     */
    public function update_item_sort_order($collection_id, $item_id, $sort_order) {
        return $this->wpdb->update(
            $this->pivot_table,
            ['sort_order' => (int) $sort_order],
            [
                'collection_id' => (int) $collection_id,
                'item_id' => (int) $item_id,
            ]
        ) !== false;
    }

    /**
     * Reordena itens de uma coleção
     *
     * @param int $collection_id
     * @param array $item_ids Array ordenado de IDs de itens
     * @return bool
     */
    public function reorder_items($collection_id, $item_ids) {
        foreach ($item_ids as $index => $item_id) {
            $this->update_item_sort_order($collection_id, (int) $item_id, $index);
        }
        return true;
    }

    /**
     * Busca coleções por status
     *
     * @param string $status
     * @param array $args
     * @return array
     */
    public function find_by_status($status, $args = []) {
        $args['status'] = $status;
        return $this->find_all($args);
    }

    /**
     * Obtém estatísticas de coleções
     *
     * @return array
     */
    public function get_stats() {
        $sql = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
                    SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
                    SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
                    SUM(CASE WHEN status = 'partial' THEN 1 ELSE 0 END) as partial
                FROM {$this->table_name}";

        return (array) $this->wpdb->get_row($sql, ARRAY_A);
    }

    /**
     * Deleta coleção e suas entradas pivot
     *
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        $this->wpdb->delete($this->pivot_table, ['collection_id' => (int) $id]);
        return parent::delete($id);
    }

    /**
     * Atualiza status das coleções baseado no status das publicações
     *
     * @return void
     */
    public function refresh_statuses() {
        $pubs_table = $this->database->get_table_name('publications');

        $sql = "UPDATE {$this->table_name} c SET status =
            CASE
                WHEN (SELECT COUNT(*) FROM {$this->pivot_table} ci WHERE ci.collection_id = c.id) = 0 THEN 'draft'
                WHEN (SELECT COUNT(*) FROM {$this->pivot_table} ci2
                      INNER JOIN {$pubs_table} p ON p.item_id = ci2.item_id
                      WHERE ci2.collection_id = c.id AND p.status = 'published')
                     =
                     (SELECT COUNT(*) FROM {$this->pivot_table} ci3 WHERE ci3.collection_id = c.id) THEN 'published'
                WHEN (SELECT COUNT(*) FROM {$this->pivot_table} ci4
                      INNER JOIN {$pubs_table} p2 ON p2.item_id = ci4.item_id
                      WHERE ci4.collection_id = c.id AND p2.status = 'published') > 0 THEN 'partial'
                ELSE c.status
            END
            WHERE c.status IN ('scheduled', 'partial')";

        $this->wpdb->query($sql);
    }

    /**
     * Prepara WHERE com filtros específicos (usa alias c.)
     *
     * @param array $args
     * @return string
     */
    protected function prepare_where($args) {
        $conditions = [];

        if (!empty($args['status'])) {
            $conditions[] = $this->wpdb->prepare('c.status = %s', $args['status']);
        }

        if (!empty($args['search'])) {
            $like = '%' . $this->wpdb->esc_like($args['search']) . '%';
            $conditions[] = $this->wpdb->prepare(
                '(c.name LIKE %s OR c.description LIKE %s)',
                $like, $like
            );
        }

        if (!empty($args['created_by'])) {
            $conditions[] = $this->wpdb->prepare('c.created_by = %d', $args['created_by']);
        }

        if (empty($conditions)) {
            return '';
        }

        return 'WHERE ' . implode(' AND ', $conditions);
    }

    /**
     * Prepara ORDER BY com alias c.
     *
     * @param string $orderby
     * @param string $order
     * @return string
     */
    private function prepare_order_aliased($orderby, $order) {
        $orderby = sanitize_key($orderby);
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        return "ORDER BY c.{$orderby} {$order}";
    }
}
