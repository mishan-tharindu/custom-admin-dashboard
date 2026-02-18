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
    let IS_REFRESHING = false;

    function refreshHomeLayout(category) {

        if (IS_REFRESHING) return;
        IS_REFRESHING = true;

        $.post(nhl_ajax.ajax_url, {
            action: 'nhl_render_home_layout',
            category: category
        }, function (res) {

            if (!res || !res.html) {
                IS_REFRESHING = false;
                return;
            }

            $('.nhl-home-layout').each(function () {

                let $block = $(this);
                let blockCategory = $block.data('category');

                if (blockCategory === category) {
                    $block.fadeTo(150, 0.3, function () {
                        $block.html(res.html).fadeTo(150, 1);
                    });
                }

            });

            CURRENT_VERSION = parseInt(res.version || CURRENT_VERSION);
            IS_REFRESHING = false;
        });
    }


    function checkLayoutUpdate() {

        $.post(nhl_ajax.ajax_url, {
            action: 'nhl_get_layout_version'
        }, function (res) {

            if (!res || !res.version || !res.category) return;

            let NEW_VERSION = parseInt(res.version);
            let CHANGED_CATEGORY = res.category;

            if (NEW_VERSION !== CURRENT_VERSION) {
                console.log(
                    '🟢 Refresh category blocks:',
                    CHANGED_CATEGORY
                );
                refreshHomeLayout(CHANGED_CATEGORY);
            }
        });
    }


    setInterval(checkLayoutUpdate, 5000);

})(jQuery);


