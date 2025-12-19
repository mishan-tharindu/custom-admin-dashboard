<?php
/**
 * Plugin Name: Custom Admin Dashboard
 * Description: A custom plugin to modify and clean up the WordPress admin dashboard.
 * Version: 1.0
 * Author: TechM
 * Author URI: https://yourwebsite.com
 */

// Exit if accessed directly (security measure)
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// All subsequent code will go below this line
function custom_remove_dashboard_widgets() { 
    // Left side widgets
    // remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' );      // Activity
    remove_meta_box( 'dashboard_site_health', 'dashboard', 'normal' );   // Site Health Status
    remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );     // Quick Draft
    remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );         // WordPress Events and News (Planet WordPress)
    
    // Right side widgets
    remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );     // At a Glance (Removed in favour of 'Activity' in newer WP versions, but still good to include)
    // remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' ); // Recent Comments
    remove_meta_box( 'dashboard_recent_drafts', 'dashboard', 'side' );   // Recent Drafts
    remove_meta_box( 'dashboard_secondary', 'dashboard', 'side' );       // Other incoming links (deprecated)
}

add_action( 'wp_dashboard_setup', 'custom_remove_dashboard_widgets' );

/**
 * Add a custom welcome widget to the dashboard
 */
function custom_add_dashboard_widget() {
    wp_add_dashboard_widget(
        'custom_welcome_widget',                // Widget slug
        'Welcome to Your Dashboard!',           // Widget title
        'custom_dashboard_widget_content'       // Display function
    );
}
add_action( 'wp_dashboard_setup', 'custom_add_dashboard_widget' );

/**
 * Custom widget content function
 */
function custom_dashboard_widget_content() {
    echo '<h3>Need help?</h3>';
    echo '<p>If you have any questions about managing your website, please contact the support team:</p>';
    
    echo '<ul>';
    echo '<li><strong>Email:</strong> <a href="mailto:support@yourcompany.com">support@yourcompany.com</a></li>';
    echo '<li><strong>Phone:</strong> 555-123-4567</li>';
    echo '</ul>';
    
    echo '<p>For quick access to the Pages section, click the button below:</p>';
    echo '<p><a href="' . esc_url( admin_url( 'edit.php?post_type=page' ) ) . '" class="button button-primary">Go to Pages</a></p>';
}

/**
 * Enqueue custom admin styles
 */
function custom_admin_styles() {
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

    wp_enqueue_style( 
        'custom-admin-style', 
        plugins_url( 'admin-style.css', __FILE__ ), 
        array(), 
        '1.0.0'
    );
}
add_action( 'admin_enqueue_scripts', 'custom_admin_styles' );

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
function custom_add_leaderboard_widget() {
    wp_add_dashboard_widget(
        'custom_writer_leaderboard',        // Widget slug
        'Writer Leaderboard',               // Widget title
        'custom_writer_leaderboard_content' // Display function
    );
}

add_action( 'wp_dashboard_setup', 'custom_add_leaderboard_widget' );

/**
 * Content for the Leaderboard Widget - STICKER VERSION
 * Displays all authors with a sticker next to the winner.
 */
function custom_writer_leaderboard_content() {
    // 1. Get all users who have published at least one post
    $all_users = get_users( array( 
        'who' => 'authors',
    ) );
    
    $leaderboard = array();

    // 2. Loop through users and count their published posts
    foreach ( $all_users as $user ) {
        $post_count = count_user_posts( $user->ID, 'post', true ); 
        
        // Only include users with at least 1 published post
        if ( $post_count > 0 ) {
            $leaderboard[] = array(
                'id' => $user->ID,
                'name' => $user->display_name,
                'count' => $post_count
            );
        }
    }
    
    // 3. Sort the array by post count (highest first)
    usort( $leaderboard, function( $a, $b ) {
        return $b['count'] <=> $a['count'];
    } );
    
    // 4. Display all writers
    echo '<h3>All Authors</h3>';
    echo '<ul class="writer-leaderboard-list">';
    
    if ( empty( $leaderboard ) ) {
         echo '<p>No authors found with published posts.</p>';
    } else {
        $rank = 0; // Initialize rank counter
        foreach ( $leaderboard as $writer ) {
            $rank++;
            $avatar = get_avatar( $writer['id'], 48 );
            
            // Check if this is the 1st place user (rank 1)
            $is_winner = ( $rank === 1 ) ? true : false;
            
            echo '<li class="leaderboard-item">';
            echo '<div class="leaderboard-avatar">' . $avatar . '</div>';
            echo '<div class="leaderboard-info">';
            
            // Display name and conditionally the sticker
            echo '<strong>' . esc_html( $writer['name'] ) . '</strong>';
                      
            echo '<span class="post-count">' . number_format_i18n( $writer['count'] ) . ' Posts</span>';
            echo '</div>';
            echo '<div class="post-winner-sticker">';
            // The WIN STICKER (Trophy Emoji)
            if ( $is_winner ) {
                echo '<span class="win-sticker"> 🏆</span>';
            }
            echo '</div>';
            echo '</li>';
        }
    }
    echo '</ul>';
}

/**
 * Enqueue Chart.js library and custom script for the Dashboard
 */
function custom_enqueue_chart_scripts( $hook ) {
    // Only load the script on the main dashboard page
    if ( 'index.php' !== $hook ) {
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
        plugins_url( 'dashboard-chart.js', __FILE__ ), 
        array( 'jquery', 'chart-js' ), // Depends on jQuery and Chart.js
        filemtime( plugin_dir_path( __FILE__ ) . 'dashboard-chart.js' ), // Version based on file modification time
        true 
    );

    // 3. Prepare and Localize (pass) Data to the JavaScript
    // Note: You would replace this static data with actual database queries (e.g., post counts by date)
    $chart_data = array(
        'labels' => array( 'Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Day 6', 'Day 7' ),
        'data' => array( 1200, 1500, 900, 2200, 1800, 2500, 2100 ),
    );
    
    wp_localize_script( 
        'custom-chart-script', 
        'CustomChartData', 
        $chart_data 
    );
}
add_action( 'admin_enqueue_scripts', 'custom_enqueue_chart_scripts' );


/**
 * Add the Website Statistics Widget
 */
function custom_add_stats_widget() {
    wp_add_dashboard_widget(
        'custom_website_stats',             // Widget slug
        'Website Statistics',               // Widget title
        'custom_website_stats_content'      // Display function
    );
}
add_action( 'wp_dashboard_setup', 'custom_add_stats_widget' );

/**
 * Content for the Stats Widget
 */
function custom_website_stats_content() {
    echo '<h3>Total Page Views: 2.5M</h3>'; // Static data as example
    echo '<canvas id="pageViewsChart" style="max-height: 200px;"></canvas>';
}

/**
 * Hook into 'admin_menu' to add our custom item
 */
add_action('admin_menu', 'my_custom_plugin_menu');

function my_custom_plugin_menu() {
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
function my_plugin_settings_page() {
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