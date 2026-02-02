jQuery(document).ready(function ($) {

    $(document).on('click', '.news-load-more', function () {

        let $btn = $(this);
        let $layout = $btn.closest('.news-layout');
        let category = $btn.data('category');

        let loaded = $btn.data('loaded') || [];

        $btn.text('Loading...').prop('disabled', true);

        $.post(nhl_ajax.ajax_url, {
            action: 'nhl_load_more_news',
            category: category,
            loaded: loaded
        }, function (response) {

            if (!response || !response.html || response.html.trim() === '') {
                $btn.hide();
                return;
            }

            $layout.find('.news-grid').append(response.html);

            // merge loaded IDs
            let updated = loaded.concat(response.ids);
            $btn.data('loaded', updated);

            // 🔥 hide button if no more posts
            if (!response.has_more) {
                $btn.hide();
                return;
            }

            $btn.text('Load More').prop('disabled', false);
        });
    });

});
