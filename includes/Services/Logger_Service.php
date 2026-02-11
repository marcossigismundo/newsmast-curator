<?php
namespace NewsmastCurator\Services;

use NewsmastCurator\Core\Database;

class Logger_Service {
    private $wpdb;
    private $table_name;

    public function __construct(Database $database) {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $database->get_table_name('logs');
    }

    public function info($message, $context = []) {
        return $this->log('system', 'info', $message, $context);
    }

    public function warning($message, $context = []) {
        return $this->log('system', 'warning', $message, $context);
    }

    public function error($message, $context = []) {
        return $this->log('system', 'error', $message, $context);
    }

    public function success($message, $context = []) {
        return $this->log('system', 'success', $message, $context);
    }

    public function log($type, $level, $message, $context = []) {
        $user_id = get_current_user_id();

        return $this->wpdb->insert(
            $this->table_name,
            [
                'type' => sanitize_text_field($type),
                'level' => sanitize_text_field($level),
                'message' => sanitize_text_field($message),
                'context' => wp_json_encode($context),
                'related_id' => $context['related_id'] ?? null,
                'related_type' => $context['related_type'] ?? null,
                'user_id' => $user_id ?: null,
                'created_at' => current_time('mysql'),
            ]
        );
    }

    public function get_logs($args = []) {
        $defaults = ['per_page' => 50, 'page' => 1, 'orderby' => 'created_at', 'order' => 'DESC'];
        $args = wp_parse_args($args, $defaults);

        $where = '';
        if (!empty($args['type'])) {
            $where .= $this->wpdb->prepare(' AND type = %s', $args['type']);
        }
        if (!empty($args['level'])) {
            $where .= $this->wpdb->prepare(' AND level = %s', $args['level']);
        }

        $offset = ($args['page'] - 1) * $args['per_page'];
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE 1=1 {$where} ORDER BY {$args['orderby']} {$args['order']} LIMIT %d, %d",
            $offset,
            $args['per_page']
        );

        return $this->wpdb->get_results($sql);
    }

    public function clear_old_logs($days = 30) {
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return $this->wpdb->query(
            $this->wpdb->prepare("DELETE FROM {$this->table_name} WHERE created_at < %s", $date)
        );
    }
}
