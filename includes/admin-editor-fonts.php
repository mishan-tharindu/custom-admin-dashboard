<?php
/**
 * Custom fonts for block editor & frontend
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue custom fonts in Gutenberg editor
 */
add_action('enqueue_block_editor_assets', function () {

    wp_enqueue_style(
        'custom-editor-fonts',
        plugin_dir_url(dirname(__FILE__)) . 'css/editor-fonts.css',
        [],
        '1.0'
    );
});

/**
 * Enqueue custom fonts on frontend
 */
add_action('wp_enqueue_scripts', function () {

    wp_enqueue_style(
        'custom-frontend-fonts',
        plugin_dir_url(dirname(__FILE__)) . 'css/frontend-fonts.css',
        [],
        '1.0'
    );
});
