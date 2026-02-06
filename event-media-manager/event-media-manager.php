<?php
/*
Plugin Name: Event Media Manager
Description: Custom event-based media dashboard with image metadata and tags
Version: 2.2.0
Author: MT
*/

if (!defined('ABSPATH')) exit;

define(
    'EMM_DEFAULT_FOLDER_IMG',
    plugin_dir_url(__FILE__) . 'assets/img/default-folder.png'
);

/* =========================================================
   1. REGISTER MEDIA TAGS TAXONOMY
========================================================= */
add_action('init', function () {

    register_taxonomy('emm_media_tag', 'attachment', [
        'labels' => [
            'name'          => 'Media Tags',
            'singular_name' => 'Media Tag',
            'search_items'  => 'Search Tags',
            'all_items'     => 'All Tags',
            'edit_item'     => 'Edit Tag',
            'update_item'   => 'Update Tag',
            'add_new_item'  => 'Add New Tag',
            'new_item_name' => 'New Tag Name',
            'menu_name'     => 'Media Tags',
        ],
        'hierarchical'      => false,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => ['slug' => 'media-tag'],
        'show_in_rest'      => true,
        'show_in_quick_edit' => false,
        'meta_box_cb'       => false,
    ]);

    // NEW: Register taxonomy to link images to multiple events
    register_taxonomy('emm_event_link', 'attachment', [
        'labels' => [
            'name'          => 'Event Links',
            'singular_name' => 'Event Link',
        ],
        'hierarchical'      => false,
        'show_ui'           => false,
        'show_admin_column' => false,
        'query_var'         => false,
        'rewrite'           => false,
        'public'            => false,
        'show_in_rest'      => false,
    ]);
});

/* =========================================================
   2. ADMIN MENU — EVENT MEDIA DASHBOARD + TAGS
========================================================= */
add_action('admin_menu', function () {

    // Main Dashboard
    add_menu_page(
        'Event Media',
        'Event Media',
        'edit_pages',
        'emm-dashboard',
        'emm_render_dashboard',
        'dashicons-format-gallery',
        6
    );
});

/* =========================================================
   3. ENQUEUE ADMIN ASSETS
========================================================= */
add_action('admin_enqueue_scripts', function ($hook) {

    if ($hook !== 'toplevel_page_emm-dashboard' && $hook !== 'event-media_page_emm-media-tags') return;

    wp_enqueue_media();
    wp_enqueue_script('jquery-ui-sortable');

    wp_enqueue_style(
        'emm-admin',
        plugin_dir_url(__FILE__) . 'assets/css/media-folders.css',
        [],
        '1.2'
    );

    wp_enqueue_script(
        'emm-admin',
        plugin_dir_url(__FILE__) . 'assets/js/media-folders.js',
        ['jquery', 'jquery-ui-autocomplete'],
        '1.2',
        true
    );

    // Pass AJAX URL to JavaScript
    wp_localize_script('emm-admin', 'emmData', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('emm_nonce'),
    ]);
});

