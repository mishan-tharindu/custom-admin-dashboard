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

(function ($) {

    let CURRENT_VERSION = parseInt(nhl_ajax.layout_version || 0);

    function checkLayoutUpdate() {

        $.post(nhl_ajax.ajax_url, {
            action: 'nhl_get_layout_version'
        }, function (res) {

            if (!res || !res.version) return;

            let NEW_VERSION = parseInt(res.version);

            if (NEW_VERSION !== CURRENT_VERSION) {
                console.log('🟢 Layout updated, refreshing frontend…');

                // Update version BEFORE reload (important)
                CURRENT_VERSION = NEW_VERSION;

                // Small delay to avoid browser block
                setTimeout(function () {
                    location.reload();
                }, 300);
            }
        });
    }

    // Check every 5 seconds
    setInterval(checkLayoutUpdate, 5000);

})(jQuery);

