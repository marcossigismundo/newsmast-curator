<?php
/**
 * Desinstalação do plugin
 *
 * @package NewsmastCurator
 */

// Verifica se é chamada de desinstalação
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

// Carrega autoloader e dependências
require_once plugin_dir_path(__FILE__) . 'newsmast-curator.php';

// Remove capabilities
require_once plugin_dir_path(__FILE__) . 'includes/Core/Deactivator.php';
\NewsmastCurator\Core\Deactivator::remove_capabilities();
\NewsmastCurator\Core\Deactivator::remove_options();

// Remove tabelas
require_once plugin_dir_path(__FILE__) . 'includes/Core/Database.php';
$database = new \NewsmastCurator\Core\Database();
$database->drop_tables();

// Limpa cache
wp_cache_flush();
