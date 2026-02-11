(function($) {
    'use strict';

    window.NC = {
        init: function() {
            this.initTabs();
        },

        initTabs: function() {
            $('.nc-tab').on('click', function() {
                const tab = $(this).data('tab');
                $('.nc-tab').removeClass('active');
                $(this).addClass('active');
            });
        },

        showNotice: function(type, message) {
            const $notice = $(`<div class="notice notice-${type} is-dismissible"><p>${message}</p></div>`);
            $('.nc-wrap').prepend($notice);
            setTimeout(() => $notice.fadeOut(() => $notice.remove()), 5000);
        },

        collectNow: function(sourceId) {
            wp.apiFetch({
                path: `${ncData.apiUrl}/sources/${sourceId}/collect`,
                method: 'POST'
            }).then(response => {
                this.showNotice('success', `${response.items_collected} itens coletados!`);
            }).catch(error => {
                this.showNotice('error', error.message);
            });
        },

        curateItem: function(itemId) {
            wp.apiFetch({
                path: `${ncData.apiUrl}/items/${itemId}/curate`,
                method: 'POST'
            }).then(() => {
                this.showNotice('success', 'Item aprovado!');
                setTimeout(() => location.reload(), 1000);
            }).catch(error => {
                this.showNotice('error', error.message);
            });
        }
    };

    $(document).ready(() => NC.init());

})(jQuery);

// Funções globais para uso em inline scripts
function ncCollect(id) {
    NC.collectNow(id);
}

function ncCurate(id) {
    NC.curateItem(id);
}
