<?php

/**
 * Plugin Name: Custom Admin Dashboard
 * Description: A custom plugin to modify and clean up the WordPress admin dashboard. [wwmt_time_ago] or [wwmt_time_ago icon="clock"], [post_image_count], [date_weather]
 * Version: 1.8.3
 * Author: TechM
 * Author URI: https://yourwebsite.com
 * Text Domain: custom-admin-dashboard
 * Domain Path: /languages
 */

// Exit if accessed directly (security measure)
if (!defined('ABSPATH')) {
    exit;
}

// ============================================================================
// 1. REMOVE DASHBOARD WIDGETS
// ============================================================================
function custom_remove_dashboard_widgets()
{
    remove_meta_box('dashboard_site_health', 'dashboard', 'normal');
    remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
    remove_meta_box('dashboard_primary', 'dashboard', 'side');
    remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
    remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');
    remove_meta_box('dashboard_secondary', 'dashboard', 'side');
}
add_action('wp_dashboard_setup', 'custom_remove_dashboard_widgets');

// ============================================================================
// 2. ADD CUSTOM WELCOME WIDGET
// ============================================================================
function custom_add_dashboard_widget()
{
    wp_add_dashboard_widget(
        'custom_welcome_widget',
        'Welcome to Your Dashboard!',
        'custom_dashboard_widget_content'
    );
}
add_action('wp_dashboard_setup', 'custom_add_dashboard_widget');

function custom_dashboard_widget_content()
{
    echo '<h3>' . esc_html__('Need help?', 'custom-admin-dashboard') . '</h3>';
    echo '<p>' . esc_html__('If you have any questions about managing your website, please contact the support team:', 'custom-admin-dashboard') . '</p>';
    echo '<ul>';
    echo '<li><strong>' . esc_html__('Email:', 'custom-admin-dashboard') . '</strong> <a href="mailto:support@yourcompany.com">support@yourcompany.com</a></li>';
    echo '<li><strong>' . esc_html__('Phone:', 'custom-admin-dashboard') . '</strong> 555-123-4567</li>';
    echo '</ul>';
    echo '<p>' . esc_html__('For quick access to the Pages section, click the button below:', 'custom-admin-dashboard') . '</p>';
    echo '<p><a href="' . esc_url(admin_url('edit.php?post_type=page')) . '" class="button button-primary">' . esc_html__('Go to Pages', 'custom-admin-dashboard') . '</a></p>';
}

// ============================================================================
// 3. INJECT USER PROFILE AT TOP OF SIDEBAR
// ============================================================================
function custom_admin_sidebar_profile()
{
    $current_user = wp_get_current_user();

    // Get avatar URL
    $custom_avatar_id = get_user_meta($current_user->ID, 'custom_avatar', true);
    if ($custom_avatar_id) {
        $avatar = wp_get_attachment_url($custom_avatar_id);
    } else {
        $avatar = get_avatar_url($current_user->ID, array('size' => 128));
    }

    $profile_url = admin_url('profile.php');

    // Get the custom login page URL for logout redirect
    $login_page = get_page_by_title('User Login');
    $redirect_target = $login_page ? get_page_link($login_page->ID) : home_url();

    // Generate logout URL that redirects to the custom login page
    $logout_url = wp_logout_url($redirect_target);

    // Sanitize and escape data
    $avatar = esc_url($avatar);
    $display_name = esc_html($current_user->display_name);
?>
    <script type="text/javascript">
        (function($) {
            $(function() {
                var profileHtml = `
                    <div class="custom-sidebar-profile">
                        <a href="<?php echo esc_attr($profile_url); ?>" class="profile-link">
                            <div class="profile-avatar">
                                <img src="<?php echo esc_attr($avatar); ?>" alt="User Avatar">
                            </div>
                            <div class="profile-info">
                                <span class="profile-greeting"><?php echo esc_html__('Welcome back', 'custom-admin-dashboard'); ?></span>
                                <span class="profile-name"><?php echo $display_name; ?></span>
                            </div>
                        </a>
                    </div>
                `;
                $('#adminmenuwrap').prepend(profileHtml);

                var logoutHtml = `
                    <div class="custom-sidebar-logout-wrap">
                        <a href="<?php echo esc_attr($logout_url); ?>" class="custom-logout-btn">
                            <i class="fa-right-from-bracket fa-solid"></i>
                            <span class="logout-text"><?php echo esc_html__('Sign Out', 'custom-admin-dashboard'); ?></span>
                        </a>
                    </div>
                `;
                $('#adminmenuwrap').append(logoutHtml);
            });
        })(jQuery);
    </script>
<?php
}
add_action('admin_footer', 'custom_admin_sidebar_profile');

// ============================================================================
// 4. RESTRICT MENU ITEMS FOR NON-ADMINS
// ============================================================================
function custom_restrict_admin_menus()
{
    if (!current_user_can('manage_options')) {
        remove_menu_page('edit.php?post_type=page');
        remove_menu_page('themes.php');
        remove_menu_page('edit.php?post_type=fl-theme-layout');
        remove_menu_page('plugins.php');
        remove_menu_page('users.php');
        remove_menu_page('tools.php');
    }
}
add_action('admin_menu', 'custom_restrict_admin_menus', 999);

