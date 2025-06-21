<?php

/**
 * Plugin Name: Webpoint youtube video tabs
 * Description: Custom video tab section with YouTube integration.
 * Version: 1.0.0
 * Author: Hawana Tamang
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WVT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WVT_PLUGIN_URL', plugin_dir_url(__FILE__));


require_once WVT_PLUGIN_DIR . 'includes/class-cpt.php';
$cpt = new WVT_CPT();

class WebpointVideoTabs
{
    public function __construct()
    {
        // Include other necessary classes early (except CPT already included above)
        require_once WVT_PLUGIN_DIR . 'includes/class-shortcode.php';
        require_once WVT_PLUGIN_DIR . 'includes/class-ajax-handler.php';
        

        // Instantiate these classes here
        new WVT_Ajax_Handler();
        new WVT_Shortcode();

        // Hook scripts enqueue
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }

    public function enqueue_scripts()
    {
        wp_enqueue_style('wvt-frontend', WVT_PLUGIN_URL . 'assets/css/frontend.css', array(), '1.0.0');

        wp_enqueue_script('isotope', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.isotope/3.0.6/isotope.pkgd.min.js', array('jquery'), '3.0.6', true);
        wp_enqueue_script('magnific-popup', 'https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/jquery.magnific-popup.min.js', array('jquery'), '1.1.0', true);
        wp_enqueue_script('wvt-frontend', WVT_PLUGIN_URL . 'assets/js/frontend.js', array('jquery', 'isotope', 'magnific-popup'), '1.0.0', true);

        wp_enqueue_style('magnific-popup', 'https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/magnific-popup.min.css', array(), '1.1.0');

        wp_localize_script('wvt-frontend', 'wvt_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wvt_nonce'),
        ));
    }

    public function admin_enqueue_scripts()
    {
        wp_enqueue_style('wvt-admin', WVT_PLUGIN_URL . 'assets/css/admin.css', array(), '1.0.0');
    }

    public function activate()
    {
        global $cpt;
        $cpt->register_post_type();
        $cpt->register_taxonomy();
        flush_rewrite_rules();
    }

    public function deactivate()
    {
        flush_rewrite_rules();
    }
}


$wvt_plugin = new WebpointVideoTabs();

// Activation and deactivation hooks
register_activation_hook(__FILE__, function () {
    $cpt = new WVT_CPT();
    $cpt->register_post_type();
    $cpt->register_taxonomy();
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});
