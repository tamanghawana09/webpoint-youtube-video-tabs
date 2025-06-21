<!-- Shortcode handler -->

<?php
/**
 * Shortcode Handler for YouTube Video Showcase
 */

class WVT_Shortcode {
    
    public function __construct() {
        add_shortcode('youtube_video_showcase', array($this, 'render_shortcode'));
    }
    
    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'initial_count' => 3,
            'load_more_count' => 6,
            'show_categories' => 'true',
            'default_category' => 'all'
        ), $atts);
        
        ob_start();
        $this->render_video_showcase($atts);
        return ob_get_clean();
    }
    
    private function render_video_showcase($atts) {
        // Get categories for tabs
        $categories = get_terms(array(
            'taxonomy' => 'video_category',
            'hide_empty' => true
        ));
        
        // Get initial videos
        $args = array(
            'post_type' => 'youtube_videos',
            'post_status' => 'publish',
            'posts_per_page' => intval($atts['initial_count']),
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $videos = new WP_Query($args);
        
        ?>
        <div class="wvt-container" data-initial-count="<?php echo esc_attr($atts['initial_count']); ?>" data-load-more-count="<?php echo esc_attr($atts['load_more_count']); ?>">
            
            <?php if ($atts['show_categories'] === 'true' && $categories && !is_wp_error($categories)): ?>
            <div class="wvt-tabs">
                <button class="tab-button active" data-filter="*" data-category="all">All</button>
                <?php foreach ($categories as $category): ?>
                    <button class="tab-button" data-filter=".category-<?php echo esc_attr($category->slug); ?>" data-category="<?php echo esc_attr($category->slug); ?>">
                        <?php echo esc_html($category->name); ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <div class="wvt-video-grid">
                <?php
                if ($videos->have_posts()) {
                    while ($videos->have_posts()) {
                        $videos->the_post();
                        $this->render_video_item(get_the_ID());
                    }
                    wp_reset_postdata();
                } else {
                    echo '<p class="no-videos">No videos found.</p>';
                }
                ?>
            </div>
            
            <?php if ($videos->found_posts > intval($atts['initial_count'])): ?>
            <div class="wvt-load-more-container">
                <button id="wvt-load-more" class="wvt-load-more-btn">
                    Load More Videos
                    <span class="loading-spinner" style="display: none;">
                        <svg width="20" height="20" viewBox="0 0 50 50">
                            <circle cx="25" cy="25" r="20" fill="none" stroke="#fff" stroke-width="5" stroke-linecap="round" stroke-dasharray="31.416" stroke-dashoffset="31.416">
                                <animate attributeName="stroke-dasharray" dur="2s" values="0 31.416;15.708 15.708;0 31.416" repeatCount="indefinite"/>
                                <animate attributeName="stroke-dashoffset" dur="2s" values="0;-15.708;-31.416" repeatCount="indefinite"/>
                            </circle>
                        </svg>
                    </span>
                </button>
            </div>
            <?php endif; ?>
            
        </div>
        <?php
    }
    
    private function render_video_item($post_id) {
        $youtube_url = get_post_meta($post_id, '_youtube_url', true);
        $video_id = $this->extract_youtube_id($youtube_url);
        $duration = get_post_meta($post_id, '_video_duration', true);
        $views = get_post_meta($post_id, '_video_views', true);
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
                    <?php if ($duration): ?>
                        <div class="video-duration"><?php echo esc_html($duration); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="video-info">
                <h3 class="video-title"><?php echo esc_html(get_the_title($post_id)); ?></h3>
                <div class="video-meta">
                    <?php if ($views): ?>
                        <span class="video-views"><?php echo esc_html($views); ?></span>
                    <?php endif; ?>
                    <span class="video-date"><?php echo get_the_date('M j, Y', $post_id); ?></span>
                </div>
                <?php if (get_the_excerpt($post_id)): ?>
                    <p class="video-excerpt"><?php echo esc_html(get_the_excerpt($post_id)); ?></p>
                <?php endif; ?>
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