// ============================================================================
// 5. CUSTOM PROFILE IMAGE UI
// ============================================================================
function custom_user_profile_fields($user)
{
    if (!current_user_can('edit_user', $user->ID)) {
        return;
    }

    $custom_avatar_id = get_user_meta($user->ID, 'custom_avatar', true);
    $custom_avatar_url = $custom_avatar_id ? wp_get_attachment_url($custom_avatar_id) : '';
?>
    <div class="custom-profile-image-section">
        <h3><?php esc_html_e('Profile Image', 'custom-admin-dashboard'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="custom_avatar"><?php esc_html_e('Custom Photo', 'custom-admin-dashboard'); ?></label></th>
                <td>
                    <div id="custom_avatar_preview" style="margin-bottom: 10px;">
                        <?php if ($custom_avatar_url) : ?>
                            <img src="<?php echo esc_url($custom_avatar_url); ?>" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 2px solid #ccc;">
                        <?php endif; ?>
                    </div>
                    <input type="hidden" name="custom_avatar" id="custom_avatar_id" value="<?php echo esc_attr($custom_avatar_id); ?>">
                    <button type="button" class="button button-primary" id="custom_avatar_button"><?php esc_html_e('Select Profile Image', 'custom-admin-dashboard'); ?></button>
                    <button type="button" class="button" id="custom_avatar_remove" style="<?php echo $custom_avatar_id ? '' : 'display:none;'; ?>"><?php esc_html_e('Remove', 'custom-admin-dashboard'); ?></button>
                    <p class="description"><?php esc_html_e('Upload a custom image to replace your Gravatar.', 'custom-admin-dashboard'); ?></p>
                </td>
            </tr>
        </table>
    </div>
<?php
}
add_action('show_user_profile', 'custom_user_profile_fields');
add_action('edit_user_profile', 'custom_user_profile_fields');

function save_custom_user_profile_fields($user_id)
{
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    if (isset($_POST['custom_avatar'])) {
        $avatar_id = sanitize_text_field($_POST['custom_avatar']);
        update_user_meta($user_id, 'custom_avatar', $avatar_id);
    }

    return true;
}
add_action('personal_options_update', 'save_custom_user_profile_fields');
add_action('edit_user_profile_update', 'save_custom_user_profile_fields');

// ============================================================================
// 6. WRITER LEADERBOARD WIDGET
// ============================================================================
function custom_add_leaderboard_widget()
{
    wp_add_dashboard_widget(
        'custom_writer_leaderboard',
        'Writer Leaderboard',
        'custom_writer_leaderboard_content'
    );
}
add_action('wp_dashboard_setup', 'custom_add_leaderboard_widget');

function custom_writer_leaderboard_content()
{
    $all_users = get_users(array('who' => 'authors'));
    $leaderboard = array();

    foreach ($all_users as $user) {
        $post_count = count_user_posts($user->ID, 'post', true);
        if ($post_count > 0) {
            $leaderboard[] = array(
                'id' => $user->ID,
                'name' => $user->display_name,
                'count' => $post_count
            );
        }
    }

    usort($leaderboard, function ($a, $b) {
        return $b['count'] <=> $a['count'];
    });

    echo '<h3>' . esc_html__('All Authors', 'custom-admin-dashboard') . '</h3>';
    echo '<ul class="writer-leaderboard-list">';

    if (empty($leaderboard)) {
        echo '<p>' . esc_html__('No authors found with published posts.', 'custom-admin-dashboard') . '</p>';
    } else {
        $rank = 0;
        foreach ($leaderboard as $writer) {
            $rank++;

            $custom_avatar_id = get_user_meta($writer['id'], 'custom_avatar', true);
            if ($custom_avatar_id) {
                $avatar_url = wp_get_attachment_url($custom_avatar_id);
                $avatar = '<img src="' . esc_url($avatar_url) . '" width="48" height="48" style="border-radius:50%; object-fit:cover;">';
            } else {
                $avatar = get_avatar($writer['id'], 48);
            }

            $is_winner = ($rank === 1);
            echo '<li class="leaderboard-item" style="display:flex; align-items:center; margin-bottom:10px; padding:10px; border-bottom:1px solid #eee;">';
            echo '<div class="leaderboard-avatar" style="margin-right:15px;">' . wp_kses_post($avatar) . '</div>';
            echo '<div class="leaderboard-info" style="flex-grow:1;">';
            echo '<strong>' . esc_html($writer['name']) . '</strong>';
            echo '<br><span class="post-count" style="color:#666; font-size:12px;">' . number_format_i18n($writer['count']) . ' Posts</span>';
            echo '</div>';
            if ($is_winner) {
                echo '<div class="win-sticker" style="font-size:20px;">🏆</div>';
            }
            echo '</li>';
        }
    }
    echo '</ul>';
}

// ============================================================================
// 7. WEBSITE STATISTICS WIDGET
// ============================================================================
function custom_add_stats_widget()
{
    wp_add_dashboard_widget(
        'custom_website_stats',
        'Website Statistics',
        'custom_website_stats_content'
    );
}
add_action('wp_dashboard_setup', 'custom_add_stats_widget');

function custom_website_stats_content()
{
    echo '<h3>' . esc_html__('Total Page Views: 2.5M', 'custom-admin-dashboard') . '</h3>';
    echo '<canvas id="pageViewsChart" style="max-height: 200px;"></canvas>';
}

// ============================================================================
// 8. ENQUEUE STYLES AND SCRIPTS
// ============================================================================
function custom_admin_styles($hook)
{
    // Enqueue Font Awesome
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css', array(), '6.5.1');

    // Load custom admin style
    $css_file = plugin_dir_path(__FILE__) . 'admin-style.css';
    $version = file_exists($css_file) ? filemtime($css_file) : '1.0.0';
    wp_enqueue_style('custom-admin-style', plugins_url('admin-style.css', __FILE__), array(), $version);

    // CHECK: Is this the Profile Page OR our Custom Plugin Settings Page?
    // Note: 'toplevel_page_my-plugin-slug' is the hook for your settings page
    if ('profile.php' === $hook || 'user-edit.php' === $hook || 'toplevel_page_my-plugin-slug' === $hook) {

        wp_enqueue_media(); // Load WordPress Media Uploader

        // Javascript to handle the Image Uploader
        $js_code = "
            jQuery(document).ready(function($) {
                
                // Generic function to handle image upload
                function setupMediaUploader(btnId, inputId, previewId, removeBtnId) {
                    var frame;
                    $(btnId).on('click', function(e) {
                        e.preventDefault();
                        if (frame) { frame.open(); return; }
                        
                        frame = wp.media({
                            title: '" . esc_js(__('Select Image', 'custom-admin-dashboard')) . "',
                            button: { text: '" . esc_js(__('Use this image', 'custom-admin-dashboard')) . "' },
                            multiple: false
                        });

                        frame.on('select', function() {
                            var attachment = frame.state().get('selection').first().toJSON();
                            $(inputId).val(attachment.id);
                            $(previewId).html('<img src=\"'+attachment.url+'\" style=\"max-width: 150px; height: auto; border: 2px solid #ccc;\">');
                            $(removeBtnId).show();
                        });
                        frame.open();
                    });

                    $(removeBtnId).on('click', function() {
                        $(inputId).val('');
                        $(previewId).empty();
                        $(this).hide();
                    });
                }

                // Initialize for User Profile (Your existing code)
                setupMediaUploader('#custom_avatar_button', '#custom_avatar_id', '#custom_avatar_preview', '#custom_avatar_remove');
                
                // Initialize for Plugin Settings (New code)
                setupMediaUploader('#cad_def_img_btn', '#cad_default_featured_image', '#cad_def_img_preview', '#cad_def_img_remove');

                // Move profile section if it exists
                var \$profileSection = $('.custom-profile-image-section');
                if (\$profileSection.length) { $('#your-profile').prepend(\$profileSection); }
                $('.user-profile-picture').closest('tr').hide();
            });
        ";
        wp_add_inline_script('jquery', $js_code);
    }
}
add_action('admin_enqueue_scripts', 'custom_admin_styles');

// ============================================================================
// 9. ENQUEUE CHART SCRIPTS
// ============================================================================
function custom_enqueue_chart_scripts($hook)
{
    if ('index.php' !== $hook) {
        return;
    }

    wp_enqueue_script(
        'chart-js',
        'https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js',
        array(),
        '4.4.2',
        true
    );

    $js_file = plugin_dir_path(__FILE__) . 'dashboard-chart.js';
    $version = file_exists($js_file) ? filemtime($js_file) : '1.0.0';

    wp_enqueue_script(
        'custom-chart-script',
        plugins_url('dashboard-chart.js', __FILE__),
        array('jquery', 'chart-js'),
        $version,
        true
    );

    $chart_data = array(
        'labels' => array('Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Day 6', 'Day 7'),
        'data' => array(1200, 1500, 900, 2200, 1800, 2500, 2100),
    );

    wp_localize_script(
        'custom-chart-script',
        'CustomChartData',
        $chart_data
    );
}
add_action('admin_enqueue_scripts', 'custom_enqueue_chart_scripts');

// ============================================================================
// 10. CUSTOM PLUGIN MENU & SETTINGS
// ============================================================================
add_action('admin_menu', 'my_custom_plugin_menu');

function my_custom_plugin_menu()
{
    add_menu_page(
        'My Plugin Settings',
        'Custom Plugin',
        'manage_options',
        'my-plugin-slug',
        'my_plugin_settings_page',
        'dashicons-admin-generic',
        6
    );
}

// Register the setting so WordPress saves it automatically
function cad_register_settings()
{
    register_setting('cad_plugin_options_group', 'cad_default_featured_image');
}
add_action('admin_init', 'cad_register_settings');

function my_plugin_settings_page()
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to access this page.', 'custom-admin-dashboard'));
    }

    // Get the saved image ID
    $default_image_id = get_option('cad_default_featured_image');
    $image_url = $default_image_id ? wp_get_attachment_url($default_image_id) : '';
?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <form method="post" action="options.php" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; max-width: 800px; margin-top: 20px;">
            <?php settings_fields('cad_plugin_options_group'); ?>
            <?php do_settings_sections('cad_plugin_options_group'); ?>

            <h2>Default Post Image</h2>
            <p>Select an image to use as the Featured Image for posts that don't have one set.</p>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Default Placeholder</th>
                    <td>
                        <div id="cad_def_img_preview" style="margin-bottom: 10px;">
                            <?php if ($image_url) : ?>
                                <img src="<?php echo esc_url($image_url); ?>" style="max-width: 150px; height: auto; border: 2px solid #ccc;">
                            <?php endif; ?>
                        </div>

                        <input type="hidden" name="cad_default_featured_image" id="cad_default_featured_image" value="<?php echo esc_attr($default_image_id); ?>">

                        <button type="button" class="button button-secondary" id="cad_def_img_btn">Select Image</button>
                        <button type="button" class="button button-link-delete" id="cad_def_img_remove" style="<?php echo $default_image_id ? '' : 'display:none;'; ?>">Remove Image</button>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <form method="post" action="options.php" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; max-width: 800px; margin-top: 20px;">
            <?php settings_fields('cad_plugin_options_group'); ?>

            <?php do_settings_sections('cad_plugin_options_group'); ?>
            <?php cad_custom_fonts_section_html(); ?>
            <?php submit_button(); ?>
        </form>
    </div>
<?php
}

// ============================================================================
// 11. POST IMAGE COUNT SHORTCODE
// ============================================================================
/**
 * Custom function to count the number of <img> tags in a post's content.
 * Usage: [post_image_count]
 */
function custom_post_image_count_shortcode($atts)
{
    global $post;

    // Validate that we have a valid post object
    if (!is_object($post) || !isset($post->post_content)) {
        return '0';
    }

    // Get the post content
    $content = $post->post_content;

    // Count all <img> tags using regex (case-insensitive)
    $image_count = preg_match_all('/<img[^>]+>/i', $content, $matches);

    // Return the count or 0 if no images found
    return esc_html($image_count ? $image_count : 0);
}
add_shortcode('post_image_count', 'custom_post_image_count_shortcode');

// ============================================================================
// 12. POST TIME ELAPSED FUNCTION - CLEAN VERSION
// ============================================================================
/**
 * Calculate and display time elapsed since post was published.
 */
function wwmt_time_ago($post_id = null)
{
    // If no post_id provided, use global post
    if ($post_id === null) {
        global $post;
        if (!$post || !isset($post->ID)) {
            return '';
        }
        $post_id = $post->ID;
        // $post_id = '124';
    }

    // Get the post publication time in UTC
    $post_date = get_post_time('U', true, $post_id);

    if (!$post_date) {
        return '';
    }

    // Get Current Time in UTC to match post_date
    $current_time = current_time('timestamp', true);

    // Calculate time difference
    $time_diff = $current_time - $post_date;

    // Define time constants
    $minute = 60;
    $hour   = 3600;
    $day    = 86400;
    $week   = 604800;
    $month  = 2592000;  // ~30 days
    $year   = 31536000; // ~365 days

    // Logic Tree
    $output = '';

    if ($time_diff < $minute) {
        $count = floor($time_diff);
        $output = ($count <= 1) ? 'just now' : $count . ' sec ago';
    } elseif ($time_diff < $hour) {
        $count = floor($time_diff / $minute);
        $output = $count . ' min' . ($count > 1 ? 's' : '') . ' ago';
    } elseif ($time_diff < $day) {
        $count = floor($time_diff / $hour);
        $output = $count . ' hour' . ($count > 1 ? 's' : '') . ' ago';
    } elseif ($time_diff < $week) {
        $count = floor($time_diff / $day);
        $output = $count . ' day' . ($count > 1 ? 's' : '') . ' ago';
    } elseif ($time_diff < $month) {
        $count = floor($time_diff / $week);
        $output = $count . ' week' . ($count > 1 ? 's' : '') . ' ago';
    } elseif ($time_diff < $year) {
        $count = floor($time_diff / $month);
        $output = $count . ' month' . ($count > 1 ? 's' : '') . ' ago';
    } else {
        $count = floor($time_diff / $year);
        $output = $count . ' year' . ($count > 1 ? 's' : '') . ' ago';
    }

    // Return The Result
    return esc_html($output);
}

