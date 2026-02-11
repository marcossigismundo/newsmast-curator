<div class="wrap nc-wrap">
    <h1><?php _e('Sistema e Logs', 'newsmast-curator'); ?></h1>
    <h2><?php _e('Status', 'newsmast-curator'); ?></h2>
    <table class="form-table">
        <tr><th>Versão</th><td><?php echo NC_VERSION; ?></td></tr>
        <tr><th>WordPress</th><td><?php echo get_bloginfo('version'); ?></td></tr>
        <tr><th>PHP</th><td><?php echo PHP_VERSION; ?></td></tr>
    </table>
    <h2><?php _e('Logs Recentes', 'newsmast-curator'); ?></h2>
    <div id="nc-logs-list"><p><?php _e('Carregando...', 'newsmast-curator'); ?></p></div>
</div>
<script>
jQuery(document).ready(function($) {
    wp.apiFetch({path: ncData.apiUrl + '/logs'}).then(data => {
        let html = '<table class="wp-list-table widefat"><thead><tr><th>Data</th><th>Tipo</th><th>Mensagem</th></tr></thead><tbody>';
        (data.logs || []).forEach(log => {
            html += `<tr><td>${log.created_at}</td><td>${log.type}</td><td>${log.message}</td></tr>`;
        });
        html += '</tbody></table>';
        $('#nc-logs-list').html(html);
    });
});
</script>
