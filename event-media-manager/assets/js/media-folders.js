jQuery(document).ready(function ($) {

    /* =========================================================
       EXISTING FUNCTIONALITY - Event Search, Upload, Delete etc.
    ========================================================= */

    // Event search
    $('.emm-search').on('keyup', function () {
        const val = $(this).val().toLowerCase();
        $('.emm-event').each(function () {
            const title = $(this).data('title');
            $(this).toggle(title.indexOf(val) > -1);
        });
    });

    // Upload photos to event
    $('.emm-upload').on('click', function (e) {
        e.preventDefault();
        const eventId = $(this).data('event');
        const frame = wp.media({
            title: 'Select Images',
            multiple: true,
            library: { type: 'image' }
        });

        frame.on('select', function () {
            const selection = frame.state().get('selection').toJSON();
            const ids = selection.map(img => img.id);

            $.post(ajaxurl, {
                action: 'emm_attach_images',
                event_id: eventId,
                files: ids
            }, () => location.reload());
        });

        frame.open();
    });

    // Remove image from event
    $(document).on('click', '.emm-remove', function (e) {
        e.preventDefault();
        e.stopPropagation();

        if (!confirm('Remove this image from the event?')) return;

        const imageId = $(this).data('id');
        const $card = $(this).closest('.emm-image');

        $.post(ajaxurl, {
            action: 'emm_remove_image',
            image_id: imageId
        }, () => $card.fadeOut(300, () => $card.remove()));
    });

    // Delete event
    $(document).on('click', '.emm-event-delete', function (e) {
        e.preventDefault();
        e.stopPropagation();

        if (!confirm('Delete this event? Images will be unlinked but not deleted.')) return;

        const eventId = $(this).data('id');
        const $card = $(this).closest('.emm-event');

        $.post(ajaxurl, {
            action: 'emm_delete_event',
            event_id: eventId
        }, (response) => {
            if (response.success) {
                $card.fadeOut(300, () => $card.remove());
            }
        });
    });

    // Set event cover image
    $(document).on('click', '.emm-set-cover', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const eventId = $(this).data('id');
        const frame = wp.media({
            title: 'Select Cover Image',
            multiple: false,
            library: { type: 'image' }
        });

        frame.on('select', function () {
            const selection = frame.state().get('selection').first().toJSON();

            $.post(ajaxurl, {
                action: 'emm_set_event_cover',
                event_id: eventId,
                image_id: selection.id
            }, () => location.reload());
        });

        frame.open();
    });

    // Sortable events
    if ($('.emm-grid').length && !$('.emm-grid').hasClass('emm-images-grid')) {
        $('.emm-grid').sortable({
            items: '.emm-event',
            cursor: 'move',
            opacity: 0.7,
            update: function () {
                const order = [];
                $('.emm-event').each(function (i) {
                    order.push({
                        id: $(this).data('id'),
                        position: i
                    });
                });

                $.post(ajaxurl, {
                    action: 'emm_save_event_order',
                    order: order
                });
            }
        });
    }

    // Sortable images inside event
    if ($('.emm-image').length) {
        $('.emm-grid').sortable({
            items: '.emm-image',
            cursor: 'move',
            opacity: 0.7,
            update: function () {
                const order = [];
                $('.emm-image').each(function (i) {
                    order.push({
                        id: $(this).data('id'),
                        position: i
                    });
                });

                $.post(ajaxurl, {
                    action: 'emm_save_image_order',
                    order: order
                });
            }
        });
    }

    // Filter by photographer
    $('#emm-photographer-filter').on('change', function () {
        const eventId = $(this).data('event');
        const photographer = $(this).val();
        const tagFilter = $('#emm-tag-filter').val();

        $.post(ajaxurl, {
            action: 'emm_filter_by_tag',
            event_id: eventId,
            photographer: photographer,
            tag_id: tagFilter
        }, function (response) {
            $('.emm-grid').html(response);
        });
    });

    /* =========================================================
       NEW TAG FUNCTIONALITY
    ========================================================= */

    // Filter by tag
    $('#emm-tag-filter').on('change', function () {
        const eventId = $(this).data('event');
        const tagId = $(this).val();
        const photographer = $('#emm-photographer-filter').val();

        $.post(ajaxurl, {
            action: 'emm_filter_by_tag',
            event_id: eventId,
            photographer: photographer,
            tag_id: tagId
        }, function (response) {
            $('.emm-grid').html(response);
        });
    });

    /* =========================================================
       TAG MANAGEMENT PAGE
    ========================================================= */

    // Add new tag
    $('#emm-add-tag-form').on('submit', function (e) {
        e.preventDefault();

        const $form = $(this);
        const $button = $form.find('button[type="submit"]');
        const buttonText = $button.text();

        $button.prop('disabled', true).text('Adding...');

        $.post(ajaxurl, {
            action: 'emm_add_tag',
            nonce: $('#emm_tag_nonce').val(),
            name: $('#tag-name').val(),
            slug: $('#tag-slug').val(),
            description: $('#tag-description').val()
        }, function (response) {
            if (response.success) {
                const tag = response.data;

                // Remove "no tags" message if exists
                $('#emm-tags-list tr').first().each(function () {
                    if ($(this).find('td').length === 1) {
                        $(this).remove();
                    }
                });

                // Add new row
                const newRow = `
                    <tr data-tag-id="${tag.term_id}">
                        <td>
                            <strong class="tag-name-display">${tag.name}</strong>
                            <input type="text" class="tag-name-edit regular-text" value="${tag.name}" style="display:none;">
                        </td>
                        <td>
                            <span class="tag-slug-display">${tag.slug}</span>
                            <input type="text" class="tag-slug-edit regular-text" value="${tag.slug}" style="display:none;">
                        </td>
                        <td>
                            <span class="tag-desc-display">${tag.description}</span>
                            <textarea class="tag-desc-edit" style="display:none; width: 100%;" rows="2">${tag.description}</textarea>
                        </td>
                        <td>${tag.count}</td>
                        <td>
                            <button class="button button-small emm-edit-tag-btn">Edit</button>
                            <button class="button button-small emm-save-tag-btn" style="display:none;">Save</button>
                            <button class="button button-small emm-cancel-tag-btn" style="display:none;">Cancel</button>
                            <button class="button button-small button-link-delete emm-delete-tag-btn">Delete</button>
                        </td>
                    </tr>
                `;

                $('#emm-tags-list').append(newRow);

                // Reset form
                $form[0].reset();
                alert('Tag added successfully!');
            } else {
                alert('Error: ' + response.data);
            }
        }).always(function () {
            $button.prop('disabled', false).text(buttonText);
        });
    });

    // Edit tag inline
    $(document).on('click', '.emm-edit-tag-btn', function () {
        const $row = $(this).closest('tr');

        $row.find('.tag-name-display, .tag-slug-display, .tag-desc-display').hide();
        $row.find('.tag-name-edit, .tag-slug-edit, .tag-desc-edit').show();

        $(this).hide();
        $row.find('.emm-save-tag-btn, .emm-cancel-tag-btn').show();
    });

    // Cancel edit
    $(document).on('click', '.emm-cancel-tag-btn', function () {
        const $row = $(this).closest('tr');

        $row.find('.tag-name-display, .tag-slug-display, .tag-desc-display').show();
        $row.find('.tag-name-edit, .tag-slug-edit, .tag-desc-edit').hide();

        $(this).hide();
        $row.find('.emm-save-tag-btn').hide();
        $row.find('.emm-edit-tag-btn').show();
    });

    // Save tag
    $(document).on('click', '.emm-save-tag-btn', function () {
        const $row = $(this).closest('tr');
        const $button = $(this);
        const termId = $row.data('tag-id');

        const newName = $row.find('.tag-name-edit').val();
        const newSlug = $row.find('.tag-slug-edit').val();
        const newDesc = $row.find('.tag-desc-edit').val();

        $button.prop('disabled', true).text('Saving...');

        $.post(ajaxurl, {
            action: 'emm_update_tag',
            term_id: termId,
            name: newName,
            slug: newSlug,
            description: newDesc
        }, function (response) {
            if (response.success) {
                $row.find('.tag-name-display').text(newName);
                $row.find('.tag-slug-display').text(newSlug);
                $row.find('.tag-desc-display').text(newDesc);

                $row.find('.tag-name-display, .tag-slug-display, .tag-desc-display').show();
                $row.find('.tag-name-edit, .tag-slug-edit, .tag-desc-edit').hide();

                $button.hide();
                $row.find('.emm-cancel-tag-btn').hide();
                $row.find('.emm-edit-tag-btn').show();

                alert('Tag updated successfully!');
            } else {
                alert('Error: ' + response.data);
            }
        }).always(function () {
            $button.prop('disabled', false).text('Save');
        });
    });

    // Delete tag
    $(document).on('click', '.emm-delete-tag-btn', function () {
        if (!confirm('Are you sure you want to delete this tag?')) return;

        const $row = $(this).closest('tr');
        const termId = $row.data('tag-id');

        $.post(ajaxurl, {
            action: 'emm_delete_tag',
            term_id: termId
        }, function (response) {
            if (response.success) {
                $row.fadeOut(300, function () {
                    $(this).remove();

                    // Check if table is empty
                    if ($('#emm-tags-list tr').length === 0) {
                        $('#emm-tags-list').html(`
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px;">
                                    No tags found. Add your first tag!
                                </td>
                            </tr>
                        `);
                    }
                });
            } else {
                alert('Error deleting tag');
            }
        });
    });

    /* =========================================================
       TAG EDITOR MODAL FOR IMAGES
    ========================================================= */

    let currentImageId = 0;
    let selectedTags = [];

    // Open tag editor
    $(document).on('click', '.emm-edit-tags', function (e) {
        e.preventDefault();
        e.stopPropagation();

        currentImageId = $(this).data('id');
        selectedTags = [];

        console.log('Opening tag editor for image:', currentImageId);

        // Load current tags
        $.post(ajaxurl, {
            action: 'emm_get_image_tags',
            image_id: currentImageId
        }, function (response) {
            console.log('Loaded tags:', response);

            if (response.success) {
                selectedTags = response.data;
                renderSelectedTags();
            }

            $('#emm-tag-modal').fadeIn(300);
            $('#emm-tag-search').focus();
        });
    });

    // Close modal
    $('.emm-tag-modal-close, #emm-tag-modal').on('click', function (e) {
        if (e.target === this) {
            $('#emm-tag-modal').fadeOut(300);
            $('#emm-tag-search').val('');
            $('#emm-tag-suggestions').hide();
        }
    });

    // Prevent modal content clicks from closing
    $('.emm-tag-modal-content').on('click', function (e) {
        e.stopPropagation();
    });

    // Tag search autocomplete
    let searchTimeout;
    $('#emm-tag-search').on('keyup', function (e) {
        const search = $(this).val();

        clearTimeout(searchTimeout);

        // Allow Enter key to add first suggestion
        if (e.which === 13) {
            e.preventDefault();
            const firstSuggestion = $('#emm-tag-suggestions div').first();
            if (firstSuggestion.length) {
                firstSuggestion.click();
            }
            return;
        }

        if (search.length < 1) {
            $('#emm-tag-suggestions').hide();
            return;
        }

        searchTimeout = setTimeout(function () {
            console.log('Searching tags:', search);

            $.post(ajaxurl, {
                action: 'emm_search_tags',
                search: search
            }, function (response) {
                console.log('Search results:', response);

                if (response.success && response.data.length > 0) {
                    const suggestions = response.data
                        .filter(tag => !selectedTags.find(t => t.id === tag.id))
                        .map(tag => `<div data-id="${tag.id}" data-name="${tag.name}">${tag.name}</div>`)
                        .join('');

                    if (suggestions) {
                        $('#emm-tag-suggestions').html(suggestions).show();
                    } else {
                        $('#emm-tag-suggestions').hide();
                    }
                } else {
                    $('#emm-tag-suggestions').hide();
                }
            });
        }, 300);
    });

    // Select tag from suggestions
    $(document).on('click', '#emm-tag-suggestions div', function () {
        const tag = {
            id: $(this).data('id'),
            name: $(this).data('name')
        };

        console.log('Selected tag:', tag);

        // Check if not already selected
        if (!selectedTags.find(t => t.id === tag.id)) {
            selectedTags.push(tag);
            renderSelectedTags();
        }

        $('#emm-tag-search').val('');
        $('#emm-tag-suggestions').hide();
    });

    // Remove tag
    $(document).on('click', '.emm-tag-remove', function (e) {
        e.preventDefault();
        const tagId = $(this).data('id');
        console.log('Removing tag:', tagId);
        selectedTags = selectedTags.filter(t => t.id !== tagId);
        renderSelectedTags();
    });

    // Render selected tags
    function renderSelectedTags() {
        const html = selectedTags.map(tag =>
            `<span class="emm-tag-item">
                ${tag.name}
                <span class="emm-tag-remove" data-id="${tag.id}">×</span>
            </span>`
        ).join('');

        $('#emm-selected-tags').html(html || '<em style="color: #999;">No tags selected</em>');
        console.log('Rendered tags:', selectedTags);
    }

    // Save tags
    $('#emm-save-tags').on('click', function () {
        const $button = $(this);
        const buttonText = $button.text();

        console.log('Saving tags for image:', currentImageId, selectedTags);

        $button.prop('disabled', true).text('Saving...');

        const tagIds = selectedTags.map(t => t.id);

        $.post(ajaxurl, {
            action: 'emm_save_image_tags',
            image_id: currentImageId,
            tag_ids: tagIds,
            nonce: emmData.nonce
        }, function (response) {
            console.log('Save response:', response);

            if (response.success) {
                $('#emm-tag-modal').fadeOut(300);

                // Update tag display on image card
                const $card = $(`.emm-image[data-id="${currentImageId}"]`);
                const tagNames = selectedTags.map(t => t.name).join(', ');

                let $tagDisplay = $card.find('.emm-tags-display');
                if ($tagDisplay.length) {
                    if (tagNames) {
                        $tagDisplay.text(tagNames);
                    } else {
                        $tagDisplay.remove();
                    }
                } else if (tagNames) {
                    $card.find('p').after(`<div class="emm-tags-display">${tagNames}</div>`);
                }

                // Show success message
                $('<div class="notice notice-success is-dismissible" style="position: fixed; top: 32px; right: 20px; z-index: 100001;"><p>Tags saved successfully!</p></div>')
                    .appendTo('body')
                    .delay(3000)
                    .fadeOut(300, function () { $(this).remove(); });
            } else {
                alert('Error saving tags: ' + (response.data || 'Unknown error'));
            }
        }).fail(function (xhr, status, error) {
            console.error('AJAX error:', status, error);
            alert('Error saving tags. Please check console.');
        }).always(function () {
            $button.prop('disabled', false).text(buttonText);
        });
    });

});

(function ($) {

    if (typeof wp === 'undefined' || !wp.media) return;

    window.emmRefreshAttachment = function (attachmentId) {

        const attachment = wp.media.attachment(attachmentId);
        if (!attachment) return;

        attachment.fetch({
            success: function (model, response) {

                // ✅ Update compat silently (prevents validation crash)
                if (response && response.compat) {
                    model.set('compat', response.compat, { silent: true });
                }

                // ✅ Safely re-render sidebar (no Backbone validation)
                if (wp.media.frame && wp.media.frame.content) {
                    wp.media.frame.content.render();
                }
            }
        });
    };

})(jQuery);