/* =========================================================
   3B. ENQUEUE AUTOCOMPLETE FOR MEDIA UPLOAD/EDIT
========================================================= */
add_action('admin_enqueue_scripts', function ($hook) {
    global $pagenow;

    if (
        in_array($pagenow, ['post.php', 'upload.php', 'media-upload.php', 'async-upload.php']) ||
        $hook === 'upload.php' ||
        isset($_GET['action']) && $_GET['action'] === 'edit'
    ) {

        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-widget');
        wp_enqueue_script('jquery-ui-position');
        wp_enqueue_script('jquery-ui-menu');
        wp_enqueue_script('jquery-ui-autocomplete');

        wp_enqueue_style(
            'jquery-ui-css',
            'https://code.jquery.com/ui/1.13.2/themes/smoothness/jquery-ui.css',
            [],
            '1.13.2'
        );

        wp_add_inline_style('jquery-ui-css', '
            .ui-autocomplete {
                max-height: 200px;
                overflow-y: auto;
                overflow-x: hidden;
                z-index: 160000 !important;
            }
            .ui-menu-item {
                font-size: 13px;
            }
        ');
    }
}, 5);

add_action('wp_enqueue_media', function () {
    wp_enqueue_script('jquery-ui-autocomplete');
    wp_enqueue_style(
        'jquery-ui-css',
        'https://code.jquery.com/ui/1.13.2/themes/smoothness/jquery-ui.css',
        [],
        '1.13.2'
    );
});

add_action('wp_enqueue_media', function () {
    wp_enqueue_script(
        'emm-media-global',
        plugin_dir_url(__FILE__) . 'assets/js/media-folders.js',
        ['jquery'],
        '1.2',
        true
    );
});

/* =========================================================
   4. DASHBOARD ROUTER
========================================================= */
function emm_render_dashboard()
{
    if (isset($_GET['event'])) {
        emm_render_event_view((int) $_GET['event']);
        return;
    }

    emm_render_event_grid();
}

/* =========================================================
   5. EVENT GRID VIEW (FOLDERS)
========================================================= */
function emm_render_event_grid()
{
    $events = get_posts([
        'post_type'      => 'emm_event',
        'posts_per_page' => -1,
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
    ]);
?>

    <div class="emm-wrap">
        <h1>Event Media</h1>
        <div class="emm-search-card">
            <input
                type="text"
                class="emm-search"
                placeholder="Search events..."
                autocomplete="off" />
        </div>

        <div class="emm-grid">

            <!-- CREATE EVENT -->
            <form method="post" class="emm-card emm-create">
                <?php wp_nonce_field('emm_create_event'); ?>

                <div class="emm-folder emm-folder-create">
                    <img src="<?php echo esc_url(EMM_DEFAULT_FOLDER_IMG); ?>" alt="Create Event">
                    <span class="emm-plus">＋</span>
                </div>

                <input
                    type="text"
                    name="event_name"
                    placeholder="Create new event"
                    required />

                <button type="submit">Create Event</button>
            </form>

            <?php foreach ($events as $event):

                // Get images linked to this event via taxonomy
                $images = emm_get_event_images($event->ID);
                $count = count($images);
                $cover_id = emm_get_event_cover($event->ID);

                if (!$cover_id && !empty($images)) {
                    $cover_id = $images[0];
                }
            ?>
                <a href="<?php echo admin_url('admin.php?page=emm-dashboard&event=' . $event->ID); ?>"
                    class="emm-card emm-event"
                    data-id="<?php echo $event->ID; ?>"
                    data-title="<?php echo esc_attr(strtolower($event->post_title)); ?>">

                    <div class="emm-folder">
                        <button
                            class="emm-event-delete"
                            data-id="<?php echo $event->ID; ?>"
                            title="Delete event">
                            ✕
                        </button>

                        <?php
                        if ($cover_id) {
                            echo wp_get_attachment_image($cover_id, 'medium');
                        } else {
                            echo '<img src="' . esc_url(EMM_DEFAULT_FOLDER_IMG) . '" alt="Event folder">';
                        }
                        ?>

                        <?php if ($count > 0): ?>
                            <span class="emm-count"><?php echo $count; ?></span>
                        <?php endif; ?>

                        <button
                            class="emm-set-cover"
                            data-id="<?php echo $event->ID; ?>"
                            title="Set cover image">
                            🖼
                        </button>
                    </div>

                    <p><?php echo esc_html($event->post_title); ?></p>
                </a>

            <?php endforeach; ?>
        </div>
    </div>

<?php
}

/* =========================================================
   5B. HELPER — GET EVENT IMAGES (using taxonomy)
========================================================= */
function emm_get_event_images($event_id)
{
    $args = [
        'post_type'      => 'attachment',
        'post_mime_type' => 'image',
        'posts_per_page' => -1,
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
        'fields'         => 'ids',
        'tax_query'      => [
            [
                'taxonomy' => 'emm_event_link',
                'field'    => 'slug',
                'terms'    => 'event-' . $event_id,
            ],
        ],
    ];

    return get_posts($args);
}

/* =========================================================
   6. HANDLE CREATE EVENT
========================================================= */
add_action('admin_init', function () {

    if (
        isset($_POST['event_name']) &&
        check_admin_referer('emm_create_event')
    ) {
        wp_insert_post([
            'post_type' => 'emm_event',
            'post_title' => sanitize_text_field($_POST['event_name']),
            'post_status' => 'publish'
        ]);

        wp_redirect(admin_url('admin.php?page=emm-dashboard'));
        exit;
    }
});

/* =========================================================
   7. EVENT → PHOTOS VIEW
========================================================= */
function emm_render_event_view($event_id)
{
    $event = get_post($event_id);

    if (!$event || $event->post_type !== 'emm_event') {
        echo '<p>Invalid event</p>';
        return;
    }

    $images = emm_get_event_images($event_id);
?>

    <div class="emm-wrap">

        <div class="emm-event-header">

            <div class="emm-event-header-top">
                <a class="emm-back" href="<?php echo admin_url('admin.php?page=emm-dashboard'); ?>">
                    ← Back to Events
                </a>

                <button class="button emm-upload" data-event="<?php echo $event_id; ?>">
                    + Add Photos
                </button>
            </div>

            <div class="emm-event-header-bottom">
                <h1 class="emm-event-title"><?php echo esc_html($event->post_title); ?></h1>

                <div class="emm-filters">
                    <select class="emm-filter" id="emm-photographer-filter" data-event="<?php echo $event_id; ?>">
                        <option value="">All photographers</option>
                        <?php echo emm_get_event_photographers($event_id); ?>
                    </select>

                    <select class="emm-filter" id="emm-tag-filter" data-event="<?php echo $event_id; ?>">
                        <option value="">All tags</option>
                        <?php echo emm_get_event_tags_options($event_id); ?>
                    </select>
                </div>
            </div>

        </div>

        <div class="emm-grid">
            <?php foreach ($images as $img_id):
                $tags = wp_get_post_terms($img_id, 'emm_media_tag', ['fields' => 'names']);
                $tag_list = !empty($tags) ? implode(', ', $tags) : '';
                
                // Get all events this image belongs to
                $linked_events = emm_get_image_events($img_id);
                $event_count = count($linked_events);
            ?>
                <div class="emm-card emm-image" data-id="<?php echo $img_id; ?>">
                    <?php echo wp_get_attachment_image($img_id, 'medium'); ?>
                    <button class="emm-remove" data-id="<?php echo $img_id; ?>" data-event="<?php echo $event_id; ?>">✕</button>
                    <button class="emm-edit-tags" style="display: none;" data-id="<?php echo $img_id; ?>" title="Edit tags">🏷️</button>
                    
                    <?php if ($event_count > 1): ?>
                        <span class="emm-multi-event-badge" title="This image is in <?php echo $event_count; ?> events">
                            📁 <?php echo $event_count; ?>
                        </span>
                    <?php endif; ?>
                    
                    <p><?php echo esc_html(get_the_title($img_id)); ?></p>
                    <?php if ($tag_list): ?>
                        <div class="emm-tags-display"><?php echo esc_html($tag_list); ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

    </div>

    <!-- Tag Editor Modal -->
    <div id="emm-tag-modal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 100000;">
        <div class="emm-tag-modal-content">
            <span class="emm-tag-modal-close">&times;</span>
            <h2>Edit Media Tags</h2>
            <div class="emm-tag-input-wrapper">
                <input type="text" id="emm-tag-search" placeholder="Search or add tags..." autocomplete="off">
                <div id="emm-tag-suggestions"></div>
            </div>
            <div id="emm-selected-tags"></div>
            <button class="button button-primary" id="emm-save-tags">Save Tags</button>
        </div>
    </div>

<?php
}

/* =========================================================
   7B. HELPER — GET IMAGE EVENTS
========================================================= */
function emm_get_image_events($image_id)
{
    $terms = wp_get_post_terms($image_id, 'emm_event_link', ['fields' => 'slugs']);
    
    $event_ids = [];
    foreach ($terms as $slug) {
        if (strpos($slug, 'event-') === 0) {
            $event_ids[] = (int) str_replace('event-', '', $slug);
        }
    }
    
    return $event_ids;
}

/* =========================================================
   8. MEDIA TAGS MANAGEMENT PAGE (keeping original)
========================================================= */
function emm_render_tags_page()
{
    $tags = get_terms([
        'taxonomy'   => 'emm_media_tag',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ]);
?>

    <div class="wrap">
        <h1>Media Tags</h1>

        <div style="display: flex; gap: 40px; margin-top: 20px;">

            <!-- ADD NEW TAG -->
            <div style="flex: 0 0 300px;">
                <div class="card" style="padding: 20px;">
                    <h2>Add New Tag</h2>
                    <form id="emm-add-tag-form">
                        <?php wp_nonce_field('emm_manage_tags', 'emm_tag_nonce'); ?>

                        <div style="margin-bottom: 15px;">
                            <label for="tag-name" style="display: block; margin-bottom: 5px; font-weight: 600;">Tag Name</label>
                            <input type="text" id="tag-name" name="tag_name" class="regular-text" required>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label for="tag-slug" style="display: block; margin-bottom: 5px; font-weight: 600;">Slug (optional)</label>
                            <input type="text" id="tag-slug" name="tag_slug" class="regular-text">
                            <p class="description">Leave empty to auto-generate</p>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label for="tag-description" style="display: block; margin-bottom: 5px; font-weight: 600;">Description (optional)</label>
                            <textarea id="tag-description" name="tag_description" class="large-text" rows="3"></textarea>
                        </div>

                        <button type="submit" class="button button-primary">Add Tag</button>
                    </form>
                </div>
            </div>

            <!-- TAGS LIST -->
            <div style="flex: 1;">
                <table class="wp-list-table fixed widefat striped">
                    <thead>
                        <tr>
                            <th style="width: 30%;">Name</th>
                            <th style="width: 30%;">Slug</th>
                            <th style="width: 25%;">Description</th>
                            <th style="width: 10%;">Count</th>
                            <th style="width: 5%;"></th>
                        </tr>
                    </thead>
                    <tbody id="emm-tags-list">
                        <?php if (empty($tags)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px;">
                                    No tags found. Add your first tag!
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tags as $tag): ?>
                                <tr data-tag-id="<?php echo $tag->term_id; ?>">
                                    <td>
                                        <strong class="tag-name-display"><?php echo esc_html($tag->name); ?></strong>
                                        <input type="text" class="tag-name-edit regular-text" value="<?php echo esc_attr($tag->name); ?>" style="display:none;">
                                    </td>
                                    <td>
                                        <span class="tag-slug-display"><?php echo esc_html($tag->slug); ?></span>
                                        <input type="text" class="tag-slug-edit regular-text" value="<?php echo esc_attr($tag->slug); ?>" style="display:none;">
                                    </td>
                                    <td>
                                        <span class="tag-desc-display"><?php echo esc_html($tag->description); ?></span>
                                        <textarea class="tag-desc-edit" style="display:none; width: 100%;" rows="2"><?php echo esc_textarea($tag->description); ?></textarea>
                                    </td>
                                    <td><?php echo $tag->count; ?></td>
                                    <td>
                                        <button class="button button-small emm-edit-tag-btn" title="Edit">Edit</button>
                                        <button class="button button-small emm-save-tag-btn" style="display:none;" title="Save">Save</button>
                                        <button class="button button-small emm-cancel-tag-btn" style="display:none;" title="Cancel">Cancel</button>
                                        <button class="button button-small button-link-delete emm-delete-tag-btn" title="Delete">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <style>
        .emm-tag-modal-content {
            background: white;
            padding: 20px;
            border-radius: 5px;
            max-width: 500px;
            margin: 50px auto;
        }

        .emm-tag-modal-close {
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .emm-tag-input-wrapper {
            position: relative;
            margin: 20px 0;
        }

        #emm-tag-search {
            width: 100%;
            padding: 8px;
        }

        #emm-tag-suggestions {
            position: absolute;
            background: white;
            border: 1px solid #ddd;
            max-height: 200px;
            overflow-y: auto;
            width: 100%;
            z-index: 1000;
            display: none;
        }

        #emm-tag-suggestions div {
            padding: 8px;
            cursor: pointer;
        }

        #emm-tag-suggestions div:hover {
            background: #f0f0f0;
        }

        #emm-selected-tags {
            margin: 15px 0;
            min-height: 40px;
        }

        .emm-tag-item {
            display: inline-block;
            background: #0073aa;
            color: white;
            padding: 5px 10px;
            margin: 3px;
            border-radius: 3px;
        }

        .emm-tag-remove {
            margin-left: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .emm-filters {
            display: flex;
            gap: 10px;
        }

        .emm-tags-display {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
            font-style: italic;
        }

        .emm-edit-tags {
            position: absolute;
            top: 35px;
            right: 5px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            padding: 5px 8px;
            cursor: pointer;
            border-radius: 3px;
            font-size: 14px;
        }

        .emm-edit-tags:hover {
            background: white;
        }
    </style>

<?php
}

