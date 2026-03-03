<div class="nc-page-header">
    <div class="nc-page-title">
        <h1>
            <span class="dashicons dashicons-portfolio"></span>
            <?php _e('Coleções', 'newsmast-curator'); ?>
        </h1>
        <div class="nc-page-actions">
            <button class="nc-button nc-button-primary" onclick="NC.openCreateCollectionModal()">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php _e('Nova Coleção', 'newsmast-curator'); ?>
            </button>
        </div>
    </div>
    <p class="nc-page-description"><?php _e('Agrupe itens curados em coleções temáticas para agendamento coordenado', 'newsmast-curator'); ?></p>
</div>

<div class="nc-tabs">
    <button class="nc-tab active" data-tab="all" onclick="NC.loadCollections('all')">
        <?php _e('Todas', 'newsmast-curator'); ?> <span id="nc-count-all" class="nc-badge">0</span>
    </button>
    <button class="nc-tab" data-tab="draft" onclick="NC.loadCollections('draft')">
        <?php _e('Rascunhos', 'newsmast-curator'); ?> <span id="nc-count-draft" class="nc-badge nc-badge-warning">0</span>
    </button>
    <button class="nc-tab" data-tab="scheduled" onclick="NC.loadCollections('scheduled')">
        <?php _e('Agendadas', 'newsmast-curator'); ?> <span id="nc-count-scheduled" class="nc-badge nc-badge-info">0</span>
    </button>
    <button class="nc-tab" data-tab="published" onclick="NC.loadCollections('published')">
        <?php _e('Publicadas', 'newsmast-curator'); ?> <span id="nc-count-published" class="nc-badge nc-badge-success">0</span>
    </button>
</div>

<div id="nc-collections-container">
    <div class="nc-loading"><div class="nc-spinner"></div></div>
</div>

<script>
jQuery(document).ready(function($) {
    NC.loadCollections('all');
    NC.loadCollectionStats();
});

// ========== Collections - Load & Render ==========

NC._collectionsFilter = 'all';

NC.loadCollectionStats = function() {
    wp.apiFetch({path: ncData.apiUrl + '/collections/stats'}).then(function(stats) {
        jQuery('#nc-count-all').text(stats.total || 0);
        jQuery('#nc-count-draft').text(stats.draft || 0);
        jQuery('#nc-count-scheduled').text(stats.scheduled || 0);
        jQuery('#nc-count-published').text(stats.published || 0);
    }).catch(function() {});
};

NC.loadCollections = function(filter) {
    filter = filter || 'all';
    NC._collectionsFilter = filter;

    jQuery('.nc-tab').removeClass('active');
    jQuery('.nc-tab[data-tab="' + filter + '"]').addClass('active');
    jQuery('#nc-collections-container').html('<div class="nc-loading"><div class="nc-spinner"></div></div>');

    var path = ncData.apiUrl + '/collections?per_page=50';
    if (filter !== 'all') {
        path += '&status=' + filter;
    }

    wp.apiFetch({path: path}).then(function(data) {
        if (!data.collections || data.collections.length === 0) {
            jQuery('#nc-collections-container').html(
                '<div class="nc-empty-state">' +
                    '<span class="dashicons dashicons-portfolio"></span>' +
                    '<p>' + NC.__('no_collections', 'Nenhuma coleção encontrada. Crie sua primeira coleção!') + '</p>' +
                '</div>'
            );
            return;
        }

        var html = '<div class="nc-collections-grid">';
        data.collections.forEach(function(col) {
            html += NC.renderCollectionCard(col);
        });
        html += '</div>';
        jQuery('#nc-collections-container').html(html);
    }).catch(function() {
        jQuery('#nc-collections-container').html(
            '<div class="nc-notice nc-notice-error">' +
                '<span class="dashicons dashicons-dismiss"></span> ' +
                NC.__('load_items_error', 'Erro ao carregar itens') +
            '</div>'
        );
    });
};

