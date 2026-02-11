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
            [$this, 'render_page'],
            'dashicons-megaphone',
            30
        );

        add_submenu_page('newsmast-curator', __('Dashboard', 'newsmast-curator'), __('Dashboard', 'newsmast-curator'), 'manage_nc_items', 'newsmast-curator', [$this, 'render_page']);
        add_submenu_page('newsmast-curator', __('Fontes', 'newsmast-curator'), __('Fontes', 'newsmast-curator'), 'manage_nc_sources', 'newsmast-curator-sources', [$this, 'render_page']);
        add_submenu_page('newsmast-curator', __('Curadoria', 'newsmast-curator'), __('Curadoria', 'newsmast-curator'), 'manage_nc_items', 'newsmast-curator-curation', [$this, 'render_page']);
        add_submenu_page('newsmast-curator', __('Fila', 'newsmast-curator'), __('Fila', 'newsmast-curator'), 'manage_nc_publications', 'newsmast-curator-queue', [$this, 'render_page']);
        add_submenu_page('newsmast-curator', __('Configurações', 'newsmast-curator'), __('Configurações', 'newsmast-curator'), 'manage_nc_settings', 'newsmast-curator-settings', [$this, 'render_page']);
        add_submenu_page('newsmast-curator', __('Sistema', 'newsmast-curator'), __('Sistema', 'newsmast-curator'), 'manage_nc_settings', 'newsmast-curator-system', [$this, 'render_page']);
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'newsmast-curator') === false) return;

        wp_enqueue_style('nc-admin', NC_ASSETS_URL . 'css/admin.css', [], $this->version);
        wp_enqueue_script('nc-admin', NC_ASSETS_URL . 'js/admin.js', ['jquery', 'wp-api-fetch'], $this->version, true);

        wp_localize_script('nc-admin', 'ncData', [
            'apiUrl' => esc_url_raw(rest_url('newsmast-curator/v1')),
            'nonce' => wp_create_nonce('wp_rest'),
            'currentPage' => isset($_GET['page']) ? sanitize_text_field($_GET['page']) : 'newsmast-curator',
        ]);
    }

    public function render_page() {
        $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : 'newsmast-curator';

        // Inicia o layout com sidebar
        $this->render_header();
        $this->render_sidebar($current_page);

        // Renderiza o conteúdo da página
        echo '<div class="nc-main-content">';

        switch ($current_page) {
            case 'newsmast-curator':
                include NC_PLUGIN_DIR . 'includes/Admin/views/dashboard.php';
                break;
            case 'newsmast-curator-sources':
                include NC_PLUGIN_DIR . 'includes/Admin/views/sources.php';
                break;
            case 'newsmast-curator-curation':
                include NC_PLUGIN_DIR . 'includes/Admin/views/curation.php';
                break;
            case 'newsmast-curator-queue':
                include NC_PLUGIN_DIR . 'includes/Admin/views/queue.php';
                break;
            case 'newsmast-curator-settings':
                include NC_PLUGIN_DIR . 'includes/Admin/views/settings.php';
                break;
            case 'newsmast-curator-system':
                include NC_PLUGIN_DIR . 'includes/Admin/views/system.php';
                break;
        }

        echo '</div>'; // .nc-main-content
        $this->render_footer();
    }

    private function render_header() {
        ?>
        <div class="nc-container">
        <?php
    }

    private function render_sidebar($current_page) {
        $menu_items = [
            'newsmast-curator' => [
                'icon' => 'dashicons-dashboard',
                'label' => __('Dashboard', 'newsmast-curator'),
                'description' => __('Visão geral', 'newsmast-curator'),
            ],
            'newsmast-curator-sources' => [
                'icon' => 'dashicons-admin-site-alt3',
                'label' => __('Fontes', 'newsmast-curator'),
                'description' => __('Gerenciar fontes de conteúdo', 'newsmast-curator'),
            ],
            'newsmast-curator-curation' => [
                'icon' => 'dashicons-yes-alt',
                'label' => __('Curadoria', 'newsmast-curator'),
                'description' => __('Aprovar itens coletados', 'newsmast-curator'),
            ],
            'newsmast-curator-queue' => [
                'icon' => 'dashicons-calendar-alt',
                'label' => __('Fila de Publicação', 'newsmast-curator'),
                'description' => __('Agendar publicações', 'newsmast-curator'),
            ],
            'newsmast-curator-settings' => [
                'icon' => 'dashicons-admin-settings',
                'label' => __('Configurações', 'newsmast-curator'),
                'description' => __('Configurar Mastodon', 'newsmast-curator'),
            ],
            'newsmast-curator-system' => [
                'icon' => 'dashicons-info',
                'label' => __('Sistema', 'newsmast-curator'),
                'description' => __('Logs e informações', 'newsmast-curator'),
            ],
        ];
        ?>
        <div class="nc-sidebar">
            <div class="nc-sidebar-header">
                <div class="nc-logo">
                    <span class="dashicons dashicons-megaphone"></span>
                    <h2>Newsmast Curator</h2>
                </div>
                <p class="nc-tagline"><?php _e('Curadoria e publicação automatizada', 'newsmast-curator'); ?></p>
            </div>

            <nav class="nc-sidebar-nav">
                <?php foreach ($menu_items as $page => $item): ?>
                    <a href="<?php echo admin_url('admin.php?page=' . $page); ?>"
                       class="nc-nav-item <?php echo $current_page === $page ? 'active' : ''; ?>">
                        <span class="dashicons <?php echo esc_attr($item['icon']); ?>"></span>
                        <div class="nc-nav-content">
                            <span class="nc-nav-label"><?php echo esc_html($item['label']); ?></span>
                            <span class="nc-nav-description"><?php echo esc_html($item['description']); ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="nc-sidebar-footer">
                <div class="nc-version">
                    <small><?php printf(__('Versão %s', 'newsmast-curator'), NC_VERSION); ?></small>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_footer() {
        ?>
        </div><!-- .nc-container -->
        <?php
    }
}
