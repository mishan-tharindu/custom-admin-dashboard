jQuery(function ($) {

    /* =========================================
       HELPERS — NORMALIZERS
    ========================================= */

    function normalizeToGrid($item) {
        let imgSrc = $item.find('img').attr('src') || '';
        let title = $item.find('.nhl-title').text() || $item.find('.nhl-card-title').text() || '';

        return $(`
            <li class="nhl-item nhl-card-item" data-id="${$item.data('id')}">
                <div class="nhl-card-thumb">
                    <img src="${imgSrc}" alt="">
                </div>
                <div class="nhl-card-title">${title}</div>
            </li>
        `);
    }

    function normalizeToHero($item) {
        let imgSrc = $item.find('img').attr('src') || '';
        let title = $item.find('.nhl-card-title').text() || $item.find('.nhl-title').text() || '';

        return $(`
            <li class="nhl-item" data-id="${$item.data('id')}">
                <img src="${imgSrc}">
                <span class="nhl-title">${title}</span>
                <span class="nhl-remove" title="Remove">✖</span>
            </li>
        `);
    }

    function getActiveCategory() {
        return $('.nhl-tab.active').data('tab');
    }

    function getActiveGrid() {
        let cat = getActiveCategory();
        return $('.nhl-tab-content[data-tab="' + cat + '"] .nhl-sortable-grid');
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
       GRID — SORTABLE (PER CATEGORY)
    ========================================= */

    $('.nhl-sortable-grid').sortable({
        placeholder: 'nhl-placeholder',
        items: '> .nhl-item',
        tolerance: 'pointer',

        update: function () {
            let category = getActiveCategory();
            let order = [];

            getActiveGrid().find('.nhl-item').each(function () {
                order.push($(this).data('id'));
            });

            $.post(nhl_ajax.ajax_url, {
                action: 'nhl_save_grid_order',
                order: order,
                category: category,
                nonce: nhl_ajax.nonce
            });
        }
    });

    /* =========================================
       SLOTS — GRID → HERO (PER CATEGORY)
    ========================================= */

    $('.nhl-drop').droppable({
        accept: '.nhl-sortable-grid .nhl-item',
        hoverClass: 'nhl-drop-hover',

        drop: function (event, ui) {

            let category = getActiveCategory();
            let postId = ui.draggable.data('id');
            let slot = $(this).data('slot');

            let $slot = $(this);
            let $activeGrid = getActiveGrid();

            let $oldHeroItem = $slot.children('.nhl-item').first();
            let $newHeroItem = normalizeToHero(ui.draggable);

            // Move old hero back to ACTIVE grid
            if ($oldHeroItem.length) {
                let $oldGridItem = normalizeToGrid($oldHeroItem);
                $activeGrid.prepend($oldGridItem);
            }

            $slot.empty().append($newHeroItem);
            ui.draggable.remove();

            // SAVE SLOT (DYNAMIC)
            $.post(nhl_ajax.ajax_url, {
                action: 'nhl_save_news_slot',
                post_id: postId,
                slot: slot,
                category: category,
                nonce: nhl_ajax.nonce
            });
        }
    });

    /* =========================================
       REMOVE FROM HERO → GRID (PER CATEGORY)
    ========================================= */

    $(document).on('click', '.nhl-remove', function (e) {
        e.stopPropagation();

        let category = getActiveCategory();
        let $heroItem = $(this).closest('.nhl-item');
        let postId = $heroItem.data('id');

        let $gridItem = normalizeToGrid($heroItem);
        getActiveGrid().prepend($gridItem);

        $.post(nhl_ajax.ajax_url, {
            action: 'nhl_clear_news_slot',
            post_id: postId,
            category: category,
            nonce: nhl_ajax.nonce
        });

        $heroItem.remove();
    });

    /* =========================================
       RESET — ALL CATEGORIES
    ========================================= */

    $(document).on('click', '[id^="nhl-reset-"]', function (e) {
        e.preventDefault();

        let category = getActiveCategory();

        if (!confirm('Reset ' + category + ' layout to default (newest first)?')) return;

        $.post(nhl_ajax.ajax_url, {
            action: 'nhl_reset_category_layout',
            category: category,
            nonce: nhl_ajax.nonce
        }, function (response) {
            if (response.success) {
                alert(category + ' layout reset!');
                location.reload();
            } else {
                alert('Reset failed.');
            }
        });
    });

});
