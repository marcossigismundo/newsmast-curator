<?php
/**
 * Repository Base com métodos CRUD comuns
 *
 * @package NewsmastCurator
 * @subpackage Repositories
 */

namespace NewsmastCurator\Repositories;

use NewsmastCurator\Core\Database;

abstract class Base_Repository {
    /**
     * Instância do wpdb
     *
     * @var \wpdb
     */
    protected $wpdb;

    /**
     * Instância do Database
     *
     * @var Database
     */
    protected $database;

    /**
     * Nome da tabela
     *
     * @var string
     */
    protected $table_name;

    /**
     * Classe do model
     *
     * @var string
     */
    protected $model_class;

    /**
     * Construtor
     *
     * @param Database $database
     */
    public function __construct(Database $database) {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->database = $database;
    }

    /**
     * Busca por ID
     *
     * @param int $id
     * @return mixed|null
     */
    public function find($id) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        );

        $row = $this->wpdb->get_row($sql);

        if (!$row) {
            return null;
        }

        return call_user_func([$this->model_class, 'from_row'], $row);
    }

    /**
     * Busca todos com filtros e paginação
     *
     * @param array $args Argumentos de busca
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

        // Monta WHERE
        $where = $this->prepare_where($args);

        // Monta ORDER BY
        $orderby = $this->prepare_order($args['orderby'], $args['order']);

        // Monta LIMIT
        $offset = ($args['page'] - 1) * $args['per_page'];
        $limit = $this->wpdb->prepare('LIMIT %d, %d', $offset, $args['per_page']);

        // Query
        $sql = "SELECT * FROM {$this->table_name} {$where} {$orderby} {$limit}";

        $rows = $this->wpdb->get_results($sql);

        return array_map(function($row) {
            return call_user_func([$this->model_class, 'from_row'], $row);
        }, $rows);
    }

    /**
     * Conta registros com filtros
     *
     * @param array $args
     * @return int
     */
    public function count($args = []) {
        $where = $this->prepare_where($args);
        $sql = "SELECT COUNT(*) FROM {$this->table_name} {$where}";
        return (int) $this->wpdb->get_var($sql);
    }

    /**
     * Insere novo registro
     *
     * @param mixed $model
     * @return int|false ID inserido ou false
     */
    public function insert($model) {
        $data = $model->to_array();

        $result = $this->wpdb->insert(
            $this->table_name,
            $data
        );

        if ($result) {
            $model->set_id($this->wpdb->insert_id);
            return $this->wpdb->insert_id;
        }

        return false;
    }

    /**
     * Atualiza registro
     *
     * @param mixed $model
     * @return bool
     */
    public function update($model) {
        $data = $model->to_array();
        $id = $model->get_id();

        // Remove created_at se estiver no array
        unset($data['created_at']);

        $result = $this->wpdb->update(
            $this->table_name,
            $data,
            ['id' => $id]
        );

        return $result !== false;
    }

    /**
     * Deleta registro
     *
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        $result = $this->wpdb->delete(
            $this->table_name,
            ['id' => $id],
            ['%d']
        );

        return $result !== false;
    }

    /**
     * Prepara cláusula WHERE baseada em argumentos
     *
     * @param array $args
     * @return string
     */
    protected function prepare_where($args) {
        $conditions = [];

        // Implementado em classes filhas conforme necessário

        if (empty($conditions)) {
            return '';
        }

        return 'WHERE ' . implode(' AND ', $conditions);
    }

    /**
     * Prepara cláusula ORDER BY
     *
     * @param string $orderby
     * @param string $order
     * @return string
     */
    protected function prepare_order($orderby, $order) {
        $orderby = sanitize_key($orderby);
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        return "ORDER BY {$orderby} {$order}";
    }

    /**
     * Verifica se registro existe
     *
     * @param int $id
     * @return bool
     */
    public function exists($id) {
        $sql = $this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE id = %d",
            $id
        );

        return (int) $this->wpdb->get_var($sql) > 0;
    }
}
