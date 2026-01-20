jQuery(document).ready(function($) {
    
    $('.reaction-btn').on('click', function() {
        const $btn = $(this);
        const reaction = $btn.data('reaction');
        
        // Prevent multiple clicks
        if ($btn.hasClass('loading')) {
            return;
        }
        
        $btn.addClass('loading');
        
        $.ajax({
            url: reactionsData.ajax_url,
            type: 'POST',
            data: {
                action: 'react_to_post',
                nonce: reactionsData.nonce,
                post_id: reactionsData.post_id,
                reaction: reaction
            },
            success: function(response) {
                if (response.success) {
                    updateReactions(response.data.stats, response.data.user_vote);
                }
            },
            error: function() {
                console.error('Failed to save reaction');
            },
            complete: function() {
                $btn.removeClass('loading');
            }
        });
    });
    
    function updateReactions(stats, userVote) {
        // Update percentages and active states
        $('.reaction-btn').each(function() {
            const $btn = $(this);
            const reaction = $btn.data('reaction');
            const percentage = stats[reaction];
            
            // Update percentage text
            const $percentage = $btn.find('.reaction-percentage');
            $percentage.text(percentage + '%');
            
            // Update has-votes class
            if (percentage > 0) {
                $percentage.addClass('has-votes');
            } else {
                $percentage.removeClass('has-votes');
            }
            
            // Update active state
            if (userVote === reaction) {
                $btn.addClass('active');
            } else {
                $btn.removeClass('active');
            }
        });
    }
    
});