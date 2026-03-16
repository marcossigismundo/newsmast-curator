(function($) {
    'use strict';

    window.NC = {
        _viewMode: 'grid',
        _currentCuratedTab: 0,
        _bulkItems: [],
        _mastodonAccounts: [],

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

        // ========== Mastodon Accounts ==========

        loadMastodonAccounts: function() {
            wp.apiFetch({path: ncData.apiUrl + '/mastodon-accounts'}).then(function(accounts) {
                NC._mastodonAccounts = accounts;
                NC.renderAccountsList(accounts);
            }).catch(function(error) {
                $('#nc-mastodon-accounts-list').html(
                    '<div class="nc-notice nc-notice-error"><span class="dashicons dashicons-dismiss"></span> Erro ao carregar contas: ' + (error.message || '') + '</div>'
                );
            });
        },

        renderAccountsList: function(accounts) {
            if (!accounts || accounts.length === 0) {
                $('#nc-mastodon-accounts-list').html(
                    '<div style="text-align:center;padding:30px 20px;color:var(--nc-text-light);">' +
                        '<span class="dashicons dashicons-share-alt2" style="font-size:40px;width:40px;height:40px;display:block;margin:0 auto 10px;color:var(--nc-border);"></span>' +
                        '<p>Nenhuma conta Mastodon configurada.</p>' +
                        '<p style="font-size:12px;">Clique em <strong>"Nova Conta"</strong> para adicionar sua primeira conta.</p>' +
                    '</div>'
                );
                return;
            }

            var html = '<table class="nc-table"><thead><tr>' +
                '<th>Nome</th><th>Instância</th><th>Usuário</th><th>Status</th><th>Padrão</th><th>Ações</th>' +
                '</tr></thead><tbody>';

            accounts.forEach(function(a) {
                var statusBadge = a.status === 'active' ? 'nc-badge-success' : 'nc-badge-danger';
                var statusLabel = a.status === 'active' ? 'Ativo' : 'Erro';
                var defaultBadge = a.is_default
                    ? '<span class="nc-badge nc-badge-info" style="font-size:10px;">Padrão</span>'
                    : '<button class="nc-button nc-button-secondary" style="font-size:10px;padding:2px 8px;" onclick="NC.setDefaultAccount(' + a.id + ')" title="Definir como padrão">Definir</button>';
                var instanceDisplay = a.instance_url.length > 35 ? a.instance_url.substring(0, 35) + '...' : a.instance_url;

                html += '<tr>' +
                    '<td><strong>' + NC.escapeHtml(a.name) + '</strong></td>' +
                    '<td><a href="' + NC.escapeHtml(a.instance_url) + '" target="_blank" style="color:var(--nc-accent);">' + NC.escapeHtml(instanceDisplay) + '</a></td>' +
                    '<td>' + (a.username ? '@' + NC.escapeHtml(a.username) : '<span style="color:#999;">-</span>') + '</td>' +
                    '<td><span class="nc-badge ' + statusBadge + '">' + statusLabel + '</span>' +
                        (a.last_error ? '<span class="dashicons dashicons-warning" style="color:var(--nc-warning);cursor:help;margin-left:4px;" title="' + NC.escapeHtml(a.last_error) + '"></span>' : '') +
                    '</td>' +
                    '<td>' + defaultBadge + '</td>' +
                    '<td class="nc-table-actions">' +
                        '<button class="nc-button nc-button-secondary" onclick="NC.testAccount(' + a.id + ')" title="Testar conexão">' +
                            '<span class="dashicons dashicons-update"></span></button>' +
                        '<button class="nc-button nc-button-secondary" onclick="NC.editAccount(' + a.id + ')" title="Editar">' +
                            '<span class="dashicons dashicons-edit"></span></button>' +
                        '<button class="nc-button nc-button-danger" onclick="NC.deleteAccount(' + a.id + ')" title="Remover">' +
                            '<span class="dashicons dashicons-trash"></span></button>' +
                    '</td></tr>';
            });
            html += '</tbody></table>';
            $('#nc-mastodon-accounts-list').html(html);
        },

        showAddAccountModal: function() {
            $('#nc-account-modal-title').text('Nova Conta Mastodon');
            $('#nc-account-form')[0].reset();
            $('#nc-account-id').val('');
            $('#nc-account-test-result').html('');
            NC.openModal('nc-account-modal');
        },

        editAccount: function(id) {
            wp.apiFetch({path: ncData.apiUrl + '/mastodon-accounts/' + id}).then(function(account) {
                $('#nc-account-modal-title').text('Editar Conta Mastodon');
                $('#nc-account-id').val(account.id);
                $('#nc-account-name').val(account.name);
                $('#nc-account-instance').val(account.instance_url);
                $('#nc-account-token').val('').attr('placeholder', '••••••••••••••••');
                $('#nc-account-token').prop('required', false);
                $('#nc-account-test-result').html('');
                NC.openModal('nc-account-modal');
            }).catch(function(error) {
                NC.showNotice('error', 'Erro ao carregar conta: ' + (error.message || ''));
            });
        },

        saveAccount: function() {
            var form = $('#nc-account-form')[0];
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            var id = $('#nc-account-id').val();
            var data = {
                name: $('#nc-account-name').val(),
                instance_url: $('#nc-account-instance').val()
            };

            var token = $('#nc-account-token').val();
            if (token) {
                data.access_token = token;
            }

            if (id) {
                // Update
                wp.apiFetch({path: ncData.apiUrl + '/mastodon-accounts/' + id, method: 'PUT', data: data}).then(function() {
                    NC.showNotice('success', 'Conta atualizada!');
                    NC.closeModal('nc-account-modal');
                    NC.loadMastodonAccounts();
                }).catch(function(error) {
                    NC.showNotice('error', 'Erro ao atualizar: ' + (error.message || ''));
                });
            } else {
                // Create - token required
                if (!token) {
                    NC.showNotice('warning', 'Token de acesso é obrigatório para novas contas');
                    return;
                }
                NC.showNotice('info', 'Testando conexão e salvando...');
                wp.apiFetch({path: ncData.apiUrl + '/mastodon-accounts', method: 'POST', data: data}).then(function(account) {
                    NC.showNotice('success', 'Conta criada! Conectado como @' + (account.username || ''));
                    NC.closeModal('nc-account-modal');
                    NC.loadMastodonAccounts();
                }).catch(function(error) {
                    NC.showNotice('error', 'Erro ao criar conta: ' + (error.message || ''));
                    $('#nc-account-test-result').html(
                        '<div class="nc-notice nc-notice-error" style="margin-top:10px;"><span class="dashicons dashicons-dismiss"></span> ' + NC.escapeHtml(error.message || 'Erro') + '</div>'
                    );
                });
            }
        },

        testAccount: function(id) {
            NC.showNotice('info', 'Testando conexão...');
            wp.apiFetch({path: ncData.apiUrl + '/mastodon-accounts/' + id + '/test', method: 'POST'}).then(function(result) {
                if (result.success) {
                    NC.showNotice('success', 'Conectado como @' + result.account.username);
                } else {
                    NC.showNotice('error', 'Falha: ' + (result.message || 'Erro desconhecido'));
                }
                NC.loadMastodonAccounts();
            }).catch(function(error) {
                NC.showNotice('error', 'Erro: ' + (error.message || ''));
            });
        },

        setDefaultAccount: function(id) {
            wp.apiFetch({path: ncData.apiUrl + '/mastodon-accounts/' + id + '/set-default', method: 'POST'}).then(function() {
                NC.showNotice('success', 'Conta definida como padrão!');
                NC.loadMastodonAccounts();
            }).catch(function(error) {
                NC.showNotice('error', 'Erro: ' + (error.message || ''));
            });
        },

        deleteAccount: function(id) {
            if (!confirm('Tem certeza que deseja remover esta conta Mastodon?\n\nPublicações já realizadas não serão afetadas.')) return;
            wp.apiFetch({path: ncData.apiUrl + '/mastodon-accounts/' + id, method: 'DELETE'}).then(function() {
                NC.showNotice('success', 'Conta removida!');
                NC.loadMastodonAccounts();
            }).catch(function(error) {
                NC.showNotice('error', 'Erro: ' + (error.message || ''));
            });
        },

        migrateLegacyMastodon: function() {
            if (!confirm('Migrar a configuração atual do Mastodon para o novo sistema de contas?\n\nUma nova conta será criada como padrão.')) return;
            NC.showNotice('info', 'Migrando...');
            wp.apiFetch({path: ncData.apiUrl + '/settings/migrate-mastodon', method: 'POST', data: {name: 'Conta Principal'}}).then(function(result) {
                NC.showNotice('success', result.message || 'Migrado com sucesso!');
                $('#nc-legacy-mastodon-notice').fadeOut();
                NC.loadMastodonAccounts();
            }).catch(function(error) {
                NC.showNotice('error', 'Erro ao migrar: ' + (error.message || ''));
            });
        },

        // ========== Account Selector Helper ==========

        _loadAccountsForSelector: function(callback) {
            if (NC._mastodonAccounts && NC._mastodonAccounts.length > 0) {
                callback(NC._mastodonAccounts);
                return;
            }
            wp.apiFetch({path: ncData.apiUrl + '/mastodon-accounts'}).then(function(accounts) {
                NC._mastodonAccounts = accounts;
                callback(accounts);
            }).catch(function() {
                callback([]);
            });
        },

        _buildAccountSelector: function(accounts, prefix, isMultiple) {
            if (!accounts || accounts.length === 0) {
                return '';
            }

            var html = '<div class="nc-form-group">' +
                '<label class="nc-form-label">Contas Mastodon</label>';

            if (isMultiple) {
                html += '<div id="' + prefix + '-accounts-checkboxes" style="max-height:150px;overflow-y:auto;border:1px solid var(--nc-border,#dee2e6);border-radius:6px;padding:10px;">';
                accounts.forEach(function(a) {
                    if (a.status !== 'active') return;
                    var checkedAttr = a.is_default ? ' checked' : '';
                    html += '<label style="display:flex;align-items:center;gap:8px;padding:4px 0;cursor:pointer;">' +
                        '<input type="checkbox" class="' + prefix + '-account-cb" value="' + a.id + '"' + checkedAttr + '>' +
                        '<span>' + NC.escapeHtml(a.name) + '</span>' +
                        '<small style="color:#999;">(' + NC.escapeHtml(a.instance_url.replace('https://', '')) + ')</small>' +
                        (a.is_default ? ' <span class="nc-badge nc-badge-info" style="font-size:9px;">Padrão</span>' : '') +
                    '</label>';
                });
                html += '</div>';
                html += '<span class="nc-form-help">Selecione uma ou mais contas para publicar. Uma publicação será criada para cada conta.</span>';
            } else {
                html += '<select id="' + prefix + '-account-select" class="nc-form-control">' +
                    '<option value="">Conta padrão</option>';
                accounts.forEach(function(a) {
                    if (a.status !== 'active') return;
                    var defaultLabel = a.is_default ? ' (padrão)' : '';
                    html += '<option value="' + a.id + '"' + (a.is_default ? ' selected' : '') + '>' +
                        NC.escapeHtml(a.name) + defaultLabel + '</option>';
                });
                html += '</select>';
            }

            html += '</div>';
            return html;
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
            now.setMinutes(now.getMinutes() + 30, 0, 0);
            var y = now.getFullYear();
            var m = String(now.getMonth() + 1).padStart(2, '0');
            var d = String(now.getDate()).padStart(2, '0');
            var h = String(now.getHours()).padStart(2, '0');
            var min = String(now.getMinutes()).padStart(2, '0');
            $('#nc-schedule-datetime').val(y + '-' + m + '-' + d + 'T' + h + ':' + min);

            var minNow = new Date();
            var minY = minNow.getFullYear();
            var minM = String(minNow.getMonth() + 1).padStart(2, '0');
            var minD = String(minNow.getDate()).padStart(2, '0');
            var minH = String(minNow.getHours()).padStart(2, '0');
            var minMin = String(minNow.getMinutes()).padStart(2, '0');
            $('#nc-schedule-datetime').attr('min', minY + '-' + minM + '-' + minD + 'T' + minH + ':' + minMin);

            // Load account selector
            NC._loadAccountsForSelector(function(accounts) {
                var selectorHtml = NC._buildAccountSelector(accounts, 'nc-schedule', true);
                $('#nc-schedule-accounts-container').html(selectorHtml);
            });

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
                            '<div id="nc-schedule-accounts-container"></div>' +
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

            var scheduledDate = new Date($('#nc-schedule-datetime').val());
            var nowCheck = new Date();
            nowCheck.setSeconds(nowCheck.getSeconds() - 60);
            if (scheduledDate < nowCheck) {
                this.showNotice('warning', NC.__('date_not_retroactive', 'A data não pode ser retroativa. Selecione o horário atual ou futuro.'));
                return;
            }

            // Collect selected account IDs
            var accountIds = [];
            $('.nc-schedule-account-cb:checked').each(function() {
                accountIds.push(parseInt($(this).val()));
            });

            var data = {
                item_id: parseInt($('#nc-schedule-item-select').val()),
                scheduled_for: $('#nc-schedule-datetime').val(),
                content: content
            };

            if (accountIds.length > 0) {
                data.mastodon_account_ids = accountIds;
            }

            wp.apiFetch({
                path: ncData.apiUrl + '/publications',
                method: 'POST',
                data: data
            }).then(function() {
                var msg = accountIds.length > 1
                    ? 'Publicação agendada em ' + accountIds.length + ' contas!'
                    : NC.__('publication_scheduled', 'Publicação agendada com sucesso!');
                NC.showNotice('success', msg);
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

            // Load account selector for bulk
            NC._loadAccountsForSelector(function(accounts) {
                var selectorHtml = NC._buildAccountSelector(accounts, 'nc-bulk', true);
                $('#nc-bulk-accounts-container').html(selectorHtml);
            });

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
                        '<div id="nc-bulk-accounts-container"></div>' +
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
            var nowBulk = new Date();
            nowBulk.setSeconds(nowBulk.getSeconds() - 60);
            if (start < nowBulk) {
                this.showNotice('warning', NC.__('date_not_retroactive', 'A data não pode ser retroativa. Selecione o horário atual ou futuro.'));
                return;
            }

            // Collect selected account IDs for bulk
            var accountIds = [];
            $('.nc-bulk-account-cb:checked').each(function() {
                accountIds.push(parseInt($(this).val()));
            });

            this.showNotice('info', NC.__('scheduling', 'Agendando') + ' ' + items.length + ' ' + NC.__('publications', 'publicações...'));

            var promises = items.map(function(item, i) {
                var pubDate = new Date(start.getTime() + (i * interval * 60 * 1000));
                var scheduled = pubDate.getFullYear() + '-' +
                    String(pubDate.getMonth() + 1).padStart(2, '0') + '-' +
                    String(pubDate.getDate()).padStart(2, '0') + 'T' +
                    String(pubDate.getHours()).padStart(2, '0') + ':' +
                    String(pubDate.getMinutes()).padStart(2, '0');

                var data = {
                    item_id: parseInt(item.id),
                    scheduled_for: scheduled,
                    content: item.formatted
                };

                if (accountIds.length > 0) {
                    data.mastodon_account_ids = accountIds;
                }

                return wp.apiFetch({
                    path: ncData.apiUrl + '/publications',
                    method: 'POST',
                    data: data
                });
            });

            Promise.all(promises).then(function() {
                var totalPubs = accountIds.length > 1
                    ? items.length + ' itens x ' + accountIds.length + ' contas = ' + (items.length * accountIds.length) + ' publicações agendadas!'
                    : items.length + ' ' + NC.__('publications_scheduled', 'publicações agendadas com sucesso!');
                NC.showNotice('success', totalPubs);
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

            wp.apiFetch({
                path: ncData.apiUrl + '/publications/' + id + '/retry',
                method: 'POST'
            }).then(function() {
                NC.showNotice('success', NC.__('publication_rescheduled', 'Publicação reagendada com sucesso!'));
                if (typeof NC.loadPublications === 'function') {
                    NC.loadPublications('failed');
                }
            }).catch(function(error) {
                NC.showNotice('error', NC.__('retry_error', 'Erro ao reagendar: ') + (error.message || ''));
            });
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
            var token = $('#nc-setting-token').val();
            var apiKey = $('#nc-setting-api-key').val();
            var data = {
                post_template: $('#nc-setting-template').val(),
                default_hashtags: $('#nc-setting-hashtags').val(),
                api_enabled: $('#nc-setting-api-enabled').is(':checked') ? 1 : 0
            };
            // Legacy mastodon fields (if they exist on the page)
            var instance = $('#nc-setting-instance').val();
            if (instance !== undefined) {
                data.mastodon_instance = instance;
            }
            if (token && token !== '********') {
                data.mastodon_token = token;
            }
            if (apiKey && apiKey !== '********') {
                data.api_key = apiKey;
            }

            wp.apiFetch({
                path: ncData.apiUrl + '/settings',
                method: 'POST',
                data: data
            }).then(function() {
                NC.showNotice('success', NC.__('settings_saved', 'Configurações salvas!'));
                if (apiKey && apiKey !== '********' && apiKey.length > 0) {
                    $('#nc-setting-api-key').val('********');
                }
            }).catch(function(error) {
                NC.showNotice('error', NC.__('save_error', 'Erro ao salvar: ') + (error.message || ''));
            });
        },

        generateApiKey: function() {
            var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            var key = '';
            var array = new Uint8Array(40);
            crypto.getRandomValues(array);
            for (var i = 0; i < 40; i++) {
                key += chars.charAt(array[i] % chars.length);
            }
            $('#nc-setting-api-key').val(key);
            NC.showNotice('info', NC.__('api_key_generated', 'Chave gerada! Salve as configurações e copie a chave antes de sair.'));
        },

        copyApiKey: function() {
            var key = $('#nc-setting-api-key').val();
            if (!key || key === '********') {
                NC.showNotice('warning', NC.__('api_key_masked', 'A chave está mascarada. Gere uma nova chave para copiá-la.'));
                return;
            }
            navigator.clipboard.writeText(key).then(function() {
                NC.showNotice('success', NC.__('api_key_copied', 'Chave copiada!'));
            }).catch(function() {
                var $temp = $('<input>');
                $('body').append($temp);
                $temp.val(key).select();
                document.execCommand('copy');
                $temp.remove();
                NC.showNotice('success', NC.__('api_key_copied', 'Chave copiada!'));
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
