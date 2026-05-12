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
            '<div class="nc-modal" style="max-width:700px;">' +
                '<div class="nc-modal-header">' +
                    '<h3 class="nc-modal-title"><span class="dashicons dashicons-list-view" style="color:var(--nc-accent);"></span> ' +
                        '<span id="nc-detail-title">' + NC.__('collection_items', 'Itens da Coleção') + '</span></h3>' +
                    '<button class="nc-modal-close" onclick="NC.closeModal(\'nc-collection-detail-modal\')">&times;</button>' +
                '</div>' +
                '<div class="nc-modal-body">' +
                    '<div class="nc-detail-toolbar">' +
                        '<span class="nc-detail-hint">' +
                            '<span class="dashicons dashicons-move"></span> ' +
                            NC.__('drag_to_reorder', 'Arraste para reordenar a publicação') +
                        '</span>' +
                        '<span id="nc-detail-item-count"></span>' +
                    '</div>' +
                    '<div id="nc-collection-detail-body">' +
                        '<div class="nc-loading"><div class="nc-spinner"></div></div>' +
                    '</div>' +
                '</div>' +
                '<div class="nc-modal-footer">' +
                    '<button class="nc-button nc-button-secondary" onclick="NC.closeModal(\'nc-collection-detail-modal\')">' +
                        NC.__('close', 'Fechar') + '</button>' +
                '</div>' +
            '</div>' +
        '</div>';
        jQuery('body').append(html);
    }

    NC._currentDetailCollectionId = id;
    NC._detailItems = [];
    jQuery('#nc-collection-detail-body').html('<div class="nc-loading"><div class="nc-spinner"></div></div>');
    jQuery('#nc-detail-item-count').text('');
    NC.openModal('nc-collection-detail-modal');

    wp.apiFetch({path: ncData.apiUrl + '/collections/' + id}).then(function(col) {
        jQuery('#nc-detail-title').text(col.name);
    });

    wp.apiFetch({path: ncData.apiUrl + '/collections/' + id + '/items'}).then(function(items) {
        if (!items || items.length === 0) {
            jQuery('#nc-collection-detail-body').html(
                '<div class="nc-empty-state" style="padding:30px 0;">' +
                    '<span class="dashicons dashicons-portfolio"></span>' +
                    '<p>' + NC.__('collection_no_items', 'Adicione itens à coleção antes de agendar') + '</p>' +
                '</div>'
            );
            jQuery('.nc-detail-toolbar').hide();
            return;
        }

        jQuery('.nc-detail-toolbar').show();
        NC._detailItems = items;
        jQuery('#nc-detail-item-count').text(items.length + ' ' + NC.__('items_label', 'itens'));
        NC.renderCollectionDetailItems(id, items);
    }).catch(function() {
        jQuery('#nc-collection-detail-body').html(
            '<div class="nc-notice nc-notice-error">' +
                '<span class="dashicons dashicons-dismiss"></span> ' + NC.__('load_items_error', 'Erro ao carregar itens') +
            '</div>'
        );
    });
};

NC.renderCollectionDetailItems = function(collectionId, items) {
    var html = '<div class="nc-collection-items-list" id="nc-sortable-list">';
    items.forEach(function(item, idx) {
        var thumb = item.image_url
            ? '<img src="' + NC.escapeHtml(item.image_url) + '" class="nc-detail-thumb" alt="">'
            : '<span class="nc-detail-thumb-placeholder dashicons dashicons-format-image"></span>';
        var date = item.published_date ? new Date(item.published_date).toLocaleDateString('pt-BR') : '';
        var author = item.author ? NC.escapeHtml(item.author) : '';

        html += '<div class="nc-collection-item-row nc-draggable" draggable="true" data-item-id="' + item.id + '">';
        html += '<span class="nc-drag-handle" title="' + NC.__('drag_to_move', 'Arraste para mover') + '">' +
            '<span class="dashicons dashicons-menu"></span></span>';
        html += '<span class="nc-collection-item-order">' + (idx + 1) + '</span>';
        html += thumb;
        html += '<div class="nc-detail-item-info">';
        html += '<span class="nc-collection-item-title">' + NC.escapeHtml(item.title || NC.__('untitled', 'Sem título')) + '</span>';
        if (date || author) {
            html += '<span class="nc-detail-item-meta">';
            if (author) html += '<span class="dashicons dashicons-admin-users"></span> ' + author;
            if (date && author) html += ' · ';
            if (date) html += '<span class="dashicons dashicons-calendar"></span> ' + date;
            html += '</span>';
        }
        html += '</div>';
        html += '<div class="nc-collection-item-actions">';
        html += '<button class="nc-button nc-button-primary nc-button-sm" onclick="NC.scheduleCollectionItem(' + item.id + ')" title="' + NC.__('schedule', 'Agendar') + '">' +
            '<span class="dashicons dashicons-calendar-alt"></span></button>';
        html += '<button class="nc-collection-item-remove" onclick="NC.removeItemFromCollection(' + collectionId + ', ' + item.id + ')" title="' + NC.__('remove', 'Remover') + '">' +
            '<span class="dashicons dashicons-no-alt"></span></button>';
        html += '</div>';
        html += '</div>';
    });
    html += '</div>';
    jQuery('#nc-collection-detail-body').html(html);
    NC.initDragAndDrop(collectionId);
};

