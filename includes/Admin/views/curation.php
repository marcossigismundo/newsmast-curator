<div class="nc-page-header">
    <div class="nc-page-title">
        <h1>
            <span class="dashicons dashicons-yes-alt"></span>
            <?php _e('Curadoria', 'newsmast-curator'); ?>
        </h1>
        <div class="nc-page-actions">
            <div class="nc-view-toggle" id="nc-view-toggle">
                <button class="nc-view-btn active" data-view="grid" onclick="NC.setViewMode('grid')" title="<?php esc_attr_e('Visualização em Grade', 'newsmast-curator'); ?>">
                    <span class="dashicons dashicons-grid-view"></span>
                </button>
                <button class="nc-view-btn" data-view="list" onclick="NC.setViewMode('list')" title="<?php esc_attr_e('Visualização em Lista', 'newsmast-curator'); ?>">
                    <span class="dashicons dashicons-list-view"></span>
                </button>
            </div>
            <button class="nc-button nc-button-success" id="nc-btn-bulk-curate" onclick="NC.bulkCurate()">
                <span class="dashicons dashicons-yes"></span>
                <?php _e('Aprovar Selecionados', 'newsmast-curator'); ?>
            </button>
            <button class="nc-button nc-button-primary" id="nc-btn-bulk-schedule" style="display:none;" onclick="NC.openBulkScheduleModal()">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php _e('Agendar Selecionados', 'newsmast-curator'); ?>
            </button>
            <button class="nc-button nc-button-secondary" id="nc-btn-add-to-collection" style="display:none;" onclick="NC.openAddToCollectionModal()">
                <span class="dashicons dashicons-portfolio"></span>
                <?php _e('Adicionar à Coleção', 'newsmast-curator'); ?>
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

<div class="nc-filters" style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
    <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--nc-text-light);">
        <span class="dashicons dashicons-filter" style="font-size:16px;"></span>
        <?php _e('Fonte:', 'newsmast-curator'); ?>
    </label>
    <select id="nc-filter-source" class="nc-form-control" style="width:auto;min-width:200px;max-width:350px;" onchange="NC.loadItems(NC._currentCuratedTab)">
        <option value=""><?php _e('Todas as fontes', 'newsmast-curator'); ?></option>
    </select>
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
    wp.apiFetch({path: ncData.apiUrl + '/items/stats'}).then(function(stats) {
        $('#nc-uncurated-count').text(stats.uncurated || 0);
        $('#nc-curated-count').text(stats.curated || 0);
    });

    // Load sources for filter dropdown
    wp.apiFetch({path: ncData.apiUrl + '/sources'}).then(function(data) {
        var $sel = $('#nc-filter-source');
        (data.sources || data || []).forEach(function(s) {
            var label = NC.escapeHtml(s.name);
            if (s.config && s.config.search_terms) {
                label += ' (' + NC.escapeHtml(s.config.search_terms) + ')';
            }
            $sel.append('<option value="' + s.id + '">' + label + '</option>');
        });
    });

    NC._viewMode = localStorage.getItem('nc_view_mode') || 'grid';
    $('#nc-view-toggle .nc-view-btn').removeClass('active');
    $('#nc-view-toggle .nc-view-btn[data-view="' + NC._viewMode + '"]').addClass('active');

    NC.loadItems(0);

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

    if (curated === 1) {
        jQuery('#nc-btn-bulk-curate').hide();
        jQuery('#nc-btn-bulk-schedule').show();
        jQuery('#nc-btn-add-to-collection').show();
    } else {
        jQuery('#nc-btn-bulk-curate').show();
        jQuery('#nc-btn-bulk-schedule').hide();
        jQuery('#nc-btn-add-to-collection').hide();
    }

    jQuery('#nc-selected-count').hide();
    jQuery('#nc-select-all-header input[type="checkbox"]').prop('checked', false);
    jQuery('#nc-items-list').html('<div class="nc-loading"><div class="nc-spinner"></div></div>');

    var sourceFilter = jQuery('#nc-filter-source').val();
    var apiPath = ncData.apiUrl + '/items?curated=' + curated + '&per_page=50';
    if (sourceFilter) {
        apiPath += '&source_id=' + sourceFilter;
    }

    wp.apiFetch({path: apiPath}).then(function(data) {
        if (!data.items || data.items.length === 0) {
            var emptyMsg = curated === 0 ?
                NC.__('no_new_items', 'Nenhum item novo para curadoria. Execute uma coleta para trazer novos itens.') :
                NC.__('no_approved_items_yet', 'Nenhum item aprovado ainda. Aprove itens na aba "Novos".');
            jQuery('#nc-items-list').html(
                '<div class="nc-empty-state">' +
                    '<span class="dashicons dashicons-' + (curated === 0 ? 'inbox' : 'yes-alt') + '"></span>' +
                    '<p>' + emptyMsg + '</p>' +
                '</div>'
            );
            return;
        }

        var viewMode = NC._viewMode || 'grid';
        var html = viewMode === 'list' ? NC.renderItemsList(data.items, curated) : NC.renderItemsGrid(data.items, curated);
        jQuery('#nc-items-list').html(html);
    }).catch(function() {
        jQuery('#nc-items-list').html(
            '<div class="nc-notice nc-notice-error">' +
                '<span class="dashicons dashicons-dismiss"></span> ' + NC.__('load_items_error', 'Erro ao carregar itens') +
            '</div>'
        );
    });
};