// Shortcode usage: [wwmt_time_ago] or [wwmt_time_ago icon="clock"]
function wwmt_time_ago_shortcode($atts)
{
    $atts = shortcode_atts(array(
        'icon' => 'clock',
        'text' => '',
    ), $atts, 'wwmt_time_ago');

    // Get current post ID from the loop
    $post_id = get_the_ID();

    if (!$post_id) {
        return '';
    }

    $time_ago = wwmt_time_ago($post_id);

    if (empty($time_ago)) {
        return '';
    }

    $icon_class = 'fa-' . sanitize_text_field($atts['icon']);
    $label_text = sanitize_text_field($atts['text']);

    $output = '<span class="wwmt-time-ago-shortcode">';
    $output .= '<i class="far ' . esc_attr($icon_class) . '"></i> ';
    $output .= '<span class="time-label">' . esc_html($label_text) . '</span> ';
    $output .= '<strong>' . $time_ago . '</strong>';
    $output .= '</span>';

    return $output;
}
add_shortcode('wwmt_time_ago', 'wwmt_time_ago_shortcode');

// ============================================================================
// AJAX Handler - Get time ago for specific post
// ============================================================================
function get_post_time_ago_ajax()
{
    // Verify nonce
    check_ajax_referer('get_post_time_ago_nonce', 'nonce');

    // Get post ID
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

    if (!$post_id) {
        wp_send_json_error('Invalid post ID');
    }

    // Get time ago for this specific post
    $time_ago = wwmt_time_ago($post_id);

    wp_send_json_success(array(
        'time_ago' => $time_ago,
        'post_id' => $post_id
    ));
}
add_action('wp_ajax_get_post_time_ago', 'get_post_time_ago_ajax');
add_action('wp_ajax_nopriv_get_post_time_ago', 'get_post_time_ago_ajax');

// ============================================================================
// 15. CSS STYLING FOR TIME ELAPSED
// ============================================================================
/**
 * Add custom styling for the time elapsed display.
 */
function custom_time_elapsed_styles()
{
    echo '<style>
        .custom-time-ago-wrap {
            margin-left: 8px;
            color: #666;
        }
        
        .custom-time-ago {
            font-style: italic;
            color: #999;
            font-size: 13px;
        }
        
        .custom-time-ago i {
            margin-right: 4px;
            color: #999;
        }
        
        .fl-post-grid-meta .custom-time-ago {
            margin-left: 5px;
        }
        
        /* Shortcode Styling */
        .wwmt-time-ago-shortcode {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #666;
            font-size: 14px;
        }
        
        .wwmt-time-ago-shortcode i {
            color: #999;
        }
        
        .wwmt-time-ago-shortcode .time-label {
            font-weight: 500;
        }
        
        .wwmt-time-ago-shortcode strong {
            color: #333;
            font-weight: 600;
        }
    </style>';
}
add_action('wp_head', 'custom_time_elapsed_styles');


// ============================================================================
// 16. POST TABLE COLUMN STYLING
// ============================================================================
/**
 * Add post status column styling in admin post list.
 */
// Step 1: Add the column header
add_filter('manage_posts_columns', 'add_post_status_column');
function add_post_status_column($columns)
{
    $columns['post_status_custom'] = 'Post Status';
    return $columns;
}

// Step 2: Make the column sortable (optional)
add_filter('manage_edit-post_sortable_columns', 'make_post_status_sortable');
function make_post_status_sortable($columns)
{
    $columns['post_status_custom'] = 'post_status';
    return $columns;
}

// Step 3: Display content in the column with colored buttons
add_action('manage_posts_custom_column', 'display_post_status_column', 10, 2);
function display_post_status_column($column, $post_id)
{
    if ($column === 'post_status_custom') {
        $post = get_post($post_id);
        $post_url = get_edit_post_link($post_id);
        $status = $post->post_status;

        // Determine button color and text based on post status
        if ($status === 'publish') {
            $button_class = 'status-approved';
            $button_text = 'Approved';
            $bg_color = '#4CAF50'; // Green
        } elseif ($status === 'draft') {
            $button_class = 'status-review';
            $button_text = 'Under Review';
            $bg_color = '#FFC107'; // Yellow
        } else {
            $button_class = 'status-other';
            $button_text = ucfirst($status);
            $bg_color = '#9E9E9E'; // Gray
        }

        // Output the button
        echo sprintf(
            '<a href="%s" class="button %s" style="background-color: %s; color: %s; padding: 5px 10px; border-radius: 3px; text-decoration: none; display: inline-block; border: none;">%s</a>',
            esc_url($post_url),
            esc_attr($button_class),
            esc_attr($bg_color),
            $status === 'draft' ? '#000' : '#fff',
            esc_html($button_text)
        );
    }
}

// Step 4: Add custom CSS for better styling
add_action('admin_head', 'add_post_status_column_styles');
function add_post_status_column_styles()
{
    echo '<style>
        .status-approved, .status-review, .status-other {
            font-weight: bold !important;
            cursor: pointer !important;
            transition: opacity 0.3s ease !important;
        }
        .status-approved:hover, .status-review:hover, .status-other:hover {
            opacity: 0.8 !important;
        }

    </style>';
}

// ============================================================================
// 17. CREATE CUSTOM LOGIN & REGISTRATION PAGES
// ============================================================================

/**
 * Create pages on plugin activation
 */
function custom_auth_create_pages()
{
    // Check if pages already exist
    $login_page = get_page_by_title('User Login');
    $signup_page = get_page_by_title('User Signup');
    $not_found_page = get_page_by_title('Page Not Found');

    // Create Login Page
    if (!$login_page) {
        wp_insert_post(array(
            'post_title' => 'User Login',
            'post_content' => '[custom_login_form]',
            'post_status' => 'publish',
            'post_type' => 'page',
        ));
    }

    // Create Signup Page
    if (!$signup_page) {
        wp_insert_post(array(
            'post_title' => 'User Signup',
            'post_content' => '[custom_signup_form]',
            'post_status' => 'publish',
            'post_type' => 'page',
        ));
    }

    // Create Custom 404 Page
    if (!$not_found_page) {
        wp_insert_post(array(
            'post_title' => 'Page Not Found',
            // 'post_content' => custom_get_404_page_content(),
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => get_current_user_id() ?: 1,
        ));
    }
}
register_activation_hook(__FILE__, 'custom_auth_create_pages');

// ============================================================================
// 18. CUSTOM 404 PAGE CONTENT & TEMPLATE LOGIC
// ============================================================================

function custom_get_404_page_content()
{
    return '<h1>Page Not Found</h1>
<p>Sorry, the page you are looking for could not be found. It may have been moved or deleted.</p>
<p><a href="' . esc_url(home_url()) . '" class="button button-primary">Back to Home</a></p>
<p><strong>What happened?</strong></p>
<ul>
<li>The page might have been deleted</li>
<li>The URL might be incorrect</li>
<li>You might not have permission to view this page</li>
</ul>
<p>Please check the URL and try again, or use the navigation menu to find what you are looking for.</p>
';
}

/**
 * Alternative Method: Load custom 404 template
 * This replaces the theme's 404.php template
 */
function custom_404_template($template)
{
    if (is_404()) {
        $not_found_page = get_page_by_title('Page Not Found');

        if ($not_found_page && $not_found_page->post_status === 'publish') {
            // Make WordPress think this is a regular page
            global $wp_query;
            $wp_query->is_404 = false;
            $wp_query->is_page = true;
            $wp_query->is_singular = true;
            $wp_query->queried_object = $not_found_page;
            $wp_query->queried_object_id = $not_found_page->ID;
            $wp_query->post = $not_found_page;
            $wp_query->posts = array($not_found_page);
            $wp_query->post_count = 1;
            $wp_query->found_posts = 1;
            $wp_query->max_num_pages = 1;

            // Set 404 status header
            status_header(404);

            // Use the page template
            $page_template = get_page_template();
            if ($page_template) {
                return $page_template;
            }
        }
    }

    return $template;
}
add_filter('template_include', 'custom_404_template', 99);

/**
 * Ensure the custom 404 page itself doesn't trigger 404
 */
function prevent_404_page_from_404($posts, $query)
{
    if ($query->is_main_query() && !is_admin()) {
        $not_found_page = get_page_by_title('Page Not Found');

        if ($not_found_page && empty($posts) && $query->is_page) {
            $page_slug = $query->get('pagename');

            if (
                $page_slug === $not_found_page->post_name ||
                $page_slug === sanitize_title('Page Not Found')
            ) {
                return array($not_found_page);
            }
        }
    }

    return $posts;
}
add_filter('the_posts', 'prevent_404_page_from_404', 10, 2);


// ============================================================================
// 19. AUTH HELPER FUNCTION
// ============================================================================
// Check if auth pages exist and redirect if missing
function check_auth_page_exists($page_title)
{
    $page = get_page_by_title($page_title);

    if (!$page) {
        // Log the error for debugging
        error_log('Missing required page: ' . $page_title . ' (Auth Plugin)');

        // Redirect to custom 404 page
        $not_found_page = get_page_by_title('Page Not Found');

        if ($not_found_page) {
            wp_redirect(get_page_link($not_found_page->ID));
            exit;
        }

        // Fallback: Trigger WordPress 404
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
        get_template_part('404');
        exit;
    }

    return $page;
}

// ============================================================================
// 20. CUSTOM LOGIN FORM SHORTCODE
// ============================================================================