NC.renderCollectionCard = function(col) {
    var badgeClass = col.status === 'draft' ? 'nc-badge-warning' :
                     col.status === 'scheduled' ? 'nc-badge-info' :
                     col.status === 'published' ? 'nc-badge-success' :
                     col.status === 'partial' ? 'nc-badge-info' : '';
    var statusLabel = col.status_label || col.status;
    var pct = col.items_count > 0 ? Math.round((col.published_count / col.items_count) * 100) : 0;
    var borderColor = col.color || '#457B9D';

    var html = '<div class="nc-collection-card" style="border-left-color:' + NC.escapeHtml(borderColor) + ';">';

    // Header
    html += '<div class="nc-collection-header">';
    html += '<div class="nc-collection-info">';
    html += '<h3 class="nc-collection-name">' + NC.escapeHtml(col.name) + '</h3>';
    if (col.description) {
        html += '<p class="nc-collection-desc">' + NC.escapeHtml(col.description) + '</p>';
    }
    html += '<div class="nc-collection-meta">';
    html += '<span class="nc-badge ' + badgeClass + '">' + NC.escapeHtml(statusLabel) + '</span>';
    html += '<span>' + col.items_count + ' ' + NC.__('items_label', 'itens') + '</span>';
    if (col.scheduled_for) {
        html += '<span><span class="dashicons dashicons-calendar-alt" style="font-size:14px;"></span> ' +
            new Date(col.scheduled_for).toLocaleString('pt-BR') + '</span>';
    }
    html += '</div></div>';
    html += '<span class="nc-color-swatch" style="background:' + NC.escapeHtml(borderColor) + ';"></span>';
    html += '</div>';

    // Progress bar
    if (col.items_count > 0) {
        html += '<div class="nc-collection-progress">';
        html += '<div class="nc-progress-bar"><div class="nc-progress-fill" style="width:' + pct + '%;"></div></div>';
        html += '<span class="nc-progress-text">' + col.published_count + ' ' +
            NC.__('published_of', 'publicados de') + ' ' + col.items_count + '</span>';
        html += '</div>';
    }

    // Footer actions
    html += '<div class="nc-collection-footer">';
    html += '<button class="nc-button nc-button-secondary" onclick="NC.openCollectionDetailModal(' + col.id + ')">' +
        '<span class="dashicons dashicons-visibility"></span> ' + NC.__('collection_items', 'Itens da Coleção') + '</button>';
    if (col.status === 'draft') {
        html += '<button class="nc-button nc-button-primary" onclick="NC.openScheduleCollectionModal(' + col.id + ', ' + col.items_count + ')">' +
            '<span class="dashicons dashicons-calendar-alt"></span> ' + NC.__('schedule', 'Agendar') + '</button>';
    }
    html += '<button class="nc-button nc-button-secondary" onclick="NC.openEditCollectionModal(' + col.id + ')">' +
        '<span class="dashicons dashicons-edit"></span> ' + NC.__('edit', 'Editar') + '</button>';
    html += '<button class="nc-button nc-button-danger" onclick="NC.deleteCollection(' + col.id + ')">' +
        '<span class="dashicons dashicons-trash"></span> ' + NC.__('delete', 'Excluir') + '</button>';
    html += '</div>';

    html += '</div>';
    return html;
};

// ========== Create Collection Modal ==========

NC.openCreateCollectionModal = function() {
    if (!jQuery('#nc-create-collection-modal').length) {
        NC.createCollectionFormModal();
    }
    jQuery('#nc-coll-form-title').text(NC.__('new_collection', 'Nova Coleção'));
    jQuery('#nc-coll-form-id').val('');
    jQuery('#nc-coll-form-name').val('');
    jQuery('#nc-coll-form-desc').val('');
    jQuery('.nc-color-option').removeClass('active');
    jQuery('.nc-color-option[data-color="#457B9D"]').addClass('active');
    jQuery('#nc-coll-form-color').val('#457B9D');
    jQuery('#nc-coll-form-submit').text(NC.__('create', 'Criar'));
    NC.openModal('nc-create-collection-modal');
};

NC.openEditCollectionModal = function(id) {
    if (!jQuery('#nc-create-collection-modal').length) {
        NC.createCollectionFormModal();
    }
    wp.apiFetch({path: ncData.apiUrl + '/collections/' + id}).then(function(col) {
        jQuery('#nc-coll-form-title').text(NC.__('edit', 'Editar') + ': ' + col.name);
        jQuery('#nc-coll-form-id').val(col.id);
        jQuery('#nc-coll-form-name').val(col.name);
        jQuery('#nc-coll-form-desc').val(col.description || '');
        jQuery('.nc-color-option').removeClass('active');
        jQuery('.nc-color-option[data-color="' + col.color + '"]').addClass('active');
        jQuery('#nc-coll-form-color').val(col.color || '#457B9D');
        jQuery('#nc-coll-form-submit').text(NC.__('save', 'Salvar'));
        NC.openModal('nc-create-collection-modal');
    }).catch(function(e) {
        NC.showNotice('error', (e.message || ''));
    });
};

