<?php
/*
* News Homepage Layout Plugin
* @package NewsHomepageLayout
*/

if (!defined('ABSPATH')) exit;

class News_Homepage_Layout
{


    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_nhl_save_order', [$this, 'save_order']);

        add_action('wp_ajax_nhl_save_grid_order', [$this, 'save_grid_order']);

        add_action('wp_ajax_nhl_save_news_slot', function () {

            check_ajax_referer('nhl_nonce', 'nonce');

            $post_id = (int) $_POST['post_id'];
            $slot = sanitize_text_field($_POST['slot']);
            $category = sanitize_text_field($_POST['category']); // 🔥 ADD

            $slot_key = $category . '_slot';

            // Clear this slot from ANY other post in same category
            $old = get_posts([
                'category_name' => $category,
                'meta_key'      => $slot_key,
                'meta_value'    => $slot,
                'numberposts'   => 1
            ]);

            if (!empty($old)) {
                delete_post_meta($old[0]->ID, $slot_key);
            }

            // Assign new post to slot
            delete_post_meta($post_id, $slot_key);
            update_post_meta($post_id, $slot_key, $slot);

            wp_send_json_success();
        });

        add_action('wp_ajax_nhl_reset_news_layout', function () {

            check_ajax_referer('nhl_nonce', 'nonce');

            // Get all NEWS posts by newest first
            $posts = get_posts([
                'category_name' => 'news',
                'numberposts'   => -1,
                'orderby'       => 'date',
                'order'         => 'DESC'
            ]);

            // Clear all existing slots
            foreach ($posts as $p) {
                delete_post_meta($p->ID, 'news_slot');
            }

            // Assign defaults
            if (!empty($posts)) {

                // Featured = newest
                update_post_meta($posts[0]->ID, 'news_slot', 'featured');

                // Top 1–4 = next newest
                $top_slots = ['top_1', 'top_2', 'top_3', 'top_4'];

                for ($i = 0; $i < 4; $i++) {
                    if (isset($posts[$i + 1])) {
                        update_post_meta($posts[$i + 1]->ID, 'news_slot', $top_slots[$i]);
                    }
                }
            }

            wp_send_json_success([
                'message' => 'News layout reset to default'
            ]);
        });

        add_action('wp_ajax_nhl_clear_news_slot', function () {

            check_ajax_referer('nhl_nonce', 'nonce');

            $post_id = (int) $_POST['post_id'];

            $category = sanitize_text_field($_POST['category']);
            $slot_key = $category . '_slot';

            delete_post_meta($post_id, $slot_key);


            wp_send_json_success();
        });

        add_action('wp_ajax_nhl_load_more_news', [$this, 'nhl_load_more_news']);
        add_action('wp_ajax_nopriv_nhl_load_more_news', [$this, 'nhl_load_more_news']);

        add_action('wp_enqueue_scripts', function () {

            wp_enqueue_script(
                'nhl-news-frontend',
                plugin_dir_url(__FILE__) . 'js/news-frontend.js',
                ['jquery'],
                null,
                true
            );

            // Make ajax_url available on frontend too
            wp_localize_script('nhl-news-frontend', 'nhl_ajax', [
                'ajax_url' => admin_url('admin-ajax.php')
            ]);
        });

