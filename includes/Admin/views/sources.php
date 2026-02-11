<div class="wrap nc-wrap">
    <h1><?php _e('Fontes de Conteúdo', 'newsmast-curator'); ?> <button class="button button-primary" onclick="ncShowAddSource()"><?php _e('Nova Fonte', 'newsmast-curator'); ?></button></h1>

    <div id="nc-sources-list">
        <p><?php _e('Carregando...', 'newsmast-curator'); ?></p>
    </div>
</div>

<script>
function ncLoadSources() {
    wp.apiFetch({path: ncData.apiUrl + '/sources'}).then(sources => {
        let html = '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Nome</th><th>Tipo</th><th>Status</th><th>Ações</th></tr></thead><tbody>';
        sources.forEach(s => {
            html += `<tr>
                <td><strong>${s.name}</strong></td>
                <td>${s.connector_type}</td>
                <td>${s.status}</td>
                <td><button class="button" onclick="ncCollect(${s.id})">Coletar</button></td>
            </tr>`;
        });
        html += '</tbody></table>';
        jQuery('#nc-sources-list').html(html);
    });
}

function ncCollect(id) {
    wp.apiFetch({path: ncData.apiUrl + `/sources/${id}/collect`, method: 'POST'}).then(result => {
        alert(`${result.items_collected} itens coletados!`);
        ncLoadSources();
    });
}

jQuery(document).ready(ncLoadSources);
</script>