function custom_login_form_shortcode()
{
    // Redirect if already logged in
    if (is_user_logged_in()) {
        return '<div class="auth-message auth-success"><p>You are already logged in. <a href="' . esc_url(admin_url()) . '">Go to Dashboard</a></p></div>';
    }

    $login_error = '';
    $login_success = '';

    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['custom_login_nonce'])) {
        // Verify nonce
        if (!wp_verify_nonce($_POST['custom_login_nonce'], 'custom_login_action')) {
            $login_error = 'Security check failed. Please try again.';
        } else {
            // Sanitize inputs
            $username = sanitize_text_field($_POST['username']);
            $password = sanitize_text_field($_POST['password']);
            $remember = isset($_POST['remember']) ? true : false;

            // Validate inputs
            if (empty($username) || empty($password)) {
                $login_error = 'Please enter both username and password.';
            } else {
                // Attempt to authenticate user
                $user = wp_authenticate($username, $password);

                if (is_wp_error($user)) {
                    $login_error = 'Invalid username or password.';
                } else {
                    // Login successful
                    wp_set_current_user($user->ID);
                    wp_set_auth_cookie($user->ID, $remember);
                    do_action('wp_login', $user->user_login, $user);

                    // Redirect to dashboard or referrer
                    $redirect_url = isset($_POST['redirect_to']) ? esc_url_raw($_POST['redirect_to']) : admin_url();
                    wp_safe_remote_post($redirect_url);
                    wp_redirect($redirect_url);
                    exit;
                }
            }
        }
    }

    // Get signup page - will trigger 404 if missing
    $signup_page = check_auth_page_exists('User Signup');
    $signup_link = esc_url(get_page_link($signup_page->ID));

    ob_start();
?>
    <div class="custom-auth-container">
        <div class="auth-form-wrapper">
            <h2 class="auth-form-title">Sign In</h2>

            <?php if (!empty($login_error)) : ?>
                <div class="auth-message auth-error">
                    <p><?php echo esc_html($login_error); ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" class="custom-login-form" novalidate>
                <?php wp_nonce_field('custom_login_action', 'custom_login_nonce'); ?>

                <div class="form-group">
                    <label for="username"><?php esc_html_e('Username or Email', 'custom-user-auth'); ?></label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control"
                        placeholder="Enter your username or email"
                        required
                        value="<?php echo isset($_POST['username']) ? esc_attr($_POST['username']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="password"><?php esc_html_e('Password', 'custom-user-auth'); ?></label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Enter your password"
                        required>
                </div>

                <div class="checkbox-group form-group">
                    <label for="remember">
                        <input type="checkbox" id="remember" name="remember" value="1">
                        <?php esc_html_e('Remember me', 'custom-user-auth'); ?>
                    </label>
                </div>

                <button type="submit" class="btn-block btn btn-primary"><?php esc_html_e('Sign In', 'custom-user-auth'); ?></button>

                <div class="auth-links">
                    <a href="<?php echo esc_url(wp_lostpassword_url()); ?>" class="forgot-password"><?php esc_html_e('Forgot Password?', 'custom-user-auth'); ?></a>
                </div>
            </form>

            <div class="auth-footer">
                <p><?php esc_html_e("Don't have an account?", 'custom-user-auth'); ?> <a href="<?php echo $signup_link; ?>"><?php esc_html_e('Sign up here', 'custom-user-auth'); ?></a></p>
            </div>
        </div>
    </div>
<?php
    return ob_get_clean();
}
add_shortcode('custom_login_form', 'custom_login_form_shortcode');

// ============================================================================
// 21. CUSTOM SIGNUP FORM SHORTCODE
// ============================================================================

function custom_signup_form_shortcode()
{
    // Redirect if already logged in
    if (is_user_logged_in()) {
        return '<div class="auth-message auth-success"><p>You are already logged in. <a href="' . esc_url(admin_url()) . '">Go to Dashboard</a></p></div>';
    }

    $signup_error = '';
    $signup_success = '';

    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['custom_signup_nonce'])) {
        // Verify nonce
        if (!wp_verify_nonce($_POST['custom_signup_nonce'], 'custom_signup_action')) {
            $signup_error = 'Security check failed. Please try again.';
        } else {
            // Sanitize inputs
            $username = sanitize_user($_POST['username']);
            $email = sanitize_email($_POST['email']);
            $password = $_POST['password'];
            $password_confirm = $_POST['password_confirm'];
            $first_name = sanitize_text_field($_POST['first_name']);
            $last_name = sanitize_text_field($_POST['last_name']);

            // Validation
            $errors = array();

            if (empty($username)) {
                $errors[] = 'Username is required.';
            } elseif (strlen($username) < 3) {
                $errors[] = 'Username must be at least 3 characters.';
            } elseif (username_exists($username)) {
                $errors[] = 'Username already exists.';
            }

            if (empty($email)) {
                $errors[] = 'Email is required.';
            } elseif (!is_email($email)) {
                $errors[] = 'Invalid email address.';
            } elseif (email_exists($email)) {
                $errors[] = 'Email already registered.';
            }

            if (empty($password)) {
                $errors[] = 'Password is required.';
            } elseif (strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters.';
            }

            if ($password !== $password_confirm) {
                $errors[] = 'Passwords do not match.';
            }

            if (empty($first_name)) {
                $errors[] = 'First name is required.';
            }

            if (!empty($errors)) {
                $signup_error = implode('<br>', $errors);
            } else {
                // Create user
                $user_id = wp_create_user($username, $password, $email);

                if (is_wp_error($user_id)) {
                    $signup_error = $user_id->get_error_message();
                } else {
                    // Update user meta
                    update_user_meta($user_id, 'first_name', $first_name);
                    update_user_meta($user_id, 'last_name', $last_name);

                    // Set user role (subscriber by default)
                    $user = new WP_User($user_id);
                    $user->set_role('subscriber');

                    // Log user in
                    wp_set_current_user($user_id);
                    wp_set_auth_cookie($user_id);
                    do_action('wp_login', $username, $user);

                    // Send welcome email
                    wp_mail(
                        $email,
                        'Welcome to ' . get_bloginfo('name'),
                        'Thank you for signing up! Your account has been created successfully.'
                    );

                    // Redirect to dashboard
                    wp_redirect(admin_url());
                    exit;
                }
            }
        }
    }

    // Get login page - will trigger 404 if missing
    $login_page = check_auth_page_exists('User Login');
    $login_link = esc_url(get_page_link($login_page->ID));

    ob_start();
?>
    <div class="custom-auth-container">
        <div class="auth-form-wrapper">
            <h2 class="auth-form-title">Create Account</h2>

            <?php if (!empty($signup_error)) : ?>
                <div class="auth-message auth-error">
                    <p><?php echo wp_kses_post($signup_error); ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" class="custom-signup-form" novalidate>
                <?php wp_nonce_field('custom_signup_action', 'custom_signup_nonce'); ?>

                <div class="form-row">
                    <div class="form-group form-group-half">
                        <label for="first_name"><?php esc_html_e('First Name', 'custom-user-auth'); ?></label>
                        <input
                            type="text"
                            id="first_name"
                            name="first_name"
                            class="form-control"
                            placeholder="First Name"
                            required
                            value="<?php echo isset($_POST['first_name']) ? esc_attr($_POST['first_name']) : ''; ?>">
                    </div>

                    <div class="form-group form-group-half">
                        <label for="last_name"><?php esc_html_e('Last Name', 'custom-user-auth'); ?></label>
                        <input
                            type="text"
                            id="last_name"
                            name="last_name"
                            class="form-control"
                            placeholder="Last Name"
                            value="<?php echo isset($_POST['last_name']) ? esc_attr($_POST['last_name']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="username"><?php esc_html_e('Username', 'custom-user-auth'); ?></label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control"
                        placeholder="Choose a username"
                        required
                        value="<?php echo isset($_POST['username']) ? esc_attr($_POST['username']) : ''; ?>">
                    <small class="form-text"><?php esc_html_e('At least 3 characters', 'custom-user-auth'); ?></small>
                </div>

                <div class="form-group">
                    <label for="email"><?php esc_html_e('Email Address', 'custom-user-auth'); ?></label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="your@email.com"
                        required
                        value="<?php echo isset($_POST['email']) ? esc_attr($_POST['email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="password"><?php esc_html_e('Password', 'custom-user-auth'); ?></label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="At least 8 characters"
                        required>
                    <small class="form-text"><?php esc_html_e('Minimum 8 characters', 'custom-user-auth'); ?></small>
                </div>

                <div class="form-group">
                    <label for="password_confirm"><?php esc_html_e('Confirm Password', 'custom-user-auth'); ?></label>
                    <input
                        type="password"
                        id="password_confirm"
                        name="password_confirm"
                        class="form-control"
                        placeholder="Confirm your password"
                        required>
                </div>

                <button type="submit" class="btn-block btn btn-primary"><?php esc_html_e('Create Account', 'custom-user-auth'); ?></button>
            </form>

            <div class="auth-footer">
                <p><?php esc_html_e('Already have an account?', 'custom-user-auth'); ?> <a href="<?php echo $login_link; ?>"><?php esc_html_e('Sign in here', 'custom-user-auth'); ?></a></p>
            </div>
        </div>
    </div>
<?php
    return ob_get_clean();
}
add_shortcode('custom_signup_form', 'custom_signup_form_shortcode');

// ============================================================================
// 22. ENQUEUE AUTH STYLES
// ============================================================================

function custom_auth_enqueue_styles()
{
    wp_enqueue_style(
        'custom-auth-style',
        plugins_url('auth-style.css', __FILE__),
        array(),
        '1.0.0'
    );
}
add_action('wp_enqueue_scripts', 'custom_auth_enqueue_styles');

// ============================================================================
// 23. REDIRECT WP-LOGIN TO CUSTOM LOGIN PAGE
// ============================================================================

function custom_login_page_redirect()
{
    $request_uri = $_SERVER['REQUEST_URI'];

    // Check if we are visiting wp-login.php
    if (strpos($request_uri, 'wp-login.php') !== false && !is_admin()) {

        // 1. ALLOW LOGOUT: If the action is logout, do not redirect. Let WP handle it.
        if (isset($_GET['action']) && $_GET['action'] === 'logout') {
            return;
        }

        // 2. ALLOW POST REQUESTS: (Optional but good for compatibility)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return;
        }

        // 3. Otherwise, redirect to custom login page
        $login_page = get_page_by_title('User Login');
        if ($login_page) {
            wp_redirect(get_page_link($login_page->ID));
            exit;
        }
    }
}
add_action('init', 'custom_login_page_redirect');