        add_action('wp_ajax_nhl_reset_category_layout', function () {

            check_ajax_referer('nhl_nonce', 'nonce');

            $category = sanitize_text_field($_POST['category']);
            $slot_key = $category . '_slot';

            $posts = get_posts([
                'category_name' => $category,
                'numberposts'   => -1,
                'orderby'       => 'date',
                'order'         => 'DESC'
            ]);

            foreach ($posts as $p) {
                delete_post_meta($p->ID, $slot_key);
            }

            if (!empty($posts)) {

                update_post_meta($posts[0]->ID, $slot_key, 'featured');

                $top_slots = ['top_1', 'top_2', 'top_3', 'top_4'];

                for ($i = 0; $i < 4; $i++) {
                    if (isset($posts[$i + 1])) {
                        update_post_meta($posts[$i + 1]->ID, $slot_key, $top_slots[$i]);
                    }
                }
            }

            wp_send_json_success();
        });
    }

    // Add Admin Menu
    public function add_menu()
    {
        add_menu_page(
            'Homepage Layout',
            'Homepage Layout',
            'edit_posts',
            'homepage-layout',
            [$this, 'render_page'],
            'dashicons-screenoptions',
            3
        );
    }

    // Load JS & CSS
    public function enqueue_assets($hook)
    {
        if ($hook !== 'toplevel_page_homepage-layout') return;

        wp_enqueue_style('nhl-admin-css', plugin_dir_url(__FILE__) . 'css/news-post-layout.css');

        // jQuery UI components (REQUIRED)
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('jquery-ui-draggable');
        wp_enqueue_script('jquery-ui-droppable');

        wp_enqueue_script(
            'news-post-layout',
            plugin_dir_url(__FILE__) . 'js/news-post-layout.js',
            ['jquery', 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-droppable'],
            null,
            true
        );

        wp_localize_script('news-post-layout', 'nhl_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('nhl_nonce')
        ]);
    }

    // Admin Page UI
    public function render_page()
    {
        echo '<div class="wrap"><h1>Homepage News Layout</h1>';

        // 🔥 Load categories dynamically
        $categories = get_categories([
            'hide_empty' => true,
            'orderby'    => 'name',
            'order'      => 'ASC',
            'exclude'    => [1], // Exclude Uncategorized
        ]);

        /* =====================================================
                TABS NAV
        ===================================================== */
        echo '<div class="nhl-tabs">';
        $first = true;

        foreach ($categories as $cat) {
            $active = $first ? 'active' : '';
            echo '<button class="nhl-tab ' . esc_attr($active) . '" data-tab="' . esc_attr($cat->slug) . '">'
                . esc_html($cat->name) .
                '</button>';
            $first = false;
        }

        echo '</div>';
        echo '<div class="nhl-tab-contents">';

        /* =====================================================
            TAB CONTENTS
        ===================================================== */
        $first = true;

        foreach ($categories as $catObj) {

            $cat        = $catObj->slug;
            $isActive   = $first ? 'active' : '';
            $first      = false;

            $slot_key  = $cat . '_slot';
            $grid_key  = $cat . '_grid_position';

            echo "<div class='nhl-tab-content {$isActive}' data-tab='{$cat}'>";
            echo "<div class='nhl-{$cat}-layout'>";
            echo "<h2>" . esc_html($catObj->name) . " Layout</h2>";

            /* =========================
                    RESET BUTTON
            ========================= */
            echo "
            <div class='nhl-reset-wrap'>
                <button id='nhl-reset-{$cat}' class='button button-secondary'>
                    🔄 Reset {$catObj->name} Layout to Default
                </button>
            </div>
            ";

            /* =========================
                    HERO AREA
            ========================= */
            echo "<div class='nhl-hero-admin nhl-{$cat}-hero-admin'>";

            /* ---------- TOP STORIES ---------- */
            echo "
            <div class='nhl-top-admin'>
                <h3>🔵 Top Stories</h3>
                <div class='nhl-top-slots'>
            ";

            $top_slots = ['top_1', 'top_2', 'top_3', 'top_4'];

            foreach ($top_slots as $slot) {

                $slot_post = get_posts([
                    'category_name' => $cat,
                    'meta_key'      => $slot_key,
                    'meta_value'    => $slot,
                    'numberposts'   => 1
                ]);

                echo "<ul class='nhl-drop' data-slot='{$slot}'>";

                if (!empty($slot_post)) {
                    $p     = $slot_post[0];
                    $thumb = get_the_post_thumbnail_url($p->ID, 'thumbnail')
                        ?: get_template_directory_uri() . '/assets/no-image.jpg';

                    echo "
                <li class='nhl-item' data-id='{$p->ID}'>
                    <img src='{$thumb}'>
                    <span class='nhl-title'>" . esc_html($p->post_title) . "</span>
                    <span class='nhl-remove'>✖</span>
                </li>
                ";
                } else {
                    echo "<li class='nhl-slot-placeholder'>" .
                        esc_html(strtoupper(str_replace('_', ' ', $slot))) .
                        "</li>";
                }

                echo "</ul>";
            }

            echo "</div></div>";

            /* ---------- FEATURED ---------- */
            $featured = get_posts([
                'category_name' => $cat,
                'meta_key'      => $slot_key,
                'meta_value'    => 'featured',
                'numberposts'   => 1
            ]);

            echo "
            <div class='nhl-featured-admin'>
                <h3>🔴 Featured</h3>
                <ul class='nhl-featured-slot nhl-drop' data-slot='featured'>
            ";

            if (!empty($featured)) {
                $p     = $featured[0];
                $thumb = get_the_post_thumbnail_url($p->ID, 'thumbnail')
                    ?: get_template_directory_uri() . '/assets/no-image.jpg';

                echo "
            <li class='nhl-item' data-id='{$p->ID}'>
                <img src='{$thumb}'>
                <span class='nhl-title'>" . esc_html($p->post_title) . "</span>
                <span class='nhl-remove'>✖</span>
            </li>
            ";
            } else {
                echo "<li class='nhl-slot-placeholder'>FEATURED</li>";
            }

            echo "</ul></div>";
            echo "</div>"; // hero admin

            /* =========================
                    GRID
             ========================= */
            $grid = get_posts([
                'category_name' => $cat,
                'meta_query' => [
                    'relation' => 'OR',
                    [
                        'key'     => $slot_key,
                        'compare' => 'NOT EXISTS'
                    ],
                    [
                        'key'     => $slot_key,
                        'value'   => ['featured', 'top_1', 'top_2', 'top_3', 'top_4'],
                        'compare' => 'NOT IN'
                    ],
                ],
                'numberposts' => -1
            ]);

            // Sort by grid position if exists
            usort($grid, function ($a, $b) use ($grid_key) {
                $aPos = get_post_meta($a->ID, $grid_key, true);
                $bPos = get_post_meta($b->ID, $grid_key, true);

                if ($aPos !== '' && $bPos !== '') return (int)$aPos - (int)$bPos;
                if ($aPos !== '') return -1;
                if ($bPos !== '') return 1;
                return 0;
            });

            echo "
                <h3>🟦 Grid</h3>
                <ul class='nhl-card-grid nhl-sortable-grid' data-cat='{$cat}'>
                ";

            foreach ($grid as $post) {
                $thumb = get_the_post_thumbnail_url($post->ID, 'medium')
                    ?: get_template_directory_uri() . '/assets/no-image.jpg';

                echo "
                <li class='nhl-item nhl-card-item' data-id='{$post->ID}'>
                    <div class='nhl-card-thumb'>
                        <img src='{$thumb}' alt=''>
                    </div>
                    <div class='nhl-card-title'>" . esc_html($post->post_title) . "</div>
                </li>
                ";
            }

            echo "</ul>";
            echo "</div>"; // layout
            echo "</div>"; // tab content
        }

        echo '</div></div>'; // contents + wrap
    }


    // Save Order AJAX
    public function save_order()
    {

        check_ajax_referer('nhl_nonce', 'nonce');

        $category = sanitize_text_field($_POST['category']);
        $order = isset($_POST['order']) ? $_POST['order'] : [];

        if (!empty($order)) {
            foreach ($order as $position => $post_id) {
                update_post_meta((int)$post_id, $category . '_grid_position', $position);
            }
        }

        wp_send_json_success();
    }

    public function nhl_load_more_news()
    {

        $page = isset($_POST['page']) ? (int) $_POST['page'] : 1;

        $query = new WP_Query([
            'category_name' => 'news',
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key'     => 'news_slot',
                    'compare' => 'NOT EXISTS'
                ],
                [
                    'key'     => 'news_slot',
                    'value'   => ['featured', 'top_1', 'top_2', 'top_3', 'top_4'],
                    'compare' => 'NOT IN'
                ],
            ],
            'posts_per_page' => 8,
            'paged' => $page + 1
        ]);

        if ($query->have_posts()) :
            while ($query->have_posts()) : $query->the_post(); ?>
                <div class="news-grid-item">
                    <h5><?php the_title(); ?></h5>
                </div>
    <?php endwhile;
        endif;

        wp_die();
    }

    public function save_grid_order()
    {
        check_ajax_referer('nhl_nonce', 'nonce');

        $order = $_POST['order'] ?? [];
        $category = sanitize_text_field($_POST['category']); // 🔥 ADD

        $grid_key = $category . '_grid_position';

        if (!empty($order)) {
            foreach ($order as $position => $post_id) {
                update_post_meta((int)$post_id, $grid_key, $position);
            }
        }

        wp_send_json_success();
    }
}


