<!-- AJAX Functions -->

<?php
/**
 * AJAX Handler for Load More Functionality
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
        if (!wp_verify_nonce($_POST['nonce'],  'wvt_nonce')) {
            wp_die('Security check failed');
        }

        $offset = intval($_POST['offset']);
        $category = sanitize_text_field($_POST['category']);
        $posts_per_page = 6;

        $args = array(
            'post_type' => 'youtube_videos',
            'post_status' => 'publish',
            'posts_per_page' => $posts_per_page,
            'offset' => $offset,
            'orderby' => 'date',
            'order' => 'DESC'
        );

        if ($category && $category !== 'all') {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'video_category',
                    'field' => 'slug',
                    'terms' => $category
                )
            );
        }

        $videos = new WP_Query($args);

        $response = array(
            'html' => '',
            'has_more' => false
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
        }

        wp_send_json($response);
    }

    private function render_video_item($post_id)
    {
        $youtube_url = get_post_meta($post_id, '_youtube_url', true);
        $video_id = $this->extract_youtube_id($youtube_url);
        $pastor_name = get_post_meta($post_id, '_pastor_name', true);
        $uploaded_date = get_post_meta($post_id, '_uploaded_date', true);

        $categories = wp_get_post_terms($post_id, 'video_category');
        $category_classes = '';

        if ($categories && !is_wp_error($categories)) {
            foreach ($categories as $category) {
                $category_classes .= ' category-' . $category->slug;
            }
        }

        $thumbnail_url = $video_id ? "https://img.youtube.com/vi/{$video_id}/maxresdefault.jpg" : get_the_post_thumbnail_url($post_id, 'medium');

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
                <p class="video-pastor"><strong>Pastor:</strong> <?php echo esc_html($pastor_name); ?></p>
                <p class="video-uploaded-date"><strong>Uploaded on:</strong> <?php echo esc_html(date('F j, Y', strtotime($uploaded_date))); ?></p>
                <span class="video-date"><?php echo get_the_date('M j, Y', $post_id); ?></span>
            </div>
        </div>
<?php
    }
    private function extract_youtube_id($url) {
        if (empty($url)) return false;
        
        $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/';
        preg_match($pattern, $url, $matches);
        
        return isset($matches[1]) ? $matches[1] : false;
    }
}
?>