// ============================================================================
// 24. CUSTOM LOGOUT REDIRECT
// ============================================================================

function custom_logout_redirect($redirect_to, $requested_redirect_to, $user)
{
    // Redirect to the Custom Login Page instead of Home
    $login_page = get_page_by_title('User Login');

    if ($login_page) {
        return get_page_link($login_page->ID);
    }

    // Fallback to home if page doesn't exist
    return home_url();
}
add_filter('logout_redirect', 'custom_logout_redirect', 10, 3);

// ============================================================================
// 25. RESTRICT DIRECT ACCESS TO WP-LOGIN
// ============================================================================

function restrict_wp_login()
{
    // Check if we are on wp-login.php
    if (strpos($_SERVER['REQUEST_URI'], '/wp-login.php') !== false && !is_admin() && !defined('DOING_CRON')) {

        // CRITICAL FIX: Allow the logout action to pass through
        if (isset($_GET['action']) && $_GET['action'] === 'logout') {
            return;
        }

        $login_page = get_page_by_title('User Login');
        if ($login_page) {
            wp_redirect(get_page_link($login_page->ID));
            exit;
        }
    }
}
add_action('init', 'restrict_wp_login', 1);

// ============================================================================
// 26. DEFAULT USER ROLE SETTINGS
// ============================================================================

function custom_auth_default_user_role()
{
    return 'subscriber';
}
add_filter('default_user_role', 'custom_auth_default_user_role');

// ============================================================================
// 27. CUSTOM PROFILE REDIRECT
// ============================================================================

function redirect_after_profile_update($user_id)
{
    if (isset($_POST['user_id'])) {
        wp_redirect(admin_url('profile.php?updated=true'));
        exit;
    }
}
add_action('profile_update', 'redirect_after_profile_update');

// ============================================================================
// 28. HIDE ADMIN BAR FOR NON-ADMINISTRATORS
// ============================================================================

function custom_hide_admin_bar_for_non_admin()
{
    // Check if user is logged in
    if (is_user_logged_in()) {
        // Get current user
        $current_user = wp_get_current_user();

        // Only show admin bar for administrators
        if (!in_array('administrator', (array) $current_user->roles)) {
            show_admin_bar(false);
        }
    }
}
add_action('init', 'custom_hide_admin_bar_for_non_admin');

// ============================================================================
// 29. AUTO-SET DEFAULT FEATURED IMAGE
// ============================================================================
function my_custom_plugin_set_default_thumbnail($post_id)
{

    // 1. Check if autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // 2. Check if post type is 'post' (change to 'page' or custom type if needed)
    if (get_post_type($post_id) !== 'post') {
        return;
    }

    // 3. Check if the post already has a thumbnail
    if (has_post_thumbnail($post_id)) {
        return;
    }

    // 4. GET DEFAULT IMAGE ID FROM SETTINGS
    $default_thumbnail_id = get_option('cad_default_featured_image');

    // 5. If an ID is set in settings, apply it
    if (!empty($default_thumbnail_id)) {
        update_post_meta($post_id, '_thumbnail_id', $default_thumbnail_id);
    }
}
add_action('save_post', 'my_custom_plugin_set_default_thumbnail');

// ============================================================================
// 30. Weather & Date Shortcode [date_weather]
// ============================================================================

function date_with_live_weather_shortcode()
{

    // 1️⃣ Get city by IP
    $ip_response = wp_remote_get("http://ip-api.com/json/");
    if (is_wp_error($ip_response)) return "Location unavailable";

    $ip_data = json_decode(wp_remote_retrieve_body($ip_response), true);
    $city = $ip_data['city'] ?? 'Colombo'; // fallback city

    // 2️⃣ Weather API
    $apiKey = "f524f4a3f66b684de434d97cabcd043c"; // OpenWeatherMap API key
    $url = "https://api.openweathermap.org/data/2.5/weather?q=" . urlencode($city) . "&units=metric&appid=" . $apiKey;

    $weather_response = wp_remote_get($url);
    if (is_wp_error($weather_response)) return "Weather unavailable";

    $weather_data = json_decode(wp_remote_retrieve_body($weather_response), true);
    if (!isset($weather_data['main']['temp'])) return "Weather data error";

    $temp = round($weather_data['main']['temp'], 1) . "°C";

    // 3️⃣ Date
    $date = date("l j F, Y");

    // 4️⃣ Final output
    return $temp . " " . $city . " — " . $date;
}
add_shortcode('date_weather', 'date_with_live_weather_shortcode');

// ============================================================================
// 31. Post Time Elapsed in Post Grid and Enqueue script to inject post time
// ============================================================================

add_action('wp_footer', 'enqueue_post_time_script');

function enqueue_post_time_script()
{
    // Get all posts from database
    $args = array(
        'post_type' => 'post',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    );

    $posts = get_posts($args);
    $post_times = array();

    // Get time for each post
    foreach ($posts as $post) {
        $post_id = $post->ID;
        $post_timestamp = strtotime($post->post_date);

        // --- CHANGED TO RELATIVE TIME ---
        // Calculate "Time Ago" (e.g., "2 hours ago", "1 month ago")
        // human_time_diff returns "1 hour", "5 mins". We append " ago".
        $post_time = human_time_diff($post_timestamp, current_time('timestamp')) . ' ago';

        $post_times[$post_id] = $post_time;
    }

    wp_reset_postdata();
?>
    <script>
        var postTimesData = <?php echo json_encode($post_times); ?>;

        document.addEventListener('DOMContentLoaded', function() {
            var postTitles = document.querySelectorAll('.fl-post-grid-title');
            var postComments = document.querySelectorAll('.fl-post-feed-comments');

            postComments.forEach(function(comment) {
                var postLink = comment.querySelector('a');

                if (postLink) {
                    // 1. Get the Post ID
                    var postGridPost = comment.closest('.fl-post-grid-post');

                    if (postGridPost) {
                        var classList = postGridPost.getAttribute('class');
                        var match = classList.match(/post-(\d+)/);
                        var postId = match ? match[1] : null;

                        if (postId && postTimesData[postId]) {
                            var postGridText = postGridPost.querySelector('.fl-post-grid-text');

                            // We target the EXISTING date class (.fl-post-grid-date)
                            var timeElapsedEl = postGridText.querySelector('.fl-post-grid-meta .fl-post-grid-date');

                            // Fallback: If not in meta, try searching generally in text area
                            if (!timeElapsedEl) {
                                timeElapsedEl = postGridText.querySelector('.fl-post-grid-date');
                            }

                            // Get the original date text (e.g., "Jan 4, 2026") to show in parentheses
                            var originalDate = timeElapsedEl ? timeElapsedEl.textContent.trim() : '';

                            // Construct the new HTML
                            // Result: Posted: 2 hours ago (Jan 4, 2026)
                            var dateSuffix = originalDate ? ' (' + originalDate + ')' : '';

                            // var timeHtml = '<div class="fl-post-time-custom"><i class="far fa-clock"></i> <strong>Posted:</strong> ' + postTimesData[postId] + dateSuffix + '</div>';

                            // Create time elapsed HTML with clock icon
                            var timeHtml = '<span class="fl-post-time-elapsed">' +
                                '<i class="far fa-clock"></i> ' +
                                postTimesData[postId] +
                                '</span>';



                            // title.insertAdjacentHTML('afterend', timeHtml);
                            comment.insertAdjacentHTML('afterend', timeHtml);

                            // var $commentsSpan = $metaContainer.find('.fl-post-feed-comments');


                            // Optional: Hide the original date element to avoid duplicates
                            if (timeElapsedEl) {
                                timeElapsedEl.style.display = 'none';
                            }
                        }
                    }
                }
            });
        });
    </script>
<?php
}


// Add CSS styling for the post time
add_action('wp_head', 'post_time_custom_css');

function post_time_custom_css()
{
?>
    <style>
        .fl-post-time-custom {
            background-color: #f9f9f9;
            padding: 10px 0;
            margin: 10px 0;
            font-size: 14px;
            color: #666;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .fl-post-time-custom strong {
            color: #333;
        }

        .fl-post-time-custom i {
            color: #007cba;
        }

        .fl-post-time-elapsed {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-right: 10px;
            font-size: 13px;
            color: #666;
        }
    </style>
<?php
}

// ============================================================================
// 32. FORCE RTL (RIGHT-TO-LEFT) IN EDITORS - FIXED
// ============================================================================

/**
 * Method 2: Inline CSS directly (Alternative if CSS file doesn't work)
 * Uncomment if Method 1 doesn't work for you
 */

