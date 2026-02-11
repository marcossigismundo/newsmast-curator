<div class="nc-page-header">
    <div class="nc-page-title">
        <h1>
            <span class="dashicons dashicons-info"></span>
            <?php _e('Sistema e Logs', 'newsmast-curator'); ?>
        </h1>
    </div>
    <p class="nc-page-description"><?php _e('Informações do sistema e registro de atividades', 'newsmast-curator'); ?></p>
</div>

<div class="nc-card">
    <div class="nc-card-header">
        <h2 class="nc-card-title">
            <span class="dashicons dashicons-admin-tools"></span>
            <?php _e('Informações do Sistema', 'newsmast-curator'); ?>
        </h2>
    </div>
    <div class="nc-card-body">
        <table class="nc-table">
            <tbody>
                <tr>
                    <td><strong><?php _e('Versão do Plugin', 'newsmast-curator'); ?></strong></td>
                    <td><?php echo NC_VERSION; ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('WordPress', 'newsmast-curator'); ?></strong></td>
                    <td><?php echo get_bloginfo('version'); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('PHP', 'newsmast-curator'); ?></strong></td>
                    <td><?php echo PHP_VERSION; ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Banco de Dados', 'newsmast-curator'); ?></strong></td>
                    <td><?php global $wpdb; echo $wpdb->db_version(); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="nc-card">
    <div class="nc-card-header">
        <h2 class="nc-card-title">
            <span class="dashicons dashicons-clock"></span>
            <?php _e('Cron Jobs', 'newsmast-curator'); ?>
        </h2>
    </div>
    <div class="nc-card-body">
        <table class="nc-table">
            <thead>
                <tr>
                    <th><?php _e('Job', 'newsmast-curator'); ?></th>
                    <th><?php _e('Próxima Execução', 'newsmast-curator'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $crons = [
                    'nc_collect_sources' => __('Coleta de Fontes', 'newsmast-curator'),
                    'nc_process_publications' => __('Processamento de Publicações', 'newsmast-curator'),
                    'nc_cleanup_logs' => __('Limpeza de Logs', 'newsmast-curator'),
                ];

                foreach ($crons as $hook => $label):
                    $timestamp = wp_next_scheduled($hook);
                    $next = $timestamp ? date('d/m/Y H:i:s', $timestamp) : __('Não agendado', 'newsmast-curator');
                ?>
                    <tr>
                        <td><strong><?php echo $label; ?></strong></td>
                        <td><?php echo $next; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="nc-card">
    <div class="nc-card-header">
        <h2 class="nc-card-title">
            <span class="dashicons dashicons-list-view"></span>
            <?php _e('Logs Recentes', 'newsmast-curator'); ?>
        </h2>
        <div class="nc-card-actions">
            <select id="nc-log-filter" class="nc-form-control" style="width:150px;">
                <option value=""><?php _e('Todos', 'newsmast-curator'); ?></option>
                <option value="info">Info</option>
                <option value="warning">Warning</option>
                <option value="error">Error</option>
                <option value="success">Success</option>
            </select>
        </div>
    </div>
    <div class="nc-card-body" id="nc-logs-list">
        <div class="nc-loading"><div class="nc-spinner"></div></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    NC.loadLogs();

    $('#nc-log-filter').on('change', function() {
        NC.loadLogs($(this).val());
    });
});

NC.loadLogs = function(level) {
    let path = ncData.apiUrl + '/logs?per_page=50';
    if (level) path += '&level=' + level;

    wp.apiFetch({path}).then(data => {
        if (!data.logs || data.logs.length === 0) {
            jQuery('#nc-logs-list').html('<p style="text-align:center;padding:40px;color:#6C757D;">Nenhum log encontrado</p>');
            return;
        }

        let html = '<table class="nc-table"><thead><tr><th>Data/Hora</th><th>Nível</th><th>Tipo</th><th>Mensagem</th></tr></thead><tbody>';
        data.logs.forEach(log => {
            const date = new Date(log.created_at);
            const badgeClass = log.level === 'error' ? 'nc-badge-danger' :
                              log.level === 'warning' ? 'nc-badge-warning' :
                              log.level === 'success' ? 'nc-badge-success' : 'nc-badge-info';

            html += `<tr>
                <td><small>${date.toLocaleString('pt-BR')}</small></td>
                <td><span class="nc-badge ${badgeClass}">${log.level}</span></td>
                <td>${log.type}</td>
                <td>${log.message}</td>
            </tr>`;
        });
        html += '</tbody></table>';
        jQuery('#nc-logs-list').html(html);
    }).catch(error => {
        jQuery('#nc-logs-list').html('<div class="nc-notice nc-notice-error">Erro ao carregar logs</div>');
    });
};
</script>
