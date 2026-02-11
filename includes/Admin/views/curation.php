<div class="nc-page-header">
    <div class="nc-page-title">
        <h1>
            <span class="dashicons dashicons-yes-alt"></span>
            <?php _e('Curadoria', 'newsmast-curator'); ?>
        </h1>
        <button class="nc-button nc-button-success" onclick="NC.bulkCurate()">
            <span class="dashicons dashicons-yes"></span>
            <?php _e('Aprovar Selecionados', 'newsmast-curator'); ?>
        </button>
    </div>
    <p class="nc-page-description"><?php _e('Revise e aprove itens para publicação', 'newsmast-curator'); ?></p>
</div>

<div class="nc-tabs">
    <button class="nc-tab active" data-tab="novos" onclick="NC.loadItems(0)">
        <?php _e('Novos', 'newsmast-curator'); ?> <span id="nc-uncurated-count" class="nc-badge nc-badge-warning">0</span>
    </button>
    <button class="nc-tab" data-tab="aprovados" onclick="NC.loadItems(1)">
        <?php _e('Aprovados', 'newsmast-curator'); ?> <span id="nc-curated-count" class="nc-badge nc-badge-success">0</span>
    </button>
</div>

<div class="nc-card">
    <div class="nc-card-header">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
            <input type="checkbox" onchange="NC.selectAll(this)">
            <span><?php _e('Selecionar Todos', 'newsmast-curator'); ?></span>
        </label>
    </div>
    <div class="nc-card-body" id="nc-items-list">
        <div class="nc-loading"><div class="nc-spinner"></div></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Carregar contadores
    wp.apiFetch({path: ncData.apiUrl + '/items/stats'}).then(stats => {
        $('#nc-uncurated-count').text(stats.uncurated || 0);
        $('#nc-curated-count').text(stats.curated || 0);
    });

    // Carregar itens não curados
    NC.loadItems(0);
});

NC.loadItems = function(curated) {
    wp.apiFetch({path: `${ncData.apiUrl}/items?curated=${curated}&per_page=50`}).then(data => {
        if (!data.items || data.items.length === 0) {
            jQuery('#nc-items-list').html('<p style="text-align:center;padding:40px;color:#6C757D;">Nenhum item encontrado</p>');
            return;
        }

        let html = '<div class="nc-items-grid">';
        data.items.forEach(item => {
            html += `<div class="nc-item-card" data-item-id="${item.id}">`;

            if (item.image_url) {
                html += `<img src="${item.image_url}" class="nc-item-image" alt="">`;
            }

            html += `<div class="nc-item-content">`;

            if (curated === 0) {
                html += `<label style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                    <input type="checkbox" class="nc-item-checkbox" value="${item.id}">
                    <span style="font-size:12px;color:#6C757D;">Selecionar</span>
                </label>`;
            }

            html += `<h3 class="nc-item-title">${item.title}</h3>
                    <div class="nc-item-meta">
                        <span><span class="dashicons dashicons-calendar" style="font-size:14px;"></span> ${new Date(item.collected_at).toLocaleDateString('pt-BR')}</span>
                    </div>
                    <p class="nc-item-excerpt">${item.preview_text}</p>
                    <div class="nc-item-actions">`;

            if (curated === 0) {
                html += `<button class="nc-button nc-button-success" onclick="NC.curateItem(${item.id})">
                            <span class="dashicons dashicons-yes-alt"></span> Aprovar
                        </button>`;
            }

            html += `<a href="${item.url}" target="_blank" class="nc-button nc-button-secondary">
                        <span class="dashicons dashicons-external"></span> Ver Original
                    </a>
                </div></div></div>`;
        });
        html += '</div>';

        jQuery('#nc-items-list').html(html);
    }).catch(error => {
        jQuery('#nc-items-list').html('<div class="nc-notice nc-notice-error">Erro ao carregar itens</div>');
    });
};
</script>