NC.initDragAndDrop = function(collectionId) {
    var list = document.getElementById('nc-sortable-list');
    if (!list) return;

    var dragItem = null;
    var placeholder = document.createElement('div');
    placeholder.className = 'nc-drag-placeholder';

    list.addEventListener('dragstart', function(e) {
        var row = e.target.closest('.nc-draggable');
        if (!row) return;
        dragItem = row;
        row.classList.add('nc-dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', '');
    });

    list.addEventListener('dragend', function(e) {
        if (dragItem) {
            dragItem.classList.remove('nc-dragging');
            dragItem = null;
        }
        if (placeholder.parentNode) {
            placeholder.parentNode.removeChild(placeholder);
        }
    });

    list.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        var target = e.target.closest('.nc-draggable');
        if (!target || target === dragItem) {
            // If hovering over placeholder area at end of list
            if (e.target === list || e.target === placeholder) {
                if (placeholder.parentNode !== list) list.appendChild(placeholder);
            }
            return;
        }

        var rect = target.getBoundingClientRect();
        var midY = rect.top + rect.height / 2;
        if (e.clientY < midY) {
            list.insertBefore(placeholder, target);
        } else {
            list.insertBefore(placeholder, target.nextSibling);
        }
    });

    list.addEventListener('drop', function(e) {
        e.preventDefault();
        if (!dragItem) return;

        if (placeholder.parentNode) {
            list.insertBefore(dragItem, placeholder);
            placeholder.parentNode.removeChild(placeholder);
        }

        // Update order numbers
        var rows = list.querySelectorAll('.nc-draggable');
        var itemIds = [];
        rows.forEach(function(row, idx) {
            row.querySelector('.nc-collection-item-order').textContent = idx + 1;
            itemIds.push(parseInt(row.getAttribute('data-item-id')));
        });

        // Save new order
        wp.apiFetch({
            path: ncData.apiUrl + '/collections/' + collectionId + '/reorder',
            method: 'POST',
            data: { item_ids: itemIds }
        }).then(function() {
            NC.showNotice('success', NC.__('order_saved', 'Ordem salva!'));
        }).catch(function() {
            NC.showNotice('error', NC.__('order_save_error', 'Erro ao salvar ordem'));
        });
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

    // Remove previous modal to rebuild with fresh accounts
    jQuery('#nc-schedule-collection-modal').remove();

    NC._loadAccountsForSelector(function(accounts) {
        var now = new Date();
        now.setMinutes(now.getMinutes() + 30);
        var defaultDate = now.toISOString().slice(0, 16);

        var accountsHtml = NC._buildAccountSelector(accounts, 'nc-sched-coll', false);

        var html =
        '<div id="nc-schedule-collection-modal" class="nc-modal-overlay">' +
            '<div class="nc-modal" style="max-width:540px;">' +
                '<div class="nc-modal-header">' +
                    '<h3 class="nc-modal-title"><span class="dashicons dashicons-calendar-alt" style="color:var(--nc-accent);"></span> ' +
                        NC.__('schedule_collection', 'Agendar Coleção') + '</h3>' +
                    '<button class="nc-modal-close" onclick="NC.closeModal(\'nc-schedule-collection-modal\')">&times;</button>' +
                '</div>' +
                '<div class="nc-modal-body">' +
                    '<div class="nc-notice nc-notice-info" style="margin-bottom:20px;"><span class="dashicons dashicons-info"></span>' +
                        '<span id="nc-schedule-coll-info"></span></div>' +

                    // Thread toggle
                    '<div class="nc-form-group">' +
                        '<label class="nc-form-label" style="display:flex;align-items:center;gap:10px;cursor:pointer;">' +
                            '<input type="checkbox" id="nc-schedule-coll-thread" style="width:18px;height:18px;accent-color:var(--nc-accent);">' +
                            '<span class="dashicons dashicons-admin-comments" style="color:var(--nc-accent);"></span>' +
                            ' ' + NC.__('publish_as_thread', 'Publicar como thread') +
                        '</label>' +
                        '<small class="nc-form-help" style="margin-left:28px;">' +
                            NC.__('thread_help', 'Os itens serão publicados como uma sequência de respostas (thread) no Mastodon. Ideal para contar uma história ou exibir uma coleção conectada.') +
                        '</small>' +
                        '<div id="nc-thread-info" style="display:none;margin-top:8px;margin-left:28px;padding:10px;background:var(--nc-secondary);border-radius:8px;font-size:12px;color:var(--nc-text);">' +
                            '<span class="dashicons dashicons-info" style="font-size:14px;width:14px;height:14px;margin-right:4px;color:var(--nc-accent);"></span>' +
                            NC.__('thread_info', 'No modo thread, cada item é publicado como resposta ao anterior, criando uma conversa encadeada. Requer uma única conta Mastodon.') +
                        '</div>' +
                    '</div>' +

                    // Destinos: Mastodon / WordPress / Ambos
                    '<div class="nc-form-group">' +
                        '<label class="nc-form-label">' +
                            '<span class="dashicons dashicons-share" style="font-size:16px;vertical-align:text-bottom;"></span> ' +
                            NC.__('destinations', 'Destinos da publicação') +
                        '</label>' +
                        '<div style="display:flex;gap:14px;align-items:center;flex-wrap:wrap;">' +
                            '<label style="display:flex;align-items:center;gap:6px;cursor:pointer;">' +
                                '<input type="checkbox" class="nc-coll-dest-cb" value="mastodon" checked> ' +
                                '<span class="dashicons dashicons-admin-site-alt3" style="color:var(--nc-accent);"></span> Mastodon' +
                            '</label>' +
                            '<label style="display:flex;align-items:center;gap:6px;cursor:pointer;">' +
                                '<input type="checkbox" class="nc-coll-dest-cb" value="wordpress"> ' +
                                '<span class="dashicons dashicons-wordpress" style="color:var(--nc-accent);"></span> WordPress' +
                            '</label>' +
                        '</div>' +
                    '</div>' +

                    // WP category selector
                    '<div class="nc-form-group" id="nc-coll-wp-group" style="display:none;">' +
                        '<label class="nc-form-label">' +
                            '<span class="dashicons dashicons-category" style="font-size:16px;vertical-align:text-bottom;"></span> ' +
                            NC.__('wp_category', 'Categoria WordPress') + ' *' +
                        '</label>' +
                        '<select id="nc-coll-wp-category" class="nc-form-control">' +
                            '<option value="">' + NC.__('loading', 'Carregando...') + '</option>' +
                        '</select>' +
                        '<small class="nc-form-help">' + NC.__('wp_category_collection_help', 'Cada item da coleção será publicado como post nesta categoria.') + '</small>' +
                    '</div>' +

                    // Mastodon account selector (hidden if only WP)
                    '<div id="nc-coll-mastodon-group">' +
                        accountsHtml +
                    '</div>' +

                    '<div class="nc-form-group">' +
                        '<label class="nc-form-label">' + NC.__('first_publication', 'Primeira publicação') + '</label>' +
                        '<input type="datetime-local" id="nc-schedule-coll-date" class="nc-form-control" value="' + defaultDate + '">' +
                        '<small class="nc-form-help">' + NC.__('first_pub_help', 'Data/hora da primeira publicação') + '</small>' +
                    '</div>' +
                    '<div class="nc-form-group">' +
                        '<label class="nc-form-label">' + NC.__('interval_between', 'Intervalo entre posts') + '</label>' +
                        '<select id="nc-schedule-coll-interval" class="nc-form-control">' +
                            '<option value="1">' + NC.__('every_1min', 'A cada 1 minuto (thread rápida)') + '</option>' +
                            '<option value="2">' + NC.__('every_2min', 'A cada 2 minutos') + '</option>' +
                            '<option value="5">' + NC.__('every_5min', 'A cada 5 minutos') + '</option>' +
                            '<option value="15">' + NC.__('every_15min', 'A cada 15 minutos') + '</option>' +
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
                    '<button class="nc-button nc-button-primary" id="nc-schedule-coll-submit" onclick="NC.submitScheduleCollection()">' +
                        '<span class="dashicons dashicons-calendar-alt"></span> ' + NC.__('schedule_all', 'Agendar Todos') + '</button>' +
                '</div>' +
            '</div>' +
        '</div>';
        jQuery('body').append(html);

        jQuery('#nc-schedule-coll-date, #nc-schedule-coll-interval').on('change', function() {
            NC.updateScheduleCollectionTimeline();
        });

        // Destination toggle handler
        jQuery('.nc-coll-dest-cb').on('change', function() {
            var $wp = jQuery('.nc-coll-dest-cb[value="wordpress"]');
            var $masto = jQuery('.nc-coll-dest-cb[value="mastodon"]');
            var wpOn = $wp.is(':checked');
            var mastoOn = $masto.is(':checked');

            if (!wpOn && !mastoOn) {
                $masto.prop('checked', true);
                mastoOn = true;
            }

            jQuery('#nc-coll-wp-group').toggle(wpOn);
            jQuery('#nc-coll-mastodon-group').toggle(mastoOn);

            // Lazy-load categorias
            if (wpOn && jQuery('#nc-coll-wp-category option').length <= 1) {
                wp.apiFetch({path: ncData.apiUrl + '/wp-categories'}).then(function(data) {
                    var html = '<option value="">' + NC.__('select_category', 'Selecione uma categoria...') + '</option>';
                    (data.categories || []).forEach(function(c) {
                        html += '<option value="' + c.id + '">' + NC.escapeHtml(c.name) + ' (' + c.count + ')</option>';
                    });
                    jQuery('#nc-coll-wp-category').html(html);
                });
            }

            // Se só WP, desabilita thread (thread é só Mastodon)
            if (!mastoOn) {
                jQuery('#nc-schedule-coll-thread').prop('checked', false).prop('disabled', true);
                jQuery('#nc-thread-info').hide();
            } else {
                jQuery('#nc-schedule-coll-thread').prop('disabled', false);
            }
        });

        // Thread toggle behavior
        jQuery('#nc-schedule-coll-thread').on('change', function() {
            var isThread = jQuery(this).is(':checked');
            jQuery('#nc-thread-info').toggle(isThread);

            if (isThread) {
                // Default to 2min interval for threads
                jQuery('#nc-schedule-coll-interval').val('2');
                // Update button text
                jQuery('#nc-schedule-coll-submit').html(
                    '<span class="dashicons dashicons-admin-comments"></span> ' +
                    NC.__('schedule_as_thread', 'Agendar como Thread')
                );
            } else {
                jQuery('#nc-schedule-coll-interval').val('60');
                jQuery('#nc-schedule-coll-submit').html(
                    '<span class="dashicons dashicons-calendar-alt"></span> ' +
                    NC.__('schedule_all', 'Agendar Todos')
                );
            }
            NC.updateScheduleCollectionTimeline();
        });

        NC._scheduleCollectionId = id;
        NC._scheduleCollectionItemsCount = itemsCount;
        jQuery('#nc-schedule-coll-info').html(
            '<strong>' + itemsCount + '</strong> ' + NC.__('items_will_be_scheduled', 'itens serão agendados para publicação.')
        );

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
    });
};

NC.updateScheduleCollectionTimeline = function() {
    var startDate = jQuery('#nc-schedule-coll-date').val();
    var interval = parseInt(jQuery('#nc-schedule-coll-interval').val()) || 60;
    var count = NC._scheduleCollectionItemsCount || 0;
    var isThread = jQuery('#nc-schedule-coll-thread').is(':checked');

    if (!startDate || count === 0) {
        jQuery('#nc-schedule-coll-timeline').html('');
        return;
    }

    var start = new Date(startDate);
    var html = '<div style="background:var(--nc-bg);border-radius:8px;padding:12px;font-size:13px;">';

    if (isThread) {
        html += '<strong><span class="dashicons dashicons-admin-comments" style="font-size:14px;width:14px;height:14px;color:var(--nc-accent);"></span> ' +
            NC.__('thread_timeline', 'Thread prevista') + ':</strong><br>';
    } else {
        html += '<strong>' + NC.__('expected_timeline', 'Cronograma previsto') + ':</strong><br>';
    }

    var max = Math.min(count, 5);
    for (var i = 0; i < max; i++) {
        var d = new Date(start.getTime() + (i * interval * 60000));
        html += '<div style="margin-top:6px;display:flex;align-items:center;gap:8px;' +
            (isThread && i > 0 ? 'margin-left:16px;border-left:2px solid var(--nc-accent);padding-left:10px;' : '') + '">';

        if (isThread) {
            if (i === 0) {
                html += '<span class="dashicons dashicons-admin-post" style="font-size:14px;width:14px;height:14px;color:var(--nc-accent);"></span>';
            } else {
                html += '<span class="dashicons dashicons-format-status" style="font-size:12px;width:12px;height:12px;color:var(--nc-text-light);"></span>';
            }
        } else {
            html += '<span class="nc-collection-item-order" style="width:20px;height:20px;font-size:10px;">' + (i + 1) + '</span>';
        }

        html += '<span>' + d.toLocaleString('pt-BR') + '</span>';
        if (isThread && i === 0) {
            html += ' <small style="color:var(--nc-accent);">(' + NC.__('thread_start', 'início da thread') + ')</small>';
        }
        html += '</div>';
    }
    if (count > 5) {
        var extraStyle = isThread ? 'margin-left:28px;' : '';
        html += '<div style="margin-top:8px;color:var(--nc-text-light);' + extraStyle + '">... +' + (count - 5) + ' ' +
            (isThread ? NC.__('replies_label', 'respostas') : NC.__('items_label', 'itens')) + '</div>';
    }
    var last = new Date(start.getTime() + ((count - 1) * interval * 60000));
    html += '<div style="margin-top:10px;padding-top:8px;border-top:1px solid var(--nc-border);font-size:12px;color:var(--nc-text-light);">' +
        NC.__('from', 'De') + ' ' + start.toLocaleString('pt-BR') + ' ' + NC.__('until', 'até') + ' ' + last.toLocaleString('pt-BR') +
        '</div>';

    if (isThread) {
        html += '<div style="margin-top:8px;font-size:11px;color:var(--nc-text-light);">' +
            '<span class="dashicons dashicons-info" style="font-size:12px;width:12px;height:12px;"></span> ' +
            NC.__('thread_note', 'Cada post será uma resposta ao anterior, formando uma conversa encadeada visível no Mastodon.') +
            '</div>';
    }

    html += '</div>';
    jQuery('#nc-schedule-coll-timeline').html(html);
};

NC.submitScheduleCollection = function() {
    var startDate = jQuery('#nc-schedule-coll-date').val();
    var interval = parseInt(jQuery('#nc-schedule-coll-interval').val()) || 60;
    var isThread = jQuery('#nc-schedule-coll-thread').is(':checked');

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

    // Get destinations
    var destinations = [];
    jQuery('.nc-coll-dest-cb:checked').each(function() {
        destinations.push(jQuery(this).val());
    });
    if (destinations.length === 0) destinations = ['mastodon'];

    // Validate WP category if WordPress selected
    var wpCategoryId = 0;
    if (destinations.indexOf('wordpress') !== -1) {
        wpCategoryId = parseInt(jQuery('#nc-coll-wp-category').val()) || 0;
        if (!wpCategoryId) {
            NC.showNotice('warning', NC.__('wp_category_required', 'Selecione uma categoria do WordPress.'));
            return;
        }
    }

    // Get selected Mastodon account
    var accountId = jQuery('#nc-sched-coll-account-select').val();
    var accountIds = accountId ? [parseInt(accountId)] : [];

    var modeLabel = isThread
        ? NC.__('scheduling_thread', 'Agendando thread com')
        : NC.__('scheduling', 'Agendando');
    NC.showNotice('info', modeLabel + ' ' + NC._scheduleCollectionItemsCount + ' ' + NC.__('publications', 'publicações...'));

    var payload = {
        scheduled_for: startDate.replace('T', ' ') + ':00',
        interval_minutes: interval,
        as_thread: isThread,
        mastodon_account_ids: accountIds,
        destinations: destinations
    };
    if (wpCategoryId > 0) payload.wp_category_id = wpCategoryId;

    wp.apiFetch({
        path: ncData.apiUrl + '/collections/' + NC._scheduleCollectionId + '/schedule',
        method: 'POST',
        data: payload
    }).then(function(response) {
        var dests = response.destinations || [];
        var msg = (response.publications_created || 0) + ' publicações agendadas';
        if (dests.length > 0) {
            msg += ' em ' + dests.map(function(d) { return d === 'mastodon' ? 'Mastodon' : 'WordPress'; }).join(' + ');
        }
        if (response.as_thread) msg += ' (thread)';
        NC.showNotice('success', msg);
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
