<div class="wrap nc-wrap">
    <h1><?php _e('Fila de Publicação', 'newsmast-curator'); ?></h1>
    <div id="nc-publications-list"><p><?php _e('Carregando...', 'newsmast-curator'); ?></p></div>
</div>
<script>
jQuery(document).ready(function($) {
    wp.apiFetch({path: ncData.apiUrl + '/publications'}).then(pubs => {
        let html = '<table class="wp-list-table widefat"><thead><tr><th>Data</th><th>Conteúdo</th><th>Status</th></tr></thead><tbody>';
        pubs.forEach(p => {
            html += `<tr><td>${p.scheduled_for}</td><td>${p.content.substring(0, 50)}...</td><td>${p.status}</td></tr>`;
        });
        html += '</tbody></table>';
        $('#nc-publications-list').html(html);
    });
});
</script>
