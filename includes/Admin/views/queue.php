<div class="nc-page-header">
    <div class="nc-page-title">
        <h1>
            <span class="dashicons dashicons-calendar-alt"></span>
            <?php _e('Fila de Publicação', 'newsmast-curator'); ?>
        </h1>
    </div>
    <p class="nc-page-description"><?php _e('Gerencie publicações agendadas', 'newsmast-curator'); ?></p>
</div>

<div class="nc-tabs">
    <button class="nc-tab active" data-tab="scheduled" onclick="NC.loadPublications('scheduled')">
        <?php _e('Agendadas', 'newsmast-curator'); ?>
    </button>
    <button class="nc-tab" data-tab="published" onclick="NC.loadPublications('published')">
        <?php _e('Publicadas', 'newsmast-curator'); ?>
    </button>
    <button class="nc-tab" data-tab="failed" onclick="NC.loadPublications('failed')">
        <?php _e('Falhas', 'newsmast-curator'); ?>
    </button>
</div>

<div class="nc-card">
    <div class="nc-card-body" id="nc-publications-list">
        <div class="nc-loading"><div class="nc-spinner"></div></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    NC.loadPublications('scheduled');
});

NC.loadPublications = function(status) {
    wp.apiFetch({path: `${ncData.apiUrl}/publications?status=${status}`}).then(pubs => {
        if (!pubs || pubs.length === 0) {
            jQuery('#nc-publications-list').html('<p style="text-align:center;padding:40px;color:#6C757D;">Nenhuma publicação encontrada</p>');
            return;
        }

        let html = '<table class="nc-table"><thead><tr><th>Data/Hora</th><th>Conteúdo</th><th>Status</th><th>Tentativas</th><th>Ações</th></tr></thead><tbody>';
        pubs.forEach(pub => {
            const date = new Date(pub.scheduled_for);
            const badgeClass = pub.status === 'scheduled' ? 'nc-badge-info' :
                              pub.status === 'published' ? 'nc-badge-success' :
                              pub.status === 'failed' ? 'nc-badge-danger' : 'nc-badge-warning';

            html += `<tr>
                <td>${date.toLocaleString('pt-BR')}</td>
                <td>${pub.content.substring(0, 80)}...</td>
                <td><span class="nc-badge ${badgeClass}">${pub.status}</span></td>
                <td>${pub.attempt_count} / 3</td>
                <td class="nc-table-actions">`;

            if (pub.status === 'published' && pub.mastodon_url) {
                html += `<a href="${pub.mastodon_url}" target="_blank" class="nc-button nc-button-secondary">
                    <span class="dashicons dashicons-external"></span> Ver Post
                </a>`;
            }

            if (pub.status === 'scheduled') {
                html += `<button class="nc-button nc-button-danger" onclick="NC.deletePublication(${pub.id})">
                    <span class="dashicons dashicons-trash"></span> Cancelar
                </button>`;
            }

            html += `</td></tr>`;
        });
        html += '</tbody></table>';
        jQuery('#nc-publications-list').html(html);
    }).catch(error => {
        jQuery('#nc-publications-list').html('<div class="nc-notice nc-notice-error">Erro ao carregar publicações</div>');
    });
};
</script>
