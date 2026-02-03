<?php
/**
 * Post Reactions Feature
 * Handles post reactions with percentage display
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Post_Reactions {
    
    private $reaction_types = ['inlove', 'wow', 'angry', 'sad', 'happy'];
    
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_react_to_post', [$this, 'handle_reaction']);
        add_action('wp_ajax_nopriv_react_to_post', [$this, 'handle_reaction']);
        // add_filter('the_content', [$this, 'add_reactions_to_content']); // Uncomment to auto-append reactions to post content
        add_shortcode('post_reactions', [$this, 'render_reactions_shortcode']);
    }
    
    public function enqueue_scripts() {
        if (is_single()) {
            wp_enqueue_style(
                'post-reactions', 
                plugin_dir_url(__FILE__) . 'css/reactions.css', 
                array(), 
                '1.0.0'
            );
            
            wp_enqueue_script(
                'post-reactions', 
                plugin_dir_url(__FILE__) . 'js/reactions.js', 
                ['jquery'], 
                '1.0.0', 
                true
            );
            
            wp_localize_script('post-reactions', 'reactionsData', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('reaction_nonce'),
                'post_id' => get_the_ID()
            ]);
        }
    }
    
    public function handle_reaction() {
        check_ajax_referer('reaction_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $reaction = sanitize_text_field($_POST['reaction']);
        
        if (!in_array($reaction, $this->reaction_types)) {
            wp_send_json_error('Invalid reaction type');
        }
        
        // Get user identifier (IP + User Agent for guests, or user ID)
        $user_identifier = $this->get_user_identifier();
        
        // Get existing reactions
        $reactions = get_post_meta($post_id, '_post_reactions', true);
        if (!$reactions) {
            $reactions = array_fill_keys($this->reaction_types, []);
        }
        
        // Get user's previous vote
        $user_votes = get_post_meta($post_id, '_user_reactions', true);
        if (!$user_votes) {
            $user_votes = [];
        }
        
        $previous_vote = isset($user_votes[$user_identifier]) ? $user_votes[$user_identifier] : null;
        
        // Remove previous vote if exists
        if ($previous_vote && isset($reactions[$previous_vote])) {
            $reactions[$previous_vote] = array_diff($reactions[$previous_vote], [$user_identifier]);
        }
        
        // Add new vote (or remove if clicking same reaction)
        if ($previous_vote !== $reaction) {
            $reactions[$reaction][] = $user_identifier;
            $user_votes[$user_identifier] = $reaction;
        } else {
            unset($user_votes[$user_identifier]);
        }
        
        // Update post meta
        update_post_meta($post_id, '_post_reactions', $reactions);
        update_post_meta($post_id, '_user_reactions', $user_votes);
        
        wp_send_json_success([
            'stats' => $this->calculate_percentages($reactions),
            'user_vote' => isset($user_votes[$user_identifier]) ? $user_votes[$user_identifier] : null
        ]);
    }
    
    private function get_user_identifier() {
        if (is_user_logged_in()) {
            return 'user_' . get_current_user_id();
        }
        return 'guest_' . md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
    }
    
    private function calculate_percentages($reactions) {
        $total = 0;
        foreach ($reactions as $votes) {
            $total += count($votes);
        }
        
        $percentages = [];
        foreach ($this->reaction_types as $type) {
            $count = isset($reactions[$type]) ? count($reactions[$type]) : 0;
            $percentages[$type] = $total > 0 ? round(($count / $total) * 100) : 0;
        }
        
        return $percentages;
    }
    
    public function add_reactions_to_content($content) {
        if (is_single() && in_the_loop() && is_main_query()) {
            $post_id = get_the_ID();
            $reactions = get_post_meta($post_id, '_post_reactions', true);
            if (!$reactions) {
                $reactions = array_fill_keys($this->reaction_types, []);
            }
            
            $stats = $this->calculate_percentages($reactions);
            $user_identifier = $this->get_user_identifier();
            $user_votes = get_post_meta($post_id, '_user_reactions', true);
            $user_vote = isset($user_votes[$user_identifier]) ? $user_votes[$user_identifier] : null;
            
            $reactions_html = $this->render_reactions($stats, $user_vote);
            $content .= $reactions_html;
        }
        
        return $content;
    }
    
    private function render_reactions($stats, $user_vote) {
        ob_start();
        ?>
        <div class="post-reactions-container">
            <div class="reactions-wrapper">
                <?php foreach ($this->reaction_types as $reaction): ?>
                    <button class="reaction-btn <?php echo $user_vote === $reaction ? 'active' : ''; ?>" 
                            data-reaction="<?php echo esc_attr($reaction); ?>"
                            aria-label="<?php echo ucfirst($reaction); ?> reaction">
                        <span class="reaction-percentage <?php echo $stats[$reaction] > 0 ? 'has-votes' : ''; ?>">
                            <?php echo $stats[$reaction]; ?>%
                        </span>
                        <div class="reaction-icon-wrapper">
                            <img src="<?php echo plugin_dir_url(__FILE__) . 'images/' . $reaction . '.png'; ?>" 
                                 alt="<?php echo ucfirst($reaction); ?> reaction icon" 
                                 class="reaction-icon">
                        </div>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode to display reactions anywhere
     * Usage: [post_reactions]
     */
    public function render_reactions_shortcode($atts) {
        // Ensure scripts are loaded
        $this->enqueue_scripts();
        
        $post_id = get_the_ID();
        if (!$post_id) {
            return '';
        }
        
        $reactions = get_post_meta($post_id, '_post_reactions', true);
        if (!$reactions) {
            $reactions = array_fill_keys($this->reaction_types, []);
        }
        
        $stats = $this->calculate_percentages($reactions);
        $user_identifier = $this->get_user_identifier();
        $user_votes = get_post_meta($post_id, '_user_reactions', true);
        $user_vote = isset($user_votes[$user_identifier]) ? $user_votes[$user_identifier] : null;
        
        return $this->render_reactions($stats, $user_vote);
    }
}

// Initialize the Post Reactions feature
new Post_Reactions();