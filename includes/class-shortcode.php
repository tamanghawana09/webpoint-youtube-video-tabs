<?php
class WVT_Shortcode
{
    public function __construct()
    {
        add_shortcode('webpoint_video_tabs', array($this, 'render_video_tabs'));
    }

    public function render_video_tabs($atts)
    {
        // Parse shortcode attributes
        $atts = shortcode_atts(array(
            'posts_per_page' => 3,  // Show 3 videos initially
            'load_more_count' => 6, // Load 6 more when clicked
            'default_category' => 'all'
        ), $atts);

        $posts_per_page = intval($atts['posts_per_page']);
        $load_more_count = intval($atts['load_more_count']);
        $default_category = sanitize_text_field($atts['default_category']);

        // Base query for initial load
        $args = [
            'post_type' => 'youtube_videos',
            'post_status' => 'publish',
            'posts_per_page' => $posts_per_page,
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        // Apply default category filter if not 'all'
        if ($default_category !== 'all' && !empty($default_category)) {
            $term_exists = term_exists($default_category, 'video_category');
            if ($term_exists) {
                $args['tax_query'] = array(
                    array(
                        'taxonomy' => 'video_category',
                        'field' => 'slug',
                        'terms' => $default_category
                    )
                );
            }
        }

        $query = new WP_Query($args);
        
        // Get total count for all videos and current category
        $total_all_videos = wp_count_posts('youtube_videos')->publish;
        $total_current_category = $query->found_posts;

        ob_start();
        ?>

        <div class="wvt-container" 
             data-initial-count="<?php echo esc_attr($posts_per_page); ?>" 
             data-load-more-count="<?php echo esc_attr($load_more_count); ?>" 
             data-category="<?php echo esc_attr($default_category); ?>"
             data-total-all="<?php echo esc_attr($total_all_videos); ?>"
             data-total-current="<?php echo esc_attr($total_current_category); ?>">
            
            <!-- Tab Navigation -->
            <div class="wvt-tabs">
                <button class="tab-button <?php echo ($default_category === 'all') ? 'active' : ''; ?>" 
                        data-filter="*" 
                        data-category="all">
                    All
                </button>
                <?php
                $terms = get_terms(array(
                    'taxonomy' => 'video_category', 
                    'hide_empty' => true,
                    'orderby' => 'name',
                    'order' => 'ASC'
                ));
                
                if (!is_wp_error($terms) && !empty($terms)) {
                    foreach ($terms as $term) {
                        $is_active = ($default_category === $term->slug) ? 'active' : '';
                        echo '<button class="tab-button ' . esc_attr($is_active) . '" ' .
                             'data-filter=".category-' . esc_attr($term->slug) . '" ' .
                             'data-category="' . esc_attr($term->slug) . '">' . 
                             esc_html($term->name) . 
                             ' <span class="count">(' . $term->count . ')</span>' .
                             '</button>';
                    }
                }
                ?>
            </div>

            <!-- Loading indicator for category switching -->
            <div class="wvt-loading-overlay" style="display: none;">
                <div class="loading-spinner-large">Loading videos...</div>
            </div>

            <!-- Video Grid -->
            <div class="wvt-video-grid">
                <?php
                if ($query->have_posts()) {
                    while ($query->have_posts()) {
                        $query->the_post();
                        $this->render_video_item(get_the_ID());
                    }
                    wp_reset_postdata();
                } else {
                    ?>
                    <div class="no-videos-message">
                        <p>No videos found<?php echo ($default_category !== 'all') ? ' in this category' : ''; ?>.</p>
                    </div>
                    <?php
                }
                ?>
            </div>

            <!-- Load More Button -->
            <div class="wvt-load-more-container">
                <?php 
                // Show load more button if there are more videos than initially displayed
                // OR if we want to test with fewer videos than initial count
                $should_show_load_more = ($total_current_category > $posts_per_page);
                
                if ($should_show_load_more): ?>
                    <button id="wvt-load-more" class="wvt-load-more-btn">
                        Load More Videos
                        <span class="loading-spinner" style="display:none;">
                            <span class="spinner-dot"></span>
                            <span class="spinner-dot"></span>
                            <span class="spinner-dot"></span>
                        </span>
                    </button>
                <?php else: ?>
                    <!-- Hidden button for testing - remove in production -->
                    <button id="wvt-load-more" class="wvt-load-more-btn" style="display: none;">
                        Load More Videos
                        <span class="loading-spinner" style="display:none;">Loading...</span>
                    </button>
                <?php endif; ?>
                
                    </div>
        </div>

        <?php
        return ob_get_clean();
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
        $category_names = array();

        if ($categories && !is_wp_error($categories)) {
            foreach ($categories as $cat) {
                $category_classes .= ' category-' . $cat->slug;
                $category_names[] = $cat->name;
            }
        }

        // Get thumbnail URL with fallback
        $thumbnail_url = '';
        if ($video_id) {
            $thumbnail_url = "https://img.youtube.com/vi/{$video_id}/maxresdefault.jpg";
        } else {
            $thumbnail_url = get_the_post_thumbnail_url($post_id, 'medium');
            if (!$thumbnail_url) {
                $thumbnail_url = 'data:image/svg+xml;base64,' . base64_encode('<svg width="300" height="200" xmlns="http://www.w3.org/2000/svg"><rect width="100%" height="100%" fill="#f0f0f0"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="#999">No Thumbnail</text></svg>');
            }
        }

        ?>
        <div class="video-item<?php echo esc_attr($category_classes); ?>" 
             data-youtube-id="<?php echo esc_attr($video_id); ?>" 
             data-youtube-url="<?php echo esc_attr($youtube_url); ?>"
             data-post-id="<?php echo esc_attr($post_id); ?>">
            
            <div class="video-thumbnail">
                <img src="<?php echo esc_url($thumbnail_url); ?>" 
                     alt="<?php echo esc_attr(get_the_title($post_id)); ?>" 
                     loading="lazy" />
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
                        <strong>Pastor:</strong> <?php echo esc_html($pastor_name); ?>
                    </p>
                <?php endif; ?>
                
                <?php if (!empty($uploaded_date)): ?>
                    <p class="video-uploaded-date">
                        <strong>Uploaded:</strong> <?php echo esc_html(date('F j, Y', strtotime($uploaded_date))); ?>
                    </p>
                <?php endif; ?>
                
                <span class="video-date"><?php echo get_the_date('M j, Y', $post_id); ?></span>
                
                <?php if (!empty($category_names)): ?>
                    <div class="video-categories">
                        <span class="categories-label">Categories:</span>
                        <?php foreach ($category_names as $index => $name): ?>
                            <span class="category-tag"><?php echo esc_html($name); ?></span><?php 
                            if ($index < count($category_names) - 1) echo ', '; 
                        ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    private function extract_youtube_id($url)
    {
        if (empty($url)) return false;
        
        $patterns = array(
            '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&\n?#]+)/',
            '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/'
        );
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return isset($matches[1]) ? $matches[1] : false;
            }
        }
        
        return false;
    }
}
?>