<?php
/**
 * Repository de Contas Mastodon
 *
 * @package NewsmastCurator
 * @subpackage Repositories
 */

namespace NewsmastCurator\Repositories;

use NewsmastCurator\Core\Database;
use NewsmastCurator\Models\Mastodon_Account;

class Mastodon_Account_Repository extends Base_Repository {
    public function __construct(Database $database) {
        parent::__construct($database);
        $this->table_name = $database->get_table_name('mastodon_accounts');
        $this->model_class = Mastodon_Account::class;
    }

    /**
     * Busca contas ativas
     */
    public function find_active() {
        $sql = "SELECT * FROM {$this->table_name} WHERE status = 'active' ORDER BY is_default DESC, name ASC";
        $rows = $this->wpdb->get_results($sql);
        return array_map([Mastodon_Account::class, 'from_row'], $rows);
    }

    /**
     * Busca a conta padrão
     */
    public function find_default() {
        $sql = "SELECT * FROM {$this->table_name} WHERE is_default = 1 AND status = 'active' LIMIT 1";
        $row = $this->wpdb->get_row($sql);
        return $row ? Mastodon_Account::from_row($row) : null;
    }

    /**
     * Define uma conta como padrão (remove padrão das outras)
     */
    public function set_default($id) {
        $this->wpdb->update($this->table_name, ['is_default' => 0], ['is_default' => 1]);
        return $this->wpdb->update($this->table_name, ['is_default' => 1], ['id' => $id]) !== false;
    }

    /**
     * Atualiza erro da conta
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
     * Limpa erro da conta
     */
    public function clear_error($id) {
        return $this->wpdb->update(
            $this->table_name,
            [
                'last_error' => null,
                'status' => 'active',
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $id]
        ) !== false;
    }

    protected function prepare_where($args) {
        $conditions = [];

        if (!empty($args['status'])) {
            $conditions[] = $this->wpdb->prepare('status = %s', $args['status']);
        }

        if (!empty($args['search'])) {
            $like = '%' . $this->wpdb->esc_like($args['search']) . '%';
            $conditions[] = $this->wpdb->prepare('(name LIKE %s OR instance_url LIKE %s)', $like, $like);
        }

        if (empty($conditions)) {
            return '';
        }

        return 'WHERE ' . implode(' AND ', $conditions);
    }
}
