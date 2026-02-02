<?php
/*
Plugin Name: Event Media Manager
Description: Custom event-based media dashboard with image metadata
Version: 2.0.0
Author: MT
*/

if (!defined('ABSPATH')) exit;

define(
    'EMM_DEFAULT_FOLDER_IMG',
    plugin_dir_url(__FILE__) . 'assets/img/default-folder.png'
);

/* =========================================================
   1. REGISTER EVENT (FOLDER) CPT
========================================================= */
// add_action('init', function () {

//     register_post_type('emm_event', [
//         'label' => 'Events',
//         'public' => false,
//         'show_ui' => true,
//         'supports' => ['title', 'page-attributes'], // 🔥 enables menu_order
//         'menu_icon' => 'dashicons-calendar-alt',
//     ]);
// });

/* =========================================================
   2. ADMIN MENU — EVENT MEDIA DASHBOARD
========================================================= */
add_action('admin_menu', function () {

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

    if ($hook !== 'toplevel_page_emm-dashboard') return;

    wp_enqueue_media();

    wp_enqueue_style(
        'emm-admin',
        plugin_dir_url(__FILE__) . 'assets/css/media-folders.css',
        [],
        '1.0'
    );

    wp_enqueue_script(
        'emm-admin',
        plugin_dir_url(__FILE__) . 'assets/js/media-folders.js',
        ['jquery'],
        '1.0',
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
            <!-- <span class="emm-search-icon">🔍</span> -->
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

                $images = get_posts([
                    'post_type'      => 'attachment',
                    'post_parent'    => $event->ID,
                    'post_mime_type' => 'image',
                    'posts_per_page' => -1,
                    'fields'         => 'ids',
                ]);

                $count = count($images);
                $cover_id = emm_get_event_cover($event->ID);

                if (!$cover_id && !empty($images)) {
                    $cover_id = $images[0]; // fallback
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

    $images = get_posts([
        'post_type'      => 'attachment',
        'post_parent'    => $event_id,
        'post_mime_type' => 'image',
        'posts_per_page' => -1,
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
    ]);
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

                <select class="emm-filter" id="emm-photographer-filter" data-event="<?php echo $event_id; ?>">
                    <option value="">All photographers</option>
                    <?php echo emm_get_event_photographers($event_id); ?>
                </select>
            </div>

        </div>


        <!-- ✅ IMAGE GRID MUST EXIST -->
        <div class="emm-grid">
            <?php foreach ($images as $img): ?>
                <div class="emm-card emm-image" data-id="<?php echo $img->ID; ?>">
                    <?php echo wp_get_attachment_image($img->ID, 'medium'); ?>
                    <button class="emm-remove" data-id="<?php echo $img->ID; ?>">✕</button>
                    <p><?php echo esc_html($img->post_title); ?></p>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
<?php
}


/* =========================================================
   8. AJAX — ATTACH IMAGES TO EVENT
========================================================= */
add_action('wp_ajax_emm_attach_images', function () {

    if (!current_user_can('upload_files')) {
        wp_send_json_error();
    }

    $event_id = (int) $_POST['event_id'];
    $files = $_POST['files'] ?? [];

    foreach ($files as $id) {
        wp_update_post([
            'ID' => (int) $id,
            'post_parent' => $event_id
        ]);
    }

    wp_send_json_success();
});

/* =========================================================
   9. ACF FIELDS FOR IMAGE DETAILS
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

    /* =========================================================
   10. AJAX — REMOVE IMAGE FROM EVENT
========================================================= */

    add_action('wp_ajax_emm_remove_image', function () {

        if (!current_user_can('upload_files')) {
            wp_send_json_error();
        }

        $image_id = (int) $_POST['image_id'];

        wp_update_post([
            'ID' => $image_id,
            'post_parent' => 0
        ]);

        wp_send_json_success();
    });

    /* =========================================================
   DELETE EVENT + UNLINK IMAGES
========================================================= */
    add_action('wp_ajax_emm_delete_event', function () {

        if (!current_user_can('upload_files')) {
            wp_send_json_error('Permission denied');
        }

        $event_id = (int) ($_POST['event_id'] ?? 0);
        if (!$event_id) {
            wp_send_json_error('Invalid event');
        }

        // Unlink images
        $images = get_posts([
            'post_type'      => 'attachment',
            'post_parent'    => $event_id,
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]);

        foreach ($images as $img_id) {
            wp_update_post([
                'ID'          => $img_id,
                'post_parent' => 0,
            ]);
        }

        // Delete event
        wp_delete_post($event_id, true);

        wp_send_json_success();
    });

    /* =========================================================
   11. HELPER FUNCTION — GET EVENT COVER IMAGE ID
========================================================= */

    function emm_get_event_cover($event_id)
    {
        $cover_id = (int) get_post_meta($event_id, '_emm_cover_image', true);
        return $cover_id ?: 0;
    }

    /* =========================================================
    12. AJAX — SET EVENT COVER IMAGE
========================================================= */

    add_action('wp_ajax_emm_set_event_cover', function () {

        if (!current_user_can('upload_files')) {
            wp_send_json_error();
        }

        $event_id = (int) $_POST['event_id'];
        $image_id = (int) $_POST['image_id'];

        update_post_meta($event_id, '_emm_cover_image', $image_id);

        wp_send_json_success();
    });

    /* =========================================================
    13. ENQUEUE JQUERY UI SORTABLE
========================================================= */

    add_action('admin_enqueue_scripts', function ($hook) {

        if ($hook !== 'toplevel_page_emm-dashboard') return;

        wp_enqueue_media();
        wp_enqueue_script('jquery-ui-sortable'); // 🔥 REQUIRED
    });

    /* =========================================================
   SAVE EVENT ORDER
========================================================= */
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

    /* =========================================================
   SAVE IMAGE ORDER INSIDE EVENT
========================================================= */
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

    /* =========================================================
   HELPER FUNCTION — GET PHOTOGRAPHER OPTIONS
========================================================= */

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

    /* =========================================================
   14. GET PHOTOGRAPHER OPTIONS FOR SELECT
========================================================= */

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

    /* =========================================================
   15. AJAX — FILTER IMAGES BY PHOTOGRAPHER
========================================================= */

    add_action('wp_ajax_emm_filter_images', function () {

        if (!current_user_can('upload_files')) {
            wp_die();
        }

        $event_id     = (int) $_POST['event_id'];
        $photographer = (int) ($_POST['photographer'] ?? 0);

        $images = get_posts([
            'post_type'      => 'attachment',
            'post_parent'    => $event_id,
            'post_mime_type' => 'image',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
            'fields'         => 'ids',
        ]);

        foreach ($images as $img_id) {

            $img_photographer = (int) get_post_meta($img_id, 'photographer', true);

            // If filter active and doesn't match → skip
            if ($photographer && $img_photographer !== $photographer) {
                continue;
            }

            echo '<div class="emm-card emm-image" data-id="' . esc_attr($img_id) . '">';
            echo wp_get_attachment_image($img_id, 'medium');
            echo '<button class="emm-remove" data-id="' . esc_attr($img_id) . '">✕</button>';
            echo '<p>' . esc_html(get_the_title($img_id)) . '</p>';
            echo '</div>';
        }

        wp_die();
    });


    /* =========================================================
   16. HELPER FUNCTION — GET EVENT PHOTOGRAPHERS OPTIONS
========================================================= */

    function emm_get_event_photographers($event_id)
    {
        $images = get_posts([
            'post_type'      => 'attachment',
            'post_parent'    => $event_id,
            'post_mime_type' => 'image',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]);

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







endif;