NC.createCollectionFormModal = function() {
    var colors = ['#457B9D', '#E63946', '#F4A261', '#2A9D8F', '#264653', '#E9C46A', '#6C5CE7', '#00B894'];
    var colorsHtml = '<div class="nc-color-options">';
    colors.forEach(function(c) {
        colorsHtml += '<span class="nc-color-option' + (c === '#457B9D' ? ' active' : '') +
            '" data-color="' + c + '" style="background:' + c + ';" onclick="NC.selectCollectionColor(\'' + c + '\')"></span>';
    });
    colorsHtml += '</div>';

    var html =
    '<div id="nc-create-collection-modal" class="nc-modal-overlay">' +
        '<div class="nc-modal" style="max-width:500px;">' +
            '<div class="nc-modal-header">' +
                '<h3 class="nc-modal-title"><span class="dashicons dashicons-portfolio" style="color:var(--nc-accent);"></span> ' +
                    '<span id="nc-coll-form-title">' + NC.__('new_collection', 'Nova Coleção') + '</span></h3>' +
                '<button class="nc-modal-close" onclick="NC.closeModal(\'nc-create-collection-modal\')">&times;</button>' +
            '</div>' +
            '<div class="nc-modal-body">' +
                '<input type="hidden" id="nc-coll-form-id" value="">' +
                '<div class="nc-form-group">' +
                    '<label class="nc-form-label">' + NC.__('collection_name', 'Nome da Coleção') + ' *</label>' +
                    '<input type="text" id="nc-coll-form-name" class="nc-form-control" placeholder="' +
                        NC.__('collection_name_placeholder', 'Ex: Semana dos Museus 2026') + '">' +
                '</div>' +
                '<div class="nc-form-group">' +
                    '<label class="nc-form-label">' + NC.__('description', 'Descrição') + '</label>' +
                    '<textarea id="nc-coll-form-desc" class="nc-form-control" rows="3"></textarea>' +
                '</div>' +
                '<div class="nc-form-group">' +
                    '<label class="nc-form-label">' + NC.__('color', 'Cor') + '</label>' +
                    '<input type="hidden" id="nc-coll-form-color" value="#457B9D">' +
                    colorsHtml +
                '</div>' +
            '</div>' +
            '<div class="nc-modal-footer">' +
                '<button class="nc-button nc-button-secondary" onclick="NC.closeModal(\'nc-create-collection-modal\')">' +
                    NC.__('cancel', 'Cancelar') + '</button>' +
                '<button id="nc-coll-form-submit" class="nc-button nc-button-primary" onclick="NC.submitCollectionForm()">' +
                    NC.__('create', 'Criar') + '</button>' +
            '</div>' +
        '</div>' +
    '</div>';
    jQuery('body').append(html);
};

NC.selectCollectionColor = function(color) {
    jQuery('.nc-color-option').removeClass('active');
    jQuery('.nc-color-option[data-color="' + color + '"]').addClass('active');
    jQuery('#nc-coll-form-color').val(color);
};

NC.submitCollectionForm = function() {
    var id = jQuery('#nc-coll-form-id').val();
    var name = jQuery('#nc-coll-form-name').val();
    if (!name) {
        NC.showNotice('warning', NC.__('collection_name_required', 'Nome da coleção é obrigatório'));
        return;
    }

    var data = {
        name: name,
        description: jQuery('#nc-coll-form-desc').val() || '',
        color: jQuery('#nc-coll-form-color').val() || '#457B9D'
    };

    var path = ncData.apiUrl + '/collections';
    var method = 'POST';
    if (id) {
        path += '/' + id;
        method = 'PUT';
    }

    wp.apiFetch({path: path, method: method, data: data}).then(function() {
        NC.showNotice('success', NC.__('collection_saved', 'Coleção salva!'));
        NC.closeModal('nc-create-collection-modal');
        NC.loadCollections(NC._collectionsFilter);
        NC.loadCollectionStats();
    }).catch(function(e) {
        NC.showNotice('error', NC.__('create_collection_error', 'Erro ao criar coleção: ') + (e.message || ''));
    });
};

// ========== Collection Detail Modal ==========

