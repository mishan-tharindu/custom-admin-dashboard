<?php

/**
 * Plugin Name: Custom Admin Dashboard
 * Description: A custom plugin to modify and clean up the WordPress admin dashboard.
 * Version: 1.3.0
 * Author: TechM
 * Author URI: https://yourwebsite.com
 */

// Exit if accessed directly (security measure)
if (! defined('ABSPATH')) {
    exit;
}

// All subsequent code will go below this line
function custom_remove_dashboard_widgets()
{
    // Left side widgets
    // remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' );      // Activity
    remove_meta_box('dashboard_site_health', 'dashboard', 'normal');   // Site Health Status
    remove_meta_box('dashboard_quick_press', 'dashboard', 'side');     // Quick Draft
    remove_meta_box('dashboard_primary', 'dashboard', 'side');         // WordPress Events and News (Planet WordPress)

    // Right side widgets
    remove_meta_box('dashboard_right_now', 'dashboard', 'normal');     // At a Glance (Removed in favour of 'Activity' in newer WP versions, but still good to include)
    // remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' ); // Recent Comments
    remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');   // Recent Drafts
    remove_meta_box('dashboard_secondary', 'dashboard', 'side');       // Other incoming links (deprecated)
}

add_action('wp_dashboard_setup', 'custom_remove_dashboard_widgets');

/**
 * 3. INJECT USER PROFILE AT TOP OF SIDEBAR
 */