new News_Homepage_Layout();

// ============================================================================
// TIME ELAPSED — SHORT DHIVEHI FORMAT (NO "AGO")
// ============================================================================

if (!function_exists('nhl_time_elapsed_dv')) {
    function nhl_time_elapsed_dv($post_id)
    {
        if (!$post_id) return '';

        $post_time = get_post_time('U', true, $post_id);
        $now       = current_time('timestamp');

        if (!$post_time || $post_time > $now) {
            return '';
        }

        $diff = $now - $post_time;

        // Seconds (optional – you can remove if not needed)
        if ($diff < 60) {
            return $diff . ' މިނިޓް';
        }

        $units = [
            31536000 => 'އަހަރު', // year
            2592000  => 'މަސް',   // month
            86400    => 'ދުވަސް', // day
            3600     => 'ގަޑި',   // hour
            60       => 'މިނިޓް', // minute
        ];

        foreach ($units as $seconds => $label) {
            if ($diff >= $seconds) {
                return floor($diff / $seconds) . ' ' . $label;
            }
        }

        return '0 މިނިޓް';
    }
}



function nhl_post_card($post, $is_featured = false)
{
    $thumb = get_the_post_thumbnail_url($post->ID, 'large');
    $thumb = $thumb ?: get_template_directory_uri() . '/assets/no-image.jpg';

    $comments = get_comments_number($post->ID);
    $time = nhl_time_elapsed_dv($post->ID); // ← FIXED: Changed from nhl_time_elapsed to nhl_time_elapsed_dv
    $excerpt = wp_trim_words(strip_tags($post->post_content), 25);

    ob_start(); ?>

    <div class="news-card <?= $is_featured ? 'news-featured-card' : '' ?>">

        <div class="news-thumb">
            <a href="<?= get_permalink($post->ID); ?>">
                <img src="<?= esc_url($thumb); ?>" alt="<?= esc_attr($post->post_title); ?>">
            </a>
        </div>

        <div class="news-content">

            <h2 class="news-title">
                <a href="<?= get_permalink($post->ID); ?>">
                    <?= esc_html($post->post_title); ?>
                </a>
            </h2>

            <div class="news-meta">
                <span class="news-comments">
                    <?= $comments; ?> <i class="far fa-comment"></i>
                </span>
                <span class="news-time">
                    <i class="far fa-clock"></i> <?= $time; ?>
                </span>
            </div>

            <?php if ($is_featured): ?>
                <div class="news-excerpt">
                    <p><?= esc_html($excerpt); ?></p>
                </div>
            <?php endif; ?>

        </div>
    </div>

<?php
    return ob_get_clean();
}