NC.openCollectionDetailModal = function(id) {
    if (!jQuery('#nc-collection-detail-modal').length) {
        var html =
        '<div id="nc-collection-detail-modal" class="nc-modal-overlay">' +
            '<div class="nc-modal" style="max-width:600px;">' +
                '<div class="nc-modal-header">' +
                    '<h3 class="nc-modal-title"><span class="dashicons dashicons-list-view" style="color:var(--nc-accent);"></span> ' +
                        '<span id="nc-detail-title">' + NC.__('collection_items', 'Itens da Coleção') + '</span></h3>' +
                    '<button class="nc-modal-close" onclick="NC.closeModal(\'nc-collection-detail-modal\')">&times;</button>' +
                '</div>' +
                '<div class="nc-modal-body" id="nc-collection-detail-body">' +
                    '<div class="nc-loading"><div class="nc-spinner"></div></div>' +
                '</div>' +
                '<div class="nc-modal-footer">' +
                    '<button class="nc-button nc-button-secondary" onclick="NC.closeModal(\'nc-collection-detail-modal\')">' +
                        NC.__('cancel', 'Cancelar') + '</button>' +
                '</div>' +
            '</div>' +
        '</div>';
        jQuery('body').append(html);
    }

    NC._currentDetailCollectionId = id;
    jQuery('#nc-collection-detail-body').html('<div class="nc-loading"><div class="nc-spinner"></div></div>');
    NC.openModal('nc-collection-detail-modal');

    wp.apiFetch({path: ncData.apiUrl + '/collections/' + id}).then(function(col) {
        jQuery('#nc-detail-title').text(NC.__('collection_items', 'Itens da Coleção') + ': ' + col.name);
    });

    wp.apiFetch({path: ncData.apiUrl + '/collections/' + id + '/items'}).then(function(items) {
        if (!items || items.length === 0) {
            jQuery('#nc-collection-detail-body').html(
                '<div class="nc-empty-state" style="padding:30px 0;">' +
                    '<span class="dashicons dashicons-portfolio"></span>' +
                    '<p>' + NC.__('collection_no_items', 'Adicione itens à coleção antes de agendar') + '</p>' +
                '</div>'
            );
            return;
        }

        var html = '<div class="nc-collection-items-list">';
        items.forEach(function(item, idx) {
            html += '<div class="nc-collection-item-row">';
            html += '<span class="nc-collection-item-order">' + (idx + 1) + '</span>';
            html += '<span class="nc-collection-item-title">' + NC.escapeHtml(item.title || NC.__('untitled', 'Sem título')) + '</span>';
            html += '<div class="nc-collection-item-actions">';
            html += '<button class="nc-button nc-button-primary nc-button-sm" onclick="NC.scheduleCollectionItem(' + item.id + ')" title="' + NC.__('schedule', 'Agendar') + '">' +
                '<span class="dashicons dashicons-calendar-alt"></span></button>';
            html += '<button class="nc-collection-item-remove" onclick="NC.removeItemFromCollection(' + id + ', ' + item.id + ')" title="' + NC.__('remove', 'Remover') + '">' +
                '<span class="dashicons dashicons-no-alt"></span></button>';
            html += '</div>';
            html += '</div>';
        });
        html += '</div>';
        jQuery('#nc-collection-detail-body').html(html);
    }).catch(function() {
        jQuery('#nc-collection-detail-body').html(
            '<div class="nc-notice nc-notice-error">' +
                '<span class="dashicons dashicons-dismiss"></span> ' + NC.__('load_items_error', 'Erro ao carregar itens') +
            '</div>'
        );
    });
};

NC.removeItemFromCollection = function(collectionId, itemId) {
    if (!confirm(NC.__('confirm_remove_item', 'Remover este item da coleção?'))) return;
    wp.apiFetch({
        path: ncData.apiUrl + '/collections/' + collectionId + '/items/' + itemId,
        method: 'DELETE'
    }).then(function() {
        NC.showNotice('success', NC.__('item_removed', 'Item removido!'));
        NC.openCollectionDetailModal(collectionId);
        NC.loadCollections(NC._collectionsFilter);
        NC.loadCollectionStats();
    }).catch(function(e) {
        NC.showNotice('error', NC.__('delete_error', 'Erro ao excluir: ') + (e.message || ''));
    });
};

// ========== Schedule Collection Modal ==========

