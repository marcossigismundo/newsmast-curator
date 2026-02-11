<div class="nc-page-header">
    <div class="nc-page-title">
        <h1>
            <span class="dashicons dashicons-dashboard"></span>
            <?php _e('Dashboard', 'newsmast-curator'); ?>
        </h1>
        <button class="nc-button nc-button-primary" onclick="NC.collectAll()">
            <span class="dashicons dashicons-update"></span>
            <?php _e('Coletar Agora', 'newsmast-curator'); ?>
        </button>
    </div>
    <p class="nc-page-description"><?php _e('Visão geral do sistema de curadoria', 'newsmast-curator'); ?></p>
</div>

<div class="nc-stats-grid">
    <div class="nc-stat-card">
        <div class="nc-stat-card-header">
            <div class="nc-stat-icon">
                <span class="dashicons dashicons-admin-site-alt3"></span>
            </div>
        </div>
        <div class="nc-stat-number" id="nc-sources-count">
            <div class="nc-spinner"></div>
        </div>
        <div class="nc-stat-label"><?php _e('Fontes Ativas', 'newsmast-curator'); ?></div>
    </div>

    <div class="nc-stat-card">
        <div class="nc-stat-card-header">
            <div class="nc-stat-icon">
                <span class="dashicons dashicons-media-document"></span>
            </div>
        </div>
        <div class="nc-stat-number" id="nc-items-total">
            <div class="nc-spinner"></div>
        </div>
        <div class="nc-stat-label"><?php _e('Itens Coletados', 'newsmast-curator'); ?></div>
    </div>

    <div class="nc-stat-card">
        <div class="nc-stat-card-header">
            <div class="nc-stat-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
        </div>
        <div class="nc-stat-number" id="nc-items-curated">
            <div class="nc-spinner"></div>
        </div>
        <div class="nc-stat-label"><?php _e('Itens Aprovados', 'newsmast-curator'); ?></div>
    </div>

    <div class="nc-stat-card">
        <div class="nc-stat-card-header">
            <div class="nc-stat-icon">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
        </div>
        <div class="nc-stat-number" id="nc-publications-count">
            <div class="nc-spinner"></div>
        </div>
        <div class="nc-stat-label"><?php _e('Publicações Agendadas', 'newsmast-curator'); ?></div>
    </div>
</div>

<div class="nc-card">
    <div class="nc-card-header">
        <h2 class="nc-card-title">
            <span class="dashicons dashicons-clock"></span>
            <?php _e('Atividade Recente', 'newsmast-curator'); ?>
        </h2>
    </div>
    <div class="nc-card-body" id="nc-recent-activity">
        <div class="nc-loading">
            <div class="nc-spinner"></div>
        </div>
    </div>
</div>

<div class="nc-card">
    <div class="nc-card-header">
        <h2 class="nc-card-title">
            <span class="dashicons dashicons-calendar"></span>
            <?php _e('Próximas Publicações', 'newsmast-curator'); ?>
        </h2>
        <a href="<?php echo admin_url('admin.php?page=newsmast-curator-queue'); ?>" class="nc-button nc-button-secondary">
            <?php _e('Ver Todas', 'newsmast-curator'); ?>
        </a>
    </div>
    <div class="nc-card-body" id="nc-upcoming-publications">
        <div class="nc-loading">
            <div class="nc-spinner"></div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    wp.apiFetch({path: ncData.apiUrl + '/sources'}).then(sources => {
        const active = sources.filter(s => s.status === 'active').length;
        $('#nc-sources-count').html(active);
    }).catch(() => $('#nc-sources-count').html('0'));

    wp.apiFetch({path: ncData.apiUrl + '/items/stats'}).then(stats => {
        $('#nc-items-total').html(stats.total || 0);
        $('#nc-items-curated').html(stats.curated || 0);
    }).catch(() => {
        $('#nc-items-total').html('0');
        $('#nc-items-curated').html('0');
    });

    wp.apiFetch({path: ncData.apiUrl + '/publications?status=scheduled'}).then(pubs => {
        $('#nc-publications-count').html(pubs.length || 0);

        if (pubs.length > 0) {
            let html = '<table class="nc-table"><thead><tr><th>Data/Hora</th><th>Conteúdo</th><th>Status</th></tr></thead><tbody>';
            pubs.slice(0, 5).forEach(pub => {
                const date = new Date(pub.scheduled_for);
                html += `<tr>
                    <td>${date.toLocaleString('pt-BR')}</td>
                    <td>${pub.content.substring(0, 60)}...</td>
                    <td><span class="nc-badge nc-badge-info">${pub.status}</span></td>
                </tr>`;
            });
            html += '</tbody></table>';
            $('#nc-upcoming-publications').html(html);
        } else {
            $('#nc-upcoming-publications').html('<p style="text-align:center;color:#6C757D;">Nenhuma publicação agendada</p>');
        }
    }).catch(() => $('#nc-publications-count').html('0'));

    wp.apiFetch({path: ncData.apiUrl + '/logs?per_page=10'}).then(data => {
        if (data.logs && data.logs.length > 0) {
            let html = '<div style="display:flex;flex-direction:column;gap:10px;">';
            data.logs.forEach(log => {
                const badgeClass = log.level === 'error' ? 'nc-badge-danger' :
                                 log.level === 'warning' ? 'nc-badge-warning' :
                                 log.level === 'success' ? 'nc-badge-success' : 'nc-badge-info';
                const date = new Date(log.created_at);
                html += `<div style="display:flex;align-items:center;gap:10px;padding:10px;background:#F8F9FA;border-radius:8px;">
                    <span class="nc-badge ${badgeClass}">${log.level}</span>
                    <span style="flex:1;">${log.message}</span>
                    <small style="color:#6C757D;">${date.toLocaleString('pt-BR')}</small>
                </div>`;
            });
            html += '</div>';
            $('#nc-recent-activity').html(html);
        } else {
            $('#nc-recent-activity').html('<p style="text-align:center;color:#6C757D;">Nenhuma atividade recente</p>');
        }
    }).catch(() => {
        $('#nc-recent-activity').html('<p style="text-align:center;color:#EF476F;">Erro ao carregar atividades</p>');
    });
});
</script>
