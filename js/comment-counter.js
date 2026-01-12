jQuery(function($) {
    'use strict';
    
    const minWords = commentCounter.min_words;
    const maxWords = commentCounter.max_words;
    const textarea = $('#comment');
    
    // Create counter display element
    function createCounterBox() {
        const html = `
            <div class="comment-counter-box">
                <span class="word-count-label">${commentCounter.currentText}</span>
                <span class="word-count-value word-count-current" id="wordCount">0</span> / ${maxWords}
                <div class="word-count-limits">
                    (Min: ${minWords} | Max: ${maxWords})
                </div>
            </div>
        `;
        
        if (textarea.length) {
            // Wrap textarea in a container for positioning
            textarea.wrap('<div class="textarea-wrapper"></div>');
            const wrapper = textarea.closest('.textarea-wrapper');
            wrapper.prepend(html);
        }
    }
    
    // Update word count
    function updateWordCount() {
        const text = textarea.val().trim();
        const words = text.length === 0 ? 0 : text.split(/\s+/).length;
        const counter = $('#wordCount');
        
        counter.text(words);
        
        // Remove all status classes
        counter.removeClass('warning error success');
        
        // Add appropriate status class
        if (words < minWords) {
            counter.addClass('error');
        } else if (words > maxWords) {
            counter.addClass('warning');
        } else if (words > 0) {
            counter.addClass('success');
        }
    }
    
    // Initialize on page load
    if (textarea.length) {
        createCounterBox();
        updateWordCount();
        
        // Update on input and change events
        textarea.on('input change keyup paste', function() {
            setTimeout(updateWordCount, 10);
        });
    }
});