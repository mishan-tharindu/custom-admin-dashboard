jQuery(document).ready(function ($) {

    $(document).on('click', '#news-load-more', function () {

        let $btn = $(this);
        let page = parseInt($btn.data('page'));

        $btn.text('Loading...').prop('disabled', true);

        $.post(nhl_ajax.ajax_url, {
            action: 'nhl_load_more_news',
            page: page
        }, function (html) {

            if (html.trim() === '') {
                $btn.hide();
                return;
            }

            $('.news-grid').append(html);
            $btn.data('page', page + 1);
            $btn.text('Load More').prop('disabled', false);
        });
    });


});