/* =========================================================
   9-14. TAG MANAGEMENT AJAX (keeping all original)
========================================================= */
add_action('wp_ajax_emm_add_tag', function () {
    check_ajax_referer('emm_manage_tags', 'nonce');

    if (!current_user_can('edit_pages')) {
        wp_send_json_error('Permission denied');
    }

    $name = sanitize_text_field($_POST['name']);
    $slug = sanitize_title($_POST['slug']);
    $description = sanitize_textarea_field($_POST['description']);

    $args = ['description' => $description];
    if (!empty($slug)) {
        $args['slug'] = $slug;
    }

    $result = wp_insert_term($name, 'emm_media_tag', $args);

    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }

    $term = get_term($result['term_id'], 'emm_media_tag');

    wp_send_json_success([
        'term_id' => $term->term_id,
        'name' => $term->name,
        'slug' => $term->slug,
        'description' => $term->description,
        'count' => 0,
    ]);
});

add_action('wp_ajax_emm_update_tag', function () {
    if (!current_user_can('edit_pages')) {
        wp_send_json_error('Permission denied');
    }

    $term_id = (int) $_POST['term_id'];
    $name = sanitize_text_field($_POST['name']);
    $slug = sanitize_title($_POST['slug']);
    $description = sanitize_textarea_field($_POST['description']);

    $result = wp_update_term($term_id, 'emm_media_tag', [
        'name' => $name,
        'slug' => $slug,
        'description' => $description,
    ]);

    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }

    wp_send_json_success();
});

