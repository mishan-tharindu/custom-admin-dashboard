jQuery(document).ready(function($) {
    
    // Toggle accordion
    $('.cad-post-accordion-header').on('click', function() {
        const $item = $(this).closest('.cad-post-accordion-item');
        const $content = $item.find('.cad-post-accordion-content');
        const postId = $item.data('post-id');
        
        // Close other accordions
        $('.cad-post-accordion-item').not($item).removeClass('active');
        $('.cad-post-accordion-content').not($content).slideUp(300);
        
        // Toggle current accordion
        $item.toggleClass('active');
        
        if ($item.hasClass('active')) {
            $content.slideDown(300);
            
            // Load comments if not already loaded
            if (!$content.data('loaded')) {
                loadComments(postId, $content);
            }
        } else {
            $content.slideUp(300);
        }
    });
    
    // Load comments via AJAX
    function loadComments(postId, $container) {
        $.ajax({
            url: cadCommentsAccordion.ajax_url,
            type: 'POST',
            data: {
                action: 'cad_load_post_comments',
                post_id: postId,
                nonce: cadCommentsAccordion.nonce
            },
            success: function(response) {
                if (response.success) {
                    $container.html(response.data.html);
                    $container.data('loaded', true);
                    initCommentActions();
                } else {
                    $container.html('<p>Error loading comments.</p>');
                }
            },
            error: function() {
                $container.html('<p>Error loading comments.</p>');
            }
        });
    }
    
    
        // Approve comment
        $(document).on('click', '.cad-approve-comment', function(e) {
            e.preventDefault();
            const commentId = $(this).data('comment-id');
            updateCommentStatus(commentId, 'approve', $(this));
        });
        
        // Unapprove comment
        $(document).on('click', '.cad-unapprove-comment', function(e) {
            e.preventDefault();
            const commentId = $(this).data('comment-id');
            updateCommentStatus(commentId, 'hold', $(this));
        });
        
        // Spam comment
        $(document).on('click', '.cad-spam-comment', function(e) {
            e.preventDefault();
            const commentId = $(this).data('comment-id');
            if (confirm('Mark this comment as spam?')) {
                updateCommentStatus(commentId, 'spam', $(this));
            }
        });
        
        // Unspam comment
        $(document).on('click', '.cad-unspam-comment', function(e) {
            e.preventDefault();
            const commentId = $(this).data('comment-id');
            updateCommentStatus(commentId, 'approve', $(this));
        });
        
        // Trash comment
        $(document).on('click', '.cad-trash-comment', function(e) {
            e.preventDefault();
            const commentId = $(this).data('comment-id');
            if (confirm('Move this comment to trash?')) {
                trashComment(commentId, $(this));
            }
        });
        
        // Restore comment
        $(document).on('click', '.cad-restore-comment', function(e) {
            e.preventDefault();
            const commentId = $(this).data('comment-id');
            restoreComment(commentId, $(this));
        });
        
        // Delete comment permanently
        $(document).on('click', '.cad-delete-comment', function(e) {
            e.preventDefault();
            const commentId = $(this).data('comment-id');
            if (confirm('Permanently delete this comment? This cannot be undone!')) {
                deleteComment(commentId, $(this));
            }
        });
    
    // Update comment status
    function updateCommentStatus(commentId, status, $button) {
        const $row = $button.closest('.cad-comment-row');
        const $item = $row.closest('.cad-post-accordion-item');
        
        $.ajax({
            url: cadCommentsAccordion.ajax_url,
            type: 'POST',
            data: {
                action: 'cad_toggle_comment_status',
                comment_id: commentId,
                status: status,
                nonce: cadCommentsAccordion.nonce
            },
            beforeSend: function() {
                $button.prop('disabled', true).text('Processing...');
            },
            success: function(response) {
                if (response.success) {
                    // Update counts
                    console.log('Received counts:', response.data.counts); // Debug log
                    updateCountBadges($item, response.data.counts);
                    
                    // Reload the current accordion
                    const $content = $item.find('.cad-post-accordion-content');
                    const postId = $item.data('post-id');
                    
                    $content.data('loaded', false);
                    loadComments(postId, $content);
                    
                    // Show success message
                    showNotice('Comment status updated successfully', 'success');
                } else {
                    showNotice('Failed to update comment status', 'error');
                    $button.prop('disabled', false).text('Try Again');
                }
            },
            error: function() {
                showNotice('An error occurred', 'error');
                $button.prop('disabled', false).text('Try Again');
            }
        });
    }
    
    // Trash comment
    function trashComment(commentId, $button) {
        const $row = $button.closest('.cad-comment-row');
        const $item = $row.closest('.cad-post-accordion-item');
        
        $.ajax({
            url: cadCommentsAccordion.ajax_url,
            type: 'POST',
            data: {
                action: 'cad_trash_comment',
                comment_id: commentId,
                nonce: cadCommentsAccordion.nonce
            },
            beforeSend: function() {
                $button.prop('disabled', true).text('Trashing...');
            },
            success: function(response) {
                if (response.success) {
                    // Update counts
                    updateCountBadges($item, response.data.counts);
                    
                    // Reload the accordion
                    const $content = $item.find('.cad-post-accordion-content');
                    const postId = $item.data('post-id');
                    $content.data('loaded', false);
                    loadComments(postId, $content);
                    
                    showNotice('Comment moved to trash', 'success');
                } else {
                    showNotice('Failed to trash comment', 'error');
                    $button.prop('disabled', false).text('Trash');
                }
            },
            error: function() {
                showNotice('An error occurred', 'error');
                $button.prop('disabled', false).text('Trash');
            }
        });
    }
    
    // Restore comment from trash
    function restoreComment(commentId, $button) {
        const $row = $button.closest('.cad-comment-row');
        const $item = $row.closest('.cad-post-accordion-item');
        
        $.ajax({
            url: cadCommentsAccordion.ajax_url,
            type: 'POST',
            data: {
                action: 'cad_restore_comment',
                comment_id: commentId,
                nonce: cadCommentsAccordion.nonce
            },
            beforeSend: function() {
                $button.prop('disabled', true).text('Restoring...');
            },
            success: function(response) {
                if (response.success) {
                    // Update counts
                    updateCountBadges($item, response.data.counts);
                    
                    // Reload the accordion
                    const $content = $item.find('.cad-post-accordion-content');
                    const postId = $item.data('post-id');
                    $content.data('loaded', false);
                    loadComments(postId, $content);
                    
                    showNotice('Comment restored successfully', 'success');
                } else {
                    showNotice('Failed to restore comment', 'error');
                    $button.prop('disabled', false).text('Restore');
                }
            },
            error: function() {
                showNotice('An error occurred', 'error');
                $button.prop('disabled', false).text('Restore');
            }
        });
    }
    
    // Delete comment
    function deleteComment(commentId, $button) {
        const $row = $button.closest('.cad-comment-row');
        const $item = $row.closest('.cad-post-accordion-item');
        
        $.ajax({
            url: cadCommentsAccordion.ajax_url,
            type: 'POST',
            data: {
                action: 'cad_delete_comment',
                comment_id: commentId,
                nonce: cadCommentsAccordion.nonce
            },
            beforeSend: function() {
                $button.prop('disabled', true).text('Deleting...');
            },
            success: function(response) {
                if (response.success) {
                    // Update counts
                    updateCountBadges($item, response.data.counts);
                    
                    // Remove row with animation
                    $row.fadeOut(300, function() {
                        $(this).remove();
                    });
                    
                    showNotice('Comment permanently deleted', 'success');
                } else {
                    showNotice('Failed to delete comment', 'error');
                    $button.prop('disabled', false).text('Delete');
                }
            },
            error: function() {
                showNotice('An error occurred', 'error');
                $button.prop('disabled', false).text('Delete');
            }
        });
    }
    
    // Update count badges in the accordion header
    function updateCountBadges($item, counts) {
        const $counts = $item.find('.cad-comment-counts');
        
        console.log('Updating badges with counts:', counts); // Debug log
        
        // Update approved
        const $approved = $counts.find('[data-count-type="approved"]');
        $approved.find('.count-number').text(counts.approved);
        $approved.css('display', 'inline-block'); // Always show approved
        
        // Update pending
        const $pending = $counts.find('[data-count-type="pending"]');
        $pending.find('.count-number').text(counts.pending);
        if (counts.pending > 0) {
            $pending.css('display', 'inline-block');
        } else {
            $pending.css('display', 'none');
        }
        
        // Update spam
        const $spam = $counts.find('[data-count-type="spam"]');
        $spam.find('.count-number').text(counts.spam);
        if (counts.spam > 0) {
            $spam.css('display', 'inline-block');
        } else {
            $spam.css('display', 'none');
        }
        
        // Update trash
        const $trash = $counts.find('[data-count-type="trash"]');
        $trash.find('.count-number').text(counts.trash);
        if (counts.trash > 0) {
            $trash.css('display', 'inline-block');
        } else {
            $trash.css('display', 'none');
        }
        
        // Update total
        const $total = $counts.find('[data-count-type="total"]');
        $total.find('.count-number').text(counts.total);
        $total.css('display', 'inline-block'); // Always show total
    }
    
    // Show notice message
    function showNotice(message, type) {
        const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        const $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.wrap h1').after($notice);
        
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }
    
});