NC.openScheduleCollectionModal = function(id, itemsCount) {
    if (itemsCount === 0) {
        NC.showNotice('warning', NC.__('collection_no_items', 'Adicione itens à coleção antes de agendar'));
        return;
    }

    if (!jQuery('#nc-schedule-collection-modal').length) {
        var now = new Date();
        now.setMinutes(now.getMinutes() + 30);
        var defaultDate = now.toISOString().slice(0, 16);

        var html =
        '<div id="nc-schedule-collection-modal" class="nc-modal-overlay">' +
            '<div class="nc-modal" style="max-width:500px;">' +
                '<div class="nc-modal-header">' +
                    '<h3 class="nc-modal-title"><span class="dashicons dashicons-calendar-alt" style="color:var(--nc-accent);"></span> ' +
                        NC.__('schedule_collection', 'Agendar Coleção') + '</h3>' +
                    '<button class="nc-modal-close" onclick="NC.closeModal(\'nc-schedule-collection-modal\')">&times;</button>' +
                '</div>' +
                '<div class="nc-modal-body">' +
                    '<div class="nc-notice nc-notice-info" style="margin-bottom:20px;"><span class="dashicons dashicons-info"></span>' +
                        '<span id="nc-schedule-coll-info"></span></div>' +
                    '<div class="nc-form-group">' +
                        '<label class="nc-form-label">' + NC.__('first_publication', 'Primeira publicação') + '</label>' +
                        '<input type="datetime-local" id="nc-schedule-coll-date" class="nc-form-control" value="' + defaultDate + '">' +
                        '<small class="nc-form-help">' + NC.__('first_pub_help', 'Data/hora da primeira publicação') + '</small>' +
                    '</div>' +
                    '<div class="nc-form-group">' +
                        '<label class="nc-form-label">' + NC.__('interval_between', 'Intervalo entre posts') + '</label>' +
                        '<select id="nc-schedule-coll-interval" class="nc-form-control">' +
                            '<option value="30">' + NC.__('every_30min', 'A cada 30 minutos') + '</option>' +
                            '<option value="60" selected>' + NC.__('every_1h', 'A cada 1 hora') + '</option>' +
                            '<option value="120">' + NC.__('every_2h', 'A cada 2 horas') + '</option>' +
                            '<option value="360">' + NC.__('every_6h', 'A cada 6 horas') + '</option>' +
                            '<option value="720">' + NC.__('every_12h', 'A cada 12 horas') + '</option>' +
                            '<option value="1440">' + NC.__('every_24h', 'A cada 24 horas') + '</option>' +
                        '</select>' +
                    '</div>' +
                    '<div id="nc-schedule-coll-timeline" style="margin-top:10px;"></div>' +
                '</div>' +
                '<div class="nc-modal-footer">' +
                    '<button class="nc-button nc-button-secondary" onclick="NC.closeModal(\'nc-schedule-collection-modal\')">' +
                        NC.__('cancel', 'Cancelar') + '</button>' +
                    '<button class="nc-button nc-button-primary" onclick="NC.submitScheduleCollection()">' +
                        '<span class="dashicons dashicons-calendar-alt"></span> ' + NC.__('schedule_all', 'Agendar Todos') + '</button>' +
                '</div>' +
            '</div>' +
        '</div>';
        jQuery('body').append(html);

        jQuery('#nc-schedule-coll-date, #nc-schedule-coll-interval').on('change', function() {
            NC.updateScheduleCollectionTimeline();
        });
    }

    NC._scheduleCollectionId = id;
    NC._scheduleCollectionItemsCount = itemsCount;
    jQuery('#nc-schedule-coll-info').html(
        '<strong>' + itemsCount + '</strong> ' + NC.__('items_will_be_scheduled', 'itens selecionados serão agendados automaticamente com o template configurado.')
    );

    var now = new Date();
    now.setMinutes(now.getMinutes() + 30);
    jQuery('#nc-schedule-coll-date').val(now.toISOString().slice(0, 16));
    jQuery('#nc-schedule-coll-interval').val('60');

    // Set min attribute to prevent retroactive scheduling
    var minNowColl = new Date();
    jQuery('#nc-schedule-coll-date').attr('min',
        minNowColl.getFullYear() + '-' +
        String(minNowColl.getMonth() + 1).padStart(2, '0') + '-' +
        String(minNowColl.getDate()).padStart(2, '0') + 'T' +
        String(minNowColl.getHours()).padStart(2, '0') + ':' +
        String(minNowColl.getMinutes()).padStart(2, '0')
    );

    NC.updateScheduleCollectionTimeline();
    NC.openModal('nc-schedule-collection-modal');
};