function custom_admin_sidebar_profile()
{
    $current_user = wp_get_current_user();
    // FIX: Check for custom avatar meta first
    $custom_avatar_id = get_user_meta($current_user->ID, 'custom_avatar', true);
    if ($custom_avatar_id) {
        $avatar = wp_get_attachment_url($custom_avatar_id);
    } else {
        $avatar = get_avatar_url($current_user->ID, array('size' => 128));
    }
    $profile_url = admin_url('profile.php');
    $logout_url = wp_logout_url(admin_url());
?>
    <script type="text/javascript">
        (function($) {
            $(function() {
                var profileHtml = `
                    <div class="custom-sidebar-profile">
                        <a href="<?php echo $profile_url; ?>" class="profile-link">
                            <div class="profile-avatar">
                                <img src="<?php echo $avatar; ?>" alt="User Avatar">
                            </div>
                            <div class="profile-info">
                                <span class="profile-greeting">Welcome back</span>
                                <span class="profile-name"><?php echo esc_html($current_user->display_name); ?></span>
                            </div>
                        </a>
                    </div>
                `;
                $('#adminmenuwrap').prepend(profileHtml);

                // Append Sign Out Button at the bottom
                var logoutHtml = `
                    <div class="custom-sidebar-logout-wrap">
                        <a href="<?php echo $logout_url; ?>" class="custom-logout-btn">
                            <i class="fa-right-from-bracket fa-solid"></i>
                            <span class="logout-text">Sign Out</span>
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

/**
 * 4. RESTRICT MENU ITEMS FOR NON-ADMINS
 */
function custom_restrict_admin_menus()
{
    // Check if the current user is NOT an administrator
    if (! current_user_can('administrator')) {
        remove_menu_page('edit.php?post_type=page');          // Pages
        remove_menu_page('themes.php');                       // Appearance
        remove_menu_page('edit.php?post_type=fl-theme-layout'); // Beaver Builder
        remove_menu_page('plugins.php');                      // Plugins
        remove_menu_page('users.php');                        // Users
        remove_menu_page('tools.php');                        // Tools
    }
}
// Priority 999 ensures this runs after plugins have registered their menus
add_action('admin_menu', 'custom_restrict_admin_menus', 999);

/**
 * 5. CUSTOM PROFILE IMAGE UI
 */
function custom_user_profile_fields($user) {
    $custom_avatar_id = get_user_meta($user->ID, 'custom_avatar', true);
    $custom_avatar_url = $custom_avatar_id ? wp_get_attachment_url($custom_avatar_id) : '';
    ?>
    <div class="custom-profile-image-section">
        <h3>Profile Image</h3>
        <table class="form-table">
            <tr>
                <th><label for="custom_avatar">Custom Photo</label></th>
                <td>
                    <div id="custom_avatar_preview" style="margin-bottom: 10px;">
                        <?php if ($custom_avatar_url) : ?>
                            <img src="<?php echo esc_url($custom_avatar_url); ?>" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 2px solid #ccc;">
                        <?php endif; ?>
                    </div>
                    <input type="hidden" name="custom_avatar" id="custom_avatar_id" value="<?php echo esc_attr($custom_avatar_id); ?>">
                    <button type="button" class="button button-primary" id="custom_avatar_button">Select Profile Image</button>
                    <button type="button" class="button" id="custom_avatar_remove" style="<?php echo $custom_avatar_id ? '' : 'display:none;'; ?>">Remove</button>
                    <p class="description">Upload a custom image to replace your Gravatar.</p>
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
    if (!current_user_can('edit_user', $user_id)) return false;
    if (isset($_POST['custom_avatar'])) {
        update_user_meta($user_id, 'custom_avatar', sanitize_text_field($_POST['custom_avatar']));
    }
}
add_action('personal_options_update', 'save_custom_user_profile_fields');
add_action('edit_user_profile_update', 'save_custom_user_profile_fields');

/**
 * Add a custom welcome widget to the dashboard
 */
function custom_add_dashboard_widget()
{
    wp_add_dashboard_widget(
        'custom_welcome_widget',                // Widget slug
        'Welcome to Your Dashboard!',           // Widget title
        'custom_dashboard_widget_content'       // Display function
    );
}
add_action('wp_dashboard_setup', 'custom_add_dashboard_widget');

/**
 * Custom widget content function
 */
function custom_dashboard_widget_content()
{
    echo '<h3>Need help?</h3>';
    echo '<p>If you have any questions about managing your website, please contact the support team:</p>';

    echo '<ul>';
    echo '<li><strong>Email:</strong> <a href="mailto:support@yourcompany.com">support@yourcompany.com</a></li>';
    echo '<li><strong>Phone:</strong> 555-123-4567</li>';
    echo '</ul>';

    echo '<p>For quick access to the Pages section, click the button below:</p>';
    echo '<p><a href="' . esc_url(admin_url('edit.php?post_type=page')) . '" class="button button-primary">Go to Pages</a></p>';
}

/**
 * Enqueue custom admin styles
 */
function custom_admin_styles($hook)
{
    // Only load the CSS on the main dashboard page
    // if ( 'index.php' !== $GLOBALS['pagenow'] ) {
    //     return;
    // }

    // 1. Enqueue Font Awesome from a CDN
    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
        array(),
        '6.5.1'
    );

    // 2. Load custom admin style with CACHE BUSTING
    // We use filemtime to automatically change the version number whenever the file is saved
    $css_file = plugin_dir_path(__FILE__) . 'admin-style.css';
    $version = file_exists($css_file) ? filemtime($css_file) : '1.0.0';

    wp_enqueue_style(
        'custom-admin-style',
        plugins_url('admin-style.css', __FILE__),
        array(),
        $version // This is the magic part!
    );

    // 3. Enqueue Media Uploader only on Profile pages
    if ('profile.php' === $hook || 'user-edit.php' === $hook) {
        wp_enqueue_media();

        $js_code = "
            jQuery(document).ready(function($) {
                // Move our custom profile section to the top of the profile form
                var \$profileSection = $('.custom-profile-image-section');
                if (\$profileSection.length) {
                    $('#your-profile').prepend(\$profileSection);
                }

                var frame;
                $('#custom_avatar_button').on('click', function(e) {
                    e.preventDefault();
                    if (frame) { frame.open(); return; }
                    frame = wp.media({
                        title: 'Select Profile Image',
                        button: { text: 'Use this image' },
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
                // Hide default Gravatar section
                $('.user-profile-picture').closest('tr').hide();
            });
        ";
        wp_add_inline_script('jquery', $js_code);
    }
}
add_action('admin_enqueue_scripts', 'custom_admin_styles');

/**
 * Add user avatar HTML to the top admin bar menu
 */
// function custom_admin_bar_avatar( $wp_admin_bar ) {
//     $current_user = wp_get_current_user();
    
//     // Get the avatar HTML (which includes the img tag)
//     $avatar = get_avatar( $current_user->ID, 32 ); 
    
//     // Check if user is logged in and not a guest
//     if ( is_user_logged_in() && $current_user->ID > 0 ) {
//         $wp_admin_bar->add_menu( array(
//             'id'    => 'custom-user-avatar',
//             'title' => '<span class="ab-item-avatar">' . $avatar . '</span>', // Add custom HTML wrapper
//             'href'  => admin_url( 'profile.php' ),
//             'meta'  => array(
//                 'title' => $current_user->display_name,
//             ),
//         ) );
//     }
// }
// add_action( 'admin_bar_menu', 'custom_admin_bar_avatar', 11 ); // Priority 11 puts it after the standard WP logo


/**
 * Add the Writer Leaderboard Dashboard Widget
 */
function custom_add_leaderboard_widget()
{
    wp_add_dashboard_widget(
        'custom_writer_leaderboard',        // Widget slug
        'Writer Leaderboard',               // Widget title
        'custom_writer_leaderboard_content' // Display function
    );
}

add_action('wp_dashboard_setup', 'custom_add_leaderboard_widget');

/**
 * Content for the Leaderboard Widget - STICKER VERSION
 * Displays all authors with a sticker next to the winner.
 */
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

    usort($leaderboard, function ($a, $b) { return $b['count'] <=> $a['count']; });

    echo '<h3>All Authors</h3>';
    echo '<ul class="writer-leaderboard-list">';

    if (empty($leaderboard)) {
        echo '<p>No authors found with published posts.</p>';
    } else {
        $rank = 0;
        foreach ($leaderboard as $writer) {
            $rank++;
            
            // Check for custom avatar meta for the leaderboard as well
            $custom_avatar_id = get_user_meta($writer['id'], 'custom_avatar', true);
            if ($custom_avatar_id) {
                $avatar_url = wp_get_attachment_url($custom_avatar_id);
                $avatar = '<img src="' . esc_url($avatar_url) . '" width="48" height="48" style="border-radius:50%; object-fit:cover;">';
            } else {
                $avatar = get_avatar($writer['id'], 48);
            }

            $is_winner = ($rank === 1);
            echo '<li class="leaderboard-item" style="display:flex; align-items:center; margin-bottom:10px; padding:10px; border-bottom:1px solid #eee;">';
            echo '<div class="leaderboard-avatar" style="margin-right:15px;">' . $avatar . '</div>';
            echo '<div class="leaderboard-info" style="flex-grow:1;">';
            echo '<strong>' . esc_html($writer['name']) . '</strong>';
            echo '<br><span class="post-count" style="color:#666; font-size:12px;">' . number_format_i18n($writer['count']) . ' Posts</span>';
            echo '</div>';
            if ($is_winner) { echo '<div class="win-sticker" style="font-size:20px;">🏆</div>'; }
            echo '</li>';
        }
    }
    echo '</ul>';
}

/**
 * Enqueue Chart.js library and custom script for the Dashboard
 */
function custom_enqueue_chart_scripts($hook)
{
    // Only load the script on the main dashboard page
    if ('index.php' !== $hook) {
        return;
    }

    // 1. Enqueue Chart.js from a CDN (or host locally)
    wp_enqueue_script(
        'chart-js',
        'https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js',
        array(),
        '4.4.2',
        true
    );

    // 2. Enqueue Custom Dashboard Script
    wp_enqueue_script(
        'custom-chart-script',
        plugins_url('dashboard-chart.js', __FILE__),
        array('jquery', 'chart-js'), // Depends on jQuery and Chart.js
        filemtime(plugin_dir_path(__FILE__) . 'dashboard-chart.js'), // Version based on file modification time
        true
    );

    // 3. Prepare and Localize (pass) Data to the JavaScript
    // Note: You would replace this static data with actual database queries (e.g., post counts by date)
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


/**
 * Add the Website Statistics Widget
 */
function custom_add_stats_widget()
{
    wp_add_dashboard_widget(
        'custom_website_stats',             // Widget slug
        'Website Statistics',               // Widget title
        'custom_website_stats_content'      // Display function
    );
}
add_action('wp_dashboard_setup', 'custom_add_stats_widget');

/**
 * Content for the Stats Widget
 */
function custom_website_stats_content()
{
    echo '<h3>Total Page Views: 2.5M</h3>'; // Static data as example
    echo '<canvas id="pageViewsChart" style="max-height: 200px;"></canvas>';
}

/**
 * Hook into 'admin_menu' to add our custom item
 */
add_action('admin_menu', 'my_custom_plugin_menu');

function my_custom_plugin_menu()
{
    // 1. Add Top-Level Menu
    add_menu_page(
        'My Plugin Settings',      // Page title (in browser tab)
        'Custom Plugin',           // Menu title (in sidebar)
        'manage_options',          // Capability (who can see it)
        'my-plugin-slug',          // Menu slug (unique ID)
        'my_plugin_settings_page', // Callback function to show content
        'dashicons-admin-generic', // Icon (Dashicons)
        6                          // Position (6 = below Dashboard)
    );

    // 2. Add a Submenu (Optional)
    add_submenu_page(
        'my-plugin-slug',          // Parent slug
        'General Settings',        // Page title
        'Settings',                // Submenu title
        'manage_options',          // Capability
        'my-plugin-slug',          // Submenu slug (same as parent makes it the first item)
        'my_plugin_settings_page'  // Callback function
    );
}

/**
 * Display the content for the custom menu page
 */
function my_plugin_settings_page()
{
?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <p>Welcome to your custom plugin settings page!</p>
        <form method="post" action="options.php">
            <?php
            // Your settings fields go here
            ?>
        </form>
    </div>
<?php
}

/**
 * Change Default Menu Icons to Font Awesome Icons
 */

// function custom_change_admin_menu_icons() {
//     global $menu;
    
//     // Icon mappings (Menu Slug => Font Awesome Class)
//     $icon_map = array(
//         'index.php'                         => 'fa-gauge-high',        // Dashboard
//         'edit.php'                          => 'fa-file-lines',        // Posts
//         'upload.php'                        => 'fa-images',            // Media
//         'edit.php?post_type=page'           => 'fa-layer-group',       // Pages
//         'edit-comments.php'                 => 'fa-comments',          // Comments
//         'themes.php'                        => 'fa-palette',           // Appearance
//         'plugins.php'                       => 'fa-plug',              // Plugins
//         'users.php'                         => 'fa-users',             // Users
//         'tools.php'                         => 'fa-screwdriver-wrench',// Tools
//         'options-general.php'               => 'fa-gear',              // Settings
//         'custom-analytics-page'             => 'fa-chart-column',      // Your new Analytics page
//     );

//     foreach ( $menu as $key => $value ) {
//         $menu_slug = $value[2];
        
//         if ( isset( $icon_map[ $menu_slug ] ) ) {
//             // Replace the icon HTML (index 6) with the new Font Awesome class
//             $menu[ $key ][6] = 'fa ' . $icon_map[ $menu_slug ];
//         }
//     }
// }
// add_action( 'admin_menu', 'custom_change_admin_menu_icons' );