(function($) {
    'use strict';

    window.NC = {
        _viewMode: 'grid',
        _currentCuratedTab: 0,
        _bulkItems: [],

        init: function() {
            this._viewMode = localStorage.getItem('nc_view_mode') || 'grid';
            this.initTabs();
            this.initModalClose();
            this.setupApiFetch();
        },

        // ========== i18n Helper ==========

        __: function(key, fallback) {
            if (typeof ncData !== 'undefined' && ncData.i18n && ncData.i18n[key]) {
                return ncData.i18n[key];
            }
            return fallback || key;
        },

        // ========== Security Helper ==========

        escapeHtml: function(str) {
            if (!str) return '';
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        },

        setupApiFetch: function() {
            if (typeof wp !== 'undefined' && typeof wp.apiFetch !== 'undefined') {
                wp.apiFetch.use(wp.apiFetch.createNonceMiddleware(ncData.nonce));
            }
        },

        initTabs: function() {
            $('.nc-tab').on('click', function() {
                var tab = $(this).data('tab');
                $('.nc-tab').removeClass('active');
                $(this).addClass('active');
                $('.nc-tab-content').hide();
                $('#' + tab).show();
            });
        },

        initModalClose: function() {
            $(document).on('click', '.nc-modal-overlay', function(e) {
                if (e.target === this) {
                    $(this).removeClass('active');
                }
            });
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    $('.nc-modal-overlay.active').removeClass('active');
                    $('.nc-container').removeClass('nc-sidebar-open');
                }
            });
        },

        // ========== Sidebar ==========

        toggleSidebar: function() {
            $('.nc-container').toggleClass('nc-sidebar-open');
        },

        // ========== Modals ==========

        openModal: function(modalId) {
            $('#' + modalId).addClass('active');
        },

        closeModal: function(modalId) {
            $('#' + modalId).removeClass('active');
        },

        // ========== View Mode ==========

        setViewMode: function(mode) {
            NC._viewMode = mode;
            localStorage.setItem('nc_view_mode', mode);
            $('#nc-view-toggle .nc-view-btn').removeClass('active');
            $('#nc-view-toggle .nc-view-btn[data-view="' + mode + '"]').addClass('active');
            if (typeof NC.loadItems === 'function') {
                NC.loadItems(NC._currentCuratedTab);
            }
        },

        // ========== Notices ==========

        showNotice: function(type, message) {
            var noticeClass = type === 'success' ? 'nc-notice-success' :
                              type === 'error' ? 'nc-notice-error' :
                              type === 'warning' ? 'nc-notice-warning' : 'nc-notice-info';
            var icon = type === 'success' ? 'yes-alt' :
                        type === 'error' ? 'dismiss' :
                        type === 'warning' ? 'warning' : 'info';

            var $notice = $('<div class="nc-notice ' + noticeClass + '" style="position:fixed;top:50px;right:20px;z-index:999999;min-width:300px;animation:slideInRight 0.3s ease;"></div>');
            $notice.append($('<span class="dashicons dashicons-' + icon + '"></span>'));
            $notice.append($('<span></span>').text(message));
            $('body').append($notice);
            setTimeout(function() {
                $notice.fadeOut(300, function() { $(this).remove(); });
            }, 5000);
        },

        // ========== Sources ==========

        collectNow: function(sourceId) {
            this.showNotice('info', NC.__('collecting', 'Iniciando coleta...'));
            wp.apiFetch({
                path: ncData.apiUrl + '/sources/' + sourceId + '/collect',
                method: 'POST'
            }).then(function(response) {
                NC.showNotice('success', (response.items_collected || 0) + ' ' + NC.__('items_collected', 'itens coletados!'));
                if (typeof NC.loadSources === 'function') {
                    setTimeout(function() { NC.loadSources(); }, 1000);
                }
            }).catch(function(error) {
                NC.showNotice('error', NC.__('collection_error', 'Erro na coleta: ') + (error.message || ''));
            });
        },

        collectAll: function() {
            if (!confirm(NC.__('confirm_collect_all', 'Iniciar coleta de todas as fontes ativas?'))) return;
            this.showNotice('info', NC.__('collecting_all', 'Coletando de todas as fontes...'));

            wp.apiFetch({path: ncData.apiUrl + '/sources'}).then(function(sources) {
                var activeSources = sources.filter(function(s) { return s.status === 'active'; });
                if (activeSources.length === 0) {
                    NC.showNotice('warning', NC.__('no_active_sources', 'Nenhuma fonte ativa encontrada'));
                    return;
                }
                var promises = activeSources.map(function(source) {
                    return wp.apiFetch({
                        path: ncData.apiUrl + '/sources/' + source.id + '/collect',
                        method: 'POST'
                    });
                });
                Promise.all(promises).then(function(results) {
                    var total = results.reduce(function(sum, r) { return sum + (r.items_collected || 0); }, 0);
                    NC.showNotice('success', total + ' ' + NC.__('items_collected_from', 'itens coletados de ') + activeSources.length + ' ' + NC.__('sources', 'fontes!'));
                    setTimeout(function() { location.reload(); }, 2000);
                }).catch(function() {
                    NC.showNotice('error', NC.__('collection_error_some', 'Erro ao coletar de algumas fontes'));
                });
            }).catch(function(error) {
                NC.showNotice('error', NC.__('sources_load_error', 'Erro ao carregar fontes: ') + (error.message || ''));
            });
        },

        // ========== Curation ==========

        curateItem: function(itemId) {
            wp.apiFetch({
                path: ncData.apiUrl + '/items/' + itemId + '/curate',
                method: 'POST'
            }).then(function() {
                NC.showNotice('success', NC.__('item_approved', 'Item aprovado!'));
                $('[data-item-id="' + itemId + '"]').fadeOut(300, function() { $(this).remove(); });
                var $counter = $('#nc-uncurated-count');
                if ($counter.length) {
                    var current = parseInt($counter.text()) || 0;
                    $counter.text(Math.max(0, current - 1));
                }
                var $curatedCounter = $('#nc-curated-count');
                if ($curatedCounter.length) {
                    var current2 = parseInt($curatedCounter.text()) || 0;
                    $curatedCounter.text(current2 + 1);
                }
            }).catch(function(error) {
                NC.showNotice('error', NC.__('approve_error', 'Erro ao aprovar: ') + (error.message || ''));
            });
        },

        bulkCurate: function() {
            var $checked = $('.nc-item-checkbox:checked');
            if ($checked.length === 0) {
                this.showNotice('warning', NC.__('select_at_least_one', 'Selecione ao menos um item'));
                return;
            }
            if (!confirm(NC.__('confirm_approve', 'Aprovar') + ' ' + $checked.length + ' ' + NC.__('selected_items', 'itens selecionados?'))) return;

            var itemIds = $checked.map(function() { return $(this).val(); }).get();
            var promises = itemIds.map(function(id) {
                return wp.apiFetch({
                    path: ncData.apiUrl + '/items/' + id + '/curate',
                    method: 'POST'
                });
            });
            Promise.all(promises).then(function() {
                NC.showNotice('success', itemIds.length + ' ' + NC.__('items_approved', 'itens aprovados!'));
                setTimeout(function() { location.reload(); }, 1500);
            }).catch(function() {
                NC.showNotice('error', NC.__('approve_items_error', 'Erro ao aprovar itens'));
            });
        },

        selectAll: function(checkbox) {
            $('.nc-item-checkbox').prop('checked', checkbox.checked);
            var count = $('.nc-item-checkbox:checked').length;
            if (count > 0) {
                $('#nc-selected-count').show();
                $('#nc-selected-num').text(count);
            } else {
                $('#nc-selected-count').hide();
            }
        },

        // ========== Schedule Modal ==========

        openScheduleModal: function(itemId) {
            if (!$('#nc-schedule-modal').length) {
                this.createScheduleModal();
            }

            $('#nc-schedule-form')[0].reset();
            $('#nc-schedule-item-select').prop('disabled', false);
            $('#nc-schedule-preview-text').text('');
            $('#nc-schedule-char-count').text('0');

            var now = new Date();
            now.setHours(now.getHours() + 1, 0, 0, 0);
            var y = now.getFullYear();
            var m = String(now.getMonth() + 1).padStart(2, '0');
            var d = String(now.getDate()).padStart(2, '0');
            var h = String(now.getHours()).padStart(2, '0');
            $('#nc-schedule-datetime').val(y + '-' + m + '-' + d + 'T' + h + ':00');

            this.loadApprovedItems(itemId);
            this.openModal('nc-schedule-modal');
        },

        createScheduleModal: function() {
            var modalHtml =
            '<div id="nc-schedule-modal" class="nc-modal-overlay">' +
                '<div class="nc-modal" style="max-width:700px;">' +
                    '<div class="nc-modal-header">' +
                        '<h3 class="nc-modal-title">' +
                            '<span class="dashicons dashicons-calendar-alt" style="color:var(--nc-accent);"></span> ' +
                            NC.__('schedule_publication', 'Agendar Publicação') +
                        '</h3>' +
                        '<button class="nc-modal-close" onclick="NC.closeModal(\'nc-schedule-modal\')">&times;</button>' +
                    '</div>' +
                    '<div class="nc-modal-body">' +
                        '<form id="nc-schedule-form">' +
                            '<div class="nc-form-group">' +
                                '<label class="nc-form-label">' + NC.__('approved_item', 'Item aprovado') + ' *</label>' +
                                '<select id="nc-schedule-item-select" class="nc-form-control" required>' +
                                    '<option value="">' + NC.__('loading_approved', 'Carregando itens aprovados...') + '</option>' +
                                '</select>' +
                                '<span class="nc-form-help">' + NC.__('select_approved_item', 'Selecione um item aprovado na curadoria') + '</span>' +
                            '</div>' +
                            '<div class="nc-form-group">' +
                                '<label class="nc-form-label">' + NC.__('post_content', 'Conteúdo do Post') + ' *</label>' +
                                '<textarea id="nc-schedule-content" class="nc-form-control" rows="6" required' +
                                    ' placeholder="' + NC.__('content_auto_fill', 'O conteúdo será preenchido automaticamente ao selecionar um item...') + '"></textarea>' +
                                '<div class="nc-char-counter">' +
                                    '<span id="nc-schedule-char-count">0</span> / 500 ' + NC.__('characters', 'caracteres') +
                                '</div>' +
                                '<span class="nc-form-help">' + NC.__('edit_mastodon_text', 'Edite o texto que será publicado no Mastodon') + '</span>' +
                            '</div>' +
                            '<div class="nc-schedule-row">' +
                                '<div class="nc-form-group" style="flex:1;">' +
                                    '<label class="nc-form-label">' + NC.__('datetime', 'Data e Hora') + ' *</label>' +
                                    '<input type="datetime-local" id="nc-schedule-datetime" class="nc-form-control" required>' +
                                '</div>' +
                                '<div class="nc-form-group" style="flex:1;">' +
                                    '<label class="nc-form-label">' + NC.__('options', 'Opções') + '</label>' +
                                    '<label class="nc-schedule-option">' +
                                        '<input type="checkbox" id="nc-schedule-include-image" checked>' +
                                        '<span>' + NC.__('include_image', 'Incluir imagem (se disponível)') + '</span>' +
                                    '</label>' +
                                '</div>' +
                            '</div>' +
                            '<div class="nc-form-group">' +
                                '<label class="nc-form-label">Preview</label>' +
                                '<div class="nc-schedule-preview">' +
                                    '<div class="nc-schedule-preview-header">' +
                                        '<span class="dashicons dashicons-admin-site-alt3"></span>' +
                                        '<strong>Mastodon Post</strong>' +
                                    '</div>' +
                                    '<p id="nc-schedule-preview-text"></p>' +
                                '</div>' +
                            '</div>' +
                        '</form>' +
                    '</div>' +
                    '<div class="nc-modal-footer">' +
                        '<button class="nc-button nc-button-secondary" onclick="NC.closeModal(\'nc-schedule-modal\')">' +
                            NC.__('cancel', 'Cancelar') +
                        '</button>' +
                        '<button class="nc-button nc-button-primary" onclick="NC.submitSchedule()">' +
                            '<span class="dashicons dashicons-calendar-alt"></span> ' + NC.__('schedule_publication', 'Agendar Publicação') +
                        '</button>' +
                    '</div>' +
                '</div>' +
            '</div>';

            $('body').append(modalHtml);

            $('#nc-schedule-content').on('input', function() {
                NC.updateCharCounter();
                NC.updateSchedulePreview();
            });

            $('#nc-schedule-item-select').on('change', function() {
                var itemId = $(this).val();
                if (itemId) {
                    NC.loadItemContent(itemId);
                }
            });
        },

        loadApprovedItems: function(selectedId) {
            var $select = $('#nc-schedule-item-select');
            $select.html('<option value="">' + NC.__('loading', 'Carregando...') + '</option>');

            wp.apiFetch({path: ncData.apiUrl + '/items?curated=1&per_page=100'}).then(function(data) {
                $select.html('<option value="">' + NC.__('select_approved', 'Selecione um item aprovado...') + '</option>');

                if (data.items && data.items.length > 0) {
                    data.items.forEach(function(item) {
                        var selected = selectedId && item.id == selectedId ? 'selected' : '';
                        var title = item.title.length > 60 ? NC.escapeHtml(item.title.substring(0, 60)) + '...' : NC.escapeHtml(item.title);
                        $select.append(
                            '<option value="' + item.id + '" ' + selected +
                                ' data-formatted="' + encodeURIComponent(item.formatted_content || '') + '"' +
                                ' data-has-image="' + (item.has_image ? '1' : '0') + '">' + title + '</option>'
                        );
                    });

                    if (selectedId) {
                        NC.loadItemContent(selectedId);
                    }
                } else {
                    $select.html('<option value="">' + NC.__('no_approved_items', 'Nenhum item aprovado encontrado') + '</option>');
                }
            }).catch(function() {
                $select.html('<option value="">' + NC.__('load_items_error', 'Erro ao carregar itens') + '</option>');
            });
        },

        loadItemContent: function(itemId) {
            var $option = $('#nc-schedule-item-select option[value="' + itemId + '"]');
            var formatted = decodeURIComponent($option.data('formatted') || '');
            var hasImage = $option.data('has-image') === '1' || $option.data('has-image') === 1;

            if (formatted) {
                $('#nc-schedule-content').val(formatted);
                NC.updateCharCounter();
                NC.updateSchedulePreview();
            }

            $('#nc-schedule-include-image').prop('checked', hasImage);
        },

        updateCharCounter: function() {
            var len = ($('#nc-schedule-content').val() || '').length;
            var $counter = $('#nc-schedule-char-count');
            $counter.text(len);
            if (len > 500) {
                $counter.parent().addClass('nc-char-over');
            } else {
                $counter.parent().removeClass('nc-char-over');
            }
        },

        updateSchedulePreview: function() {
            var content = $('#nc-schedule-content').val() || '';
            $('#nc-schedule-preview-text').text(content);
        },

        submitSchedule: function() {
            var form = $('#nc-schedule-form')[0];
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            var content = $('#nc-schedule-content').val();
            if (content.length > 500) {
                this.showNotice('warning', NC.__('content_too_long', 'O conteúdo excede 500 caracteres. Reduza o texto antes de agendar.'));
                return;
            }

            var data = {
                item_id: parseInt($('#nc-schedule-item-select').val()),
                scheduled_for: $('#nc-schedule-datetime').val(),
                content: content
            };

            wp.apiFetch({
                path: ncData.apiUrl + '/publications',
                method: 'POST',
                data: data
            }).then(function() {
                NC.showNotice('success', NC.__('publication_scheduled', 'Publicação agendada com sucesso!'));
                NC.closeModal('nc-schedule-modal');
                if (typeof NC.loadPublications === 'function') {
                    setTimeout(function() { NC.loadPublications('scheduled'); }, 1000);
                }
            }).catch(function(error) {
                NC.showNotice('error', NC.__('schedule_error', 'Erro ao agendar: ') + (error.message || ''));
            });
        },

        // ========== Bulk Schedule Modal ==========

        openBulkScheduleModal: function() {
            var $checked = $('.nc-item-checkbox:checked');
            if ($checked.length === 0) {
                this.showNotice('warning', NC.__('select_at_least_one_schedule', 'Selecione ao menos um item para agendar'));
                return;
            }

            if (!$('#nc-bulk-schedule-modal').length) {
                this.createBulkScheduleModal();
            }

            var selectedItems = [];
            $checked.each(function() {
                var $parent = $(this).closest('.nc-item-card, tr');
                var title = $parent.find('.nc-item-title').first().text() || $parent.find('td:nth-child(3) strong').first().text();
                selectedItems.push({
                    id: $(this).val(),
                    title: title || NC.__('untitled', 'Sem título'),
                    formatted: decodeURIComponent($(this).data('formatted') || '')
                });
            });
            NC._bulkItems = selectedItems;

            $('#nc-bulk-count').text(selectedItems.length);
            var listHtml = '';
            selectedItems.forEach(function(item, i) {
                var title = item.title.length > 50 ? NC.escapeHtml(item.title.substring(0, 50)) + '...' : NC.escapeHtml(item.title);
                listHtml += '<div class="nc-bulk-item">' +
                    '<span class="nc-bulk-item-num">' + (i + 1) + '</span>' +
                    '<span>' + title + '</span>' +
                    '</div>';
            });
            $('#nc-bulk-items-list').html(listHtml);

            var now = new Date();
            now.setHours(now.getHours() + 1, 0, 0, 0);
            var y = now.getFullYear();
            var m = String(now.getMonth() + 1).padStart(2, '0');
            var d = String(now.getDate()).padStart(2, '0');
            var h = String(now.getHours()).padStart(2, '0');
            $('#nc-bulk-start-datetime').val(y + '-' + m + '-' + d + 'T' + h + ':00');

            $('#nc-bulk-interval').val('60');
            this.updateBulkPreview();
            this.openModal('nc-bulk-schedule-modal');
        },

        createBulkScheduleModal: function() {
            var modalHtml =
            '<div id="nc-bulk-schedule-modal" class="nc-modal-overlay">' +
                '<div class="nc-modal" style="max-width:700px;">' +
                    '<div class="nc-modal-header">' +
                        '<h3 class="nc-modal-title">' +
                            '<span class="dashicons dashicons-calendar-alt" style="color:var(--nc-accent);"></span> ' +
                            NC.__('bulk_schedule', 'Agendar em Lote') +
                        '</h3>' +
                        '<button class="nc-modal-close" onclick="NC.closeModal(\'nc-bulk-schedule-modal\')">&times;</button>' +
                    '</div>' +
                    '<div class="nc-modal-body">' +
                        '<div class="nc-notice nc-notice-info" style="margin-bottom:20px;">' +
                            '<span class="dashicons dashicons-info"></span>' +
                            '<span><strong id="nc-bulk-count">0</strong> ' + NC.__('items_will_be_scheduled', 'itens selecionados serão agendados automaticamente com o template configurado.') + '</span>' +
                        '</div>' +
                        '<div class="nc-bulk-items-summary" id="nc-bulk-items-list"></div>' +
                        '<div class="nc-schedule-row" style="margin-top:20px;">' +
                            '<div class="nc-form-group" style="flex:1;">' +
                                '<label class="nc-form-label">' + NC.__('first_publication', 'Primeira publicação') + ' *</label>' +
                                '<input type="datetime-local" id="nc-bulk-start-datetime" class="nc-form-control" required>' +
                                '<span class="nc-form-help">' + NC.__('first_pub_help', 'Data/hora da primeira publicação') + '</span>' +
                            '</div>' +
                            '<div class="nc-form-group" style="flex:1;">' +
                                '<label class="nc-form-label">' + NC.__('interval_between', 'Intervalo entre posts') + ' *</label>' +
                                '<select id="nc-bulk-interval" class="nc-form-control">' +
                                    '<option value="30">' + NC.__('every_30min', 'A cada 30 minutos') + '</option>' +
                                    '<option value="60" selected>' + NC.__('every_1h', 'A cada 1 hora') + '</option>' +
                                    '<option value="120">' + NC.__('every_2h', 'A cada 2 horas') + '</option>' +
                                    '<option value="360">' + NC.__('every_6h', 'A cada 6 horas') + '</option>' +
                                    '<option value="720">' + NC.__('every_12h', 'A cada 12 horas') + '</option>' +
                                    '<option value="1440">' + NC.__('every_24h', 'A cada 24 horas') + '</option>' +
                                '</select>' +
                                '<span class="nc-form-help">' + NC.__('interval_help', 'Tempo entre cada publicação') + '</span>' +
                            '</div>' +
                        '</div>' +
                        '<div class="nc-form-group">' +
                            '<label class="nc-form-label">' + NC.__('options', 'Opções') + '</label>' +
                            '<label class="nc-schedule-option">' +
                                '<input type="checkbox" id="nc-bulk-include-image" checked>' +
                                '<span>' + NC.__('include_images', 'Incluir imagens (quando disponíveis)') + '</span>' +
                            '</label>' +
                        '</div>' +
                        '<div class="nc-form-group">' +
                            '<label class="nc-form-label">' + NC.__('expected_timeline', 'Cronograma previsto') + '</label>' +
                            '<div class="nc-bulk-timeline" id="nc-bulk-timeline"></div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="nc-modal-footer">' +
                        '<button class="nc-button nc-button-secondary" onclick="NC.closeModal(\'nc-bulk-schedule-modal\')">' +
                            NC.__('cancel', 'Cancelar') +
                        '</button>' +
                        '<button class="nc-button nc-button-primary" onclick="NC.submitBulkSchedule()">' +
                            '<span class="dashicons dashicons-calendar-alt"></span> ' + NC.__('schedule_all', 'Agendar Todos') +
                        '</button>' +
                    '</div>' +
                '</div>' +
            '</div>';

            $('body').append(modalHtml);

            $('#nc-bulk-start-datetime, #nc-bulk-interval').on('change', function() {
                NC.updateBulkPreview();
            });
        },

        updateBulkPreview: function() {
            var items = NC._bulkItems || [];
            var startStr = $('#nc-bulk-start-datetime').val();
            var interval = parseInt($('#nc-bulk-interval').val()) || 60;

            if (!startStr || items.length === 0) {
                $('#nc-bulk-timeline').html('<p style="color:var(--nc-text-light);">' + NC.__('configure_date_interval', 'Configure a data e o intervalo') + '</p>');
                return;
            }

            var start = new Date(startStr);
            var html = '';
            items.forEach(function(item, i) {
                var pubDate = new Date(start.getTime() + (i * interval * 60 * 1000));
                var dateStr = pubDate.toLocaleString('pt-BR', {
                    day: '2-digit', month: '2-digit', year: 'numeric',
                    hour: '2-digit', minute: '2-digit'
                });
                var title = item.title.length > 40 ? NC.escapeHtml(item.title.substring(0, 40)) + '...' : NC.escapeHtml(item.title);
                html += '<div class="nc-timeline-item">' +
                    '<span class="nc-timeline-date">' + dateStr + '</span>' +
                    '<span class="nc-timeline-title">' + title + '</span>' +
                    '</div>';
            });

            var lastDate = new Date(start.getTime() + ((items.length - 1) * interval * 60 * 1000));
            html += '<div class="nc-timeline-summary">' +
                '<span class="dashicons dashicons-clock"></span> ' +
                NC.__('from', 'De') + ' ' + start.toLocaleString('pt-BR', {day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'}) +
                ' ' + NC.__('until', 'até') + ' ' + lastDate.toLocaleString('pt-BR', {day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'}) +
                '</div>';

            $('#nc-bulk-timeline').html(html);
        },

        submitBulkSchedule: function() {
            var items = NC._bulkItems || [];
            var startStr = $('#nc-bulk-start-datetime').val();
            var interval = parseInt($('#nc-bulk-interval').val()) || 60;

            if (!startStr || items.length === 0) {
                this.showNotice('warning', NC.__('configure_first_date', 'Configure a data da primeira publicação'));
                return;
            }

            var start = new Date(startStr);
            if (start <= new Date()) {
                this.showNotice('warning', NC.__('date_must_be_future', 'A data da primeira publicação deve ser no futuro'));
                return;
            }

            this.showNotice('info', NC.__('scheduling', 'Agendando') + ' ' + items.length + ' ' + NC.__('publications', 'publicações...'));

            var promises = items.map(function(item, i) {
                var pubDate = new Date(start.getTime() + (i * interval * 60 * 1000));
                var scheduled = pubDate.getFullYear() + '-' +
                    String(pubDate.getMonth() + 1).padStart(2, '0') + '-' +
                    String(pubDate.getDate()).padStart(2, '0') + 'T' +
                    String(pubDate.getHours()).padStart(2, '0') + ':' +
                    String(pubDate.getMinutes()).padStart(2, '0');

                return wp.apiFetch({
                    path: ncData.apiUrl + '/publications',
                    method: 'POST',
                    data: {
                        item_id: parseInt(item.id),
                        scheduled_for: scheduled,
                        content: item.formatted
                    }
                });
            });

            Promise.all(promises).then(function() {
                NC.showNotice('success', items.length + ' ' + NC.__('publications_scheduled', 'publicações agendadas com sucesso!'));
                NC.closeModal('nc-bulk-schedule-modal');
                if (typeof NC.loadPublications === 'function') {
                    setTimeout(function() { NC.loadPublications('scheduled'); }, 1000);
                }
                if (typeof NC.loadItems === 'function') {
                    setTimeout(function() { NC.loadItems(NC._currentCuratedTab); }, 1500);
                }
            }).catch(function(error) {
                NC.showNotice('error', NC.__('schedule_error', 'Erro ao agendar: ') + (error.message || ''));
            });
        },

        // ========== Publications ==========

        deletePublication: function(id) {
            if (!confirm(NC.__('confirm_cancel_pub', 'Cancelar esta publicação agendada?'))) return;

            wp.apiFetch({
                path: ncData.apiUrl + '/publications/' + id,
                method: 'DELETE'
            }).then(function() {
                NC.showNotice('success', NC.__('publication_cancelled', 'Publicação cancelada!'));
                if (typeof NC.loadPublications === 'function') {
                    NC.loadPublications('scheduled');
                }
            }).catch(function(error) {
                NC.showNotice('error', NC.__('cancel_error', 'Erro ao cancelar: ') + (error.message || ''));
            });
        },

        retryPublication: function(id) {
            if (!confirm(NC.__('confirm_reschedule', 'Reagendar esta publicação?'))) return;
            this.showNotice('info', NC.__('retry_info', 'A publicação será reprocessada automaticamente na próxima execução do cron.'));
        },

        // ========== Settings ==========

        testMastodon: function() {
            this.showNotice('info', NC.__('testing_connection', 'Testando conexão...'));

            wp.apiFetch({
                path: ncData.apiUrl + '/settings/test-mastodon',
                method: 'POST'
            }).then(function(result) {
                if (result.success) {
                    NC.showNotice('success', NC.__('connected_as', 'Conectado como') + ' ' + result.account.username + '!');
                    $('#nc-mastodon-status').html(
                        '<div class="nc-notice nc-notice-success">' +
                            '<span class="dashicons dashicons-yes-alt"></span>' +
                            '<span>' + NC.__('connected_as', 'Conectado como') + ' <strong>@' + NC.escapeHtml(result.account.username) + '</strong></span>' +
                        '</div>'
                    );
                } else {
                    NC.showNotice('error', NC.__('connection_failed', 'Falha na conexão: ') + (result.message || ''));
                }
            }).catch(function(error) {
                NC.showNotice('error', NC.__('test_error', 'Erro ao testar: ') + (error.message || ''));
            });
        },

        saveSettings: function() {
            var data = {
                mastodon_instance: $('#nc-setting-instance').val(),
                mastodon_token: $('#nc-setting-token').val(),
                post_template: $('#nc-setting-template').val(),
                default_hashtags: $('#nc-setting-hashtags').val()
            };

            wp.apiFetch({
                path: ncData.apiUrl + '/settings',
                method: 'POST',
                data: data
            }).then(function() {
                NC.showNotice('success', NC.__('settings_saved', 'Configurações salvas!'));
            }).catch(function(error) {
                NC.showNotice('error', NC.__('save_error', 'Erro ao salvar: ') + (error.message || ''));
            });
        },

        // ========== Template Preview ==========

        updateTemplatePreview: function() {
            var template = $('#nc-setting-template').val() || '';
            var hashtags = $('#nc-setting-hashtags').val() || '';
            var preview = template
                .replace('{title}', 'Museu Nacional reabre exposição permanente')
                .replace('{excerpt}', 'O Museu Nacional do Rio de Janeiro anuncia a reabertura da exposição permanente com novas peças restauradas...')
                .replace('{url}', 'https://www.gov.br/museus/pt-br/exemplo')
                .replace('{hashtags}', hashtags);

            $('#nc-template-preview-text').text(preview);

            var len = preview.length;
            $('#nc-template-preview-count').text(len);
            if (len > 500) {
                $('#nc-template-preview-count').parent().addClass('nc-char-over');
            } else {
                $('#nc-template-preview-count').parent().removeClass('nc-char-over');
            }
        }
    };

    // Initialize
    $(document).ready(function() { NC.init(); });

    // Slide-in animation
    $('<style>')
        .text('@keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }')
        .appendTo('head');

})(jQuery);
