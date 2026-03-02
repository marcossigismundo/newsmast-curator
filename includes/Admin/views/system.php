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

        <?php
        $cron_secret = get_option('nc_cron_secret', '');
        if (empty($cron_secret)) {
            $cron_secret = wp_generate_password(32, false);
            update_option('nc_cron_secret', $cron_secret, false);
        }
        $cron_url = home_url('/?nc_cron=1&token=' . $cron_secret);
        ?>
        <div style="margin-top:20px;padding:15px;background:var(--nc-bg-alt,#f8f9fa);border-radius:8px;">
            <h3 style="margin:0 0 10px;font-size:14px;">
                <span class="dashicons dashicons-admin-generic" style="color:var(--nc-accent);"></span>
                <?php _e('Cron Externo (recomendado)', 'newsmast-curator'); ?>
            </h3>
            <p style="margin:0 0 8px;font-size:13px;color:#6C757D;">
                <?php _e('Para não depender de visitas ao site, configure um cron real no servidor. Adicione ao crontab:', 'newsmast-curator'); ?>
            </p>
            <code style="display:block;padding:10px;background:#1e1e1e;color:#d4d4d4;border-radius:4px;font-size:12px;word-break:break-all;">*/5 * * * * wget -q -O /dev/null "<?php echo esc_url($cron_url); ?>"</code>
            <p style="margin:8px 0 0;font-size:12px;color:#999;">
                <?php _e('Ações disponíveis: &action=all (padrão), &action=publications, &action=collect, &action=cleanup', 'newsmast-curator'); ?>
            </p>
        </div>
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

        let html = '<table class="nc-table"><thead><tr><th>Data/Hora</th><th>Nível</th><th>Mensagem</th><th>Detalhes</th></tr></thead><tbody>';
        data.logs.forEach(log => {
            const date = new Date(log.created_at);
            const badgeClass = log.level === 'error' ? 'nc-badge-danger' :
                              log.level === 'warning' ? 'nc-badge-warning' :
                              log.level === 'success' ? 'nc-badge-success' : 'nc-badge-info';

            let context = {};
            try { context = typeof log.context === 'string' ? JSON.parse(log.context) : (log.context || {}); } catch(e) {}
            let detailsHtml = '';
            if (context.details) {
                detailsHtml += '<div>' + NC.escapeHtml(context.details) + '</div>';
            }
            if (context.error) {
                detailsHtml += '<div style="color:#dc3545;"><strong>Erro:</strong> ' + NC.escapeHtml(context.error) + '</div>';
            }
            if (context.mastodon_id) {
                detailsHtml += '<div><strong>Mastodon ID:</strong> ' + NC.escapeHtml(context.mastodon_id) + '</div>';
            }
            if (context.mastodon_url) {
                detailsHtml += '<div><a href="' + NC.escapeHtml(context.mastodon_url) + '" target="_blank">Ver no Mastodon</a></div>';
            }
            if (context.content_preview) {
                detailsHtml += '<div style="color:#6C757D;font-size:12px;margin-top:4px;">"' + NC.escapeHtml(context.content_preview) + '"</div>';
            }
            if (context.media_count !== undefined) {
                detailsHtml += '<div><strong>Mídia:</strong> ' + context.media_count + ' arquivo(s)</div>';
            }
            if (context.attempt) {
                detailsHtml += '<div><strong>Tentativa:</strong> ' + context.attempt + '</div>';
            }

            html += `<tr>
                <td><small>${date.toLocaleString('pt-BR')}</small></td>
                <td><span class="nc-badge ${badgeClass}">${log.level}</span></td>
                <td>${NC.escapeHtml(log.message)}${log.related_id ? '<br><small style="color:#999;">ID: ' + log.related_id + '</small>' : ''}</td>
                <td style="font-size:12px;">${detailsHtml || '<span style="color:#ccc;">—</span>'}</td>
            </tr>`;
        });
        html += '</tbody></table>';
        jQuery('#nc-logs-list').html(html);
    }).catch(error => {
        jQuery('#nc-logs-list').html('<div class="nc-notice nc-notice-error">Erro ao carregar logs</div>');
    });
};
</script>
