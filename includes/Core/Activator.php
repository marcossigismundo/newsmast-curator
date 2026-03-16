<?php
/**
 * Ativação do plugin
 *
 * @package NewsmastCurator
 * @subpackage Core
 */

namespace NewsmastCurator\Core;

class Activator {
    /**
     * Executa ações necessárias na ativação do plugin
     *
     * @return void
     */
    public static function activate() {
        // Verifica requisitos mínimos
        self::check_requirements();

        // Cria/atualiza tabelas do banco
        self::create_tables();

        // Cria opções padrão
        self::create_default_options();

        // Registra capabilities
        self::add_capabilities();

        // Agenda cron jobs
        self::schedule_cron_jobs();

        // Limpa rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Verifica requisitos mínimos
     *
     * @return void
     */
    private static function check_requirements() {
        global $wp_version;

        // Verifica versão do WordPress
        if (version_compare($wp_version, '5.8', '<')) {
            deactivate_plugins(NC_PLUGIN_BASENAME);
            wp_die(__('Este plugin requer WordPress 5.8 ou superior.', 'newsmast-curator'));
        }

        // Verifica versão do PHP
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            deactivate_plugins(NC_PLUGIN_BASENAME);
            wp_die(__('Este plugin requer PHP 7.4 ou superior.', 'newsmast-curator'));
        }

        // Verifica extensões necessárias
        $required_extensions = ['json', 'mbstring', 'curl'];
        foreach ($required_extensions as $extension) {
            if (!extension_loaded($extension)) {
                deactivate_plugins(NC_PLUGIN_BASENAME);
                wp_die(sprintf(
                    __('Este plugin requer a extensão PHP %s.', 'newsmast-curator'),
                    $extension
                ));
            }
        }
    }

    /**
     * Cria tabelas do banco de dados
     *
     * @return void
     */
    private static function create_tables() {
        $database = new Database();
        $database->install();
    }

    /**
     * Cria opções padrão do plugin
     *
     * @return void
     */
    private static function create_default_options() {
        $defaults = [
            // Configurações do Mastodon
            'nc_mastodon_instance' => '',
            'nc_mastodon_token' => '',
            'nc_mastodon_character_limit' => 500,

            // Template de post
            'nc_post_template' => "{title}\n\n{excerpt}\n\n{url}\n\n{hashtags}",
            'nc_default_hashtags' => '#museus #patrimônio #cultura',
            'nc_shorten_urls' => true,
            'nc_add_emoji' => true,

            // Configurações de coleta
            'nc_collection_frequency' => 'hourly',
            'nc_download_images' => true,
            'nc_notify_new_items' => false,
            'nc_min_content_length' => 50,
            'nc_max_content_length' => 5000,
            'nc_detect_duplicates' => true,

            // Configurações de publicação
            'nc_auto_publish' => false,
            'nc_publication_schedule' => wp_json_encode([
                '1' => ['10:00', '14:00', '18:00'], // Segunda
                '2' => ['10:00', '14:00', '18:00'], // Terça
                '3' => ['10:00', '14:00', '18:00'], // Quarta
                '4' => ['10:00', '14:00', '18:00'], // Quinta
                '5' => ['10:00', '14:00', '18:00'], // Sexta
                '6' => [],                          // Sábado
                '0' => [],                          // Domingo
            ]),
            'nc_max_retry_attempts' => 3,
            'nc_retry_interval' => 10, // minutos
            'nc_notify_on_failure' => true,

            // Sistema
            'nc_log_retention_days' => 30,
            'nc_first_activation' => current_time('mysql'),
        ];

        foreach ($defaults as $key => $value) {
            add_option($key, $value);
        }
    }

    /**
     * Adiciona capabilities personalizadas
     *
     * @return void
     */
    private static function add_capabilities() {
        $capabilities = [
            'manage_nc_sources',      // Gerenciar fontes
            'manage_nc_items',        // Ver e curar itens
            'manage_nc_publications', // Agendar publicações
            'manage_nc_settings',     // Configurações (apenas admin)
            'view_nc_logs',          // Ver logs
        ];

        // Adiciona para administrator
        $admin_role = get_role('administrator');
        if ($admin_role) {
            foreach ($capabilities as $cap) {
                $admin_role->add_cap($cap);
            }
        }

        // Adiciona para editor (exceto configurações)
        $editor_role = get_role('editor');
        if ($editor_role) {
            foreach ($capabilities as $cap) {
                if ($cap !== 'manage_nc_settings') {
                    $editor_role->add_cap($cap);
                }
            }
        }
    }

    /**
     * Agenda cron jobs
     *
     * @return void
     */
    private static function schedule_cron_jobs() {
        // Ensure custom intervals are registered before scheduling
        // (at activation time, Plugin::run() hasn't added the filter yet)
        add_filter('cron_schedules', [self::class, 'add_cron_intervals']);

        // Cron de coleta automática (verifica a cada 5 min quais fontes estão no horário)
        $next_collect = wp_next_scheduled('nc_collect_sources');
        if ($next_collect) {
            wp_unschedule_event($next_collect, 'nc_collect_sources');
        }
        wp_schedule_event(time(), 'every_5_minutes', 'nc_collect_sources');

        // Cron de processamento de publicações - reschedule to ensure it uses valid interval
        $next = wp_next_scheduled('nc_process_publications');
        if (!$next) {
            wp_schedule_event(time(), 'every_5_minutes', 'nc_process_publications');
        }

        // Cron de limpeza de logs
        if (!wp_next_scheduled('nc_cleanup_logs')) {
            wp_schedule_event(time(), 'daily', 'nc_cleanup_logs');
        }
    }

    /**
     * Adiciona intervalos personalizados de cron
     * Chamado via filter cron_schedules
     *
     * @param array $schedules Intervalos existentes
     * @return array Intervalos atualizados
     */
    public static function add_cron_intervals($schedules) {
        $schedules['every_5_minutes'] = [
            'interval' => 300,
            'display' => __('A cada 5 minutos', 'newsmast-curator')
        ];

        $schedules['every_15_minutes'] = [
            'interval' => 900,
            'display' => __('A cada 15 minutos', 'newsmast-curator')
        ];

        $schedules['every_30_minutes'] = [
            'interval' => 1800,
            'display' => __('A cada 30 minutos', 'newsmast-curator')
        ];

        $schedules['custom_6hours'] = [
            'interval' => 21600,
            'display' => __('A cada 6 horas', 'newsmast-curator')
        ];

        $schedules['every_12_hours'] = [
            'interval' => 43200,
            'display' => __('A cada 12 horas', 'newsmast-curator')
        ];

        return $schedules;
    }
}
