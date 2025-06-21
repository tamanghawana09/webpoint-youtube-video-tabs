<?php
/**
 * AJAX Handler for Load More Functionality - FIXED VERSION
 */

class WVT_Ajax_Handler
{
    public function __construct()
    {
        add_action('wp_ajax_load_more_videos', array($this, 'load_more_videos'));
        add_action('wp_ajax_nopriv_load_more_videos', array($this, 'load_more_videos'));
    }

    public function load_more_videos()
    {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'], 'wvt_nonce')) {
            wp_die('Security check failed');
        }

        // Sanitize and validate input
        $offset = intval($_POST['offset']);
        $category = sanitize_text_field($_POST['category']);
        $posts_per_page = isset($_POST['posts_per_page']) ? intval($_POST['posts_per_page']) : 6;

        // Base query arguments
        $args = array(
            'post_type' => 'youtube_videos',
            'post_status' => 'publish',
            'posts_per_page' => $posts_per_page,
            'offset' => $offset,
            'orderby' => 'date',
            'order' => 'DESC'
        );

        // Apply category filter - FIXED LOGIC

        $bypass_category = 'all';

        if (!empty($category) && $category !== $bypass_category) {
            // Check if category exists first
            $term_exists = term_exists($category, 'video_category');
            if ($term_exists) {
                $args['tax_query'] = array(
                    array(
                        'taxonomy' => 'video_category',
                        'field' => 'slug',
                        'terms' => $category, // Single term, not array
                        'operator' => 'IN'
                    )
                );
            }
        }

        $videos = new WP_Query($args);

        // Initialize response
        $response = array(
            'html' => '',
            'has_more' => false,
            'found_posts' => $videos->found_posts,
            'current_category' => $category,
            'total_loaded' => $offset + $posts_per_page,
            'query_args' => $args 
        );

        if ($videos->have_posts()) {
            ob_start();

            while ($videos->have_posts()) {
                $videos->the_post();
                $this->render_video_item(get_the_ID());
            }

            $response['html'] = ob_get_clean();

            $total_posts = $videos->found_posts;
            $response['has_more'] = ($offset + $posts_per_page) < $total_posts;

            wp_reset_postdata();
        } else {
            // No posts found
            $response['html'] = '<p class="no-videos-message">No more videos found in this category.</p>';
            $response['has_more'] = false;
        }

        wp_send_json($response);
    }

    private function render_video_item($post_id)
    {
        $youtube_url = get_post_meta($post_id, '_youtube_url', true);
        $video_id = $this->extract_youtube_id($youtube_url);
        $pastor_name = get_post_meta($post_id, '_pastor_name', true);
        $uploaded_date = get_post_meta($post_id, '_uploaded_date', true);

        // Get categories and build class string
        $categories = wp_get_post_terms($post_id, 'video_category');
        $category_classes = '';

        if ($categories && !is_wp_error($categories)) {
            foreach ($categories as $category) {
                $category_classes .= ' category-' . $category->slug;
            }
        }

        // Get thumbnail URL
        $thumbnail_url = $video_id ? "https://img.youtube.com/vi/{$video_id}/maxresdefault.jpg" : get_the_post_thumbnail_url($post_id, 'medium');

        // Render the video item
        ?>
        <div class="video-item<?php echo esc_attr($category_classes); ?>" data-youtube-id="<?php echo esc_attr($video_id); ?>" data-youtube-url="<?php echo esc_attr($youtube_url); ?>">
            <div class="video-thumbnail">
                <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr(get_the_title($post_id)); ?>" />
                <div class="video-overlay">
                    <div class="play-button">
                        <svg width="50" height="50" viewBox="0 0 50 50">
                            <circle cx="25" cy="25" r="25" fill="rgba(255,255,255,0.9)" />
                            <polygon points="20,15 20,35 35,25" fill="#ff0000" />
                        </svg>
                    </div>
                </div>
            </div>
            <div class="video-info">
                <h3 class="video-title"><?php echo esc_html(get_the_title($post_id)); ?></h3>
                <?php if (!empty($pastor_name)): ?>
                    <p class="video-pastor">
                        <?php echo esc_html($pastor_name); ?> | <?php echo esc_html(date('F j/Y', strtotime($uploaded_date))); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    private function extract_youtube_id($url) 
    {
        if (empty($url)) return false;
        
        $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/';
        preg_match($pattern, $url, $matches);
        
        return isset($matches[1]) ? $matches[1] : false;
    }
}
?>