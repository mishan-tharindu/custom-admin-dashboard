jQuery(document).ready(function($) {
    // Handle emoji button clicks
    $(document).on('click', '.emoji-btn', function(e) {
        e.preventDefault();
        
        const btn = $(this);
        const container = btn.closest('.emoji-reactions-container');
        const commentId = container.data('comment-id');
        const emojiType = btn.data('emoji');
        
        // Prevent multiple clicks while loading
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
                    const reacted = response.data.reacted; // true if added, false if removed
                    
                    // 1. Update counts for ALL buttons
                    container.find('.emoji-btn').each(function() {
                        const currentBtn = $(this);
                        const emojiKey = currentBtn.data('emoji');
                        const count = reactions[emojiKey] || 0;
                        currentBtn.find('.emoji-count').text(count);
                    });
                    
                    // 2. Handle Mutual Exclusivity (Single Choice)
                    
                    // First, remove 'active' from ALL buttons in this container
                    // because we might be switching from 'Happy' to 'Like'
                    container.find('.emoji-btn').removeClass('active clicked');

                    // If the user effectively "Added" a reaction (didn't just toggle off)
                    // Add active class to the clicked button
                    if (reacted) {
                        btn.addClass('active clicked');
                    } else {
                        // User toggled off, add unclicked animation class if desired
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