add_action('wp_ajax_emm_delete_tag', function () {
    if (!current_user_can('edit_pages')) {
        wp_send_json_error('Permission denied');
    }

    $term_id = (int) $_POST['term_id'];

    $result = wp_delete_term($term_id, 'emm_media_tag');

    if (is_wp_error($result) || $result === false) {
        wp_send_json_error('Failed to delete tag');
    }

    wp_send_json_success();
});

add_action('wp_ajax_emm_search_tags', function () {
    $search = sanitize_text_field($_POST['search']);

    $tags = get_terms([
        'taxonomy'   => 'emm_media_tag',
        'hide_empty' => false,
        'search'     => $search,
        'number'     => 10,
    ]);

    $results = array_map(function ($tag) {
        return [
            'id' => $tag->term_id,
            'name' => $tag->name,
        ];
    }, $tags);

    wp_send_json_success($results);
});

add_action('wp_ajax_emm_get_image_tags', function () {
    $image_id = (int) $_POST['image_id'];

    $tags = wp_get_post_terms($image_id, 'emm_media_tag');

    $results = array_map(function ($tag) {
        return [
            'id' => $tag->term_id,
            'name' => $tag->name,
        ];
    }, $tags);

    wp_send_json_success($results);
});

add_action('wp_ajax_emm_save_image_tags', function () {
    check_ajax_referer('emm_nonce', 'nonce');

    if (!current_user_can('upload_files')) {
        wp_send_json_error('Permission denied');
    }

    $image_id = (int) $_POST['image_id'];
    $tag_ids  = array_map('intval', $_POST['tag_ids'] ?? []);

    $result = wp_set_object_terms(
        $image_id,
        $tag_ids,
        'emm_media_tag',
        false
    );

    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }

    wp_send_json_success();
});

add_action('wp_ajax_save-attachment-compat', 'emm_ajax_save_attachment_compat', 0);
function emm_ajax_save_attachment_compat()
{
    if (!isset($_REQUEST['id'])) {
        return;
    }

    $id = absint($_REQUEST['id']);

    if (!current_user_can('edit_post', $id)) {
        return;
    }

    if (!isset($_REQUEST['attachments'][$id]['emm_media_tags'])) {
        return;
    }

    $tag_ids_string = sanitize_text_field($_REQUEST['attachments'][$id]['emm_media_tags']);

    if ($tag_ids_string === '' || trim($tag_ids_string) === '') {
        wp_set_object_terms($id, [], 'emm_media_tag', false);
        clean_object_term_cache($id, 'attachment');
        return;
    }

    $tag_ids = array_map('intval', array_filter(explode(',', $tag_ids_string)));

    if (!empty($tag_ids)) {
        $result = wp_set_object_terms($id, $tag_ids, 'emm_media_tag', false);

        if (is_wp_error($result)) {
            error_log('EMM Tag Save Error: ' . $result->get_error_message());
        }

        clean_object_term_cache($id, 'attachment');
    } else {
        wp_set_object_terms($id, [], 'emm_media_tag', false);
        clean_object_term_cache($id, 'attachment');
    }
}