NC.updateScheduleCollectionTimeline = function() {
    var startDate = jQuery('#nc-schedule-coll-date').val();
    var interval = parseInt(jQuery('#nc-schedule-coll-interval').val()) || 60;
    var count = NC._scheduleCollectionItemsCount || 0;

    if (!startDate || count === 0) {
        jQuery('#nc-schedule-coll-timeline').html('');
        return;
    }

    var start = new Date(startDate);
    var html = '<div style="background:var(--nc-bg);border-radius:8px;padding:12px;font-size:13px;">';
    html += '<strong>' + NC.__('expected_timeline', 'Cronograma previsto') + ':</strong><br>';
    var max = Math.min(count, 5);
    for (var i = 0; i < max; i++) {
        var d = new Date(start.getTime() + (i * interval * 60000));
        html += '<div style="margin-top:6px;display:flex;align-items:center;gap:8px;">';
        html += '<span class="nc-collection-item-order" style="width:20px;height:20px;font-size:10px;">' + (i + 1) + '</span>';
        html += '<span>' + d.toLocaleString('pt-BR') + '</span>';
        html += '</div>';
    }
    if (count > 5) {
        html += '<div style="margin-top:8px;color:var(--nc-text-light);">... +' + (count - 5) + ' ' + NC.__('items_label', 'itens') + '</div>';
    }
    var last = new Date(start.getTime() + ((count - 1) * interval * 60000));
    html += '<div style="margin-top:10px;padding-top:8px;border-top:1px solid var(--nc-border);font-size:12px;color:var(--nc-text-light);">' +
        NC.__('from', 'De') + ' ' + start.toLocaleString('pt-BR') + ' ' + NC.__('until', 'até') + ' ' + last.toLocaleString('pt-BR') +
        '</div>';
    html += '</div>';
    jQuery('#nc-schedule-coll-timeline').html(html);
};

NC.submitScheduleCollection = function() {
    var startDate = jQuery('#nc-schedule-coll-date').val();
    var interval = parseInt(jQuery('#nc-schedule-coll-interval').val()) || 60;

    if (!startDate) {
        NC.showNotice('warning', NC.__('configure_first_date', 'Configure a data da primeira publicação'));
        return;
    }

    var nowCollCheck = new Date();
    nowCollCheck.setSeconds(nowCollCheck.getSeconds() - 60); // 60s tolerance
    if (new Date(startDate) < nowCollCheck) {
        NC.showNotice('warning', NC.__('date_not_retroactive', 'A data não pode ser retroativa. Selecione o horário atual ou futuro.'));
        return;
    }

    NC.showNotice('info', NC.__('scheduling', 'Agendando') + ' ' + NC._scheduleCollectionItemsCount + ' ' + NC.__('publications', 'publicações...'));

    wp.apiFetch({
        path: ncData.apiUrl + '/collections/' + NC._scheduleCollectionId + '/schedule',
        method: 'POST',
        data: {
            scheduled_for: startDate.replace('T', ' ') + ':00',
            interval_minutes: interval
        }
    }).then(function(response) {
        NC.showNotice('success', (response.publications_created || 0) + ' ' + NC.__('publications_scheduled', 'publicações agendadas com sucesso!'));
        NC.closeModal('nc-schedule-collection-modal');
        NC.loadCollections(NC._collectionsFilter);
        NC.loadCollectionStats();
    }).catch(function(e) {
        NC.showNotice('error', NC.__('schedule_error', 'Erro ao agendar: ') + (e.message || ''));
    });
};

// ========== Schedule Individual Collection Item ==========

NC.scheduleCollectionItem = function(itemId) {
    NC.closeModal('nc-collection-detail-modal');
    NC.openScheduleModal(itemId);
};

// ========== Delete Collection ==========

NC.deleteCollection = function(id) {
    if (!confirm(NC.__('confirm_delete_collection', 'Excluir esta coleção? Os itens não serão removidos.'))) return;

    wp.apiFetch({
        path: ncData.apiUrl + '/collections/' + id,
        method: 'DELETE'
    }).then(function() {
        NC.showNotice('success', NC.__('collection_deleted', 'Coleção excluída!'));
        NC.loadCollections(NC._collectionsFilter);
        NC.loadCollectionStats();
    }).catch(function(e) {
        NC.showNotice('error', NC.__('delete_error', 'Erro ao excluir: ') + (e.message || ''));
    });
};
</script>
