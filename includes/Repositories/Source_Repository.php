<?php
/**
 * Repository de Fontes
 *
 * @package NewsmastCurator
 * @subpackage Repositories
 */

namespace NewsmastCurator\Repositories;

use NewsmastCurator\Core\Database;
use NewsmastCurator\Models\Source;

class Source_Repository extends Base_Repository {
    /**
     * Construtor
     *
     * @param Database $database
     */
    public function __construct(Database $database) {
        parent::__construct($database);
        $this->table_name = $database->get_table_name('sources');
        $this->model_class = Source::class;
    }

    /**
     * Busca fontes por tipo de conector
     *
     * @param string $connector_type
     * @return array
     */
    public function find_by_type($connector_type) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE connector_type = %s ORDER BY name ASC",
            $connector_type
        );

        $rows = $this->wpdb->get_results($sql);

        return array_map([Source::class, 'from_row'], $rows);
    }

    /**
     * Busca fontes ativas
     *
     * @return array
     */
    public function find_active() {
        $sql = "SELECT * FROM {$this->table_name} WHERE status = 'active' ORDER BY name ASC";

        $rows = $this->wpdb->get_results($sql);

        return array_map([Source::class, 'from_row'], $rows);
    }

    /**
     * Busca fontes com erro
     *
     * @return array
     */
    public function find_with_errors() {
        $sql = "SELECT * FROM {$this->table_name}
                WHERE status = 'error' OR last_error IS NOT NULL
                ORDER BY updated_at DESC";

        $rows = $this->wpdb->get_results($sql);

        return array_map([Source::class, 'from_row'], $rows);
    }

    /**
     * Atualiza data da última coleta
     *
     * @param int $id
     * @param string $datetime
     * @return bool
     */
    public function update_last_collection($id, $datetime) {
        return $this->wpdb->update(
            $this->table_name,
            [
                'last_collection' => $datetime,
                'last_error' => null,
                'status' => 'active',
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $id]
        ) !== false;
    }

    /**
     * Atualiza erro da fonte
     *
     * @param int $id
     * @param string $error_message
     * @return bool
     */
    public function update_error($id, $error_message) {
        return $this->wpdb->update(
            $this->table_name,
            [
                'last_error' => $error_message,
                'status' => 'error',
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $id]
        ) !== false;
    }

    /**
     * Obtém estatísticas de fontes
     *
     * @return array
     */
    public function get_stats() {
        $sql = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as errors,
                    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive
                FROM {$this->table_name}";

        return (array) $this->wpdb->get_row($sql, ARRAY_A);
    }

    /**
     * Prepara WHERE com filtros específicos
     *
     * @param array $args
     * @return string
     */
    protected function prepare_where($args) {
        $conditions = [];

        if (!empty($args['status'])) {
            $conditions[] = $this->wpdb->prepare('status = %s', $args['status']);
        }

        if (!empty($args['connector_type'])) {
            $conditions[] = $this->wpdb->prepare('connector_type = %s', $args['connector_type']);
        }

        if (!empty($args['search'])) {
            $like = '%' . $this->wpdb->esc_like($args['search']) . '%';
            $conditions[] = $this->wpdb->prepare('(name LIKE %s OR url LIKE %s)', $like, $like);
        }

        if (empty($conditions)) {
            return '';
        }

        return 'WHERE ' . implode(' AND ', $conditions);
    }
}