function cad_add_block_editor_rtl_styles()
{
    $css = "
        /* Core Editor Container */
        .edit-post-visual-editor,
        .block-editor-writing-flow,
        .wp-block-post-title,
        .editor-post-title__input {
            direction: rtl !important;
            text-align: right !important;
        }

        /* Post Title */
        .editor-post-title__input,
        h1.editor-post-title__input {
            direction: rtl !important;
            text-align: right !important;
            font-family: 'MV Faseyha', 'Thamaan', sans-serif !important;
        }

        /* Paragraph & Text Blocks */
        .wp-block-paragraph,
        .block-editor-rich-text__editable[data-is-placeholder-visible='false'],
        p[role='textbox'] {
            direction: rtl !important;
            text-align: right !important;
        }

        /* Headings */
        .wp-block-heading {
            direction: rtl !important;
            text-align: right !important;
        }

        /* Lists */
        .wp-block-list,
        .wp-block-list li {
            direction: rtl !important;
        }

        /* All Block Content */
        .wp-block {
            direction: rtl !important;
        }

        /* Text Align Controls - ensure right-align is default */
        [class*='text-align'] {
            direction: rtl !important;
        }

        /* Form Tags field in Editor */
        .components-form-token-field__input-container input[type=text].components-form-token-field__input {
            direction: rtl !important;
            text-align: right !important;
        }

        /* Form Category & Tag fields in Editor */
        .components-text-control__input, .components-text-control__input[type=color], .components-text-control__input[type=date], 
        .components-text-control__input[type=datetime-local], .components-text-control__input[type=datetime], .components-text-control__input[type=email], 
        .components-text-control__input[type=month], .components-text-control__input[type=number], .components-text-control__input[type=password], 
        .components-text-control__input[type=tel], .components-text-control__input[type=text], .components-text-control__input[type=time], .components-text-control__input[type=url], 
        .components-text-control__input[type=week] {
            direction: rtl !important;
            text-align: right !important;
        }

    ";

    wp_add_inline_style('wp-edit-blocks', $css);
}
add_action('enqueue_block_editor_assets', 'cad_add_block_editor_rtl_styles');

// RTL Styles for Category and Tag Pages
function cad_add_taxonomy_rtl_styles()
{
    $css = "
        /* Category & Tag Form Wrapper */
        .form-wrap {
            // direction: rtl !important;
        }

        /* All Form Fields */
        .form-field input[type=text],
        .form-field input[type=email],
        .form-field input[type=url],
        .form-field textarea{
            direction: rtl !important;
            text-align: right !important;
        }

        /* Specific Input Fields */
        #tag-name,
        #tag-slug,
        #tag-description{
            direction: rtl !important;
            text-align: right !important;
        }

        // /* Parent Category/Tag Dropdown */
        // select.postform {
        //     direction: rtl !important;
        // }

        // /* Description Textarea */
        // textarea[name='description'] {
        //     direction: rtl !important;
        //     text-align: right !important;
        // }

        // /* Form Field Labels */
        // .form-field label {
        //     display: block !important;
        //     text-align: right !important;
        // }

        // /* Description Paragraphs */
        // .form-field p {
        //     text-align: right !important;
        // }

    ";

    wp_add_inline_style('wp-admin', $css);
}
add_action('admin_enqueue_scripts', 'cad_add_taxonomy_rtl_styles');

// ============================================================================
// 33. Show Category and Tag Management to Editor Role
// ============================================================================

// Enable Category and Tag Management for Editor Role
function cad_add_editor_taxonomy_capabilities()
{
    // Get the Editor role
    $editor_role = get_role('editor');

    if ($editor_role) {
        // Category Capabilities
        $editor_role->add_cap('manage_categories');
        $editor_role->add_cap('edit_categories');
        $editor_role->add_cap('delete_categories');
        $editor_role->add_cap('assign_categories');

        // Tag Capabilities
        $editor_role->add_cap('manage_post_tags');
        $editor_role->add_cap('edit_post_tags');
        $editor_role->add_cap('delete_post_tags');
        $editor_role->add_cap('assign_post_tags');
    }
}

register_activation_hook(__FILE__, 'cad_add_editor_taxonomy_capabilities');


// ============================================================================
// 34. RESTRICT POSTS VIEW IN ADMIN BASED ON USER ROLE
// ============================================================================


add_action('pre_get_posts', 'restrict_posts_by_user_role');

function restrict_posts_by_user_role($query)
{
    // Only apply to admin area and main query
    if (! is_admin() || ! $query->is_main_query()) {
        return;
    }

    // Get current user
    $current_user = wp_get_current_user();

    // Allow Administrators and Editors to see all posts
    if (in_array('administrator', $current_user->roles) || in_array('editor', $current_user->roles)) {
        return;
    }

    // For Authors and other roles, show only their own posts
    if (in_array('author', $current_user->roles)) {
        $query->set('author', $current_user->ID);
    }
}

// ============================================================================
// 35.  HIDE PUBLISH BUTTON FOR AUTHOR ROLE IN GUTENBERG EDITOR
// ============================================================================

/**
 * Hide Publish button and rename Save Draft for Author role in Gutenberg Editor
 * Using WordPress filters - No DOM manipulation
 */

add_action('enqueue_block_editor_assets', 'restrict_author_publish_gutenberg');

function restrict_author_publish_gutenberg()
{
    // Get current user
    $current_user = wp_get_current_user();

    // Only apply to Authors
    if (! in_array('author', $current_user->roles)) {
        return;
    }

    // Enqueue the script
    wp_enqueue_script(
        'author-restrict-publish',
        plugin_dir_url(__FILE__) . 'js/author-restrict.js',
        array('wp-blocks', 'wp-dom-ready', 'wp-edit-post', 'wp-components'),
        '1.0',
        true
    );

    // Add inline CSS to hide publish button safely
    wp_add_inline_style(
        'wp-edit-post',
        '
        .editor-post-publish-button__button,
        .editor-post-publish-panel__toggle {
            display: none !important;
        }
        '
    );
}

// ============================================================================
// 36.  post URL as Post ID
// ============================================================================
// Add rewrite rule for post IDs
add_action('init', 'custom_post_id_rewrite_rule');
function custom_post_id_rewrite_rule()
{
    add_rewrite_rule('^([0-9]+)/?$', 'index.php?p=$1', 'top');
}

// Filter to change post URLs
add_filter('post_link', 'custom_post_url_by_id', 10, 2);
function custom_post_url_by_id($permalink, $post)
{
    if ($post->post_type === 'post') {
        return home_url('/' . $post->ID . '/');
    }
    return $permalink;
}

// ============================================================================
// 37. REGISTER CUSTOM BLOCKS
// ============================================================================
function cad_register_custom_blocks()
{
    // Register the Alert Box Block
    // Point this to the FOLDER containing block.json
    register_block_type(plugin_dir_path(__FILE__) . 'blocks/alert-box');
    // Register the Photo Caption Block
    register_block_type(plugin_dir_path(__FILE__) . 'blocks/photo-caption');
}
add_action('init', 'cad_register_custom_blocks');

// ============================================================================
// 38. CUSTOM FONT UPLOADER & MANAGER (FULLY FIXED & TESTED)
// ============================================================================

// 1. ALLOW FONT FILE UPLOADS (SECURITY FIX)
function cad_allow_font_mime_types($mimes)
{
    $mimes['woff']  = 'application/font-woff';
    $mimes['woff2'] = 'application/font-woff2';
    $mimes['ttf']   = 'application/x-font-ttf';
    $mimes['otf']   = 'application/x-font-opentype';
    return $mimes;
}
add_filter('upload_mimes', 'cad_allow_font_mime_types');

// 1.1 BYPASS WORDPRESS REAL MIME CHECK
function cad_fix_font_mime_issue($data, $file, $filename, $mimes)
{
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    if (in_array($ext, array('otf', 'ttf', 'woff', 'woff2'))) {
        $data['ext'] = $ext;
        $data['type'] = 'application/x-font-opentype';
        if ($ext === 'ttf')   $data['type'] = 'application/x-font-ttf';
        if ($ext === 'woff')  $data['type'] = 'application/font-woff';
        if ($ext === 'woff2') $data['type'] = 'application/font-woff2';
    }
    return $data;
}
add_filter('wp_check_filetype_and_ext', 'cad_fix_font_mime_issue', 10, 4);

// 2. REGISTER SETTINGS (SANITIZE ON SAVE)
function cad_register_font_settings()
{
    register_setting(
        'cad_font_options_group',
        'cad_custom_fonts',
        array(
            'type'              => 'array',
            'sanitize_callback' => 'cad_sanitize_fonts',
            'show_in_rest'      => false
        )
    );
}
add_action('admin_init', 'cad_register_font_settings');

// 2.1 SANITIZE FONTS BEFORE SAVING
function cad_sanitize_fonts($fonts)
{
    if (!is_array($fonts)) return array();

    $sanitized = array();
    foreach ($fonts as $font) {
        if (!empty($font['name']) && !empty($font['url'])) {
            $sanitized[] = array(
                'name'   => sanitize_text_field($font['name']),
                'url'    => esc_url_raw($font['url']),
                'weight' => sanitize_text_field($font['weight']) ?: 'normal'
            );
        }
    }
    return $sanitized;
}

