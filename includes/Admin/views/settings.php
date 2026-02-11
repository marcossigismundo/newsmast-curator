<div class="wrap nc-wrap">
    <h1><?php _e('Configurações', 'newsmast-curator'); ?></h1>
    <form id="nc-settings-form">
        <table class="form-table">
            <tr>
                <th><?php _e('Instância Mastodon', 'newsmast-curator'); ?></th>
                <td><input type="url" name="mastodon_instance" class="regular-text" value="<?php echo esc_attr(get_option('nc_mastodon_instance')); ?>"></td>
            </tr>
            <tr>
                <th><?php _e('Token de Acesso', 'newsmast-curator'); ?></th>
                <td><input type="text" name="mastodon_token" class="regular-text"></td>
            </tr>
        </table>
        <p><button type="submit" class="button button-primary"><?php _e('Salvar', 'newsmast-curator'); ?></button></p>
    </form>
</div>
<script>
jQuery('#nc-settings-form').on('submit', function(e) {
    e.preventDefault();
    const data = {
        mastodon_instance: jQuery('[name=mastodon_instance]').val(),
        mastodon_token: jQuery('[name=mastodon_token]').val()
    };
    wp.apiFetch({path: ncData.apiUrl + '/settings', method: 'POST', data}).then(() => {
        alert('Configurações salvas!');
    });
});
</script>
