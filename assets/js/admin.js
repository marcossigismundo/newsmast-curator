(function($) {
    'use strict';

    window.NC = {
        init: function() {
            this.initTabs();
            this.initModalClose();
        },

        initTabs: function() {
            $('.nc-tab').on('click', function() {
                const tab = $(this).data('tab');
                $('.nc-tab').removeClass('active');
                $(this).addClass('active');

                // Se houver conteúdo de tab, mostrar/ocultar
                $('.nc-tab-content').hide();
                $(`#${tab}`).show();
            });
        },

        initModalClose: function() {
            // Fechar ao clicar no overlay
            $(document).on('click', '.nc-modal-overlay', function(e) {
                if (e.target === this) {
                    $(this).removeClass('active');
                }
            });

            // Fechar com ESC
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    $('.nc-modal-overlay.active').removeClass('active');
                }
            });
        },

        openModal: function(modalId) {
            $('#' + modalId).addClass('active');
        },

        closeModal: function(modalId) {
            $('#' + modalId).removeClass('active');
        },

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
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },

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
                this.showNotice('error', 'Erro na coleta: ' + error.message);
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

                    // Recarregar página após 2 segundos
                    setTimeout(() => location.reload(), 2000);
                }).catch(error => {
                    this.showNotice('error', 'Erro ao coletar de algumas fontes');
                });
            });
        },

        curateItem: function(itemId) {
            wp.apiFetch({
                path: `${ncData.apiUrl}/items/${itemId}/curate`,
                method: 'POST'
            }).then(() => {
                this.showNotice('success', 'Item aprovado!');
                // Remover da lista visualmente
                $(`[data-item-id="${itemId}"]`).fadeOut(300, function() {
                    $(this).remove();
                });

                // Atualizar contador se existir
                const $counter = $('#nc-uncurated-count');
                if ($counter.length) {
                    const current = parseInt($counter.text()) || 0;
                    $counter.text(Math.max(0, current - 1));
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

            const itemIds = $checked.map(function() {
                return $(this).val();
            }).get();

            const promises = itemIds.map(id => {
                return wp.apiFetch({
                    path: `${ncData.apiUrl}/items/${id}/curate`,
                    method: 'POST'
                });
            });

            Promise.all(promises).then(() => {
                this.showNotice('success', `${itemIds.length} itens aprovados!`);
                setTimeout(() => location.reload(), 1500);
            }).catch(error => {
                this.showNotice('error', 'Erro ao aprovar itens');
            });
        },

        selectAll: function(checkbox) {
            $('.nc-item-checkbox').prop('checked', checkbox.checked);
        },

        previewPost: function(itemId, content) {
            // Implementação do preview
            const $preview = $('#nc-post-preview');
            if ($preview.length) {
                $preview.html(`
                    <div class="nc-card">
                        <div class="nc-card-header">
                            <h3>Preview</h3>
                        </div>
                        <div class="nc-card-body">
                            <div style="background:#F1FAEE;padding:20px;border-radius:12px;border:1px solid #A8DADC;">
                                <p style="white-space:pre-wrap;">${content}</p>
                                <small style="color:#6C757D;">${content.length} / 500 caracteres</small>
                            </div>
                        </div>
                    </div>
                `);
            }
        },

        schedulePublication: function() {
            const form = $('#nc-publication-form')[0];
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const data = {
                item_id: $('#nc-pub-item-id').val(),
                scheduled_for: $('#nc-pub-datetime').val(),
                content: $('#nc-pub-content').val()
            };

            wp.apiFetch({
                path: `${ncData.apiUrl}/publications`,
                method: 'POST',
                data: data
            }).then(() => {
                this.showNotice('success', 'Publicação agendada!');
                this.closeModal('nc-publication-modal');
                if (typeof NC.loadPublications === 'function') {
                    setTimeout(() => NC.loadPublications(), 1000);
                }
            }).catch(error => {
                this.showNotice('error', 'Erro ao agendar: ' + error.message);
            });
        },

        deletePublication: function(id) {
            if (!confirm('Cancelar esta publicação?')) return;

            wp.apiFetch({
                path: `${ncData.apiUrl}/publications/${id}`,
                method: 'DELETE'
            }).then(() => {
                this.showNotice('success', 'Publicação cancelada!');
                if (typeof NC.loadPublications === 'function') {
                    NC.loadPublications();
                }
            }).catch(error => {
                this.showNotice('error', 'Erro ao cancelar: ' + error.message);
            });
        },

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
        }
    };

    // Inicializar quando documento estiver pronto
    $(document).ready(() => NC.init());

    // Adicionar CSS para animação
    $('<style>')
        .text('@keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }')
        .appendTo('head');

})(jQuery);
