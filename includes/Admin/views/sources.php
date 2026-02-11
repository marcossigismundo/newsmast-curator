<div class="nc-page-header">
    <div class="nc-page-title">
        <h1>
            <span class="dashicons dashicons-admin-site-alt3"></span>
            <?php _e('Fontes de Conteúdo', 'newsmast-curator'); ?>
        </h1>
        <button class="nc-button nc-button-primary" onclick="NC.showAddSourceModal()">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php _e('Nova Fonte', 'newsmast-curator'); ?>
        </button>
    </div>
    <p class="nc-page-description"><?php _e('Gerencie as fontes de onde o conteúdo é coletado', 'newsmast-curator'); ?></p>
</div>

<div class="nc-card">
    <div class="nc-card-body" id="nc-sources-list">
        <div class="nc-loading">
            <div class="nc-spinner"></div>
        </div>
    </div>
</div>

<!-- Modal para adicionar/editar fonte -->
<div class="nc-modal-overlay" id="nc-source-modal">
    <div class="nc-modal">
        <div class="nc-modal-header">
            <h2 class="nc-modal-title" id="nc-modal-title"><?php _e('Nova Fonte', 'newsmast-curator'); ?></h2>
            <button class="nc-modal-close" onclick="NC.closeModal('nc-source-modal')">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="nc-modal-body">
            <form id="nc-source-form">
                <input type="hidden" id="nc-source-id" name="id">

                <div class="nc-form-group">
                    <label class="nc-form-label">
                        <?php _e('Nome da Fonte', 'newsmast-curator'); ?> *
                    </label>
                    <input type="text" id="nc-source-name" name="name" class="nc-form-control" required>
                    <span class="nc-form-help"><?php _e('Nome descritivo para identificar a fonte', 'newsmast-curator'); ?></span>
                </div>

                <div class="nc-form-group">
                    <label class="nc-form-label">
                        <?php _e('Tipo de Conector', 'newsmast-curator'); ?> *
                    </label>
                    <select id="nc-source-type" name="connector_type" class="nc-form-control" required>
                        <option value=""><?php _e('Selecione...', 'newsmast-curator'); ?></option>
                        <option value="plone">Plone CMS</option>
                        <option value="wordpress">WordPress</option>
                        <option value="tainacan">Tainacan</option>
                        <option value="visite_museus">Visite Museus</option>
                    </select>
                </div>

                <div class="nc-form-group">
                    <label class="nc-form-label">
                        <?php _e('URL', 'newsmast-curator'); ?> *
                    </label>
                    <input type="url" id="nc-source-url" name="url" class="nc-form-control" required>
                    <span class="nc-form-help"><?php _e('URL completa da fonte de conteúdo', 'newsmast-curator'); ?></span>
                </div>

                <div class="nc-form-group" id="nc-config-fields">
                    <!-- Campos dinâmicos de configuração serão inseridos aqui -->
                </div>
            </form>
        </div>
        <div class="nc-modal-footer">
            <button type="button" class="nc-button nc-button-secondary" onclick="NC.closeModal('nc-source-modal')">
                <?php _e('Cancelar', 'newsmast-curator'); ?>
            </button>
            <button type="button" class="nc-button nc-button-primary" onclick="NC.saveSource()">
                <span class="dashicons dashicons-saved"></span>
                <?php _e('Salvar', 'newsmast-curator'); ?>
            </button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    NC.loadSources();

    // Carregar campos dinâmicos ao mudar tipo
    $('#nc-source-type').on('change', function() {
        const type = $(this).val();
        if (!type) {
            $('#nc-config-fields').html('');
            return;
        }

        // Por simplicidade, vamos adicionar campos básicos
        let html = '';
        if (type === 'plone') {
            html = `<div class="nc-form-group">
                <label class="nc-form-label">CSS Selector dos Itens</label>
                <input type="text" name="config[selector]" class="nc-form-control" value=".newsItem">
            </div>`;
        } else if (type === 'wordpress') {
            html = `<div class="nc-form-group">
                <label class="nc-form-label">Endpoint da API</label>
                <input type="text" name="config[api_endpoint]" class="nc-form-control" value="/wp-json/wp/v2/posts">
            </div>`;
        } else if (type === 'tainacan') {
            html = `<div class="nc-form-group">
                <label class="nc-form-label">ID da Coleção</label>
                <input type="number" name="config[collection_id]" class="nc-form-control">
            </div>`;
        } else if (type === 'visite_museus') {
            html = `<div class="nc-form-group">
                <label class="nc-form-label">Slug da Coleção</label>
                <input type="text" name="config[collection_slug]" class="nc-form-control">
            </div>`;
        }
        $('#nc-config-fields').html(html);
    });
});

