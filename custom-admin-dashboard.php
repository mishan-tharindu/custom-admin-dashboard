<?php

/**
 * Plugin Name: Custom Admin Dashboard
 * Description: A custom plugin to modify and clean up the WordPress admin dashboard. [wwmt_time_ago] or [wwmt_time_ago icon="clock"]
 * Version: 1.7.7
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
    $logout_url = wp_logout_url(admin_url());

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
    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
        array(),
        '6.5.1'
    );

    // Load custom admin style with cache busting
    $css_file = plugin_dir_path(__FILE__) . 'admin-style.css';
    $version = file_exists($css_file) ? filemtime($css_file) : '1.0.0';

    wp_enqueue_style(
        'custom-admin-style',
        plugins_url('admin-style.css', __FILE__),
        array(),
        $version
    );

    // Enqueue Media Uploader only on Profile pages
    if ('profile.php' === $hook || 'user-edit.php' === $hook) {
        wp_enqueue_media();

        $js_code = "
            jQuery(document).ready(function($) {
                var \$profileSection = $('.custom-profile-image-section');
                if (\$profileSection.length) {
                    $('#your-profile').prepend(\$profileSection);
                }

                var frame;
                $('#custom_avatar_button').on('click', function(e) {
                    e.preventDefault();
                    if (frame) {
                        frame.open();
                        return;
                    }
                    frame = wp.media({
                        title: '" . esc_js(__('Select Profile Image', 'custom-admin-dashboard')) . "',
                        button: { text: '" . esc_js(__('Use this image', 'custom-admin-dashboard')) . "' },
                        multiple: false
                    });
                    frame.on('select', function() {
                        var attachment = frame.state().get('selection').first().toJSON();
                        $('#custom_avatar_id').val(attachment.id);
                        $('#custom_avatar_preview').html('<img src=\"'+attachment.url+'\" style=\"width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 2px solid #ccc;\">');
                        $('#custom_avatar_remove').show();
                    });
                    frame.open();
                });
                
                $('#custom_avatar_remove').on('click', function() {
                    $('#custom_avatar_id').val('');
                    $('#custom_avatar_preview').empty();
                    $(this).hide();
                });
                
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
// 10. CUSTOM PLUGIN MENU
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

    add_submenu_page(
        'my-plugin-slug',
        'General Settings',
        'Settings',
        'manage_options',
        'my-plugin-slug',
        'my_plugin_settings_page'
    );
}

function my_plugin_settings_page()
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to access this page.', 'custom-admin-dashboard'));
    }
?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <p><?php esc_html_e('Welcome to your custom plugin settings page!', 'custom-admin-dashboard'); ?></p>
        <form method="post" action="options.php">
            <?php
            // Your settings fields go here
            ?>
        </form>
    </div>
<?php
}

// ============================================================================
// 12. POST IMAGE COUNT SHORTCODE
// ============================================================================
/**
 * Custom function to count the number of <img> tags in a post's content.
 * Usage: [post_image_count]
 *
 * @param array $atts Shortcode attributes (unused).
 * @return string The count of images in the post.
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
// 11. POST TIME ELAPSED FUNCTION (IMPROVED)
// ============================================================================
/**
 * Calculate and display time elapsed since post was published.
 */
function wwmt_time_ago()
{
    global $post;

    // Ensure we have a valid post
    if (!$post || !isset($post->ID)) {
        return '';
    }

    // Get the post publication time
    $post_date = get_post_time('U', false, $post->ID);

    if (!$post_date) {
        return '';
    }

    // Calculate time difference
    $time_diff = current_time('timestamp') - $post_date;

    // Return appropriate time format
    if ($time_diff < 60) {
        return esc_html(floor($time_diff) . ' sec');
    }
    if ($time_diff < 3600) {
        return esc_html(floor($time_diff / 60) . ' min');
    }
    if ($time_diff < 86400) {
        return esc_html(floor($time_diff / 3600) . ' hour');
    }
    if ($time_diff < 604800) {
        return esc_html(floor($time_diff / 86400) . ' day');
    }
    if ($time_diff < 2592000) {
        return esc_html(floor($time_diff / 604800) . ' week');
    }
    return esc_html(floor($time_diff / 2592000) . ' month');
}

