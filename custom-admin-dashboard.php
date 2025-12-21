<?php
/**
 * Plugin Name: Custom Admin Dashboard
 * Description: A custom plugin to modify and clean up the WordPress admin dashboard.
 * Version: 1.6.1
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
// 11. POST TIME ELAPSED SHORTCODE
// ============================================================================
/**
 * Shortcode to display the time elapsed since the post was published.
 * Usage: [wwmt_time_ago]
 *
 * @return string Time elapsed in a human-readable format.
 */
function wwmt_time_ago() {
    $time_diff = current_time('timestamp') - get_the_time('U');

    if ($time_diff < 60) {
        return esc_html(floor($time_diff) . ' sec');
    }

    if ($time_diff < 3600) {
        return esc_html(floor($time_diff / 60) . ' min');
    }

    if ($time_diff < 86400) {
        return esc_html(floor($time_diff / 3600) . ' hours');
    }

    return esc_html(floor($time_diff / 86400) . ' d');
}
add_shortcode('wwmt_time_ago', 'wwmt_time_ago');

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