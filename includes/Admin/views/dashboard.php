<div class="wrap nc-wrap">
    <h1><?php _e('Dashboard - Newsmast Curator', 'newsmast-curator'); ?></h1>

    <div class="nc-stats-grid">
        <div class="nc-stat-card">
            <div class="icon">📂</div>
            <div class="number" id="nc-sources-count">-</div>
            <div class="label"><?php _e('Fontes Ativas', 'newsmast-curator'); ?></div>
        </div>

        <div class="nc-stat-card">
            <div class="icon">📝</div>
            <div class="number" id="nc-items-count">-</div>
            <div class="label"><?php _e('Itens Coletados', 'newsmast-curator'); ?></div>
        </div>

        <div class="nc-stat-card">
            <div class="icon">📤</div>
            <div class="number" id="nc-publications-count">-</div>
            <div class="label"><?php _e('Publicações Agendadas', 'newsmast-curator'); ?></div>
        </div>
    </div>

    <div class="nc-section">
        <h2><?php _e('Atividade Recente', 'newsmast-curator'); ?></h2>
        <div id="nc-recent-activity">
            <p><?php _e('Carregando...', 'newsmast-curator'); ?></p>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Carregar estatísticas
    wp.apiFetch({path: ncData.apiUrl + '/sources'}).then(data => {
        $('#nc-sources-count').text(data.length);
    });

    wp.apiFetch({path: ncData.apiUrl + '/items/stats'}).then(data => {
        $('#nc-items-count').text(data.total || 0);
    });
});
</script>