NC.renderItemsGrid = function(items, curated) {
    var html = '<div class="nc-items-grid">';
    items.forEach(function(item) {
        html += '<div class="nc-item-card" data-item-id="' + item.id + '">';
        if (item.image_url) {
            html += '<img src="' + NC.escapeHtml(item.image_url) + '" class="nc-item-image" alt="">';
        }
        html += '<div class="nc-item-content">';
        html += '<label style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">' +
            '<input type="checkbox" class="nc-item-checkbox" value="' + item.id + '"' +
            ' data-formatted="' + encodeURIComponent(item.formatted_content || '') + '">' +
            '<span style="font-size:12px;color:#6C757D;">' + NC.__('select', 'Selecionar') + '</span></label>';

        var collectionBadge = item.collection_type === 'auto'
            ? '<span class="nc-badge nc-badge-info" style="font-size:9px;vertical-align:middle;" title="Coletado automaticamente em ' + new Date(item.collected_at).toLocaleString('pt-BR') + '"><span class="dashicons dashicons-clock" style="font-size:11px;width:11px;height:11px;vertical-align:middle;"></span> Auto</span> '
            : '';

        html += '<h3 class="nc-item-title">' + collectionBadge + NC.escapeHtml(item.title) + '</h3>' +
            '<div class="nc-item-meta">';
        if (item.source_name) {
            html += '<span title="Fonte"><span class="dashicons dashicons-admin-site-alt3" style="font-size:14px;"></span> ' + NC.escapeHtml(item.source_name) + '</span>';
        }
        if (item.search_terms) {
            html += '<span title="Termo de busca"><span class="dashicons dashicons-search" style="font-size:14px;"></span> ' + NC.escapeHtml(item.search_terms) + '</span>';
        }
        html += '<span><span class="dashicons dashicons-calendar" style="font-size:14px;"></span> ' +
                new Date(item.collected_at).toLocaleString('pt-BR') + '</span>';
        if (item.author) {
            html += '<span><span class="dashicons dashicons-admin-users" style="font-size:14px;"></span> ' + NC.escapeHtml(item.author) + '</span>';
        }
        html += '</div><p class="nc-item-excerpt">' + NC.escapeHtml(item.preview_text) + '</p>' +
            '<div class="nc-item-actions">';
        if (curated === 0) {
            html += '<button class="nc-button nc-button-success" onclick="NC.curateItem(' + item.id + ')">' +
                '<span class="dashicons dashicons-yes-alt"></span> ' + NC.__('approve', 'Aprovar') + '</button>';
        } else {
            html += '<button class="nc-button nc-button-primary" onclick="NC.openScheduleModal(' + item.id + ')">' +
                '<span class="dashicons dashicons-calendar-alt"></span> ' + NC.__('schedule', 'Agendar') + '</button>';
        }
        html += '<a href="' + NC.escapeHtml(item.url) + '" target="_blank" class="nc-button nc-button-secondary">' +
            '<span class="dashicons dashicons-external"></span> ' + NC.__('view_original', 'Ver Original') + '</a>' +
            '</div></div></div>';
    });
    html += '</div>';
    return html;
};

