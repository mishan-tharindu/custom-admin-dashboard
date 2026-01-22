const { addFilter } = wp.hooks;
const { select, dispatch, subscribe } = wp.data;

console.log('Author Restrict Script Loaded');

// Change "Save draft" button text to "Ready for Review"
addFilter(
    'i18n.gettext',
    'custom/change-save-draft-text',
    function (translation, text, domain) {
        if (text === 'Save draft' || text === 'Save Draft') {
            return 'Ready for Review';
        }
        if (text === 'Switch to draft') {
            return 'Ready for Review';
        }
        return translation;
    }
);

// Main functionality
wp.domReady(function () {
    console.log('DOM Ready - Starting author restrictions');
    
    let hasSetInitialStatus = false;
    let hasMountedButton = false;
    let checkCount = 0;
    const maxChecks = 20;

    // Update button text immediately on page load
    setTimeout(function() {
        updateAllButtonTexts();
    }, 100);

    // Function to check and add button for published posts
    function checkAndAddButton() {
        const postStatus = select('core/editor') ? select('core/editor').getCurrentPostAttribute('post_status') : null;
        
        console.log('Check count:', checkCount, 'Post status:', postStatus, 'Button mounted:', hasMountedButton);
        
        if (postStatus === 'publish' && !hasMountedButton && checkCount < maxChecks) {
            const headerSettings = document.querySelector('.editor-header__settings');
            
            console.log('Header settings element:', headerSettings);
            
            if (headerSettings) {
                console.log('Adding Save Draft button...');
                addSaveDraftButton();
                hasMountedButton = true;
            } else {
                checkCount++;
                setTimeout(checkAndAddButton, 500);
            }
        }
    }

    // Start checking for button after a delay
    setTimeout(checkAndAddButton, 1000);

    // Subscribe to editor changes
    subscribe(function () {
        const postStatus = select('core/editor') ? select('core/editor').getCurrentPostAttribute('post_status') : null;
        
        // Set initial status on first load for published posts
        if (!hasSetInitialStatus && postStatus) {
            hasSetInitialStatus = true;
            
            console.log('Initial post status:', postStatus);
            
            if (postStatus === 'publish') {
                console.log('Post is published - forcing to draft');
                // Force to draft status
                dispatch('core/editor').editPost({ status: 'draft' });
                
                // Show notice
                setTimeout(function() {
                    dispatch('core/notices').createNotice(
                        'warning',
                        'This post was published. Your changes will save it as a draft requiring approval.',
                        {
                            isDismissible: true,
                            type: 'snackbar',
                        }
                    );
                }, 500);
            }
        }

        // Try to add button if not already mounted
        if (postStatus === 'publish' && !hasMountedButton) {
            checkAndAddButton();
        }

        // Continuously update button texts
        updateAllButtonTexts();
    });

    // Also update buttons on interval for reliability
    setInterval(updateAllButtonTexts, 500);
});

// Function to add Save Draft button to the header
function addSaveDraftButton() {
    console.log('addSaveDraftButton called');
    
    // Find the editor header settings area
    const headerSettings = document.querySelector('.editor-header__settings');
    
    if (!headerSettings) {
        console.log('Header settings not found, retrying...');
        setTimeout(addSaveDraftButton, 500);
        return;
    }

    // Check if button already exists
    if (document.querySelector('#custom-save-draft-btn')) {
        console.log('Button already exists');
        return;
    }

    console.log('Creating Save Draft button');

    // Create the Save Draft button
    const saveDraftBtn = document.createElement('button');
    saveDraftBtn.type = 'button';
    saveDraftBtn.id = 'custom-save-draft-btn';
    saveDraftBtn.className = 'components-button editor-post-save-draft is-compact is-tertiary';
    saveDraftBtn.textContent = 'Ready for Review';
    saveDraftBtn.setAttribute('aria-label', 'Ready for Review');
    saveDraftBtn.setAttribute('aria-disabled', 'false');
    
    // Add click handler
    saveDraftBtn.addEventListener('click', function(e) {
        e.preventDefault();
        console.log('Ready for Review button clicked');
        
        saveDraftBtn.disabled = true;
        saveDraftBtn.textContent = 'Saving...';
        
        dispatch('core/editor').editPost({ status: 'draft' });
        
        dispatch('core/editor').savePost().then(function() {
            saveDraftBtn.disabled = false;
            saveDraftBtn.textContent = 'Ready for Review';
            
            dispatch('core/notices').createNotice(
                'success',
                'Post saved as draft. Ready for review!',
                {
                    isDismissible: true,
                    type: 'snackbar',
                }
            );
        });
    });
    
    // Find the publish button
    const publishButton = headerSettings.querySelector('.editor-post-publish-button');
    
    console.log('Publish button found:', publishButton);
    
    if (publishButton) {
        headerSettings.insertBefore(saveDraftBtn, publishButton);
        console.log('Button inserted before publish button');
    } else {
        const optionsMenu = headerSettings.querySelector('.components-dropdown-menu');
        if (optionsMenu) {
            headerSettings.insertBefore(saveDraftBtn, optionsMenu);
            console.log('Button inserted before options menu');
        } else {
            headerSettings.appendChild(saveDraftBtn);
            console.log('Button appended to header settings');
        }
    }
    
    console.log('Save Draft button added successfully!');
}

// Function to update all button texts
function updateAllButtonTexts() {
    const saveDraftButtons = document.querySelectorAll('.editor-post-save-draft');
    saveDraftButtons.forEach(function(button) {
        if (button.textContent.includes('Save draft') || button.textContent.includes('Save Draft')) {
            button.textContent = 'Ready for Review';
            button.setAttribute('aria-label', 'Ready for Review');
        }
    });

    const switchToDraftButtons = document.querySelectorAll('.editor-post-switch-to-draft');
    switchToDraftButtons.forEach(function(button) {
        if (button.textContent.includes('Switch to draft')) {
            button.textContent = 'Ready for Review';
            button.setAttribute('aria-label', 'Ready for Review');
        }
    });

    const customButton = document.querySelector('#custom-save-draft-btn');
    if (customButton && !customButton.textContent.includes('Ready for Review')) {
        customButton.textContent = 'Ready for Review';
        customButton.setAttribute('aria-label', 'Ready for Review');
    }
}