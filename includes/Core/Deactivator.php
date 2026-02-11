<?php
/**
 * Desativação do plugin
 *
 * @package NewsmastCurator
 * @subpackage Core
 */

namespace NewsmastCurator\Core;

class Deactivator {
    /**
     * Executa ações necessárias na desativação do plugin
     *
     * @return void
     */
    public static function deactivate() {
        // Remove cron jobs agendados
        self::unschedule_cron_jobs();

        // Limpa cache
        self::clear_cache();

        // Limpa rewrite rules
        flush_rewrite_rules();

        // Nota: Não remove capabilities nem tabelas
        // Isso é feito apenas no uninstall.php
    }

    /**
     * Remove cron jobs agendados
     *
     * @return void
     */
    private static function unschedule_cron_jobs() {
        $cron_hooks = [
            'nc_collect_sources',
            'nc_process_publications',
            'nc_cleanup_logs',
        ];

        foreach ($cron_hooks as $hook) {
            $timestamp = wp_next_scheduled($hook);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $hook);
            }
        }
    }

    /**
     * Limpa cache e transients
     *
     * @return void
     */
    private static function clear_cache() {
        global $wpdb;

        // Remove transients do plugin
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_nc_%'
             OR option_name LIKE '_transient_timeout_nc_%'"
        );

        // Limpa cache de objetos se houver
        wp_cache_flush();
    }

    /**
     * Remove capabilities personalizadas
     * Usado apenas no uninstall.php
     *
     * @return void
     */
    public static function remove_capabilities() {
        $capabilities = [
            'manage_nc_sources',
            'manage_nc_items',
            'manage_nc_publications',
            'manage_nc_settings',
            'view_nc_logs',
        ];

        // Remove de administrator
        $admin_role = get_role('administrator');
        if ($admin_role) {
            foreach ($capabilities as $cap) {
                $admin_role->remove_cap($cap);
            }
        }

        // Remove de editor
        $editor_role = get_role('editor');
        if ($editor_role) {
            foreach ($capabilities as $cap) {
                $editor_role->remove_cap($cap);
            }
        }
    }

    /**
     * Remove opções do plugin
     * Usado apenas no uninstall.php
     *
     * @return void
     */
    public static function remove_options() {
        global $wpdb;

        // Remove todas as opções do plugin
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE 'nc_%'"
        );
    }
}