NC.renderItemsList = function(items, curated) {
    var html = '<div class="nc-items-list-view"><table class="nc-table"><thead><tr>' +
        '<th style="width:40px;"><input type="checkbox" onchange="NC.selectAll(this)"></th>' +
        '<th style="width:60px;">' + NC.__('image', 'Imagem') + '</th>' +
        '<th>' + NC.__('title', 'Título') + '</th>' +
        '<th>' + NC.__('source', 'Fonte') + '</th>' +
        '<th>' + NC.__('filter', 'Filtro') + '</th>' +
        '<th>' + NC.__('date', 'Data') + '</th>' +
        '<th>' + NC.__('actions', 'Ações') + '</th>' +
        '</tr></thead><tbody>';

    items.forEach(function(item) {
        html += '<tr data-item-id="' + item.id + '">';
        html += '<td><input type="checkbox" class="nc-item-checkbox" value="' + item.id + '"' +
            ' data-formatted="' + encodeURIComponent(item.formatted_content || '') + '"></td>';
        html += '<td>';
        if (item.image_url) {
            html += '<img src="' + NC.escapeHtml(item.image_url) + '" class="nc-list-thumb" alt="">';
        } else {
            html += '<span class="nc-list-thumb-placeholder dashicons dashicons-format-image"></span>';
        }
        html += '</td>';
        var title = item.title.length > 80 ? NC.escapeHtml(item.title.substring(0, 80)) + '...' : NC.escapeHtml(item.title);
        var excerpt = item.preview_text ? (item.preview_text.length > 100 ? NC.escapeHtml(item.preview_text.substring(0, 100)) + '...' : NC.escapeHtml(item.preview_text)) : '';
        var listBadge = item.collection_type === 'auto'
            ? '<span class="nc-badge nc-badge-info" style="font-size:9px;margin-left:6px;" title="Coleta automática"><span class="dashicons dashicons-clock" style="font-size:11px;width:11px;height:11px;vertical-align:middle;"></span> Auto</span>'
            : '';
        html += '<td><strong class="nc-item-title">' + title + listBadge + '</strong>';
        if (excerpt) {
            html += '<div class="nc-list-excerpt">' + excerpt + '</div>';
        }
        html += '</td>';
        html += '<td>' + NC.escapeHtml(item.source_name || '-') + '</td>';
        var searchBadge = item.search_terms
            ? '<span class="nc-badge nc-badge-info" style="font-size:10px;"><span class="dashicons dashicons-search" style="font-size:11px;width:11px;height:11px;vertical-align:middle;"></span> ' + NC.escapeHtml(item.search_terms) + '</span>'
            : '<span style="color:var(--nc-text-light);">—</span>';
        html += '<td>' + searchBadge + '</td>';
        html += '<td>' + new Date(item.collected_at).toLocaleDateString('pt-BR') + '</td>';
        html += '<td class="nc-table-actions">';
        if (curated === 0) {
            html += '<button class="nc-button nc-button-success" onclick="NC.curateItem(' + item.id + ')" title="' + NC.__('approve', 'Aprovar') + '">' +
                '<span class="dashicons dashicons-yes-alt"></span></button>';
        } else {
            html += '<button class="nc-button nc-button-primary" onclick="NC.openScheduleModal(' + item.id + ')" title="' + NC.__('schedule', 'Agendar') + '">' +
                '<span class="dashicons dashicons-calendar-alt"></span></button>';
        }
        html += '<a href="' + NC.escapeHtml(item.url) + '" target="_blank" class="nc-button nc-button-secondary" title="' + NC.__('view_original', 'Ver Original') + '">' +
            '<span class="dashicons dashicons-external"></span></a>';
        html += '</td></tr>';
    });
    html += '</tbody></table></div>';
    return html;
};

// ========== Add to Collection Modal ==========

NC.openAddToCollectionModal = function() {
    var $checked = jQuery('.nc-item-checkbox:checked');
    if ($checked.length === 0) {
        NC.showNotice('warning', NC.__('select_at_least_one', 'Selecione ao menos um item'));
        return;
    }
    if (!jQuery('#nc-add-collection-modal').length) { NC.createAddToCollectionModal(); }
    NC._selectedItemIds = $checked.map(function() { return jQuery(this).val(); }).get();
    jQuery('#nc-collection-item-count').text(NC._selectedItemIds.length);
    NC.loadCollectionDropdown();
    NC.openModal('nc-add-collection-modal');
};

