<?php
/**
 * Custom Post Type and Taxonomy Handler
 */

class WVT_CPT
{
    public function __construct()
    {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomy'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_action('admin_menu', array($this, 'add_help_submenu')); 
    }

    public function register_post_type()
    {
        $labels = array(
            'name' => 'YouTube Videos',
            'singular_name' => 'YouTube Video',
            'menu_name' => 'YouTube Videos',
            'add_new' => 'Add New Video',
            'add_new_item' => 'Add New YouTube Video',
            'edit_item' => 'Edit YouTube Video',
            'new_item' => 'New YouTube Video',
            'view_item' => 'View YouTube Video',
            'search_items' => 'Search YouTube Videos',
            'not_found' => 'No YouTube videos found',
            'not_found_in_trash' => 'No YouTube videos found in trash'
        );
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'youtube-videos'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'supports' => array('title', 'thumbnail','excerpt'),
            'menu_position' => 20,
            'menu_icon' => 'dashicons-video-alt3',
            'taxonomies' => array('video_category'),
        );

        register_post_type('youtube_videos', $args);
    }

    public function register_taxonomy()
    {
        $labels = array(
            'name' => 'Video Categories',
            'singular_name' => 'Video Category',
            'menu_name' => 'Categories',
            'all_items' => 'All Categories',
            'edit_item' => 'Edit Category',
            'view_item' => 'View Category',
            'update_item' => 'Update Category',
            'add_new_item' => 'Add New Category',
            'new_item_name' => 'New Category Name',
            'search_items' => 'Search Categories',
            'not_found' => 'No categories found',
        );
        $args = array(
            'labels' => $labels,
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
            'rewrite' => array('slug' => 'video-category'),
        );

        register_taxonomy('video_category', array('youtube_videos'), $args);
    }

    public function add_meta_boxes()
    {
        add_meta_box(
            'youtube_video_details',
            'YouTube Video Details',
            array($this, 'youtube_video_meta_box'),
            'youtube_videos',
            'normal',
            'high'
        );
    }

    public function add_help_submenu(){
        add_submenu_page(
            'edit.php?post_type=youtube_videos',
            'Shortcode Help',
            'Help',
            'manage_options',
            'youtube_videos_help',
            array($this,'render_help_page')
        );
    }

    public function youtube_video_meta_box($post)
    {
        wp_nonce_field('youtube_video_meta_box', 'youtube_video_meta_box_nonce');

        $youtube_url = get_post_meta($post->ID, '_youtube_url', true);
        $pastor_name = get_post_meta($post->ID, '_pastor_name', true);
        $uploaded_date = get_post_meta($post->ID, '_uploaded_date', true);

        ?>
        <table class="form-table">
            <tr>
                <th><label for="youtube_url">YouTube URL</label></th>
                <td>
                    <input type="url" id="youtube_url" name="youtube_url" value="<?php echo esc_attr($youtube_url); ?>" class="regular-text" placeholder="https://www.youtube.com/watch?v=VIDEO_ID" />
                    <p class="description">Enter the full YouTube URL</p>
                </td>
            </tr>
            <tr>
                <th><label for="pastor_name">Pastor Name</label></th>
                <td>
                    <input type="text" id="pastor_name" name="pastor_name" value="<?php echo esc_attr($pastor_name); ?>" class="regular-text" placeholder="Pastor's full name" />
                    <p class="description">Name of the pastor</p>
                </td>
            </tr>
            <tr>
                <th><label for="uploaded_date">Video Uploaded Date</label></th>
                <td>
                    <input type="date" id="uploaded_date" name="uploaded_date" value="<?php echo esc_attr($uploaded_date); ?>" />
                    <p class="description">Date when the video was uploaded</p>
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_meta_boxes($post_id)
    {
        if (!isset($_POST['youtube_video_meta_box_nonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['youtube_video_meta_box_nonce'], 'youtube_video_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (isset($_POST['post_type']) && 'youtube_videos' == $_POST['post_type']) {
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
        } else {
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
        }

        if (isset($_POST['youtube_url'])) {
            update_post_meta($post_id, '_youtube_url', sanitize_text_field($_POST['youtube_url']));
        }

        if (isset($_POST['pastor_name'])) {
            update_post_meta($post_id, '_pastor_name', sanitize_text_field($_POST['pastor_name']));
        }

        if (isset($_POST['uploaded_date'])) {
            update_post_meta($post_id, '_uploaded_date', sanitize_text_field($_POST['uploaded_date']));
        }
    }


    public function render_help_page()
    {
        echo '<div class="wrap">';
        echo '<h1>YouTube Videos – How to Use</h1>';

        echo '<p>This plugin automatically pulls all categories created under "YouTube Videos → Categories" and shows them as tabs in the frontend.</p>';

        echo '<h2>Shortcode</h2>';
        echo '<p>Use the shortcode below to show the video section:</p>';
        echo '<code>[webpoint_video_tabs]</code>';

        echo '<h2>Default Behavior</h2>';
        echo '<ul>';
        echo '<li>All categories are listed automatically as tabs.</li>';
        echo '<li>3 videos are shown by default.</li>';
        echo '<li>Clicking "Load More" loads 6 additional videos.</li>';
        echo '</ul>';

        echo '<h2>Optional Shortcode Attributes</h2>';
        echo '<ul>';
        echo '<li><code>all_text</code> – Change the label of the "All" tab (e.g. <code>all_text="All Sermons"</code>)</li>';
        echo '</ul>';

        echo '<h2>Example</h2>';
        echo '<code>[webpoint_video_tabs all_text="All Videos"]</code>';

        echo '<h2>How to Add Categories</h2>';
        echo '<p>Go to <strong>YouTube Videos → Categories</strong> and create your video categories. These categories will automatically appear as filter tabs on the front end.</p>';

        echo '</div>';
    }



   
}
