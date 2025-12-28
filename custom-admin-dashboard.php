<?php
/**
 * Plugin Name: Custom Admin Dashboard
 * Description: A custom plugin to modify and clean up the WordPress admin dashboard. [wwmt_time_ago] or [wwmt_time_ago icon="clock"]
 * Version: 1.7.6
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
function custom_remove_dashboard_widgets() {
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
function custom_add_dashboard_widget() {
    wp_add_dashboard_widget(
        'custom_welcome_widget',
        'Welcome to Your Dashboard!',
        'custom_dashboard_widget_content'
    );
}
add_action('wp_dashboard_setup', 'custom_add_dashboard_widget');

function custom_dashboard_widget_content() {
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
function custom_admin_sidebar_profile() {
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
function custom_restrict_admin_menus() {
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
function custom_user_profile_fields($user) {
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

function save_custom_user_profile_fields($user_id) {
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
function custom_add_leaderboard_widget() {
    wp_add_dashboard_widget(
        'custom_writer_leaderboard',
        'Writer Leaderboard',
        'custom_writer_leaderboard_content'
    );
}
add_action('wp_dashboard_setup', 'custom_add_leaderboard_widget');

function custom_writer_leaderboard_content() {
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
function custom_add_stats_widget() {
    wp_add_dashboard_widget(
        'custom_website_stats',
        'Website Statistics',
        'custom_website_stats_content'
    );
}
add_action('wp_dashboard_setup', 'custom_add_stats_widget');

function custom_website_stats_content() {
    echo '<h3>' . esc_html__('Total Page Views: 2.5M', 'custom-admin-dashboard') . '</h3>';
    echo '<canvas id="pageViewsChart" style="max-height: 200px;"></canvas>';
}

// ============================================================================
// 8. ENQUEUE STYLES AND SCRIPTS
// ============================================================================
function custom_admin_styles($hook) {
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
function custom_enqueue_chart_scripts($hook) {
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

function my_custom_plugin_menu() {
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

function my_plugin_settings_page() {
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
function custom_post_image_count_shortcode($atts) {
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
function wwmt_time_ago() {
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
function wwmt_time_ago_shortcode($atts) {
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
function inject_time_elapsed_bb_javascript() {
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
function cda_add_time_ago_to_bb_templates() {
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
add_filter('fl_builder_post_grid_meta', function($meta) {
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
function custom_time_elapsed_styles() {
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
add_filter( 'manage_posts_columns', 'add_post_status_column' );
function add_post_status_column( $columns ) {
    $columns['post_status_custom'] = 'Post Status';
    return $columns;
}

// Step 2: Make the column sortable (optional)
add_filter( 'manage_edit-post_sortable_columns', 'make_post_status_sortable' );
function make_post_status_sortable( $columns ) {
    $columns['post_status_custom'] = 'post_status';
    return $columns;
}

// Step 3: Display content in the column with colored buttons
add_action( 'manage_posts_custom_column', 'display_post_status_column', 10, 2 );
function display_post_status_column( $column, $post_id ) {
    if ( $column === 'post_status_custom' ) {
        $post = get_post( $post_id );
        $post_url = get_edit_post_link( $post_id );
        $status = $post->post_status;

        // Determine button color and text based on post status
        if ( $status === 'publish' ) {
            $button_class = 'status-approved';
            $button_text = 'Approved';
            $bg_color = '#4CAF50'; // Green
        } elseif ( $status === 'draft' ) {
            $button_class = 'status-review';
            $button_text = 'Under Review';
            $bg_color = '#FFC107'; // Yellow
        } else {
            $button_class = 'status-other';
            $button_text = ucfirst( $status );
            $bg_color = '#9E9E9E'; // Gray
        }

        // Output the button
        echo sprintf(
            '<a href="%s" class="button %s" style="background-color: %s; color: %s; padding: 5px 10px; border-radius: 3px; text-decoration: none; display: inline-block; border: none;">%s</a>',
            esc_url( $post_url ),
            esc_attr( $button_class ),
            esc_attr( $bg_color ),
            $status === 'draft' ? '#000' : '#fff',
            esc_html( $button_text )
        );
    }
}

// Step 4: Add custom CSS for better styling
add_action( 'admin_head', 'add_post_status_column_styles' );
function add_post_status_column_styles() {
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