add_action('wp_ajax_emm_save_attachment_tags', function () {
    if (!current_user_can('upload_files')) {
        wp_send_json_error('Permission denied');
    }

    $attachment_id = (int) $_POST['attachment_id'];
    $tag_ids = isset($_POST['tag_ids']) ? array_map('intval', (array)$_POST['tag_ids']) : [];

    $result = wp_set_object_terms($attachment_id, $tag_ids, 'emm_media_tag', false);

    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }

    clean_object_term_cache($attachment_id, 'attachment');

    wp_send_json_success();
});

add_action('wp_ajax_emm_save_attachment_tags_with_new', function () {
    if (!current_user_can('upload_files')) {
        wp_send_json_error('Permission denied');
    }

    $attachment_id = (int) $_POST['attachment_id'];
    $existing_tag_ids = isset($_POST['existing_tag_ids']) ? array_map('intval', (array)$_POST['existing_tag_ids']) : [];
    $new_tag_names = isset($_POST['new_tag_names']) ? array_map('sanitize_text_field', (array)$_POST['new_tag_names']) : [];

    $all_tag_ids = $existing_tag_ids;
    $created_tags = [];

    foreach ($new_tag_names as $tag_name) {
        if (empty($tag_name)) continue;

        $existing_term = get_term_by('name', $tag_name, 'emm_media_tag');

        if ($existing_term) {
            $all_tag_ids[] = $existing_term->term_id;
            $created_tags[] = [
                'id' => $existing_term->term_id,
                'name' => $existing_term->name
            ];
        } else {
            $result = wp_insert_term($tag_name, 'emm_media_tag');

            if (!is_wp_error($result)) {
                $all_tag_ids[] = $result['term_id'];
                $created_tags[] = [
                    'id' => $result['term_id'],
                    'name' => $tag_name
                ];
            }
        }
    }

    $result = wp_set_object_terms($attachment_id, $all_tag_ids, 'emm_media_tag', false);

    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }

    clean_object_term_cache($attachment_id, 'attachment');

    wp_send_json_success([
        'created_tags' => $created_tags
    ]);
});

/* =========================================================
   15. HELPER — GET EVENT TAGS OPTIONS
========================================================= */
function emm_get_event_tags_options($event_id)
{
    $images = emm_get_event_images($event_id);

    if (!$images) return '';

    $all_tags = [];

    foreach ($images as $img_id) {
        $tags = wp_get_post_terms($img_id, 'emm_media_tag', ['fields' => 'ids']);
        $all_tags = array_merge($all_tags, $tags);
    }

    $all_tags = array_unique($all_tags);

    if (!$all_tags) return '';

    $terms = get_terms([
        'taxonomy'   => 'emm_media_tag',
        'include'    => $all_tags,
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ]);

    $out = '';
    foreach ($terms as $term) {
        $out .= '<option value="' . esc_attr($term->term_id) . '">' .
            esc_html($term->name) .
            '</option>';
    }

    return $out;
}

/* =========================================================
   16. AJAX — FILTER IMAGES BY TAG
========================================================= */
add_action('wp_ajax_emm_filter_by_tag', function () {
    if (!current_user_can('upload_files')) {
        wp_die();
    }

    $event_id = (int) $_POST['event_id'];
    $tag_id = (int) ($_POST['tag_id'] ?? 0);
    $photographer = (int) ($_POST['photographer'] ?? 0);

    $images = emm_get_event_images($event_id);

    foreach ($images as $img_id) {

        // Filter by photographer
        if ($photographer) {
            $img_photographer = (int) get_post_meta($img_id, 'photographer', true);
            if ($img_photographer !== $photographer) {
                continue;
            }
        }

        // Filter by tag
        if ($tag_id) {
            $img_tags = wp_get_post_terms($img_id, 'emm_media_tag', ['fields' => 'ids']);
            if (!in_array($tag_id, $img_tags)) {
                continue;
            }
        }

        $tags = wp_get_post_terms($img_id, 'emm_media_tag', ['fields' => 'names']);
        $tag_list = !empty($tags) ? implode(', ', $tags) : '';
        
        $linked_events = emm_get_image_events($img_id);
        $event_count = count($linked_events);

        echo '<div class="emm-card emm-image" data-id="' . esc_attr($img_id) . '">';
        echo wp_get_attachment_image($img_id, 'medium');
        echo '<button class="emm-remove" data-id="' . esc_attr($img_id) . '" data-event="' . esc_attr($event_id) . '">✕</button>';
        
        if ($event_count > 1) {
            echo '<span class="emm-multi-event-badge" title="This image is in ' . $event_count . ' events">📁 ' . $event_count . '</span>';
        }
        
        echo '<p>' . esc_html(get_the_title($img_id)) . '</p>';
        if ($tag_list) {
            echo '<div class="emm-tags-display">' . esc_html($tag_list) . '</div>';
        }
        echo '</div>';
    }

    wp_die();
});

/* =========================================================
   NEW: AJAX — ATTACH IMAGES TO EVENT (using taxonomy)
========================================================= */
add_action('wp_ajax_emm_attach_images', function () {
    if (!current_user_can('upload_files')) {
        wp_send_json_error();
    }
    
    $event_id = (int) $_POST['event_id'];
    $files = $_POST['files'] ?? [];
    
    foreach ($files as $id) {
        $image_id = (int) $id;
        
        // Add event link term
        wp_set_object_terms(
            $image_id,
            'event-' . $event_id,
            'emm_event_link',
            true // Append, don't replace
        );
    }
    
    wp_send_json_success();
});

