<div class="nc-page-header">
    <div class="nc-page-title">
        <h1>
            <span class="dashicons dashicons-yes-alt"></span>
            <?php _e('Curadoria', 'newsmast-curator'); ?>
        </h1>
        <div class="nc-page-actions">
            <button class="nc-button nc-button-success" id="nc-btn-bulk-curate" onclick="NC.bulkCurate()">
                <span class="dashicons dashicons-yes"></span>
                <?php _e('Aprovar Selecionados', 'newsmast-curator'); ?>
            </button>
            <button class="nc-button nc-button-primary" id="nc-btn-bulk-schedule" style="display:none;" onclick="NC.openBulkScheduleModal()">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php _e('Agendar Selecionados', 'newsmast-curator'); ?>
            </button>
        </div>
    </div>
    <p class="nc-page-description"><?php _e('Revise e aprove itens para publicação no Mastodon', 'newsmast-curator'); ?></p>
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
    <div class="nc-card-header" id="nc-select-all-header">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
            <input type="checkbox" onchange="NC.selectAll(this)">
            <span><?php _e('Selecionar Todos', 'newsmast-curator'); ?></span>
        </label>
        <span id="nc-selected-count" style="font-size:13px;color:var(--nc-text-light);display:none;">
            <strong id="nc-selected-num">0</strong> <?php _e('selecionados', 'newsmast-curator'); ?>
        </span>
    </div>
    <div class="nc-card-body" id="nc-items-list">
        <div class="nc-loading"><div class="nc-spinner"></div></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Load counters
    wp.apiFetch({path: ncData.apiUrl + '/items/stats'}).then(function(stats) {
        $('#nc-uncurated-count').text(stats.uncurated || 0);
        $('#nc-curated-count').text(stats.curated || 0);
    });

    // Load uncurated items
    NC.loadItems(0);

    // Update selected count on checkbox change
    jQuery(document).on('change', '.nc-item-checkbox', function() {
        var count = jQuery('.nc-item-checkbox:checked').length;
        if (count > 0) {
            jQuery('#nc-selected-count').show();
            jQuery('#nc-selected-num').text(count);
        } else {
            jQuery('#nc-selected-count').hide();
        }
    });
});

NC._currentCuratedTab = 0;

NC.loadItems = function(curated) {
    NC._currentCuratedTab = curated;

    // Toggle header buttons based on tab
    if (curated === 1) {
        jQuery('#nc-btn-bulk-curate').hide();
        jQuery('#nc-btn-bulk-schedule').show();
    } else {
        jQuery('#nc-btn-bulk-curate').show();
        jQuery('#nc-btn-bulk-schedule').hide();
    }

    // Reset selection
    jQuery('#nc-selected-count').hide();
    jQuery('#nc-select-all-header input[type="checkbox"]').prop('checked', false);

    jQuery('#nc-items-list').html('<div class="nc-loading"><div class="nc-spinner"></div></div>');

    wp.apiFetch({path: ncData.apiUrl + '/items?curated=' + curated + '&per_page=50'}).then(function(data) {
        if (!data.items || data.items.length === 0) {
            var emptyMsg = curated === 0 ?
                'Nenhum item novo para curadoria. Execute uma coleta para trazer novos itens.' :
                'Nenhum item aprovado ainda. Aprove itens na aba "Novos".';
            jQuery('#nc-items-list').html(
                '<div class="nc-empty-state">' +
                    '<span class="dashicons dashicons-' + (curated === 0 ? 'inbox' : 'yes-alt') + '"></span>' +
                    '<p>' + emptyMsg + '</p>' +
                '</div>'
            );
            return;
        }

        var html = '<div class="nc-items-grid">';
        data.items.forEach(function(item) {
            html += '<div class="nc-item-card" data-item-id="' + item.id + '">';

            if (item.image_url) {
                html += '<img src="' + item.image_url + '" class="nc-item-image" alt="">';
            }

            html += '<div class="nc-item-content">';

            // Checkboxes on BOTH tabs
            html += '<label style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">' +
                '<input type="checkbox" class="nc-item-checkbox" value="' + item.id + '"' +
                ' data-formatted="' + encodeURIComponent(item.formatted_content || '') + '">' +
                '<span style="font-size:12px;color:#6C757D;">Selecionar</span>' +
                '</label>';

            html += '<h3 class="nc-item-title">' + item.title + '</h3>' +
                '<div class="nc-item-meta">' +
                    '<span><span class="dashicons dashicons-calendar" style="font-size:14px;"></span> ' +
                    new Date(item.collected_at).toLocaleDateString('pt-BR') + '</span>';

            if (item.author) {
                html += '<span><span class="dashicons dashicons-admin-users" style="font-size:14px;"></span> ' + item.author + '</span>';
            }

            html += '</div>' +
                '<p class="nc-item-excerpt">' + item.preview_text + '</p>' +
                '<div class="nc-item-actions">';

            // Actions depend on tab
            if (curated === 0) {
                html += '<button class="nc-button nc-button-success" onclick="NC.curateItem(' + item.id + ')">' +
                    '<span class="dashicons dashicons-yes-alt"></span> Aprovar' +
                    '</button>';
            } else {
                // Approved items: show schedule button
                html += '<button class="nc-button nc-button-primary" onclick="NC.openScheduleModal(' + item.id + ')">' +
                    '<span class="dashicons dashicons-calendar-alt"></span> Agendar' +
                    '</button>';
            }

            html += '<a href="' + item.url + '" target="_blank" class="nc-button nc-button-secondary">' +
                '<span class="dashicons dashicons-external"></span> Ver Original' +
                '</a>' +
                '</div></div></div>';
        });
        html += '</div>';

        jQuery('#nc-items-list').html(html);
    }).catch(function() {
        jQuery('#nc-items-list').html(
            '<div class="nc-notice nc-notice-error">' +
                '<span class="dashicons dashicons-dismiss"></span> Erro ao carregar itens' +
            '</div>'
        );
    });
};
</script>
