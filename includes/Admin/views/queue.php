<div class="nc-page-header">
    <div class="nc-page-title">
        <h1>
            <span class="dashicons dashicons-calendar-alt"></span>
            <?php _e('Fila de Publicação', 'newsmast-curator'); ?>
        </h1>
        <button class="nc-button nc-button-primary" onclick="NC.openScheduleModal()">
            <span class="dashicons dashicons-plus-alt2"></span>
            <?php _e('Agendar Publicação', 'newsmast-curator'); ?>
        </button>
        <button class="nc-button nc-button-success" id="nc-btn-process-now" onclick="NC.processQueueNow()">
            <span class="dashicons dashicons-controls-play"></span>
            <?php _e('Processar Fila Agora', 'newsmast-curator'); ?>
        </button>
    </div>
    <p class="nc-page-description"><?php _e('Gerencie publicações agendadas para o Mastodon', 'newsmast-curator'); ?></p>
</div>

<div class="nc-notice nc-notice-info" style="margin-bottom:20px;">
    <span class="dashicons dashicons-info"></span>
    <span><?php _e('Selecione um item aprovado na curadoria, edite o conteúdo e agende a data/hora da publicação no Mastodon.', 'newsmast-curator'); ?></span>
</div>

<div class="nc-tabs">
    <button class="nc-tab active" data-tab="scheduled" onclick="NC.loadPublications('scheduled')">
        <?php _e('Agendadas', 'newsmast-curator'); ?> <span id="nc-count-scheduled" class="nc-badge nc-badge-info">0</span>
    </button>
    <button class="nc-tab" data-tab="published" onclick="NC.loadPublications('published')">
        <?php _e('Publicadas', 'newsmast-curator'); ?> <span id="nc-count-published" class="nc-badge nc-badge-success">0</span>
    </button>
    <button class="nc-tab" data-tab="failed" onclick="NC.loadPublications('failed')">
        <?php _e('Falhas', 'newsmast-curator'); ?> <span id="nc-count-failed" class="nc-badge nc-badge-danger">0</span>
    </button>
</div>

<div style="display:flex;justify-content:flex-end;align-items:center;gap:8px;margin-bottom:10px;">
    <label for="nc-queue-sort" style="font-size:12px;color:var(--nc-text-light);">
        <span class="dashicons dashicons-sort" style="font-size:14px;width:14px;height:14px;vertical-align:middle;"></span>
        <?php _e('Ordenar:', 'newsmast-curator'); ?>
    </label>
    <select id="nc-queue-sort" class="nc-form-control" style="width:auto;font-size:12px;padding:4px 28px 4px 8px;background-position:right 8px center;" onchange="NC.applyQueueSort()">
        <!-- populado dinamicamente conforme aba ativa -->
    </select>
</div>

<div class="nc-card">
    <div class="nc-card-body" id="nc-publications-list">
        <div class="nc-loading"><div class="nc-spinner"></div></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Pre-load WP categories so the queue can display category names for WP publications
    wp.apiFetch({path: ncData.apiUrl + '/wp-categories'}).then(function(data) {
        NC._wpCategoriesCache = data.categories || [];
    }).catch(function() { NC._wpCategoriesCache = []; });

    NC._loadAccountsForSelector(function() {
        NC.loadPublications('scheduled');
    });
    NC.loadPublicationCounts();
});

NC.loadPublicationCounts = function() {
    ['scheduled', 'published', 'failed'].forEach(function(status) {
        wp.apiFetch({path: ncData.apiUrl + '/publications?status=' + status + '&per_page=100'}).then(function(pubs) {
            jQuery('#nc-count-' + status).text(Array.isArray(pubs) ? pubs.length : 0);
        }).catch(function() {});
    });
};

// Opções de ordenação por status. Primeira opção = default.
NC._sortOptionsByStatus = {
    scheduled: [
        { value: 'scheduled_for:ASC',  label: 'Próxima a publicar' },
        { value: 'scheduled_for:DESC', label: 'Mais distante primeiro' },
        { value: 'created_at:DESC',    label: 'Agendamento mais recente' },
        { value: 'created_at:ASC',     label: 'Agendamento mais antigo' }
    ],
    published: [
        { value: 'published_at:DESC',  label: 'Publicado recentemente' },
        { value: 'published_at:ASC',   label: 'Publicado há mais tempo' },
        { value: 'scheduled_for:ASC',  label: 'Por data de agendamento' }
    ],
    failed: [
        { value: 'updated_at:DESC',    label: 'Falha mais recente' },
        { value: 'updated_at:ASC',     label: 'Falha mais antiga' },
        { value: 'scheduled_for:ASC',  label: 'Por data de agendamento' }
    ]
};