/* =========================================================
   NEW: AJAX — REMOVE IMAGE FROM EVENT (not delete, just unlink)
========================================================= */
add_action('wp_ajax_emm_remove_image', function () {
    if (!current_user_can('upload_files')) {
        wp_send_json_error();
    }
    
    $image_id = (int) $_POST['image_id'];
    $event_id = (int) ($_POST['event_id'] ?? 0);
    
    if ($event_id) {
        // Remove only this event link
        wp_remove_object_terms($image_id, 'event-' . $event_id, 'emm_event_link');
    } else {
        // Remove all event links (legacy support)
        wp_set_object_terms($image_id, [], 'emm_event_link', false);
    }
    
    wp_send_json_success();
});

/* =========================================================
   EXISTING FUNCTIONS (keeping all)
========================================================= */
if (function_exists('acf_add_local_field_group')) :
    acf_add_local_field_group([
        'key' => 'group_emm_image_meta',
        'title' => 'Image Details',
        'fields' => [
            [
                'key' => 'field_photographer',
                'label' => 'Photographer',
                'name' => 'photographer',
                'type' => 'user',
                'role' => ['photographer'],
                'return_format' => 'id',
                'allow_null' => 1,
            ],
            [
                'key' => 'field_location',
                'label' => 'Location',
                'name' => 'location',
                'type' => 'text',
            ],
            [
                'key' => 'field_event_date',
                'label' => 'Event Date',
                'name' => 'event_date',
                'type' => 'date_picker',
            ],
            [
                'key' => 'field_custom_link',
                'label' => 'Custom Link',
                'name' => 'custom_link',
                'type' => 'url',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'attachment',
                    'operator' => '==',
                    'value' => 'all',
                ],
            ],
        ],
    ]);
endif;

add_filter('attachment_fields_to_edit', 'emm_add_tags_field_to_attachment', 10, 2);

add_action('wp_ajax_emm_delete_event', function () {
    if (!current_user_can('upload_files')) {
        wp_send_json_error('Permission denied');
    }
    $event_id = (int) ($_POST['event_id'] ?? 0);
    if (!$event_id) {
        wp_send_json_error('Invalid event');
    }
    
    // Get all images linked to this event
    $images = emm_get_event_images($event_id);
    
    // Remove event link from each image
    foreach ($images as $img_id) {
        wp_remove_object_terms($img_id, 'event-' . $event_id, 'emm_event_link');
    }
    
    // Delete the event post
    wp_delete_post($event_id, true);
    
    wp_send_json_success();
});

function emm_get_event_cover($event_id)
{
    $cover_id = (int) get_post_meta($event_id, '_emm_cover_image', true);
    return $cover_id ?: 0;
}

add_action('wp_ajax_emm_set_event_cover', function () {
    if (!current_user_can('upload_files')) {
        wp_send_json_error();
    }
    $event_id = (int) $_POST['event_id'];
    $image_id = (int) $_POST['image_id'];
    update_post_meta($event_id, '_emm_cover_image', $image_id);
    wp_send_json_success();
});

add_action('wp_ajax_emm_save_event_order', function () {
    if (!current_user_can('upload_files')) {
        wp_send_json_error();
    }
    $order = $_POST['order'] ?? [];
    foreach ($order as $item) {
        wp_update_post([
            'ID'         => (int) $item['id'],
            'menu_order' => (int) $item['position'],
        ]);
    }
    wp_send_json_success();
});

add_action('wp_ajax_emm_save_image_order', function () {
    if (!current_user_can('upload_files')) {
        wp_send_json_error();
    }
    $order = $_POST['order'] ?? [];
    foreach ($order as $item) {
        wp_update_post([
            'ID'         => (int) $item['id'],
            'menu_order' => (int) $item['position'],
        ]);
    }
    wp_send_json_success();
});

add_action('init', function () {
    if (!get_role('photographer')) {
        add_role(
            'photographer',
            'Photographer',
            [
                'read'         => true,
                'upload_files' => true,
            ]
        );
    }
});

function emm_get_photographer_options()
{
    $users = get_users([
        'role' => 'photographer',
        'orderby' => 'display_name',
        'order' => 'ASC',
    ]);
    $out = '';
    foreach ($users as $user) {
        $out .= '<option value="' . esc_attr($user->ID) . '">' .
            esc_html($user->display_name) .
            '</option>';
    }
    return $out;
}

add_action('wp_ajax_emm_filter_images', function () {
    if (!current_user_can('upload_files')) {
        wp_die();
    }
    $event_id     = (int) $_POST['event_id'];
    $photographer = (int) ($_POST['photographer'] ?? 0);
    
    $images = emm_get_event_images($event_id);
    
    foreach ($images as $img_id) {
        $img_photographer = (int) get_post_meta($img_id, 'photographer', true);
        if ($photographer && $img_photographer !== $photographer) {
            continue;
        }
        $tags = wp_get_post_terms($img_id, 'emm_media_tag', ['fields' => 'names']);
        $tag_list = !empty($tags) ? implode(', ', $tags) : '';
        
        $linked_events = emm_get_image_events($img_id);
        $event_count = count($linked_events);
        
        echo '<div class="emm-card emm-image" data-id="' . esc_attr($img_id) . '">';
        echo wp_get_attachment_image($img_id, 'medium');
        echo '<button class="emm-remove" data-id="' . esc_attr($img_id) . '" data-event="' . esc_attr($event_id) . '">✕</button>';
        
        if ($event_count > 1) {
            echo '<span class="emm-multi-event-badge" title="This image is in ' . $event_count . ' events">📁 ' . $event_count . '</span>';
        }
        
        echo '<p>' . esc_html(get_the_title($img_id)) . '</p>';
        if ($tag_list) {
            echo '<div class="emm-tags-display">' . esc_html($tag_list) . '</div>';
        }
        echo '</div>';
    }
    wp_die();
});

