<?php

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Author_Edit_Control {
    
    public function __construct() {
        // Hide Quick Edit for Authors
        add_action('admin_head-edit.php', array($this, 'hide_quick_edit_for_authors'));
        
        // Change post to draft when author clicks edit
        add_action('admin_init', array($this, 'change_to_draft_on_edit'));
    }
    
    /**
     * Hide Quick Edit option for Author role
     */
    public function hide_quick_edit_for_authors() {
        if (current_user_can('author') && !current_user_can('editor')) {
            ?>
            <style type="text/css">
                .inline-edit-row,
                button.editinline,
                .quickedit-action {
                    display: none !important;
                }
            </style>
            <?php
        }
    }
    
    /**
     * Change post status to draft when author edits a post
     */
    public function change_to_draft_on_edit() {
        // Check if we're in admin area
        if (!is_admin()) {
            return;
        }
        
        // Check if user is author (but not editor or admin)
        if (!current_user_can('author') || current_user_can('editor')) {
            return;
        }
        
        // Check if we're on the edit post page
        global $pagenow;
        if ($pagenow !== 'post.php') {
            return;
        }
        
        // Check if action is edit and post ID exists
        if (!isset($_GET['action']) || $_GET['action'] !== 'edit' || !isset($_GET['post'])) {
            return;
        }
        
        $post_id = intval($_GET['post']);
        $post = get_post($post_id);
        
        // Verify post exists and user can edit it
        if (!$post || !current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Only change to draft if post is published or pending
        if (in_array($post->post_status, array('publish', 'pending', 'future', 'private'))) {
            // Remove this action temporarily to avoid infinite loop
            remove_action('admin_init', array($this, 'change_to_draft_on_edit'));
            
            // Update post status to draft
            wp_update_post(array(
                'ID' => $post_id,
                'post_status' => 'draft'
            ));
            
            // Add admin notice
            add_action('admin_notices', array($this, 'draft_status_notice'));
        }
    }
    
    /**
     * Display admin notice when post is changed to draft
     */
    public function draft_status_notice() {
        ?>
        <div class="notice notice-info is-dismissible">
            <p><?php _e('Post status has been changed to Draft for editing.', 'author-edit-control'); ?></p>
        </div>
        <?php
    }
}

// Initialize the plugin
new Author_Edit_Control();