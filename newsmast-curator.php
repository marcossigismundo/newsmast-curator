<?php
/**
 * Plugin Name: Newsmast Curator
 * Plugin URI: https://github.com/ibram/newsmast-curator
 * Description: Plugin para curadoria de conteúdo de múltiplas fontes e publicação automatizada no Newsmast/Mastodon
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: IBRAM
 * Author URI: https://www.gov.br/museus
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: newsmast-curator
 * Domain Path: /languages
 */

// Prevenir acesso direto
if (!defined('WPINC')) {
    die;
}

// Definir constantes do plugin
define('NC_VERSION', '1.0.0');
define('NC_PLUGIN_FILE', __FILE__);
define('NC_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('NC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NC_INCLUDES_DIR', NC_PLUGIN_DIR . 'includes/');
define('NC_ASSETS_URL', NC_PLUGIN_URL . 'assets/');

/**
 * Código executado durante a ativação do plugin
 */
function activate_newsmast_curator() {
    require_once NC_INCLUDES_DIR . 'Core/Activator.php';
    \NewsmastCurator\Core\Activator::activate();
}

/**
 * Código executado durante a desativação do plugin
 */
function deactivate_newsmast_curator() {
    require_once NC_INCLUDES_DIR . 'Core/Deactivator.php';
    \NewsmastCurator\Core\Deactivator::deactivate();
}

// Registrar hooks de ativação e desativação
register_activation_hook(__FILE__, 'activate_newsmast_curator');
register_deactivation_hook(__FILE__, 'deactivate_newsmast_curator');

/**
 * Autoloader PSR-4
 */
spl_autoload_register(function ($class) {
    // Namespace base do plugin
    $prefix = 'NewsmastCurator\\';

    // Verifica se a classe usa o namespace do plugin
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Obtém o nome relativo da classe
    $relative_class = substr($class, $len);

    // Substitui namespace separators por directory separators
    $file = NC_INCLUDES_DIR . str_replace('\\', '/', $relative_class) . '.php';

    // Se o arquivo existe, carrega-o
    if (file_exists($file)) {
        require $file;
    }
});

/**
 * Inicializa o plugin
 */
function run_newsmast_curator() {
    // Carrega traduções
    load_plugin_textdomain(
        'newsmast-curator',
        false,
        dirname(NC_PLUGIN_BASENAME) . '/languages'
    );

    // Obtém instância do plugin e executa
    $plugin = \NewsmastCurator\Core\Plugin::get_instance();
    $plugin->run();
}

// Inicia o plugin após WordPress carregar
add_action('plugins_loaded', 'run_newsmast_curator');
