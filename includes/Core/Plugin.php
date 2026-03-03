<?php
/**
 * Classe principal do plugin (Singleton)
 *
 * @package NewsmastCurator
 * @subpackage Core
 */

namespace NewsmastCurator\Core;

class Plugin {
    /**
     * Instância única do plugin
     *
     * @var Plugin|null
     */
    private static $instance = null;

    /**
     * Versão do plugin
     *
     * @var string
     */
    private $version;

    /**
     * Instância do Database
     *
     * @var Database
     */
    private $database;

    /**
     * Serviços registrados
     *
     * @var array
     */
    private $services = [];

    /**
     * Flag de inicialização
     *
     * @var bool
     */
    private $initialized = false;

    /**
     * Obtém instância única
     *
     * @return Plugin
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Construtor privado
     */
    private function __construct() {
        $this->version = NC_VERSION;
        $this->database = new Database();
    }

    /**
     * Executa o plugin
     *
     * @return void
     */
    public function run() {
        if ($this->initialized) {
            return;
        }

        $this->initialized = true;

        // Verifica se precisa atualizar banco
        if ($this->database->needs_upgrade()) {
            $this->database->install();
        }

        // Adiciona intervalos customizados de cron
        add_filter('cron_schedules', ['\NewsmastCurator\Core\Activator', 'add_cron_intervals']);

        // Registra hooks
        $this->register_hooks();

        // Inicializa serviços
        $this->init_services();

        // Se estiver no admin, carrega interface administrativa
        if (is_admin()) {
            $this->init_admin();
        }

        // Registra REST API
        $this->init_rest_api();

        // Registra cron hooks
        $this->init_cron_hooks();

        // Registra endpoint externo de cron (independente de WP-Cron)
        $this->init_external_cron();

        // Hook de inicialização completa
        do_action('nc_loaded', $this);
    }

    /**
     * Registra hooks globais
     *
     * @return void
     */
    private function register_hooks() {
        // Links de ação na lista de plugins
        add_filter(
            'plugin_action_links_' . NC_PLUGIN_BASENAME,
            [$this, 'add_action_links']
        );

        // Link de configurações na lista de plugins
        add_filter(
            'plugin_row_meta',
            [$this, 'add_row_meta_links'],
            10,
            2
        );
    }

    /**
     * Inicializa serviços
     *
     * @return void
     */
    private function init_services() {
        // Logger
        $this->services['logger'] = new \NewsmastCurator\Services\Logger_Service($this->database);

        // Collection Service (será inicializado sob demanda)
        // Scheduler Service (será inicializado sob demanda)
        // Mastodon Service (será inicializado sob demanda)
    }

    /**
     * Inicializa interface administrativa
     *
     * @return void
     */
    private function init_admin() {
        $admin = new \NewsmastCurator\Admin\Admin_Controller($this->version);
        $admin->init();
    }

    /**
     * Registra endpoints da REST API
     *
     * @return void
     */
    private function init_rest_api() {
        add_action('rest_api_init', function() {
            // Sources Controller
            $sources = new \NewsmastCurator\API\Sources_Controller($this->database);
            $sources->register_routes();

            // Items Controller
            $items = new \NewsmastCurator\API\Items_Controller($this->database);
            $items->register_routes();

            // Publications Controller
            $publications = new \NewsmastCurator\API\Publications_Controller($this->database);
            $publications->register_routes();

            // Settings Controller
            $settings = new \NewsmastCurator\API\Settings_Controller();
            $settings->register_routes();

            // Logs Controller
            $logs = new \NewsmastCurator\API\Logs_Controller($this->database);
            $logs->register_routes();

            // Collections Controller
            $collections = new \NewsmastCurator\API\Collections_Controller($this->database);
            $collections->register_routes();

            // Public API Controller
            $public_api = new \NewsmastCurator\API\Public_API_Controller($this->database);
            $public_api->register_routes();
        });
    }

