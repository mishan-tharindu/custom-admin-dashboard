jQuery(document).ready(function($) {
    // Handle emoji button clicks
    $(document).on('click', '.emoji-btn', function(e) {
        e.preventDefault();
        
        const btn = $(this);
        const container = btn.closest('.emoji-reactions-container');
        const commentId = container.data('comment-id');
        const emojiType = btn.data('emoji');
        
        // Prevent multiple clicks
        if (btn.hasClass('loading')) {
            return;
        }
        
        // Add loading state
        btn.addClass('loading');
        
        // Send AJAX request
        $.ajax({
            url: emojiReactionsObj.ajaxUrl,
            type: 'POST',
            data: {
                action: 'emoji_reaction',
                comment_id: commentId,
                emoji_type: emojiType,
                nonce: emojiReactionsObj.nonce
            },
            success: function(response) {
                if (response.success) {
                    const reactions = response.data.reactions;
                    const reacted = response.data.reacted;
                    
                    // Update all emoji counts for this comment
                    container.find('.emoji-btn').each(function() {
                        const emoji = $(this).data('emoji');
                        const count = reactions[emoji] || 0;
                        $(this).find('.emoji-count').text(count);
                    });
                    
                    // Toggle active state
                    if (reacted) {
                        btn.addClass('active');
                        btn.addClass('clicked');
                    } else {
                        btn.removeClass('active');
                        btn.addClass('unclicked');
                    }
                    
                    // Remove animation classes after animation completes
                    setTimeout(() => {
                        btn.removeClass('clicked unclicked');
                    }, 300);
                } else {
                    console.error('Error:', response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
            },
            complete: function() {
                btn.removeClass('loading');
            }
        });
    });
});