function emm_get_event_photographers($event_id)
{
    $images = emm_get_event_images($event_id);
    if (!$images) return '';
    $photographer_ids = [];
    foreach ($images as $img_id) {
        $pid = get_post_meta($img_id, 'photographer', true);
        if ($pid) {
            $photographer_ids[] = (int) $pid;
        }
    }
    $photographer_ids = array_unique($photographer_ids);
    if (!$photographer_ids) return '';
    $users = get_users([
        'include' => $photographer_ids,
        'orderby' => 'display_name',
        'order'   => 'ASC',
    ]);
    $out = '';
    foreach ($users as $user) {
        $out .= '<option value="' . esc_attr($user->ID) . '">' .
            esc_html($user->display_name) .
            '</option>';
    }
    return $out;
}

/* =========================================================
   ADD MEDIA TAGS FIELD TO ATTACHMENT EDIT (keeping original)
========================================================= */
function emm_add_tags_field_to_attachment($form_fields, $post)
{
    $tags = wp_get_post_terms($post->ID, 'emm_media_tag');
    $tag_ids = array_map(function ($tag) {
        return $tag->term_id;
    }, $tags);

    $tag_names = array_map(function ($tag) {
        return $tag->name;
    }, $tags);

    $all_tags = get_terms([
        'taxonomy'   => 'emm_media_tag',
        'hide_empty' => false,
    ]);

    $tag_options = array_map(function ($tag) {
        return ['id' => $tag->term_id, 'name' => $tag->name];
    }, $all_tags);

    $field_id = 'emm-media-tags-' . $post->ID;
    $attachment_id = $post->ID;

    $form_fields['emm_media_tags'] = [
        'label' => 'Media Tags',
        'input' => 'html',
        'html'  => '
            <div id="emm-tag-container-' . $attachment_id . '">
                <div id="emm-tag-badges-' . $attachment_id . '" style="margin-bottom: 10px; min-height: 30px;">
                    ' . emm_render_tag_badges($tags) . '
                </div>
                <input 
                    type="text" 
                    id="' . $field_id . '" 
                    class="text emm-tags-input"
                    style="width: 100%; margin-bottom: 10px;"
                    placeholder="Type to search tags or create new ones..."
                    autocomplete="off"
                />
                <button type="button" class="button button-primary emm-save-tags-btn" data-attachment-id="' . $attachment_id . '" style="margin-right: 10px;">
                    💾 Save Tags
                </button>
                <span id="emm-save-status-' . $attachment_id . '" style="color: green; display: none;">✓ Saved!</span>
                <input 
                    type="hidden" 
                    name="attachments[' . $attachment_id . '][emm_media_tags]" 
                    id="emm-tag-hidden-' . $attachment_id . '" 
                    value="' . esc_attr(implode(',', $tag_ids)) . '"
                />
                <p class="description">Type to search existing tags or create new ones. Click the blue Save button to persist changes.</p>
            </div>
            
            <style>
                .emm-tag-badge {
                    display: inline-block;
                    background: #0073aa;
                    color: white;
                    padding: 4px 8px;
                    margin: 2px;
                    border-radius: 3px;
                    font-size: 12px;
                }
                .emm-tag-badge-remove {
                    margin-left: 5px;
                    cursor: pointer;
                    font-weight: bold;
                    color: white;
                }
                .emm-tag-badge-remove:hover {
                    color: #ff4444;
                }
                .ui-autocomplete {
                    max-height: 200px;
                    overflow-y: auto;
                    z-index: 160000 !important;
                }
                .emm-new-tag-indicator {
                    background: #46b450 !important;
                }
            </style>
            
            <script type="text/javascript">

            jQuery(document).ready(function($) {
                var attachmentId = ' . $attachment_id . ';
                var fieldId = "#' . $field_id . '";
                var badgesId = "#emm-tag-badges-" + attachmentId;
                var hiddenId = "#emm-tag-hidden-" + attachmentId;
                var statusId = "#emm-save-status-" + attachmentId;
                var availableTags = ' . json_encode($tag_options) . ';
                var selectedTags = ' . json_encode(array_map(function ($t) {
            return ['id' => $t->term_id, 'name' => $t->name, 'isNew' => false];
        }, $tags)) . ';
                
                function renderBadges() {
                    if (selectedTags.length === 0) {
                        $(badgesId).html("<em style=\"color: #999;\">No tags selected</em>");
                    } else {
                        var html = selectedTags.map(function(tag) {
                            var badgeClass = tag.isNew ? "emm-tag-badge emm-new-tag-indicator" : "emm-tag-badge";
                            var newLabel = tag.isNew ? " (new)" : "";
                            return "<span class=\"" + badgeClass + "\" data-tag-id=\"" + tag.id + "\">" +
                                   tag.name + newLabel +
                                   "<span class=\"emm-tag-badge-remove\" data-tag-id=\"" + tag.id + "\">×</span>" +
                                   "</span>";
                        }).join("");
                        $(badgesId).html(html);
                    }
                    
                    var tagIds = selectedTags.filter(function(tag) { 
                        return !tag.isNew; 
                    }).map(function(tag) { 
                        return tag.id; 
                    });
                    $(hiddenId).val(tagIds.join(","));
                }
                
                $(document).on("click", ".emm-tag-badge-remove", function(e) {
                    e.preventDefault();
                    var tagId = $(this).data("tag-id");
                    selectedTags = selectedTags.filter(function(tag) {
                        return tag.id !== tagId;
                    });
                    renderBadges();
                });
                
                function saveTags() {
                    var $btn = $(".emm-save-tags-btn[data-attachment-id=\"" + attachmentId + "\"]");
                    var btnText = $btn.html();
                    
                    $btn.prop("disabled", true).html("💾 Saving...");
                    $(statusId).hide();
                    
                    var existingTagIds = selectedTags
                        .filter(function(tag) { return !tag.isNew; })
                        .map(function(tag) { return tag.id; });
                    
                    var newTagNames = selectedTags
                        .filter(function(tag) { return tag.isNew; })
                        .map(function(tag) { return tag.name; });
                    
                    $.post(ajaxurl, {
                        action: "emm_save_attachment_tags_with_new",
                        attachment_id: attachmentId,
                        existing_tag_ids: existingTagIds,
                        new_tag_names: newTagNames
                    }, function(response) {
                        if (response.success) {
                            if (response.data.created_tags) {
                                response.data.created_tags.forEach(function(createdTag) {
                                    var tagIndex = selectedTags.findIndex(function(t) {
                                        return t.isNew && t.name === createdTag.name;
                                    });
                                    if (tagIndex !== -1) {
                                        selectedTags[tagIndex] = {
                                            id: createdTag.id,
                                            name: createdTag.name,
                                            isNew: false
                                        };
                                    }
                                });
                                
                                response.data.created_tags.forEach(function(createdTag) {
                                    availableTags.push({
                                        id: createdTag.id,
                                        name: createdTag.name
                                    });
                                });
                            }
                            
                            renderBadges();
                            emmRefreshAttachment(attachmentId);
                            $(statusId).fadeIn().delay(2000).fadeOut();
                        } else {
                            alert("Error saving tags: " + (response.data || "Unknown error"));
                        }
                    }).fail(function() {
                        alert("Error saving tags. Please try again.");
                    }).always(function() {
                        $btn.prop("disabled", false).html(btnText);
                    });
                }
                
                $(document).on("click", ".emm-save-tags-btn", function(e) {
                    e.preventDefault();
                    if ($(this).data("attachment-id") === attachmentId) {
                        saveTags();
                    }
                });
                
                function getAvailableTags() {
                    return availableTags.filter(function(tag) {
                        return !selectedTags.some(function(selected) {
                            return selected.id === tag.id;
                        });
                    });
                }
                
                if (typeof $.ui !== "undefined" && typeof $.ui.autocomplete !== "undefined") {
                    $(fieldId).autocomplete({
                        minLength: 0,
                        source: function(request, response) {
                            var available = getAvailableTags();
                            var term = request.term.toLowerCase();
                            
                            var matches = available.filter(function(tag) {
                                return tag.name.toLowerCase().indexOf(term) !== -1;
                            });
                            
                            var items = matches.map(function(tag) {
                                return { label: tag.name, value: tag.name, id: tag.id, isNew: false };
                            });
                            
                            if (term && !matches.some(function(tag) { 
                                return tag.name.toLowerCase() === term; 
                            })) {
                                items.push({ 
                                    label: "➕ Create new tag: \"" + request.term + "\"", 
                                    value: request.term, 
                                    id: "new_" + Date.now(), 
                                    isNew: true 
                                });
                            }
                            
                            response(items);
                        },
                        select: function(event, ui) {
                            event.preventDefault();
                            
                            if (!selectedTags.some(function(tag) { 
                                return tag.name.toLowerCase() === ui.item.value.toLowerCase(); 
                            })) {
                                selectedTags.push({ 
                                    id: ui.item.id, 
                                    name: ui.item.value, 
                                    isNew: ui.item.isNew 
                                });
                                renderBadges();
                            }
                            
                            $(this).val("");
                            return false;
                        },
                        focus: function(event, ui) {
                            event.preventDefault();
                        }
                    });
                    
                    $(fieldId).on("focus", function() {
                        $(this).autocomplete("search", $(this).val());
                    });
                }
                
                renderBadges();
            });
            
            </script>
        ',
        'helps' => 'Add or select tags for this media'
    ];

    return $form_fields;
}

function emm_render_tag_badges($tags)
{
    if (empty($tags)) {
        return '<em style="color: #999;">No tags selected</em>';
    }

    $html = '';
    foreach ($tags as $tag) {
        $html .= '<span class="emm-tag-badge" data-tag-id="' . $tag->term_id . '">' .
            esc_html($tag->name) .
            '<span class="emm-tag-badge-remove" data-tag-id="' . $tag->term_id . '">×</span>' .
            '</span>';
    }
    return $html;
}

add_action('init', function () {
    register_taxonomy_for_object_type('emm_media_tag', 'attachment');
}, 20);