    /**
     * Registra hooks de cron
     *
     * @return void
     */
    private function init_cron_hooks() {
        // Hook de coleta de fontes
        add_action('nc_collect_sources', function() {
            $collection_service = new \NewsmastCurator\Services\Collection_Service($this->database);
            $collection_service->collect_all_sources();
        });

        // Hook de processamento de publicações
        add_action('nc_process_publications', function() {
            $scheduler_service = new \NewsmastCurator\Services\Scheduler_Service($this->database);
            $scheduler_service->process_scheduled_publications();
        });

        // Hook de limpeza de logs
        add_action('nc_cleanup_logs', function() {
            $logger = $this->get_service('logger');
            if ($logger) {
                $retention_days = get_option('nc_log_retention_days', 30);
                $logger->clear_old_logs($retention_days);
            }
        });
    }

    /**
     * Registra endpoint externo de cron
     *
     * Permite disparar o processamento via URL direta, sem depender do WP-Cron.
     * Uso: wget -q -O /dev/null "https://seusite.com/?nc_cron=1&token=SEU_TOKEN"
     *
     * O token é gerado automaticamente na ativação e armazenado em nc_cron_secret.
     */
    private function init_external_cron() {
        add_action('init', function() {
            if (!isset($_GET['nc_cron'])) {
                return;
            }

            $secret = get_option('nc_cron_secret', '');
            if (empty($secret)) {
                // Generate secret on first use
                $secret = wp_generate_password(32, false);
                update_option('nc_cron_secret', $secret, false);
            }

            $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';
            if (!hash_equals($secret, $token)) {
                status_header(403);
                echo 'Forbidden';
                exit;
            }

            // Disable WP-Cron on this request to avoid double execution
            if (!defined('DOING_CRON')) {
                define('DOING_CRON', true);
            }

            $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'all';

            $results = [];

            if ($action === 'all' || $action === 'publications') {
                $scheduler = new \NewsmastCurator\Services\Scheduler_Service($this->database);
                $scheduler->process_scheduled_publications();
                $results[] = 'publications processed';
            }

            if ($action === 'all' || $action === 'collect') {
                $collection_service = new \NewsmastCurator\Services\Collection_Service($this->database);
                $collection_service->collect_all_sources();
                $results[] = 'sources collected';
            }

            if ($action === 'all' || $action === 'cleanup') {
                $logger = $this->get_service('logger');
                if ($logger) {
                    $retention_days = get_option('nc_log_retention_days', 30);
                    $logger->clear_old_logs($retention_days);
                    $results[] = 'logs cleaned';
                }
            }

            header('Content-Type: text/plain');
            echo 'OK: ' . implode(', ', $results);
            exit;
        });
    }

    /**
     * Adiciona links de ação na lista de plugins
     *
     * @param array $links Links existentes
     * @return array Links atualizados
     */
    public function add_action_links($links) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=newsmast-curator'),
            __('Configurações', 'newsmast-curator')
        );

        array_unshift($links, $settings_link);

        return $links;
    }

    /**
     * Adiciona links na meta da linha do plugin
     *
     * @param array $links Links existentes
     * @param string $file Arquivo do plugin
     * @return array Links atualizados
     */
    public function add_row_meta_links($links, $file) {
        if ($file === NC_PLUGIN_BASENAME) {
            $links[] = sprintf(
                '<a href="%s" target="_blank">%s</a>',
                'https://github.com/ibram/newsmast-curator',
                __('Documentação', 'newsmast-curator')
            );

            $links[] = sprintf(
                '<a href="%s" target="_blank">%s</a>',
                'https://github.com/ibram/newsmast-curator/issues',
                __('Suporte', 'newsmast-curator')
            );
        }

        return $links;
    }

    /**
     * Obtém instância do Database
     *
     * @return Database
     */
    public function get_database() {
        return $this->database;
    }

    /**
     * Obtém um serviço registrado
     *
     * @param string $name Nome do serviço
     * @return mixed|null Serviço ou null se não encontrado
     */
    public function get_service($name) {
        return $this->services[$name] ?? null;
    }

    /**
     * Registra um serviço
     *
     * @param string $name Nome do serviço
     * @param mixed $service Instância do serviço
     * @return void
     */
    public function register_service($name, $service) {
        $this->services[$name] = $service;
    }

    /**
     * Obtém versão do plugin
     *
     * @return string
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Previne clonagem
     */
    private function __clone() {}

    /**
     * Previne desserialização
     */
    public function __wakeup() {
        throw new \Exception('Cannot unserialize singleton');
    }
}