NC.createAddToCollectionModal = function() {
    var html =
    '<div id="nc-add-collection-modal" class="nc-modal-overlay">' +
        '<div class="nc-modal" style="max-width:500px;">' +
            '<div class="nc-modal-header">' +
                '<h3 class="nc-modal-title"><span class="dashicons dashicons-portfolio" style="color:var(--nc-accent);"></span> ' +
                    NC.__('add_to_collection', 'Adicionar à Coleção') + '</h3>' +
                '<button class="nc-modal-close" onclick="NC.closeModal(\'nc-add-collection-modal\')">&times;</button>' +
            '</div>' +
            '<div class="nc-modal-body">' +
                '<div class="nc-notice nc-notice-info" style="margin-bottom:20px;"><span class="dashicons dashicons-info"></span>' +
                    '<span><strong id="nc-collection-item-count">0</strong> ' + NC.__('items_selected', 'itens selecionados') + '</span></div>' +
                '<div class="nc-form-group"><label class="nc-form-label">' + NC.__('select_collection', 'Selecionar Coleção') + '</label>' +
                    '<select id="nc-collection-select" class="nc-form-control"><option value="">' + NC.__('loading', 'Carregando...') + '</option></select></div>' +
                '<div class="nc-form-group" id="nc-new-collection-fields" style="display:none;">' +
                    '<label class="nc-form-label">' + NC.__('collection_name', 'Nome da Coleção') + ' *</label>' +
                    '<input type="text" id="nc-new-collection-name" class="nc-form-control" placeholder="' + NC.__('collection_name_placeholder', 'Ex: Semana dos Museus 2026') + '">' +
                    '<div style="margin-top:10px;"><label class="nc-form-label">' + NC.__('description', 'Descrição') + '</label>' +
                    '<textarea id="nc-new-collection-desc" class="nc-form-control" rows="2"></textarea></div></div>' +
            '</div>' +
            '<div class="nc-modal-footer">' +
                '<button class="nc-button nc-button-secondary" onclick="NC.closeModal(\'nc-add-collection-modal\')">' + NC.__('cancel', 'Cancelar') + '</button>' +
                '<button class="nc-button nc-button-primary" onclick="NC.submitAddToCollection()">' +
                    '<span class="dashicons dashicons-plus-alt2"></span> ' + NC.__('add', 'Adicionar') + '</button>' +
            '</div></div></div>';
    jQuery('body').append(html);
    jQuery('#nc-collection-select').on('change', function() {
        jQuery('#nc-new-collection-fields').toggle(jQuery(this).val() === 'new');
    });
};

NC.loadCollectionDropdown = function() {
    var $s = jQuery('#nc-collection-select');
    $s.html('<option value="">' + NC.__('loading', 'Carregando...') + '</option>');
    wp.apiFetch({path: ncData.apiUrl + '/collections?status=draft&per_page=100'}).then(function(data) {
        $s.html('<option value="">' + NC.__('select_collection_option', '-- Selecione --') + '</option>');
        if (data.collections) { data.collections.forEach(function(c) {
            $s.append('<option value="' + c.id + '">' + NC.escapeHtml(c.name) + ' (' + c.items_count + ' itens)</option>');
        }); }
        $s.append('<option value="new">' + NC.__('create_new_collection', '+ Criar nova coleção') + '</option>');
    }).catch(function() { $s.html('<option value="new">' + NC.__('create_new_collection', '+ Criar nova coleção') + '</option>'); });
};

NC.submitAddToCollection = function() {
    var cid = jQuery('#nc-collection-select').val();
    var ids = NC._selectedItemIds || [];
    if (!cid) { NC.showNotice('warning', NC.__('select_a_collection', 'Selecione uma coleção')); return; }
    if (cid === 'new') {
        var name = jQuery('#nc-new-collection-name').val();
        if (!name) { NC.showNotice('warning', NC.__('collection_name_required', 'Nome da coleção é obrigatório')); return; }
        wp.apiFetch({ path: ncData.apiUrl + '/collections', method: 'POST',
            data: { name: name, description: jQuery('#nc-new-collection-desc').val() || '' }
        }).then(function(col) { NC.addItemsToExistingCollection(col.id, ids); })
        .catch(function(e) { NC.showNotice('error', NC.__('create_collection_error', 'Erro ao criar coleção: ') + (e.message || '')); });
    } else {
        NC.addItemsToExistingCollection(parseInt(cid), ids);
    }
};

NC.addItemsToExistingCollection = function(cid, ids) {
    wp.apiFetch({ path: ncData.apiUrl + '/collections/' + cid + '/items', method: 'POST',
        data: { item_ids: ids.map(function(i) { return parseInt(i); }) }
    }).then(function(r) {
        NC.showNotice('success', (r.added || 0) + ' ' + NC.__('items_added_to_collection', 'itens adicionados à coleção!'));
        NC.closeModal('nc-add-collection-modal');
    }).catch(function(e) { NC.showNotice('error', NC.__('add_to_collection_error', 'Erro ao adicionar: ') + (e.message || '')); });
};
</script>