// Shortcode usage: [wwmt_time_ago] or [wwmt_time_ago icon="clock"]
function wwmt_time_ago_shortcode($atts)
{
    $atts = shortcode_atts(array(
        'icon' => 'clock',  // clock, hourglass, calendar, history
        'text' => '',
    ), $atts, 'wwmt_time_ago');

    $time_ago = wwmt_time_ago();

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
// 13. BEAVER BUILDER META INJECTION - DIRECT HTML APPROACH
// ============================================================================
/**
 * Inject time elapsed into Beaver Builder post grids using JavaScript.
 * This adds the time to the meta section directly in the HTML.
 */
function inject_time_elapsed_bb_javascript()
{
    global $post;

    if (!$post || !isset($post->ID)) {
        return;
    }

    // Get time ago string
    $time_ago = wwmt_time_ago();

    if (empty($time_ago)) {
        return;
    }

    // Only run on pages with Beaver Builder modules
    if (!class_exists('FLBuilder')) {
        return;
    }

    echo '<script type="text/javascript">
    (function($) {
        $(document).ready(function() {
            // Target all Beaver Builder post grid meta sections
            $(".fl-post-grid-meta").each(function() {
                var $meta = $(this);
                
                // Check if time already added
                if ($meta.find(".custom-time-ago").length > 0) {
                    return;
                }
                
                // Add the time elapsed HTML
                var timeHtml = \'<span class="custom-time-ago-wrap"><span class="custom-time-ago"><i class="far fa-clock"></i> \' + "' . esc_js($time_ago) . '" + \'</span></span>\';
                
                // Append to meta
                $meta.append(timeHtml);
            });
        });
    })(jQuery);
    </script>';
}
add_action('wp_footer', 'inject_time_elapsed_bb_javascript', 999);


// ============================================================================
// ALTERNATIVE: Hook into Beaver Builder Template System
// ============================================================================
/**
 * More direct approach - inject into Beaver Builder post grid template.
 * This modifies the actual template output.
 */
function cda_add_time_ago_to_bb_templates()
{
    global $post;

    if (!$post || !isset($post->ID)) {
        return;
    }

    $time_ago = wwmt_time_ago();

    if (empty($time_ago)) {
        return;
    }

    // Hook into Beaver Builder module output
    ob_start();
    $time_html = '<span class="custom-time-ago-wrap"><span class="custom-time-ago">' . $time_ago . '</span></span>';
    echo $time_html;
}

// Filter for post grid meta output
add_filter('fl_builder_post_grid_meta', function ($meta) {
    // Make sure $meta is a string
    if (!is_string($meta)) {
        return $meta;
    }

    global $post;

    if (!$post || !isset($post->ID)) {
        return $meta;
    }

    $time_ago = wwmt_calculate_time_ago($post->ID);

    if (!empty($time_ago)) {
        $meta .= ' | <span class="custom-time-ago"><i class="far fa-clock"></i> ' . esc_html($time_ago) . ' ago</span>';
    }

    return $meta;
}, 999);


// ============================================================================
// 14. CSS STYLING FOR TIME ELAPSED
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
// 15. Post Table Column Styling
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
// 1. CREATE CUSTOM LOGIN & REGISTRATION PAGES
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
// CUSTOM 404 PAGE CONTENT
// ============================================================================

function custom_get_404_page_content()
{
    return '<!-- wp:heading -->
<h1>Page Not Found</h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Sorry, the page you are looking for could not be found. It may have been moved or deleted.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><a href="' . esc_url(home_url()) . '" class="button button-primary">Back to Home</a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><strong>What happened?</strong></p>
<!-- /wp:paragraph -->

<!-- wp:list -->
<ul>
<li>The page might have been deleted</li>
<li>The URL might be incorrect</li>
<li>You might not have permission to view this page</li>
</ul>
<!-- /wp:list -->

<!-- wp:paragraph -->
<p>Please check the URL and try again, or use the navigation menu to find what you are looking for.</p>
<!-- /wp:paragraph -->';
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
            
            if ($page_slug === $not_found_page->post_name || 
                $page_slug === sanitize_title('Page Not Found')) {
                return array($not_found_page);
            }
        }
    }
    
    return $posts;
}
add_filter('the_posts', 'prevent_404_page_from_404', 10, 2);



