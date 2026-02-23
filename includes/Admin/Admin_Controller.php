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

        // Hidden pages: accessible via plugin sidebar, not shown in WP admin submenu
        add_submenu_page(null, __('Fontes', 'newsmast-curator'), '', 'manage_nc_sources', 'newsmast-curator-sources', [$this, 'render_page']);
        add_submenu_page(null, __('Curadoria', 'newsmast-curator'), '', 'manage_nc_items', 'newsmast-curator-curation', [$this, 'render_page']);
        add_submenu_page(null, __('Fila', 'newsmast-curator'), '', 'manage_nc_publications', 'newsmast-curator-queue', [$this, 'render_page']);
        add_submenu_page(null, __('Configurações', 'newsmast-curator'), '', 'manage_nc_settings', 'newsmast-curator-settings', [$this, 'render_page']);
        add_submenu_page(null, __('Coleções', 'newsmast-curator'), '', 'manage_nc_items', 'newsmast-curator-collections', [$this, 'render_page']);
        add_submenu_page(null, __('Sistema', 'newsmast-curator'), '', 'manage_nc_settings', 'newsmast-curator-system', [$this, 'render_page']);

        // Remove auto-generated submenu for the main page
        remove_submenu_page('newsmast-curator', 'newsmast-curator');
    }

    public function enqueue_assets($hook) {
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        if (strpos($page, 'newsmast-curator') === false) return;

        wp_enqueue_style('nc-admin', NC_ASSETS_URL . 'css/admin.css', [], $this->version);
        wp_enqueue_script('nc-admin', NC_ASSETS_URL . 'js/admin.js', ['jquery', 'wp-api-fetch'], $this->version, false);

        wp_localize_script('nc-admin', 'ncData', [
            'apiUrl' => '/newsmast-curator/v1',
            'nonce' => wp_create_nonce('wp_rest'),
            'currentPage' => $page,
            'i18n' => [
                // General
                'loading' => __('Carregando...', 'newsmast-curator'),
                'cancel' => __('Cancelar', 'newsmast-curator'),
                'add' => __('Adicionar', 'newsmast-curator'),
                'select' => __('Selecionar', 'newsmast-curator'),
                'options' => __('Opções', 'newsmast-curator'),
                'characters' => __('caracteres', 'newsmast-curator'),
                'datetime' => __('Data e Hora', 'newsmast-curator'),
                'from' => __('De', 'newsmast-curator'),
                'until' => __('até', 'newsmast-curator'),
                'items' => __('itens', 'newsmast-curator'),
                'untitled' => __('Sem título', 'newsmast-curator'),
                'image' => __('Imagem', 'newsmast-curator'),
                'title' => __('Título', 'newsmast-curator'),
                'source_author' => __('Fonte', 'newsmast-curator'),
                'date' => __('Data', 'newsmast-curator'),
                'actions' => __('Ações', 'newsmast-curator'),
                'description' => __('Descrição', 'newsmast-curator'),

                // Sources
                'collecting' => __('Iniciando coleta...', 'newsmast-curator'),
                'items_collected' => __('itens coletados!', 'newsmast-curator'),
                'collection_error' => __('Erro na coleta: ', 'newsmast-curator'),
                'confirm_collect_all' => __('Iniciar coleta de todas as fontes ativas?', 'newsmast-curator'),
                'collecting_all' => __('Coletando de todas as fontes...', 'newsmast-curator'),
                'no_active_sources' => __('Nenhuma fonte ativa encontrada', 'newsmast-curator'),
                'items_collected_from' => __('itens coletados de ', 'newsmast-curator'),
                'sources' => __('fontes!', 'newsmast-curator'),
                'collection_error_some' => __('Erro ao coletar de algumas fontes', 'newsmast-curator'),
                'sources_load_error' => __('Erro ao carregar fontes: ', 'newsmast-curator'),

                // Curation
                'item_approved' => __('Item aprovado!', 'newsmast-curator'),
                'approve_error' => __('Erro ao aprovar: ', 'newsmast-curator'),
                'select_at_least_one' => __('Selecione ao menos um item', 'newsmast-curator'),
                'confirm_approve' => __('Aprovar', 'newsmast-curator'),
                'selected_items' => __('itens selecionados?', 'newsmast-curator'),
                'items_approved' => __('itens aprovados!', 'newsmast-curator'),
                'approve_items_error' => __('Erro ao aprovar itens', 'newsmast-curator'),
                'approve' => __('Aprovar', 'newsmast-curator'),
                'schedule' => __('Agendar', 'newsmast-curator'),
                'view_original' => __('Ver Original', 'newsmast-curator'),
                'no_new_items' => __('Nenhum item novo para curadoria. Execute uma coleta para trazer novos itens.', 'newsmast-curator'),
                'no_approved_items_yet' => __('Nenhum item aprovado ainda. Aprove itens na aba "Novos".', 'newsmast-curator'),
                'load_items_error' => __('Erro ao carregar itens', 'newsmast-curator'),

                // Schedule
                'schedule_publication' => __('Agendar Publicação', 'newsmast-curator'),
                'approved_item' => __('Item aprovado', 'newsmast-curator'),
                'loading_approved' => __('Carregando itens aprovados...', 'newsmast-curator'),
                'select_approved_item' => __('Selecione um item aprovado na curadoria', 'newsmast-curator'),
                'select_approved' => __('Selecione um item aprovado...', 'newsmast-curator'),
                'no_approved_items' => __('Nenhum item aprovado encontrado', 'newsmast-curator'),
                'post_content' => __('Conteúdo do Post', 'newsmast-curator'),
                'content_auto_fill' => __('O conteúdo será preenchido automaticamente ao selecionar um item...', 'newsmast-curator'),
                'edit_mastodon_text' => __('Edite o texto que será publicado no Mastodon', 'newsmast-curator'),
                'include_image' => __('Incluir imagem (se disponível)', 'newsmast-curator'),
                'include_images' => __('Incluir imagens (quando disponíveis)', 'newsmast-curator'),
                'content_too_long' => __('O conteúdo excede 500 caracteres. Reduza o texto antes de agendar.', 'newsmast-curator'),
                'publication_scheduled' => __('Publicação agendada com sucesso!', 'newsmast-curator'),
                'schedule_error' => __('Erro ao agendar: ', 'newsmast-curator'),

                // Bulk schedule
                'select_at_least_one_schedule' => __('Selecione ao menos um item para agendar', 'newsmast-curator'),
                'bulk_schedule' => __('Agendar em Lote', 'newsmast-curator'),
                'items_will_be_scheduled' => __('itens selecionados serão agendados automaticamente com o template configurado.', 'newsmast-curator'),
                'first_publication' => __('Primeira publicação', 'newsmast-curator'),
                'first_pub_help' => __('Data/hora da primeira publicação', 'newsmast-curator'),
                'interval_between' => __('Intervalo entre posts', 'newsmast-curator'),
                'interval_help' => __('Tempo entre cada publicação', 'newsmast-curator'),
                'every_30min' => __('A cada 30 minutos', 'newsmast-curator'),
                'every_1h' => __('A cada 1 hora', 'newsmast-curator'),
                'every_2h' => __('A cada 2 horas', 'newsmast-curator'),
                'every_6h' => __('A cada 6 horas', 'newsmast-curator'),
                'every_12h' => __('A cada 12 horas', 'newsmast-curator'),
                'every_24h' => __('A cada 24 horas', 'newsmast-curator'),
                'expected_timeline' => __('Cronograma previsto', 'newsmast-curator'),
                'configure_date_interval' => __('Configure a data e o intervalo', 'newsmast-curator'),
                'schedule_all' => __('Agendar Todos', 'newsmast-curator'),
                'configure_first_date' => __('Configure a data da primeira publicação', 'newsmast-curator'),
                'date_must_be_future' => __('A data da primeira publicação deve ser no futuro', 'newsmast-curator'),
                'scheduling' => __('Agendando', 'newsmast-curator'),
                'publications' => __('publicações...', 'newsmast-curator'),
                'publications_scheduled' => __('publicações agendadas com sucesso!', 'newsmast-curator'),

                // Publications
                'confirm_cancel_pub' => __('Cancelar esta publicação agendada?', 'newsmast-curator'),
                'publication_cancelled' => __('Publicação cancelada!', 'newsmast-curator'),
                'cancel_error' => __('Erro ao cancelar: ', 'newsmast-curator'),
                'confirm_reschedule' => __('Reagendar esta publicação?', 'newsmast-curator'),
                'retry_info' => __('A publicação será reprocessada automaticamente na próxima execução do cron.', 'newsmast-curator'),

                // Settings
                'testing_connection' => __('Testando conexão...', 'newsmast-curator'),
                'connected_as' => __('Conectado como', 'newsmast-curator'),
                'connection_failed' => __('Falha na conexão: ', 'newsmast-curator'),
                'test_error' => __('Erro ao testar: ', 'newsmast-curator'),
                'settings_saved' => __('Configurações salvas!', 'newsmast-curator'),
                'save_error' => __('Erro ao salvar: ', 'newsmast-curator'),

                // Collections
                'add_to_collection' => __('Adicionar à Coleção', 'newsmast-curator'),
                'items_selected' => __('itens selecionados', 'newsmast-curator'),
                'select_collection' => __('Selecionar Coleção', 'newsmast-curator'),
                'select_collection_option' => __('-- Selecione --', 'newsmast-curator'),
                'create_new_collection' => __('+ Criar nova coleção', 'newsmast-curator'),
                'collection_name' => __('Nome da Coleção', 'newsmast-curator'),
                'collection_name_placeholder' => __('Ex: Semana dos Museus 2026', 'newsmast-curator'),
                'select_a_collection' => __('Selecione uma coleção', 'newsmast-curator'),
                'collection_name_required' => __('Nome da coleção é obrigatório', 'newsmast-curator'),
                'create_collection_error' => __('Erro ao criar coleção: ', 'newsmast-curator'),
                'items_added_to_collection' => __('itens adicionados à coleção!', 'newsmast-curator'),
                'add_to_collection_error' => __('Erro ao adicionar: ', 'newsmast-curator'),
                'new_collection' => __('Nova Coleção', 'newsmast-curator'),
                'create' => __('Criar', 'newsmast-curator'),
                'edit' => __('Editar', 'newsmast-curator'),
                'delete' => __('Excluir', 'newsmast-curator'),
                'confirm_delete_collection' => __('Excluir esta coleção? Os itens não serão removidos.', 'newsmast-curator'),
                'collection_deleted' => __('Coleção excluída!', 'newsmast-curator'),
                'delete_error' => __('Erro ao excluir: ', 'newsmast-curator'),
                'schedule_collection' => __('Agendar Coleção', 'newsmast-curator'),
                'collection_scheduled' => __('Coleção agendada!', 'newsmast-curator'),
                'collection_no_items' => __('Adicione itens à coleção antes de agendar', 'newsmast-curator'),
                'items_label' => __('itens', 'newsmast-curator'),
                'published_of' => __('publicados de', 'newsmast-curator'),
                'no_collections' => __('Nenhuma coleção encontrada. Crie sua primeira coleção!', 'newsmast-curator'),
                'add_items' => __('Adicionar Itens', 'newsmast-curator'),
                'collection_items' => __('Itens da Coleção', 'newsmast-curator'),
                'remove' => __('Remover', 'newsmast-curator'),
                'item_removed' => __('Item removido!', 'newsmast-curator'),
                'save' => __('Salvar', 'newsmast-curator'),
                'collection_saved' => __('Coleção salva!', 'newsmast-curator'),
                'color' => __('Cor', 'newsmast-curator'),
                'confirm_remove_item' => __('Remover este item da coleção?', 'newsmast-curator'),
            ],
        ]);
    }

    public function render_page() {
        $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : 'newsmast-curator';

        $this->render_header();
        $this->render_sidebar($current_page);

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
            case 'newsmast-curator-collections':
                include NC_PLUGIN_DIR . 'includes/Admin/views/collections.php';
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

        echo '</div>';
        $this->render_footer();
    }

    private function render_header() {
        ?>
        <div class="nc-container">
            <button class="nc-sidebar-toggle" onclick="NC.toggleSidebar()" aria-label="<?php esc_attr_e('Menu', 'newsmast-curator'); ?>">
                <span class="dashicons dashicons-menu"></span>
            </button>
            <div class="nc-sidebar-overlay" onclick="NC.toggleSidebar()"></div>
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
            'newsmast-curator-collections' => [
                'icon' => 'dashicons-portfolio',
                'label' => __('Coleções', 'newsmast-curator'),
                'description' => __('Agrupar itens em coleções', 'newsmast-curator'),
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