NC.loadSources = function() {
    wp.apiFetch({path: ncData.apiUrl + '/sources'}).then(sources => {
        if (sources.length === 0) {
            jQuery('#nc-sources-list').html('<p style="text-align:center;padding:40px;color:#6C757D;">Nenhuma fonte cadastrada. Clique em "Nova Fonte" para começar.</p>');
            return;
        }

        let html = '<table class="nc-table"><thead><tr><th>Nome</th><th>Tipo</th><th>URL</th><th>Status</th><th>Última Coleta</th><th>Ações</th></tr></thead><tbody>';
        sources.forEach(s => {
            const statusBadge = s.status === 'active' ? 'nc-badge-success' : s.status === 'error' ? 'nc-badge-danger' : 'nc-badge-warning';
            const lastCollection = s.last_collection ? new Date(s.last_collection).toLocaleString('pt-BR') : 'Nunca';

            html += `<tr>
                <td><strong>${s.name}</strong></td>
                <td>${s.connector_type}</td>
                <td><a href="${s.url}" target="_blank" style="color:#457B9D;">${s.url.substring(0, 40)}...</a></td>
                <td><span class="nc-badge ${statusBadge}">${s.status}</span></td>
                <td><small>${lastCollection}</small></td>
                <td class="nc-table-actions">
                    <button class="nc-button nc-button-primary" onclick="NC.collectSource(${s.id})">
                        <span class="dashicons dashicons-update"></span>
                        Coletar
                    </button>
                    <button class="nc-button nc-button-danger" onclick="NC.deleteSource(${s.id})">
                        <span class="dashicons dashicons-trash"></span>
                        Remover
                    </button>
                </td>
            </tr>`;
        });
        html += '</tbody></table>';
        jQuery('#nc-sources-list').html(html);
    }).catch(error => {
        jQuery('#nc-sources-list').html('<div class="nc-notice nc-notice-error">Erro ao carregar fontes: ' + error.message + '</div>');
    });
};

NC.showAddSourceModal = function() {
    jQuery('#nc-modal-title').text('Nova Fonte');
    jQuery('#nc-source-form')[0].reset();
    jQuery('#nc-source-id').val('');
    jQuery('#nc-config-fields').html('');
    NC.openModal('nc-source-modal');
};

NC.saveSource = function() {
    const form = jQuery('#nc-source-form')[0];
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const data = {
        name: jQuery('#nc-source-name').val(),
        connector_type: jQuery('#nc-source-type').val(),
        url: jQuery('#nc-source-url').val(),
        config: {}
    };

    // Pegar campos de config
    jQuery('#nc-config-fields input, #nc-config-fields select').each(function() {
        const name = jQuery(this).attr('name');
        if (name && name.startsWith('config[')) {
            const key = name.replace('config[', '').replace(']', '');
            data.config[key] = jQuery(this).val();
        }
    });

    const id = jQuery('#nc-source-id').val();
    const method = id ? 'PUT' : 'POST';
    const path = id ? `${ncData.apiUrl}/sources/${id}` : `${ncData.apiUrl}/sources`;

    wp.apiFetch({path, method, data}).then(response => {
        NC.showNotice('success', 'Fonte salva com sucesso!');
        NC.closeModal('nc-source-modal');
        NC.loadSources();
    }).catch(error => {
        NC.showNotice('error', 'Erro ao salvar fonte: ' + error.message);
    });
};

NC.collectSource = function(id) {
    if (!confirm('Iniciar coleta desta fonte agora?')) return;

    NC.showNotice('info', 'Coletando...');

    wp.apiFetch({
        path: `${ncData.apiUrl}/sources/${id}/collect`,
        method: 'POST'
    }).then(result => {
        NC.showNotice('success', `${result.items_collected} itens coletados!`);
        NC.loadSources();
    }).catch(error => {
        NC.showNotice('error', 'Erro na coleta: ' + error.message);
    });
};

NC.deleteSource = function(id) {
    if (!confirm('Tem certeza que deseja remover esta fonte? Todos os itens coletados serão mantidos.')) return;

    wp.apiFetch({
        path: `${ncData.apiUrl}/sources/${id}`,
        method: 'DELETE'
    }).then(() => {
        NC.showNotice('success', 'Fonte removida!');
        NC.loadSources();
    }).catch(error => {
        NC.showNotice('error', 'Erro ao remover: ' + error.message);
    });
};
</script>