// ============================================================================
// HELPER FUNCTION: Check if auth pages exist and redirect if missing
// ============================================================================

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
// 2. CUSTOM LOGIN FORM SHORTCODE (FIXED)
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
                        value="<?php echo isset($_POST['username']) ? esc_attr($_POST['username']) : ''; ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="password"><?php esc_html_e('Password', 'custom-user-auth'); ?></label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Enter your password"
                        required
                    >
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
// 3. CUSTOM SIGNUP FORM SHORTCODE (FIXED)
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
                            value="<?php echo isset($_POST['first_name']) ? esc_attr($_POST['first_name']) : ''; ?>"
                        >
                    </div>

                    <div class="form-group form-group-half">
                        <label for="last_name"><?php esc_html_e('Last Name', 'custom-user-auth'); ?></label>
                        <input
                            type="text"
                            id="last_name"
                            name="last_name"
                            class="form-control"
                            placeholder="Last Name"
                            value="<?php echo isset($_POST['last_name']) ? esc_attr($_POST['last_name']) : ''; ?>"
                        >
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
                        value="<?php echo isset($_POST['username']) ? esc_attr($_POST['username']) : ''; ?>"
                    >
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
                        value="<?php echo isset($_POST['email']) ? esc_attr($_POST['email']) : ''; ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="password"><?php esc_html_e('Password', 'custom-user-auth'); ?></label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="At least 8 characters"
                        required
                    >
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
                        required
                    >
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
// 4. ENQUEUE STYLES AND SCRIPTS
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
// 5. REDIRECT WP-LOGIN TO CUSTOM LOGIN PAGE
// ============================================================================

function custom_login_page_redirect()
{
    if (strpos($_SERVER['REQUEST_URI'], '/wp-login.php') !== false && !is_admin()) {
        $login_page = get_page_by_title('User Login');
        if ($login_page) {
            wp_redirect(get_page_link($login_page->ID));
            exit;
        }
    }
}
add_action('init', 'custom_login_page_redirect');

// ============================================================================
// 6. CUSTOM LOGOUT REDIRECT
// ============================================================================

function custom_logout_redirect($redirect_to, $requested_redirect_to, $user)
{
    $home = home_url();
    return $home;
}
add_filter('logout_redirect', 'custom_logout_redirect', 10, 3);

// ============================================================================
// 7. RESTRICT DIRECT ACCESS TO WP-LOGIN
// ============================================================================

function restrict_wp_login()
{
    if (strpos($_SERVER['REQUEST_URI'], '/wp-login.php') !== false && !is_admin() && !defined('DOING_CRON')) {
        $login_page = get_page_by_title('User Login');
        if ($login_page) {
            wp_redirect(get_page_link($login_page->ID));
            exit;
        }
    }
}
add_action('init', 'restrict_wp_login', 1);

// ============================================================================
// 8. ADD USER ROLE SETTINGS
// ============================================================================

function custom_auth_default_user_role()
{
    return 'subscriber';
}
add_filter('default_user_role', 'custom_auth_default_user_role');

// ============================================================================
// 9. CUSTOM PROFILE REDIRECT
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
// 14. HIDE ADMIN BAR FOR NON-ADMINISTRATORS
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