// 3. ADMIN SETTINGS UI (TABLE LAYOUT)
function cad_custom_fonts_section_html()
{
    $fonts = get_option('cad_custom_fonts', array());
    if (!is_array($fonts)) {
        $fonts = array();
    }
?>
    <hr>
    <h2>Custom Fonts Manager</h2>
    <p>Upload your font files. These will appear in the <strong>Block Editor</strong> and <strong>Classic Editor</strong> font dropdowns.</p>

    <form method="POST" action="options.php">
        <?php settings_fields('cad_font_options_group'); ?>

        <table class="wp-list-table table-view-list fixed widefat striped" id="cad-font-table">
            <thead>
                <tr>
                    <th style="width: 20%;">Font Name</th>
                    <th style="width: 15%;">Preview</th>
                    <th style="width: 15%;">Weight</th>
                    <th style="width: 40%;">File URL</th>
                    <th style="width: 10%;">Actions</th>
                </tr>
            </thead>
            <tbody id="cad-font-tbody">
                <?php
                if (!empty($fonts)) :
                    foreach ($fonts as $index => $font) :
                        $font_family = esc_attr($font['name']);
                        $font_weight = esc_attr($font['weight']);
                        $font_url = esc_url($font['url']);
                ?>
                        <tr class="cad-font-row">
                            <td>
                                <input type="text" 
                                       name="cad_custom_fonts[<?php echo $index; ?>][name]" 
                                       value="<?php echo $font_family; ?>" 
                                       class="cad-font-name-input widefat" 
                                       placeholder="Font Name" 
                                       required>
                            </td>
                            <td>
                                <style>
                                    @font-face {
                                        font-family: '<?php echo $font_family; ?>';
                                        src: url('<?php echo $font_url; ?>');
                                        font-weight: <?php echo $font_weight; ?>;
                                        font-display: swap;
                                    }
                                </style>
                                <span class="cad-font-preview"
                                    style="
                                        font-family:'<?php echo $font_family; ?>';
                                        font-size:18px;
                                        font-weight:<?php echo $font_weight; ?>;
                                    ">
                                    Abc 123
                                </span>
                            </td>
                            <td>
                                <select name="cad_custom_fonts[<?php echo $index; ?>][weight]" class="widefat cad-weight-select">
                                    <option value="normal" <?php selected($font_weight, 'normal'); ?>>Normal</option>
                                    <option value="bold" <?php selected($font_weight, 'bold'); ?>>Bold</option>
                                    <option value="300" <?php selected($font_weight, '300'); ?>>Light</option>
                                    <option value="900" <?php selected($font_weight, '900'); ?>>Black</option>
                                </select>
                            </td>
                            <td>
                                <div style="display: flex; gap: 5px;">
                                    <input type="text" 
                                           name="cad_custom_fonts[<?php echo $index; ?>][url]" 
                                           id="cad_font_url_<?php echo $index; ?>" 
                                           value="<?php echo $font_url; ?>" 
                                           class="cad-font-url-input widefat"
                                           required>
                                    <button type="button" class="cad-upload-font-btn button" data-target="#cad_font_url_<?php echo $index; ?>">Upload</button>
                                </div>
                            </td>
                            <td>
                                <button type="button" class="button button-link-delete cad-remove-row"><span class="dashicons dashicons-trash"></span></button>
                            </td>
                        </tr>
                    <?php endforeach;
                endif; ?>
            </tbody>
        </table>

        <div style="margin-top: 10px; margin-bottom: 20px;">
            <button type="button" class="button button-secondary" id="cad-add-font-row">+ Add New Font</button>
        </div>

        <button type="submit" class="button button-primary">Save Fonts</button>
    </form>

    <script>
        jQuery(document).ready(function($) {

            // Helper to get next index
            function getNextIndex() {
                var maxIndex = -1;
                $('#cad-font-tbody tr').each(function() {
                    var inputs = $(this).find('input, select');
                    inputs.each(function() {
                        var name = $(this).attr('name');
                        if (name) {
                            var match = name.match(/\[(\d+)\]/);
                            if (match && parseInt(match[1]) > maxIndex) {
                                maxIndex = parseInt(match[1]);
                            }
                        }
                    });
                });
                return maxIndex + 1;
            }

            // ADD ROW
            $('#cad-add-font-row').on('click', function() {
                var index = getNextIndex();

                var rowHtml = `
                <tr class="cad-font-row">
                    <td>
                        <input type="text" name="cad_custom_fonts[${index}][name]" class="cad-font-name-input widefat" placeholder="Font Name" required>
                    </td>
                    <td>
                        <span class="cad-font-preview" style="font-size:18px;">Abc 123</span>
                    </td>
                    <td>
                        <select name="cad_custom_fonts[${index}][weight]" class="widefat cad-weight-select">
                            <option value="normal">Normal</option>
                            <option value="bold">Bold</option>
                            <option value="300">Light</option>
                            <option value="900">Black</option>
                        </select>
                    </td>
                    <td>
                        <div style="display:flex; gap:5px;">
                            <input type="text" name="cad_custom_fonts[${index}][url]" id="cad_font_url_${index}" class="cad-font-url-input widefat" required>
                            <button type="button" class="cad-upload-font-btn button" data-target="#cad_font_url_${index}">Upload</button>
                        </div>
                    </td>
                    <td>
                        <button type="button" class="button button-link-delete cad-remove-row">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </td>
                </tr>
            `;

                $('#cad-font-tbody').append(rowHtml);
            });

            // REMOVE ROW
            $(document).on('click', '.cad-remove-row', function(e) {
                e.preventDefault();
                $(this).closest('tr').remove();
            });

            // UPLOAD BUTTON
            var frame;
            $(document).on('click', '.cad-upload-font-btn', function(e) {
                e.preventDefault();
                var targetInput = $(this).data('target');
                
                if (frame) {
                    frame.open();
                    return;
                }
                
                frame = wp.media({
                    title: 'Select Font',
                    button: {
                        text: 'Use Font'
                    },
                    multiple: false,
                    library: {
                        type: ['application/x-font-ttf', 'application/x-font-woff', 'application/font-woff', 'application/font-woff2', 'application/x-font-opentype']
                    }
                });
                
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $(targetInput).val(attachment.url).trigger('change');
                });
                
                frame.open();
            });

            // LIVE PREVIEW UPDATE (NAME + URL + WEIGHT)
            $(document).on('input change', '.cad-font-name-input, .cad-weight-select, .cad-font-url-input', function() {
                var row = $(this).closest('tr');
                var name = row.find('.cad-font-name-input').val();
                var url = row.find('.cad-font-url-input').val();
                var weight = row.find('.cad-weight-select').val();

                if (!name || !url) return;

                // Remove old style
                row.find('style.cad-preview-style').remove();

                // Inject new font-face
                var style = `
        <style class="cad-preview-style">
            @font-face {
                font-family: '${name}';
                src: url('${url}');
                font-weight: ${weight};
                font-display: swap;
            }
        </style>
    `;

                row.find('td').first().append(style);

                // Apply to preview
                row.find('.cad-font-preview').css({
                    'font-family': name,
                    'font-weight': weight
                });
            });

        });
    </script>
<?php
}
add_action('cad_after_plugin_settings', 'cad_custom_fonts_section_html');


// 4. GENERATE CSS (FRONTEND + ADMIN + BLOCK EDITOR)
function cad_generate_font_css()
{
    $fonts = get_option('cad_custom_fonts', array());
    if (empty($fonts) || !is_array($fonts)) return;

    $css = "";
    foreach ($fonts as $font) {
        if (!empty($font['name']) && !empty($font['url'])) {
            $name = esc_attr($font['name']);
            $url = esc_url($font['url']);
            $weight = esc_attr($font['weight']);
            
            $css .= "@font-face {\n";
            $css .= "    font-family: '{$name}';\n";
            $css .= "    src: url('{$url}');\n";
            $css .= "    font-weight: {$weight};\n";
            $css .= "    font-display: swap;\n";
            $css .= "}\n";
            $css .= ".font-" . sanitize_title($name) . " { font-family: '{$name}', sans-serif; }\n\n";
        }
    }

    if (!empty($css)) {
        echo '<style type="text/css" id="cad-custom-fonts-css">' . $css . '</style>';
    }
}
add_action('wp_head', 'cad_generate_font_css', 5);
add_action('admin_head', 'cad_generate_font_css', 5);
add_action('login_head', 'cad_generate_font_css', 5);


// 5. ADD TO GUTENBERG (BLOCK EDITOR) FONT SELECTOR
function cad_add_fonts_to_gutenberg($settings)
{
    $fonts = get_option('cad_custom_fonts', array());
    if (empty($fonts) || !is_array($fonts)) return $settings;

    $new_fonts = array();
    foreach ($fonts as $font) {
        if (!empty($font['name'])) {
            $new_fonts[] = array(
                'name'       => $font['name'],
                'slug'       => sanitize_title($font['name']),
                'fontFamily' => "'" . esc_attr($font['name']) . "', sans-serif",
            );
        }
    }

    if (empty($new_fonts)) return $settings;

    // Initialize fontFamilies if not exists
    if (!isset($settings['fontFamilies'])) {
        $settings['fontFamilies'] = array();
    }

    // Add custom fonts
    if (isset($settings['fontFamilies']['custom'])) {
        $settings['fontFamilies']['custom'] = array_merge($settings['fontFamilies']['custom'], $new_fonts);
    } else {
        $settings['fontFamilies']['custom'] = $new_fonts;
    }

    return $settings;
}
add_filter('block_editor_settings_all', 'cad_add_fonts_to_gutenberg', 10, 2);


// 6. ADD TO CLASSIC EDITOR (TINY MCE) FONT SELECTOR
function cad_add_fonts_to_classic_editor($init_array)
{
    $fonts = get_option('cad_custom_fonts', array());
    if (empty($fonts) || !is_array($fonts)) return $init_array;

    $font_formats = isset($init_array['font_formats']) 
        ? $init_array['font_formats'] 
        : 'Andale Mono=andale mono,times;Arial=arial,helvetica,sans-serif;Arial Black=arial black,avant garde;Book Antiqua=book antiqua,palatino;Comic Sans MS=comic sans ms,sans-serif;Courier New=courier new,courier;Georgia=georgia,palatino;Impact=impact,chicago;Symbol=symbol;Tahoma=tahoma,arial,helvetica,sans-serif;Terminal=terminal,monaco;Times New Roman=times new roman,times;Trebuchet MS=trebuchet ms,geneva;Verdana=verdana,geneva;Webdings=webdings;Wingdings=wingdings,zapf dingbats';

    foreach ($fonts as $font) {
        if (!empty($font['name'])) {
            $font_name = $font['name'];
            $font_formats .= ';' . $font_name . '=' . $font_name;
        }
    }

    $init_array['font_formats'] = $font_formats;
    return $init_array;
}
add_filter('tiny_mce_before_init', 'cad_add_fonts_to_classic_editor');


// ============================================================================
// 7. WORDPRESS CUSTOMIZER INTEGRATION
// ============================================================================

