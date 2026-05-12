<?php
/**
 * Repository de Publicações
 *
 * @package NewsmastCurator
 * @subpackage Repositories
 */

namespace NewsmastCurator\Repositories;

use NewsmastCurator\Core\Database;
use NewsmastCurator\Models\Publication;

class Publication_Repository extends Base_Repository {
    /**
     * Construtor
     *
     * @param Database $database
     */
    public function __construct(Database $database) {
        parent::__construct($database);
        $this->table_name = $database->get_table_name('publications');
        $this->model_class = Publication::class;
    }

    /**
     * Busca publicações agendadas prontas para processar
     *
     * @param int $limit
     * @return array
     */
    public function find_pending_for_processing($limit = 5) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name}
             WHERE status = 'scheduled' AND scheduled_for <= %s
             ORDER BY scheduled_for ASC
             LIMIT %d",
            current_time('mysql'),
            $limit
        );

        $rows = $this->wpdb->get_results($sql);

        return array_map([Publication::class, 'from_row'], $rows);
    }

    /**
     * Recupera publicações travadas em 'processing' por mais de X minutos
     *
     * @param int $minutes_threshold
     * @return int Número de publicações recuperadas
     */
    public function recover_stuck_processing($minutes_threshold = 5) {
        $threshold_time = date('Y-m-d H:i:s', current_time('timestamp') - ($minutes_threshold * 60));

        return $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE {$this->table_name}
                 SET status = 'scheduled', scheduled_for = %s, updated_at = %s
                 WHERE status = 'processing' AND updated_at < %s",
                current_time('mysql'),
                current_time('mysql'),
                $threshold_time
            )
        );
    }

    /**
     * Conta publicações agendadas cuja hora já passou (vencidas / overdue).
     * Usado para diagnóstico: se > 0, o cron não está executando.
     *
     * @return int
     */
    public function count_overdue_scheduled() {
        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name}
                 WHERE status = 'scheduled' AND scheduled_for <= %s",
                current_time('mysql')
            )
        );
    }

    /**
     * Conta publicações por status para diagnóstico
     *
     * @return array
     */
    public function count_by_status() {
        $sql = "SELECT status, COUNT(*) as cnt FROM {$this->table_name} GROUP BY status";
        $rows = $this->wpdb->get_results($sql);
        $result = [];
        foreach ($rows as $row) {
            $result[$row->status] = (int) $row->cnt;
        }
        return $result;
    }

    /**
     * Busca publicações por status
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
     * Busca publicações agendadas
     *
     * @param array $args
     * @return array
     */
    public function find_scheduled($args = []) {
        return $this->find_by_status('scheduled', $args);
    }

    /**
     * Busca publicações publicadas
     *
     * @param array $args
     * @return array
     */
    public function find_published($args = []) {
        return $this->find_by_status('published', $args);
    }

    /**
     * Busca publicações que falharam
     *
     * @param array $args
     * @return array
     */
    public function find_failed($args = []) {
        return $this->find_by_status('failed', $args);
    }

    /**
     * Atualiza status da publicação
     *
     * @param int $id
     * @param string $status
     * @return bool
     */
    public function update_status($id, $status) {
        return $this->wpdb->update(
            $this->table_name,
            [
                'status' => $status,
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $id]
        ) !== false;
    }

    /**
     * Obtém próxima data agendada disponível
     *
     * @return string|null
     */
    public function get_next_scheduled_date() {
        $sql = "SELECT MIN(scheduled_for) FROM {$this->table_name}
                WHERE status = 'scheduled' AND scheduled_for > NOW()";

        return $this->wpdb->get_var($sql);
    }

    /**
     * Obtém publicações para calendário
     *
     * @param string $month
     * @param string $year
     * @return array
     */
    public function get_calendar_data($month, $year) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name}
             WHERE MONTH(scheduled_for) = %d
             AND YEAR(scheduled_for) = %d
             ORDER BY scheduled_for ASC",
            $month,
            $year
        );

        $rows = $this->wpdb->get_results($sql);

        $publications = array_map([Publication::class, 'from_row'], $rows);

        // Agrupa por dia
        $calendar = [];
        foreach ($publications as $pub) {
            $date = date('Y-m-d', strtotime($pub->get_scheduled_for()));

            if (!isset($calendar[$date])) {
                $calendar[$date] = [];
            }

            $calendar[$date][] = $pub;
        }

        return $calendar;
    }

    /**
     * Obtém estatísticas de publicação
     *
     * @return array
     */
    public function get_stats() {
        $sql = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
                    SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing
                FROM {$this->table_name}";

        return (array) $this->wpdb->get_row($sql, ARRAY_A);
    }

    /**
     * Busca publicações recentes
     *
     * @param int $limit
     * @return array
     */
    public function find_recent($limit = 10) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name}
             ORDER BY created_at DESC
             LIMIT %d",
            $limit
        );

        $rows = $this->wpdb->get_results($sql);

        return array_map([Publication::class, 'from_row'], $rows);
    }

    /**
     * Verifica se item já tem publicação agendada
     *
     * @param int $item_id
     * @return bool
     */
    public function has_scheduled_publication($item_id) {
        $sql = $this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name}
             WHERE item_id = %d AND status IN ('scheduled', 'processing')",
            $item_id
        );

        return (int) $this->wpdb->get_var($sql) > 0;
    }

    /**
     * Busca o último post publicado na thread antes de uma posição
     * Usado pelo scheduler para encadear replies
     *
     * @param string $thread_id
     * @param int $before_position
     * @return Publication|null
     */
    public function find_last_published_in_thread($thread_id, $before_position) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name}
             WHERE thread_id = %s AND thread_position < %d AND status = 'published'
             ORDER BY thread_position DESC
             LIMIT 1",
            $thread_id,
            $before_position
        );

        $row = $this->wpdb->get_row($sql);
        return $row ? Publication::from_row($row) : null;
    }

    /**
     * Busca todas as publicações de uma thread
     *
     * @param string $thread_id
     * @return array
     */
    public function find_by_thread($thread_id) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name}
             WHERE thread_id = %s
             ORDER BY thread_position ASC",
            $thread_id
        );

        $rows = $this->wpdb->get_results($sql);
        return array_map([Publication::class, 'from_row'], $rows);
    }

    /**
     * Verifica se todas as publicações anteriores na thread foram publicadas
     *
     * @param string $thread_id
     * @param int $position
     * @return bool
     */
    public function are_previous_thread_items_published($thread_id, $position) {
        if ($position <= 0) {
            return true;
        }

        $sql = $this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name}
             WHERE thread_id = %s AND thread_position < %d AND status != 'published'",
            $thread_id,
            $position
        );

        return (int) $this->wpdb->get_var($sql) === 0;
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

        if (isset($args['item_id'])) {
            $conditions[] = $this->wpdb->prepare('item_id = %d', $args['item_id']);
        }

        if (!empty($args['date_from'])) {
            $conditions[] = $this->wpdb->prepare(
                'scheduled_for >= %s',
                $args['date_from']
            );
        }

        if (!empty($args['date_to'])) {
            $conditions[] = $this->wpdb->prepare(
                'scheduled_for <= %s',
                $args['date_to']
            );
        }

        if (empty($conditions)) {
            return '';
        }

        return 'WHERE ' . implode(' AND ', $conditions);
    }
}
