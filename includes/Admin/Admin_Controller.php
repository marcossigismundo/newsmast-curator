<?php
namespace NewsmastCurator\Admin;

class Admin_Controller {
    private $version;

    public function __construct($version) {
        $this->version = $version;
    }

    public function init() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function add_admin_menu() {
        add_menu_page(
            __('Newsmast Curator', 'newsmast-curator'),
            __('Newsmast', 'newsmast-curator'),
            'manage_nc_items',
            'newsmast-curator',
            [$this, 'render_dashboard'],
            'dashicons-megaphone',
            30
        );

        add_submenu_page('newsmast-curator', __('Dashboard', 'newsmast-curator'), __('Dashboard', 'newsmast-curator'), 'manage_nc_items', 'newsmast-curator', [$this, 'render_dashboard']);
        add_submenu_page('newsmast-curator', __('Fontes', 'newsmast-curator'), __('Fontes', 'newsmast-curator'), 'manage_nc_sources', 'newsmast-curator-sources', [$this, 'render_sources']);
        add_submenu_page('newsmast-curator', __('Curadoria', 'newsmast-curator'), __('Curadoria', 'newsmast-curator'), 'manage_nc_items', 'newsmast-curator-curation', [$this, 'render_curation']);
        add_submenu_page('newsmast-curator', __('Fila', 'newsmast-curator'), __('Fila', 'newsmast-curator'), 'manage_nc_publications', 'newsmast-curator-queue', [$this, 'render_queue']);
        add_submenu_page('newsmast-curator', __('Configurações', 'newsmast-curator'), __('Configurações', 'newsmast-curator'), 'manage_nc_settings', 'newsmast-curator-settings', [$this, 'render_settings']);
        add_submenu_page('newsmast-curator', __('Sistema', 'newsmast-curator'), __('Sistema', 'newsmast-curator'), 'manage_nc_settings', 'newsmast-curator-system', [$this, 'render_system']);
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'newsmast-curator') === false) return;

        wp_enqueue_style('nc-admin', NC_ASSETS_URL . 'css/admin.css', [], $this->version);
        wp_enqueue_script('nc-admin', NC_ASSETS_URL . 'js/admin.js', ['jquery', 'wp-api-fetch'], $this->version, true);

        wp_localize_script('nc-admin', 'ncData', [
            'apiUrl' => rest_url('newsmast-curator/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);
    }

    public function render_dashboard() {
        include NC_PLUGIN_DIR . 'includes/Admin/views/dashboard.php';
    }

    public function render_sources() {
        include NC_PLUGIN_DIR . 'includes/Admin/views/sources.php';
    }

    public function render_curation() {
        include NC_PLUGIN_DIR . 'includes/Admin/views/curation.php';
    }

    public function render_queue() {
        include NC_PLUGIN_DIR . 'includes/Admin/views/queue.php';
    }

    public function render_settings() {
        include NC_PLUGIN_DIR . 'includes/Admin/views/settings.php';
    }

    public function render_system() {
        include NC_PLUGIN_DIR . 'includes/Admin/views/system.php';
    }
}
