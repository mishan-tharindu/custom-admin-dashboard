<?php
/**
 * Fully restrict block typography & color options for Authors
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Hide editor UI panels using CSS (Authors only)
 */
add_action('enqueue_block_editor_assets', function () {

    if (current_user_can('edit_others_posts')) {
        return;
    }

    wp_enqueue_style(
        'author-editor-restrictions',
        plugin_dir_url(__DIR__) . 'css/author-editor-restrictions.css',
        [],
        '1.0'
    );
});