NC.getSortPref = function(status) {
    var key = 'nc_queue_sort_' + status;
    var saved = localStorage.getItem(key);
    var opts = NC._sortOptionsByStatus[status] || [];
    if (saved && opts.some(function(o) { return o.value === saved; })) return saved;
    return opts.length ? opts[0].value : 'scheduled_for:ASC';
};

NC.populateSortSelector = function(status) {
    var opts = NC._sortOptionsByStatus[status] || [];
    var current = NC.getSortPref(status);
    var html = '';
    opts.forEach(function(o) {
        html += '<option value="' + o.value + '"' + (o.value === current ? ' selected' : '') + '>' + o.label + '</option>';
    });
    jQuery('#nc-queue-sort').html(html);
};

NC.applyQueueSort = function() {
    var val = jQuery('#nc-queue-sort').val();
    var status = jQuery('.nc-tab.active').data('tab') || 'scheduled';
    localStorage.setItem('nc_queue_sort_' + status, val);
    NC.loadPublications(status);
};

NC.loadPublications = function(status) {
    status = status || 'scheduled';

    // Update active tab
    jQuery('.nc-tab').removeClass('active');
    jQuery('.nc-tab[data-tab="' + status + '"]').addClass('active');

    // Atualiza seletor de ordenação para opções da aba atual
    NC.populateSortSelector(status);

    var sortPref = NC.getSortPref(status);
    var parts = sortPref.split(':');
    var orderby = parts[0];
    var order = parts[1] || 'ASC';

    jQuery('#nc-publications-list').html('<div class="nc-loading"><div class="nc-spinner"></div></div>');

    wp.apiFetch({path: ncData.apiUrl + '/publications?status=' + status + '&per_page=50&orderby=' + encodeURIComponent(orderby) + '&order=' + encodeURIComponent(order)}).then(function(pubs) {
        if (!pubs || pubs.length === 0) {
            var emptyIcon = status === 'scheduled' ? 'calendar-alt' : status === 'published' ? 'yes-alt' : 'warning';
            var emptyMsg = status === 'scheduled' ? 'Nenhuma publicação agendada. Clique em "Agendar Publicação" para começar.' :
                           status === 'published' ? 'Nenhuma publicação realizada ainda.' :
                           'Nenhuma falha registrada.';
            jQuery('#nc-publications-list').html(
                '<div class="nc-empty-state">' +
                    '<span class="dashicons dashicons-' + emptyIcon + '"></span>' +
                    '<p>' + emptyMsg + '</p>' +
                '</div>'
            );
            return;
        }

        var html = '<table class="nc-table"><thead><tr>' +
            '<th>Data/Hora</th><th>Destino</th><th>Conteúdo</th><th>Status</th><th>Tentativas</th><th>Ações</th>' +
            '</tr></thead><tbody>';

        pubs.forEach(function(pub) {
            var date = pub.scheduled_for ? new Date(pub.scheduled_for) : new Date();
            var badgeClass = pub.status === 'scheduled' ? 'nc-badge-info' :
                            pub.status === 'published' ? 'nc-badge-success' :
                            pub.status === 'failed' ? 'nc-badge-danger' : 'nc-badge-warning';
            var statusLabel = pub.status === 'scheduled' ? 'Agendada' :
                             pub.status === 'published' ? 'Publicada' :
                             pub.status === 'failed' ? 'Falhou' : pub.status;

            var contentText = (pub.content || '');
            var contentPreview = contentText.length > 80 ? contentText.substring(0, 80) + '...' : contentText;
            var contentAttr = contentText.replace(/"/g, '&quot;');

            // Thread indicator
            var threadBadge = '';
            if (pub.is_thread) {
                var posLabel = (pub.thread_position === 0)
                    ? '<span class="dashicons dashicons-admin-post" style="font-size:12px;width:12px;height:12px;"></span> Início'
                    : '<span class="dashicons dashicons-format-status" style="font-size:12px;width:12px;height:12px;"></span> #' + (pub.thread_position + 1);
                threadBadge = ' <span class="nc-badge" style="background:var(--nc-accent);color:#fff;font-size:10px;" title="Thread: ' + (pub.thread_id || '') + '">' +
                    posLabel + '</span>';
            }

            // Destination cell: Mastodon account name or WordPress category name
            var destCell = '—';
            if (pub.destination_type === 'wordpress') {
                var catLabel = pub.wp_category_id
                    ? (NC._wpCategoriesCache || []).find(function(c) { return c.id == pub.wp_category_id; })
                    : null;
                destCell = '<span class="nc-badge" style="background:#21759b;color:#fff;font-size:10px;">' +
                    '<span class="dashicons dashicons-wordpress" style="font-size:12px;width:12px;height:12px;"></span> WP' +
                    '</span> ' +
                    (catLabel ? '<small>' + NC.escapeHtml(catLabel.name) + '</small>' : '<small>cat #' + (pub.wp_category_id || '?') + '</small>');
            } else if (pub.mastodon_account_id && NC._mastodonAccounts) {
                var acct = NC._mastodonAccounts.find(function(a) { return a.id == pub.mastodon_account_id; });
                if (acct) {
                    destCell = '<span class="nc-badge nc-badge-info" style="font-size:10px;">' +
                        '<span class="dashicons dashicons-admin-site-alt3" style="font-size:12px;width:12px;height:12px;"></span> M</span> ' +
                        '<small>' + NC.escapeHtml(acct.name) + '</small>';
                }
            } else {
                destCell = '<span class="nc-badge nc-badge-info" style="font-size:10px;">' +
                    '<span class="dashicons dashicons-admin-site-alt3" style="font-size:12px;width:12px;height:12px;"></span> Mastodon</span>';
            }

            html += '<tr>' +
                '<td>' + date.toLocaleString('pt-BR') + '</td>' +
                '<td>' + destCell + '</td>' +
                '<td title="' + contentAttr + '">' + contentPreview + '</td>' +
                '<td><span class="nc-badge ' + badgeClass + '">' + statusLabel + '</span>' + threadBadge + '</td>' +
                '<td>' + (pub.attempt_count || 0) + ' / 3</td>' +
                '<td class="nc-table-actions">';

            // "Ver" button: Mastodon URL or WordPress post URL
            if (pub.status === 'published') {
                var viewUrl = pub.destination_type === 'wordpress' ? pub.wp_post_url : pub.mastodon_url;
                if (viewUrl) {
                    html += '<a href="' + viewUrl + '" target="_blank" class="nc-button nc-button-secondary">' +
                        '<span class="dashicons dashicons-external"></span> Ver' +
                        '</a>';
                }
            }

            if (pub.status === 'scheduled') {
                html += '<button class="nc-button nc-button-danger" onclick="NC.deletePublication(' + pub.id + ')">' +
                    '<span class="dashicons dashicons-trash"></span> Cancelar' +
                    '</button>';
            }

            if (pub.status === 'failed') {
                html += '<button class="nc-button nc-button-warning" onclick="NC.retryPublication(' + pub.id + ')">' +
                    '<span class="dashicons dashicons-update"></span> Reagendar' +
                    '</button>';
            }

            html += '</td></tr>';
        });
        html += '</tbody></table>';
        jQuery('#nc-publications-list').html(html);
    }).catch(function() {
        jQuery('#nc-publications-list').html(
            '<div class="nc-notice nc-notice-error">' +
                '<span class="dashicons dashicons-dismiss"></span> Erro ao carregar publicações' +
            '</div>'
        );
    });
};

NC.processQueueNow = function() {
    var $btn = jQuery('#nc-btn-process-now');
    $btn.prop('disabled', true).find('.dashicons').removeClass('dashicons-controls-play').addClass('dashicons-update nc-spin');
    NC.showNotice('info', 'Processando fila de publicações...');

    wp.apiFetch({
        path: ncData.apiUrl + '/publications/process-now',
        method: 'POST'
    }).then(function(response) {
        var published = response.stats ? (response.stats.published || 0) : 0;
        var failed = response.stats ? (response.stats.failed || 0) : 0;
        NC.showNotice('success', 'Fila processada! Publicadas: ' + published + ' | Falhas: ' + failed);
        NC.loadPublications('scheduled');
        NC.loadPublicationCounts();
    }).catch(function(error) {
        NC.showNotice('error', 'Erro ao processar fila: ' + (error.message || ''));
    }).finally(function() {
        $btn.prop('disabled', false).find('.dashicons').removeClass('dashicons-update nc-spin').addClass('dashicons-controls-play');
    });
};
</script>