function cad_customize_register($wp_customize)
{
    // Add Custom Fonts Section
    $wp_customize->add_section('cad_custom_fonts_section', array(
        'title'       => 'Custom Fonts',
        'priority'    => 25,
        'description' => 'Select custom fonts for your website'
    ));

    // Get all custom fonts
    $fonts = get_option('cad_custom_fonts', array());
    if (empty($fonts) || !is_array($fonts)) {
        return; // No fonts added yet
    }

    // Create a setting and control for body font
    $wp_customize->add_setting('cad_body_font', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage'
    ));

    $wp_customize->add_control('cad_body_font', array(
        'label'    => 'Body Font',
        'section'  => 'cad_custom_fonts_section',
        'type'     => 'select',
        'choices'  => cad_get_fonts_choices()
    ));

    // Create a setting and control for heading font
    $wp_customize->add_setting('cad_heading_font', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage'
    ));

    $wp_customize->add_control('cad_heading_font', array(
        'label'    => 'Heading Font',
        'section'  => 'cad_custom_fonts_section',
        'type'     => 'select',
        'choices'  => cad_get_fonts_choices()
    ));

    // Heading font size
    $wp_customize->add_setting('cad_heading_font_size', array(
        'default'           => '32',
        'sanitize_callback' => 'absint',
        'transport'         => 'postMessage'
    ));

    $wp_customize->add_control('cad_heading_font_size', array(
        'label'   => 'Heading Font Size (px)',
        'section' => 'cad_custom_fonts_section',
        'type'    => 'number',
        'input_attrs' => array(
            'min'  => 10,
            'max'  => 100,
            'step' => 1
        )
    ));

    // Body font size
    $wp_customize->add_setting('cad_body_font_size', array(
        'default'           => '16',
        'sanitize_callback' => 'absint',
        'transport'         => 'postMessage'
    ));

    $wp_customize->add_control('cad_body_font_size', array(
        'label'   => 'Body Font Size (px)',
        'section' => 'cad_custom_fonts_section',
        'type'    => 'number',
        'input_attrs' => array(
            'min'  => 10,
            'max'  => 100,
            'step' => 1
        )
    ));
}
add_action('customize_register', 'cad_customize_register');

// Helper function to get fonts choices for customizer
function cad_get_fonts_choices()
{
    $fonts = get_option('cad_custom_fonts', array());
    $choices = array(
        '' => '- Select Font -'
    );

    if (!empty($fonts) && is_array($fonts)) {
        foreach ($fonts as $font) {
            if (!empty($font['name'])) {
                $choices[$font['name']] = $font['name'];
            }
        }
    }

    return $choices;
}

// Apply customizer font settings to frontend
function cad_customizer_frontend_css()
{
    $body_font = get_theme_mod('cad_body_font');
    $heading_font = get_theme_mod('cad_heading_font');
    $body_size = get_theme_mod('cad_body_font_size', '16');
    $heading_size = get_theme_mod('cad_heading_font_size', '32');

    $css = '';

    if (!empty($body_font)) {
        $css .= "body { font-family: '" . esc_attr($body_font) . "', sans-serif; font-size: " . absint($body_size) . "px; }\n";
    }

    if (!empty($heading_font)) {
        $css .= "h1, h2, h3, h4, h5, h6 { font-family: '" . esc_attr($heading_font) . "', sans-serif; font-size: " . absint($heading_size) . "px; }\n";
    }

    if (!empty($css)) {
        echo '<style type="text/css" id="cad-customizer-fonts-css">' . $css . '</style>';
    }
}
add_action('wp_head', 'cad_customizer_frontend_css', 10);


// ============================================================================
// 8. REST API - GET FONTS (FOR JAVASCRIPT/MODULES)
// ============================================================================

function cad_register_fonts_rest_endpoint()
{
    register_rest_route('cad/v1', '/fonts', array(
        'methods'             => 'GET',
        'callback'            => 'cad_get_fonts_rest',
        'permission_callback' => '__return_true'
    ));
}
add_action('rest_api_init', 'cad_register_fonts_rest_endpoint');

function cad_get_fonts_rest($request)
{
    $fonts = get_option('cad_custom_fonts', array());

    if (empty($fonts) || !is_array($fonts)) {
        return new WP_REST_Response(array(), 200);
    }

    return new WP_REST_Response($fonts, 200);
}


// ============================================================================
// 9. HELPER FUNCTION - GET FONTS AS ARRAY (FOR PHP TEMPLATES)
// ============================================================================

function cad_get_custom_fonts()
{
    $fonts = get_option('cad_custom_fonts', array());
    return is_array($fonts) ? $fonts : array();
}


// ============================================================================
// 10. HELPER FUNCTION - GET FONT BY NAME
// ============================================================================

function cad_get_font_by_name($font_name)
{
    $fonts = cad_get_custom_fonts();

    foreach ($fonts as $font) {
        if ($font['name'] === $font_name) {
            return $font;
        }
    }

    return null;
}


// ============================================================================
// 11. ELEMENTOR INTEGRATION (if using Elementor)
// ============================================================================

function cad_register_elementor_fonts($fonts)
{
    $custom_fonts = cad_get_custom_fonts();

    if (!empty($custom_fonts)) {
        foreach ($custom_fonts as $font) {
            $fonts[$font['name']] = 'custom';
        }
    }

    return $fonts;
}
add_filter('elementor/fonts/groups', 'cad_register_elementor_fonts');

function cad_register_elementor_fonts_list($fonts)
{
    $custom_fonts = cad_get_custom_fonts();

    if (!empty($custom_fonts)) {
        foreach ($custom_fonts as $font) {
            if (!isset($fonts[$font['name']])) {
                $fonts[$font['name']] = 'custom';
            }
        }
    }

    return $fonts;
}
add_filter('elementor/controls/font_families/groups', 'cad_register_elementor_fonts_list');


// ============================================================================
// 12. BEAVER BUILDER INTEGRATION (if using Beaver Builder)
// ============================================================================

function cad_register_beaver_fonts($fonts)
{
    $custom_fonts = cad_get_custom_fonts();

    if (!empty($custom_fonts)) {
        foreach ($custom_fonts as $font) {
            $fonts[$font['name']] = $font['name'];
        }
    }

    return $fonts;
}
add_filter('fl_builder_font_families', 'cad_register_beaver_fonts');


// ============================================================================
// 13. DIVI INTEGRATION (if using Divi)
// ============================================================================

function cad_register_divi_fonts($fonts)
{
    $custom_fonts = cad_get_custom_fonts();

    if (!empty($custom_fonts)) {
        foreach ($custom_fonts as $font) {
            $fonts[$font['name']] = $font['name'];
        }
    }

    return $fonts;
}
add_filter('et_builder_fonts', 'cad_register_divi_fonts');


// ============================================================================
// 14. HELPER FUNCTION - GET FONTS DROPDOWN HTML
// ============================================================================

function cad_fonts_dropdown($selected = '')
{
    $fonts = cad_get_custom_fonts();
    $html = '<select name="font" id="cad-font-select">';
    $html .= '<option value="">- Select Font -</option>';

    foreach ($fonts as $font) {
        $selected_attr = ($selected === $font['name']) ? 'selected' : '';
        $html .= '<option value="' . esc_attr($font['name']) . '" ' . $selected_attr . '>' . esc_html($font['name']) . '</option>';
    }

    $html .= '</select>';
    return $html;
}


// ============================================================================
// 15. SHORTCODE - USE CUSTOM FONT
// ============================================================================

function cad_custom_font_shortcode($atts)
{
    $atts = shortcode_atts(array(
        'font'    => '',
        'size'    => '16',
        'weight'  => 'normal',
        'color'   => 'inherit',
        'content' => ''
    ), $atts);

    if (empty($atts['font'])) {
        return '';
    }

    $style = "font-family: '" . esc_attr($atts['font']) . "', sans-serif; ";
    $style .= "font-size: " . absint($atts['size']) . "px; ";
    $style .= "font-weight: " . esc_attr($atts['weight']) . "; ";
    $style .= "color: " . esc_attr($atts['color']) . ";";

    return '<span style="' . $style . '">' . do_shortcode($atts['content']) . '</span>';
}
add_shortcode('cad_font', 'cad_custom_font_shortcode');


// ============================================================================
// 39.  BEAVER BUILDER POST GRID - DISPLAY ACF SHORT TITLE
// ============================================================================

// First, remove your old filters to avoid conflicts
remove_filter( 'the_title', 'mt_add_acf_short_title_after_title' );
remove_filter( 'fl_builder_post_grid_after_title', 'mt_bb_add_acf_short_title' );

// Solution 1: Replace post title with short title - ONLY IN BEAVER BUILDER GRID
add_filter( 'the_title', 'mt_bb_replace_title_with_short_title', 10, 2 );
function mt_bb_replace_title_with_short_title( $title, $post_id ) {
    global $post;
    
    // Only on frontend
    if ( is_admin() ) {
        return $title;
    }
    
    // Only for posts
    if ( get_post_type( $post_id ) !== 'post' ) {
        return $title;
    }
    
    // Check if we're in a post loop (not single post page)
    if ( is_singular( 'post' ) ) {
        return $title;
    }
    
    // Check ACF exists
    if ( ! function_exists( 'get_field' ) ) {
        return $title;
    }
    
    // Get the short title
    $short_title = get_field( 'short_title', $post_id );
    if ( empty( $short_title ) ) {
        return $title;
    }
    
    // Prevent infinite loop
    if ( strpos( $title, 'fl-post-short-title' ) !== false ) {
        return $title;
    }
    
    // OPTION A: REPLACE title completely with short title (only in grids)
    return '<span class="fl-post-short-title">' . esc_html( $short_title ) . '</span>';
    
    // OPTION B: APPEND short title after original title
    // return $title . ' <span class="fl-post-short-title">' . esc_html( $short_title ) . '</span>';
    
    // OPTION C: PREPEND short title before original title
    // return '<span class="fl-post-short-title">' . esc_html( $short_title ) . '</span> ' . $title;
}
