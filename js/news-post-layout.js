jQuery(function ($) {

    /* =========================================
       HELPERS — NORMALIZERS
    ========================================= */

    function normalizeToGrid($item) {

        let imgSrc = $item.find('img').attr('src') || '';
        let title = $item.find('.nhl-title').text() || $item.find('.nhl-card-title').text() || '';

        let gridHtml = `
            <li class="nhl-item nhl-card-item" data-id="${$item.data('id')}">
                <div class="nhl-card-thumb">
                    <img src="${imgSrc}" alt="">
                </div>
                <div class="nhl-card-title">
                    ${title}
                </div>
            </li>
        `;

        return $(gridHtml);
    }

    function normalizeToHero($item) {

        let imgSrc = $item.find('img').attr('src') || '';
        let title = $item.find('.nhl-card-title').text() || $item.find('.nhl-title').text() || '';

        let heroHtml = `
            <li class="nhl-item" data-id="${$item.data('id')}">
                <img src="${imgSrc}">
                <span class="nhl-title">${title}</span>
                <span class="nhl-remove" title="Remove">✖</span>
            </li>
        `;

        return $(heroHtml);
    }

    /* =========================================
       ADMIN TABS
    ========================================= */

    $(document).on('click', '.nhl-tab', function () {
        let tab = $(this).data('tab');

        $('.nhl-tab').removeClass('active');
        $(this).addClass('active');

        $('.nhl-tab-content').removeClass('active');
        $('.nhl-tab-content[data-tab="' + tab + '"]').addClass('active');
    });

    /* =========================================
       OTHER CATEGORIES — SORTABLE
    ========================================= */

    $('.nhl-sortable').sortable({
        connectWith: '.nhl-sortable',
        placeholder: 'nhl-placeholder',

        stop: function () {
            $('.nhl-sortable').each(function () {

                let category = $(this).data('cat');
                let order = [];

                $(this).children('li').each(function () {
                    order.push($(this).attr('id').replace('post-', ''));
                });

                $.post(nhl_ajax.ajax_url, {
                    action: 'nhl_save_order',
                    order: order,
                    category: category,
                    nonce: nhl_ajax.nonce
                });

            });
        }
    });

    /* =========================================
       NEWS GRID — SORTABLE
    ========================================= */

    $('.nhl-sortable-grid').sortable({
        placeholder: 'nhl-placeholder',
        items: '> .nhl-item',
        tolerance: 'pointer',
        revert: true,
        helper: 'clone',

        update: function () {
            let order = [];

            $('.nhl-sortable-grid .nhl-item').each(function () {
                order.push($(this).data('id'));
            });

            $.post(nhl_ajax.ajax_url, {
                action: 'nhl_save_grid_order',
                order: order,
                nonce: nhl_ajax.nonce
            });
        }
    });

    /* =========================================
       SLOTS — GRID → HERO (ROCK SOLID SWAP)
    ========================================= */

    $('.nhl-drop').droppable({
        accept: '.nhl-sortable-grid .nhl-item',
        hoverClass: 'nhl-drop-hover',

        drop: function (event, ui) {

            let postId = ui.draggable.data('id');
            let slot = $(this).data('slot');

            let $slot = $(this);

            // ALWAYS get existing hero item FIRST
            let $oldHeroItem = $slot.children('.nhl-item').first();

            // Build new HERO item from GRID
            let $newHeroItem = normalizeToHero(ui.draggable);

            /* =========================
               MOVE OLD HERO → GRID
            ========================= */
            if ($oldHeroItem.length) {
                let $oldGridItem = normalizeToGrid($oldHeroItem);
                $('.nhl-sortable-grid').prepend($oldGridItem);
            }

            /* =========================
               REPLACE SLOT
            ========================= */
            $slot.empty().append($newHeroItem);

            /* =========================
               REMOVE ORIGINAL GRID ITEM
            ========================= */
            ui.draggable.remove();

            /* =========================
               SAVE SLOT
            ========================= */
            $.post(nhl_ajax.ajax_url, {
                action: 'nhl_save_news_slot',
                post_id: postId,
                slot: slot,
                nonce: nhl_ajax.nonce
            });
        }
    });

    /* =========================================
       REMOVE FROM HERO → GRID
    ========================================= */

    $(document).on('click', '.nhl-remove', function (e) {
        e.stopPropagation();

        let $heroItem = $(this).closest('.nhl-item');
        let postId = $heroItem.data('id');

        let $gridItem = normalizeToGrid($heroItem);
        $('.nhl-sortable-grid').prepend($gridItem);

        $.post(nhl_ajax.ajax_url, {
            action: 'nhl_clear_news_slot',
            post_id: postId,
            nonce: nhl_ajax.nonce
        });

        $heroItem.remove();
    });

    /* =========================================
       RESET NEWS LAYOUT
    ========================================= */

    $(document).on('click', '#nhl-reset-news', function (e) {
        e.preventDefault();

        if (!confirm('Reset News layout to default (newest first)?')) return;

        $.post(nhl_ajax.ajax_url, {
            action: 'nhl_reset_news_layout',
            nonce: nhl_ajax.nonce
        }, function (response) {
            if (response.success) {
                alert('News layout reset to default!');
                location.reload();
            } else {
                alert('Reset failed.');
            }
        });
    });

});
