<div class="wrap nc-wrap">
    <h1><?php _e('Curadoria', 'newsmast-curator'); ?></h1>
    <div class="nc-tabs">
        <button class="nc-tab active" data-tab="novos"><?php _e('Novos', 'newsmast-curator'); ?></button>
        <button class="nc-tab" data-tab="aprovados"><?php _e('Aprovados', 'newsmast-curator'); ?></button>
    </div>
    <div id="nc-items-list"><p><?php _e('Carregando...', 'newsmast-curator'); ?></p></div>
</div>
<script>
jQuery(document).ready(function($) {
    wp.apiFetch({path: ncData.apiUrl + '/items?curated=0'}).then(data => {
        let html = '<div class="nc-items">';
        (data.items || []).forEach(item => {
            html += `<div class="nc-item-card">
                <h3>${item.title}</h3>
                <p>${item.preview_text}</p>
                <button class="button button-primary" onclick="ncCurate(${item.id})">Aprovar</button>
            </div>`;
        });
        html += '</div>';
        $('#nc-items-list').html(html);
    });
});

function ncCurate(id) {
    wp.apiFetch({path: ncData.apiUrl + `/items/${id}/curate`, method: 'POST'}).then(() => {
        alert('Item aprovado!');
        location.reload();
    });
}
</script>
