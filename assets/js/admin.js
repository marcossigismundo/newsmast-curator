(function($) {
    'use strict';

    window.NC = {
        init: function() {
            this.initTabs();
            this.initModalClose();
            this.setupApiFetch();
        },

        setupApiFetch: function() {
            if (typeof wp !== 'undefined' && typeof wp.apiFetch !== 'undefined') {
                wp.apiFetch.use(wp.apiFetch.createNonceMiddleware(ncData.nonce));
            }
        },

        initTabs: function() {
            $('.nc-tab').on('click', function() {
                const tab = $(this).data('tab');
                $('.nc-tab').removeClass('active');
                $(this).addClass('active');
                $('.nc-tab-content').hide();
                $(`#${tab}`).show();
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
                    // Also close sidebar on mobile
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

        // ========== Notices ==========

        showNotice: function(type, message) {
            const noticeClass = type === 'success' ? 'nc-notice-success' :
                              type === 'error' ? 'nc-notice-error' :
                              type === 'warning' ? 'nc-notice-warning' : 'nc-notice-info';
            const icon = type === 'success' ? 'yes-alt' :
                        type === 'error' ? 'dismiss' :
                        type === 'warning' ? 'warning' : 'info';

            const $notice = $(`
                <div class="nc-notice ${noticeClass}" style="position:fixed;top:50px;right:20px;z-index:999999;min-width:300px;animation:slideInRight 0.3s ease;">
                    <span class="dashicons dashicons-${icon}"></span>
                    <span>${message}</span>
                </div>
            `);
            $('body').append($notice);
            setTimeout(() => {
                $notice.fadeOut(300, function() { $(this).remove(); });
            }, 5000);
        },

        // ========== Sources ==========

        collectNow: function(sourceId) {
            this.showNotice('info', 'Iniciando coleta...');
            wp.apiFetch({
                path: `${ncData.apiUrl}/sources/${sourceId}/collect`,
                method: 'POST'
            }).then(response => {
                this.showNotice('success', `${response.items_collected} itens coletados!`);
                if (typeof NC.loadSources === 'function') {
                    setTimeout(() => NC.loadSources(), 1000);
                }
            }).catch(error => {
                this.showNotice('error', 'Erro na coleta: ' + (error.message || 'Erro desconhecido'));
            });
        },

        collectAll: function() {
            if (!confirm('Iniciar coleta de todas as fontes ativas?')) return;
            this.showNotice('info', 'Coletando de todas as fontes...');

            wp.apiFetch({path: `${ncData.apiUrl}/sources`}).then(sources => {
                const activeSources = sources.filter(s => s.status === 'active');
                if (activeSources.length === 0) {
                    this.showNotice('warning', 'Nenhuma fonte ativa encontrada');
                    return;
                }
                const promises = activeSources.map(source => {
                    return wp.apiFetch({
                        path: `${ncData.apiUrl}/sources/${source.id}/collect`,
                        method: 'POST'
                    });
                });
                Promise.all(promises).then(results => {
                    const total = results.reduce((sum, r) => sum + (r.items_collected || 0), 0);
                    this.showNotice('success', `Total de ${total} itens coletados de ${activeSources.length} fontes!`);
                    setTimeout(() => location.reload(), 2000);
                }).catch(() => {
                    this.showNotice('error', 'Erro ao coletar de algumas fontes');
                });
            }).catch(error => {
                this.showNotice('error', 'Erro ao carregar fontes: ' + (error.message || 'Erro desconhecido'));
            });
        },

        // ========== Curation ==========

        curateItem: function(itemId) {
            wp.apiFetch({
                path: `${ncData.apiUrl}/items/${itemId}/curate`,
                method: 'POST'
            }).then(() => {
                this.showNotice('success', 'Item aprovado!');
                $(`[data-item-id="${itemId}"]`).fadeOut(300, function() { $(this).remove(); });
                const $counter = $('#nc-uncurated-count');
                if ($counter.length) {
                    const current = parseInt($counter.text()) || 0;
                    $counter.text(Math.max(0, current - 1));
                }
                const $curatedCounter = $('#nc-curated-count');
                if ($curatedCounter.length) {
                    const current = parseInt($curatedCounter.text()) || 0;
                    $curatedCounter.text(current + 1);
                }
            }).catch(error => {
                this.showNotice('error', 'Erro ao aprovar: ' + error.message);
            });
        },

        bulkCurate: function() {
            const $checked = $('.nc-item-checkbox:checked');
            if ($checked.length === 0) {
                this.showNotice('warning', 'Selecione ao menos um item');
                return;
            }
            if (!confirm(`Aprovar ${$checked.length} itens selecionados?`)) return;

            const itemIds = $checked.map(function() { return $(this).val(); }).get();
            const promises = itemIds.map(id => {
                return wp.apiFetch({
                    path: `${ncData.apiUrl}/items/${id}/curate`,
                    method: 'POST'
                });
            });
            Promise.all(promises).then(() => {
                this.showNotice('success', `${itemIds.length} itens aprovados!`);
                setTimeout(() => location.reload(), 1500);
            }).catch(() => {
                this.showNotice('error', 'Erro ao aprovar itens');
            });
        },

        selectAll: function(checkbox) {
            $('.nc-item-checkbox').prop('checked', checkbox.checked);
        },

        // ========== Schedule Modal ==========

        openScheduleModal: function(itemId) {
            if (!$('#nc-schedule-modal').length) {
                this.createScheduleModal();
            }

            // Reset form
            $('#nc-schedule-form')[0].reset();
            $('#nc-schedule-item-select').prop('disabled', false);
            $('#nc-schedule-preview-text').text('');
            $('#nc-schedule-char-count').text('0');

            // Default datetime: next hour
            const now = new Date();
            now.setHours(now.getHours() + 1, 0, 0, 0);
            const y = now.getFullYear();
            const m = String(now.getMonth() + 1).padStart(2, '0');
            const d = String(now.getDate()).padStart(2, '0');
            const h = String(now.getHours()).padStart(2, '0');
            $('#nc-schedule-datetime').val(`${y}-${m}-${d}T${h}:00`);

            // Load approved items then select if itemId provided
            this.loadApprovedItems(itemId);
            this.openModal('nc-schedule-modal');
        },

        createScheduleModal: function() {
            const modalHtml = `
            <div id="nc-schedule-modal" class="nc-modal-overlay">
                <div class="nc-modal" style="max-width:700px;">
                    <div class="nc-modal-header">
                        <h3 class="nc-modal-title">
                            <span class="dashicons dashicons-calendar-alt" style="color:var(--nc-accent);"></span>
                            Agendar Publicação
                        </h3>
                        <button class="nc-modal-close" onclick="NC.closeModal('nc-schedule-modal')">&times;</button>
                    </div>
                    <div class="nc-modal-body">
                        <form id="nc-schedule-form">
                            <div class="nc-form-group">
                                <label class="nc-form-label">Item aprovado *</label>
                                <select id="nc-schedule-item-select" class="nc-form-control" required>
                                    <option value="">Carregando itens aprovados...</option>
                                </select>
                                <span class="nc-form-help">Selecione um item aprovado na curadoria</span>
                            </div>

                            <div class="nc-form-group">
                                <label class="nc-form-label">Conteúdo do Post *</label>
                                <textarea id="nc-schedule-content" class="nc-form-control" rows="6" required
                                    placeholder="O conteúdo será preenchido automaticamente ao selecionar um item..."></textarea>
                                <div class="nc-char-counter">
                                    <span id="nc-schedule-char-count">0</span> / 500 caracteres
                                </div>
                                <span class="nc-form-help">Edite o texto que será publicado no Mastodon</span>
                            </div>

                            <div class="nc-schedule-row">
                                <div class="nc-form-group" style="flex:1;">
                                    <label class="nc-form-label">Data e Hora *</label>
                                    <input type="datetime-local" id="nc-schedule-datetime" class="nc-form-control" required>
                                </div>
                                <div class="nc-form-group" style="flex:1;">
                                    <label class="nc-form-label">Opções</label>
                                    <label class="nc-schedule-option">
                                        <input type="checkbox" id="nc-schedule-include-image" checked>
                                        <span>Incluir imagem (se disponível)</span>
                                    </label>
                                </div>
                            </div>

                            <div class="nc-form-group">
                                <label class="nc-form-label">Preview</label>
                                <div class="nc-schedule-preview">
                                    <div class="nc-schedule-preview-header">
                                        <span class="dashicons dashicons-admin-site-alt3"></span>
                                        <strong>Mastodon Post</strong>
                                    </div>
                                    <p id="nc-schedule-preview-text"></p>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="nc-modal-footer">
                        <button class="nc-button nc-button-secondary" onclick="NC.closeModal('nc-schedule-modal')">
                            Cancelar
                        </button>
                        <button class="nc-button nc-button-primary" onclick="NC.submitSchedule()">
                            <span class="dashicons dashicons-calendar-alt"></span> Agendar Publicação
                        </button>
                    </div>
                </div>
            </div>`;

            $('body').append(modalHtml);

            // Bind events
            $('#nc-schedule-content').on('input', function() {
                NC.updateCharCounter();
                NC.updateSchedulePreview();
            });

            $('#nc-schedule-item-select').on('change', function() {
                const itemId = $(this).val();
                if (itemId) {
                    NC.loadItemContent(itemId);
                }
            });
        },

        loadApprovedItems: function(selectedId) {
            const $select = $('#nc-schedule-item-select');
            $select.html('<option value="">Carregando...</option>');

            wp.apiFetch({path: `${ncData.apiUrl}/items?curated=1&per_page=100`}).then(data => {
                $select.html('<option value="">Selecione um item aprovado...</option>');

                if (data.items && data.items.length > 0) {
                    data.items.forEach(item => {
                        const selected = selectedId && item.id == selectedId ? 'selected' : '';
                        const title = item.title.length > 60 ? item.title.substring(0, 60) + '...' : item.title;
                        $select.append(
                            `<option value="${item.id}" ${selected}
                                data-formatted="${encodeURIComponent(item.formatted_content || '')}"
                                data-has-image="${item.has_image ? '1' : '0'}">${title}</option>`
                        );
                    });

                    // Auto-fill if item was pre-selected
                    if (selectedId) {
                        NC.loadItemContent(selectedId);
                    }
                } else {
                    $select.html('<option value="">Nenhum item aprovado encontrado</option>');
                }
            }).catch(() => {
                $select.html('<option value="">Erro ao carregar itens</option>');
            });
        },

        loadItemContent: function(itemId) {
            const $option = $(`#nc-schedule-item-select option[value="${itemId}"]`);
            const formatted = decodeURIComponent($option.data('formatted') || '');
            const hasImage = $option.data('has-image') === '1' || $option.data('has-image') === 1;

            if (formatted) {
                $('#nc-schedule-content').val(formatted);
                NC.updateCharCounter();
                NC.updateSchedulePreview();
            }

            $('#nc-schedule-include-image').prop('checked', hasImage);
        },

        updateCharCounter: function() {
            const len = ($('#nc-schedule-content').val() || '').length;
            const $counter = $('#nc-schedule-char-count');
            $counter.text(len);
            if (len > 500) {
                $counter.parent().addClass('nc-char-over');
            } else {
                $counter.parent().removeClass('nc-char-over');
            }
        },

        updateSchedulePreview: function() {
            const content = $('#nc-schedule-content').val() || '';
            $('#nc-schedule-preview-text').text(content);
        },

        submitSchedule: function() {
            const form = $('#nc-schedule-form')[0];
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const content = $('#nc-schedule-content').val();
            if (content.length > 500) {
                this.showNotice('warning', 'O conteúdo excede 500 caracteres. Reduza o texto antes de agendar.');
                return;
            }

            const data = {
                item_id: parseInt($('#nc-schedule-item-select').val()),
                scheduled_for: $('#nc-schedule-datetime').val(),
                content: content
            };

            wp.apiFetch({
                path: `${ncData.apiUrl}/publications`,
                method: 'POST',
                data: data
            }).then(() => {
                this.showNotice('success', 'Publicação agendada com sucesso!');
                this.closeModal('nc-schedule-modal');
                if (typeof NC.loadPublications === 'function') {
                    setTimeout(() => NC.loadPublications('scheduled'), 1000);
                }
            }).catch(error => {
                this.showNotice('error', 'Erro ao agendar: ' + (error.message || 'Erro desconhecido'));
            });
        },

        // ========== Bulk Schedule Modal ==========

        openBulkScheduleModal: function() {
            var $checked = $('.nc-item-checkbox:checked');
            if ($checked.length === 0) {
                this.showNotice('warning', 'Selecione ao menos um item para agendar');
                return;
            }

            if (!$('#nc-bulk-schedule-modal').length) {
                this.createBulkScheduleModal();
            }

            // Collect selected items info
            var selectedItems = [];
            $checked.each(function() {
                var $card = $(this).closest('.nc-item-card');
                selectedItems.push({
                    id: $(this).val(),
                    title: $card.find('.nc-item-title').text(),
                    formatted: decodeURIComponent($(this).data('formatted') || '')
                });
            });
            NC._bulkItems = selectedItems;

            // Update summary
            $('#nc-bulk-count').text(selectedItems.length);
            var listHtml = '';
            selectedItems.forEach(function(item, i) {
                var title = item.title.length > 50 ? item.title.substring(0, 50) + '...' : item.title;
                listHtml += '<div class="nc-bulk-item">' +
                    '<span class="nc-bulk-item-num">' + (i + 1) + '</span>' +
                    '<span>' + title + '</span>' +
                    '</div>';
            });
            $('#nc-bulk-items-list').html(listHtml);

            // Default: next hour
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
                            'Agendar em Lote' +
                        '</h3>' +
                        '<button class="nc-modal-close" onclick="NC.closeModal(\'nc-bulk-schedule-modal\')">&times;</button>' +
                    '</div>' +
                    '<div class="nc-modal-body">' +
                        '<div class="nc-notice nc-notice-info" style="margin-bottom:20px;">' +
                            '<span class="dashicons dashicons-info"></span>' +
                            '<span><strong id="nc-bulk-count">0</strong> itens selecionados serão agendados automaticamente com o template configurado.</span>' +
                        '</div>' +

                        '<div class="nc-bulk-items-summary" id="nc-bulk-items-list"></div>' +

                        '<div class="nc-schedule-row" style="margin-top:20px;">' +
                            '<div class="nc-form-group" style="flex:1;">' +
                                '<label class="nc-form-label">Primeira publicação *</label>' +
                                '<input type="datetime-local" id="nc-bulk-start-datetime" class="nc-form-control" required>' +
                                '<span class="nc-form-help">Data/hora da primeira publicação</span>' +
                            '</div>' +
                            '<div class="nc-form-group" style="flex:1;">' +
                                '<label class="nc-form-label">Intervalo entre posts *</label>' +
                                '<select id="nc-bulk-interval" class="nc-form-control">' +
                                    '<option value="30">A cada 30 minutos</option>' +
                                    '<option value="60" selected>A cada 1 hora</option>' +
                                    '<option value="120">A cada 2 horas</option>' +
                                    '<option value="360">A cada 6 horas</option>' +
                                    '<option value="720">A cada 12 horas</option>' +
                                    '<option value="1440">A cada 24 horas</option>' +
                                '</select>' +
                                '<span class="nc-form-help">Tempo entre cada publicação</span>' +
                            '</div>' +
                        '</div>' +

                        '<div class="nc-form-group">' +
                            '<label class="nc-form-label">Opções</label>' +
                            '<label class="nc-schedule-option">' +
                                '<input type="checkbox" id="nc-bulk-include-image" checked>' +
                                '<span>Incluir imagens (quando disponíveis)</span>' +
                            '</label>' +
                        '</div>' +

                        '<div class="nc-form-group">' +
                            '<label class="nc-form-label">Cronograma previsto</label>' +
                            '<div class="nc-bulk-timeline" id="nc-bulk-timeline"></div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="nc-modal-footer">' +
                        '<button class="nc-button nc-button-secondary" onclick="NC.closeModal(\'nc-bulk-schedule-modal\')">' +
                            'Cancelar' +
                        '</button>' +
                        '<button class="nc-button nc-button-primary" onclick="NC.submitBulkSchedule()">' +
                            '<span class="dashicons dashicons-calendar-alt"></span> Agendar Todos' +
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
                $('#nc-bulk-timeline').html('<p style="color:var(--nc-text-light);">Configure a data e o intervalo</p>');
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
                var title = item.title.length > 40 ? item.title.substring(0, 40) + '...' : item.title;
                html += '<div class="nc-timeline-item">' +
                    '<span class="nc-timeline-date">' + dateStr + '</span>' +
                    '<span class="nc-timeline-title">' + title + '</span>' +
                    '</div>';
            });

            var lastDate = new Date(start.getTime() + ((items.length - 1) * interval * 60 * 1000));
            html += '<div class="nc-timeline-summary">' +
                '<span class="dashicons dashicons-clock"></span> ' +
                'De ' + start.toLocaleString('pt-BR', {day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'}) +
                ' até ' + lastDate.toLocaleString('pt-BR', {day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'}) +
                '</div>';

            $('#nc-bulk-timeline').html(html);
        },

        submitBulkSchedule: function() {
            var items = NC._bulkItems || [];
            var startStr = $('#nc-bulk-start-datetime').val();
            var interval = parseInt($('#nc-bulk-interval').val()) || 60;

            if (!startStr || items.length === 0) {
                this.showNotice('warning', 'Configure a data da primeira publicação');
                return;
            }

            var start = new Date(startStr);
            if (start <= new Date()) {
                this.showNotice('warning', 'A data da primeira publicação deve ser no futuro');
                return;
            }

            this.showNotice('info', 'Agendando ' + items.length + ' publicações...');

            var promises = items.map(function(item, i) {
                var pubDate = new Date(start.getTime() + (i * interval * 60 * 1000));
                // Format as ISO for the API
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
                NC.showNotice('success', items.length + ' publicações agendadas com sucesso!');
                NC.closeModal('nc-bulk-schedule-modal');
                if (typeof NC.loadPublications === 'function') {
                    setTimeout(function() { NC.loadPublications('scheduled'); }, 1000);
                }
                // Reload items to refresh the list
                if (typeof NC.loadItems === 'function') {
                    setTimeout(function() { NC.loadItems(NC._currentCuratedTab); }, 1500);
                }
            }).catch(function(error) {
                NC.showNotice('error', 'Erro ao agendar: ' + (error.message || 'Erro desconhecido'));
            });
        },

        // ========== Publications ==========

        deletePublication: function(id) {
            if (!confirm('Cancelar esta publicação agendada?')) return;

            wp.apiFetch({
                path: `${ncData.apiUrl}/publications/${id}`,
                method: 'DELETE'
            }).then(() => {
                this.showNotice('success', 'Publicação cancelada!');
                if (typeof NC.loadPublications === 'function') {
                    NC.loadPublications('scheduled');
                }
            }).catch(error => {
                this.showNotice('error', 'Erro ao cancelar: ' + error.message);
            });
        },

        retryPublication: function(id) {
            if (!confirm('Reagendar esta publicação?')) return;
            this.showNotice('info', 'Reagendando publicação...');

            // Re-schedule by creating a new publication based on the failed one
            // For now, just notify - the retry system handles this via cron
            this.showNotice('info', 'A publicação será reprocessada automaticamente na próxima execução do cron.');
        },

        // ========== Settings ==========

        testMastodon: function() {
            this.showNotice('info', 'Testando conexão...');

            wp.apiFetch({
                path: `${ncData.apiUrl}/settings/test-mastodon`,
                method: 'POST'
            }).then(result => {
                if (result.success) {
                    this.showNotice('success', `Conectado como ${result.account.username}!`);
                    $('#nc-mastodon-status').html(`
                        <div class="nc-notice nc-notice-success">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <span>Conectado como <strong>@${result.account.username}</strong></span>
                        </div>
                    `);
                } else {
                    this.showNotice('error', 'Falha na conexão: ' + result.message);
                }
            }).catch(error => {
                this.showNotice('error', 'Erro ao testar: ' + error.message);
            });
        },

        saveSettings: function() {
            const data = {
                mastodon_instance: $('#nc-setting-instance').val(),
                mastodon_token: $('#nc-setting-token').val(),
                post_template: $('#nc-setting-template').val(),
                default_hashtags: $('#nc-setting-hashtags').val()
            };

            wp.apiFetch({
                path: `${ncData.apiUrl}/settings`,
                method: 'POST',
                data: data
            }).then(() => {
                this.showNotice('success', 'Configurações salvas!');
            }).catch(error => {
                this.showNotice('error', 'Erro ao salvar: ' + error.message);
            });
        },

        // ========== Template Preview ==========

        updateTemplatePreview: function() {
            const template = $('#nc-setting-template').val() || '';
            const hashtags = $('#nc-setting-hashtags').val() || '';
            const preview = template
                .replace('{title}', 'Museu Nacional reabre exposição permanente')
                .replace('{excerpt}', 'O Museu Nacional do Rio de Janeiro anuncia a reabertura da exposição permanente com novas peças restauradas...')
                .replace('{url}', 'https://www.gov.br/museus/pt-br/exemplo')
                .replace('{hashtags}', hashtags);

            $('#nc-template-preview-text').text(preview);

            const len = preview.length;
            $('#nc-template-preview-count').text(len);
            if (len > 500) {
                $('#nc-template-preview-count').parent().addClass('nc-char-over');
            } else {
                $('#nc-template-preview-count').parent().removeClass('nc-char-over');
            }
        }
    };

    // Initialize
    $(document).ready(() => NC.init());

    // Slide-in animation
    $('<style>')
        .text('@keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }')
        .appendTo('head');

})(jQuery);
