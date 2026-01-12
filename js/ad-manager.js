/**
 * Advertisement Manager JavaScript
 * Handles media upload and deletion
 */

(function($) {
    'use strict';
    
    let mediaFrame;
    
    $(document).ready(function() {
        // Upload button click
        $('.wwmt-upload-btn').on('click', function(e) {
            e.preventDefault();
            const spaceId = $(this).data('space-id');
            openMediaFrame(spaceId);
        });
        
        // Delete button click
        $('.wwmt-delete-btn').on('click', function(e) {
            e.preventDefault();
            const spaceId = $(this).data('space-id');
            
            if (confirm('Are you sure you want to delete this image?')) {
                deleteImage(spaceId);
            }
        });
    });
    
    /**
     * Open WordPress media frame for image selection
     */
    function openMediaFrame(spaceId) {
        if (mediaFrame) {
            mediaFrame.close();
        }
        
        mediaFrame = wp.media({
            title: 'Select Advertisement Image',
            button: {
                text: 'Use This Image'
            },
            library: {
                type: ['image'] // Accepts all image types: jpg, png, gif, webp, etc.
            },
            multiple: false
        });
        
        mediaFrame.on('select', function() {
            const attachment = mediaFrame.state().get('selection').first().toJSON();
            uploadImage(spaceId, attachment.id);
        });
        
        mediaFrame.open();
    }
    
    /**
     * Send selected image to server via AJAX
     */
    function uploadImage(spaceId, attachmentId) {
        $.ajax({
            url: wwmtAdManager.ajaxUrl,
            method: 'POST',
            data: {
                action: 'wwmt_upload_ad_image',
                nonce: wwmtAdManager.nonce,
                space_id: spaceId,
                attachment_id: attachmentId
            },
            success: function(response) {
                if (response.success) {
                    updatePreview(spaceId, response.data.image_url);
                    updateUrlField(spaceId, response.data.image_url);
                    enableDeleteButton(spaceId);
                    showNotice(response.data.message, 'success');
                } else {
                    showNotice(response.data || 'Error uploading image', 'error');
                }
            },
            error: function() {
                showNotice('Error uploading image', 'error');
            }
        });
    }
    
    /**
     * Delete image via AJAX
     */
    function deleteImage(spaceId) {
        $.ajax({
            url: wwmtAdManager.ajaxUrl,
            method: 'POST',
            data: {
                action: 'wwmt_delete_ad_image',
                nonce: wwmtAdManager.nonce,
                space_id: spaceId
            },
            success: function(response) {
                if (response.success) {
                    clearPreview(spaceId);
                    clearUrlField(spaceId);
                    disableDeleteButton(spaceId);
                    showNotice(response.data.message, 'success');
                } else {
                    showNotice(response.data || 'Error deleting image', 'error');
                }
            },
            error: function() {
                showNotice('Error deleting image', 'error');
            }
        });
    }
    
    /**
     * Update preview image
     */
    function updatePreview(spaceId, imageUrl) {
        const preview = $('#preview-' + spaceId);
        preview.html('<img src="' + imageUrl + '" alt="' + spaceId + '" />');
    }
    
    /**
     * Clear preview
     */
    function clearPreview(spaceId) {
        const preview = $('#preview-' + spaceId);
        preview.html('<p class="no-image">No image set</p>');
    }
    
    /**
     * Update URL field
     */
    function updateUrlField(spaceId, imageUrl) {
        $('#url-' + spaceId + ' input').val(imageUrl);
    }
    
    /**
     * Clear URL field
     */
    function clearUrlField(spaceId) {
        $('#url-' + spaceId + ' input').val('');
    }
    
    /**
     * Enable delete button
     */
    function enableDeleteButton(spaceId) {
        $('.wwmt-delete-btn[data-space-id="' + spaceId + '"]').prop('disabled', false);
    }
    
    /**
     * Disable delete button
     */
    function disableDeleteButton(spaceId) {
        $('.wwmt-delete-btn[data-space-id="' + spaceId + '"]').prop('disabled', true);
    }
    
    /**
     * Show admin notice
     */
    function showNotice(message, type) {
        const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        const notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.wrap').prepend(notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
})(jQuery);