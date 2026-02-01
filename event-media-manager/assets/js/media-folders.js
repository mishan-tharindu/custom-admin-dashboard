jQuery(function ($) {

    let mediaFrame = null;

    /* =====================================
       UPLOAD IMAGES INTO EVENT
    ====================================== */
    $('.emm-upload').on('click', function (e) {
        e.preventDefault();

        const eventId = $(this).data('event');

        if (mediaFrame) {
            mediaFrame.open();
            return;
        }

        mediaFrame = wp.media({
            title: 'Add Photos to Event',
            button: {
                text: 'Add to Event'
            },
            multiple: true
        });

        mediaFrame.on('select', function () {
            const selection = mediaFrame.state().get('selection');

            const ids = [];
            selection.each(function (attachment) {
                ids.push(attachment.id);
            });

            if (!ids.length) return;

            $.post(ajaxurl, {
                action: 'emm_attach_images',
                event_id: eventId,
                files: ids
            }, function () {
                location.reload();
            });
        });

        mediaFrame.open();
    });

    /* =====================================
       OPEN IMAGE EDIT MODAL (ACF SUPPORT)
    ====================================== */
    // $(document).on('click', '.emm-image', function (e) {
    //     if ($(e.target).closest('.emm-remove').length) return;

    //     var attachmentId = $(this).data('id');

    //     // Destroy any previous detail frame so state is clean each time
    //     if (window._emmDetailFrame) {
    //         window._emmDetailFrame.remove();
    //         window._emmDetailFrame = null;
    //     }

    //     var detailFrame = wp.media({
    //         title: 'Attachment Details',
    //         multiple: false,
    //         library: {
    //             type: 'image',
    //             post_id: attachmentId  // not actually used for filtering, just carried along
    //         }
    //     });

    //     // Once the library view has rendered its grid, find our attachment's
    //     // thumbnail in the DOM and programmatically click it. This triggers
    //     // WP's own internal routing into the details view — no missing
    //     // controllers, no manual view swapping.
    //     detailFrame.on('open', function () {
    //         var state = detailFrame.state();
    //         var library = state.get('library');

    //         // Wait for the collection to finish its initial fetch
    //         library.on('reset', function onReset() {
    //             library.off('reset', onReset);

    //             // If our attachment isn't in the loaded page, add it manually
    //             var attachment = library.get(attachmentId);
    //             if (!attachment) {
    //                 attachment = wp.media.model.Attachment.get(attachmentId);
    //                 attachment.fetch().done(function () {
    //                     library.add(attachment);
    //                     clickThumb();
    //                 });
    //             } else {
    //                 clickThumb();
    //             }
    //         });

    //         function clickThumb() {
    //             // Small delay to let the view render the new/existing attachment
    //             setTimeout(function () {
    //                 var $thumb = detailFrame.$el.find('[data-id="' + attachmentId + '"]');
    //                 if ($thumb.length) {
    //                     $thumb.trigger('click');
    //                 }
    //             }, 150);
    //         }
    //     });

    //     window._emmDetailFrame = detailFrame;
    //     detailFrame.open();
    // });

    $(document).on('click', '.emm-image', function () {

        const attachmentId = $(this).data('id');

        const frame = wp.media({
            title: 'Edit Image',
            library: {
                type: 'image'
            },
            multiple: false
        });

        frame.on('open', function () {
            const selection = frame.state().get('selection');
            const attachment = wp.media.attachment(attachmentId);
            attachment.fetch();
            selection.add(attachment);
        });

        frame.open();
    });


    /* =====================================
       REMOVE IMAGE FROM EVENT
    ====================================== */

    $(document).on('click', '.emm-remove', function (e) {
        e.stopPropagation();

        if (!confirm('Remove image from this event?')) return;

        const id = $(this).data('id');

        $.post(ajaxurl, {
            action: 'emm_remove_image',
            image_id: id
        }, function () {
            location.reload();
        });
    });

    /* =====================================
        EVENT SEARCH FILTER
    ===================================== */
    $('.emm-search').on('keyup', function () {
        const term = $(this).val().toLowerCase();

        $('.emm-event').each(function () {
            const title = $(this).data('title');

            if (title.includes(term)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    /* =====================================
   DELETE EVENT
===================================== */
    $(document).on('click', '.emm-event-delete', function (e) {
        e.preventDefault();
        e.stopPropagation();

        if (!confirm('Delete this event? Images will NOT be deleted.')) return;

        const eventId = $(this).data('id');

        $.post(ajaxurl, {
            action: 'emm_delete_event',
            event_id: eventId
        }, function () {
            location.reload();
        });
    });

    /* =====================================
   SET EVENT COVER IMAGE
===================================== */
    $(document).on('click', '.emm-set-cover', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const eventId = $(this).data('id');

        const frame = wp.media({
            title: 'Select Event Cover',
            button: { text: 'Set Cover' },
            multiple: false,
            library: { type: 'image' }
        });

        frame.on('select', function () {
            const attachment = frame.state().get('selection').first();

            $.post(ajaxurl, {
                action: 'emm_set_event_cover',
                event_id: eventId,
                image_id: attachment.id
            }, function () {
                location.reload();
            });
        });

        frame.open();
    });

    /* =====================================
   DRAG & DROP EVENT REORDER
===================================== */
    $('.emm-grid').sortable({
        items: '.emm-event',
        tolerance: 'pointer',
        cursor: 'move',
        opacity: 0.8,

        update: function () {
            const order = [];

            $('.emm-event').each(function (index) {
                order.push({
                    id: $(this).data('id'),
                    position: index
                });
            });

            $.post(ajaxurl, {
                action: 'emm_save_event_order',
                order: order
            });
        }
    });

    /* =====================================
   DRAG & DROP IMAGE REORDER (EVENT VIEW)
===================================== */
    $('.emm-grid').each(function () {

        const $grid = $(this);

        // Only enable for image grids (not event grid)
        if (!$grid.find('.emm-image').length) return;

        $grid.sortable({
            items: '.emm-image',
            tolerance: 'pointer',
            cursor: 'move',
            opacity: 0.8,
            distance: 5,

            update: function () {
                const order = [];

                $grid.find('.emm-image').each(function (index) {
                    order.push({
                        id: $(this).data('id'),
                        position: index
                    });
                });

                $.post(ajaxurl, {
                    action: 'emm_save_image_order',
                    order: order
                });
            }
        });

    });

    /* =====================================
   PHOTOGRAPHER FILTER
===================================== */

    $('#emm-photographer-filter').on('change', function () {
        const photographer = $(this).val();
        const eventId = $(this).data('event');

        console.log('Filtering images by photographer:', photographer);

        $.post(ajaxurl, {
            action: 'emm_filter_images',
            event_id: eventId,
            photographer: photographer
        }, function (html) {
            $('.emm-grid').html(html);
        });
    });





});