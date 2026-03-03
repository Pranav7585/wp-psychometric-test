<?php
/**
 * Plugin Name: WP Psychometric Test Pro
 * Description: Enterprise psychometric platform. No login required. Guest entry via name/email/phone + test passkey. Admin backend with scoring, PDF reports, email notifications.
 * Version:     2.0.0
 * License:     GPL-2.0+
 * Text Domain: wp-psycho
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WP_PSYCHO_VERSION', '2.0.0' );
define( 'WP_PSYCHO_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP_PSYCHO_URL', plugin_dir_url( __FILE__ ) );

function wp_psycho_uploads() {
    $u = wp_upload_dir();
    return [
        'path' => $u['basedir'] . '/psychometric-reports/',
        'url'  => $u['baseurl'] . '/psychometric-reports/',
    ];
}

foreach ( [
    'class-db', 'class-activator', 'class-auth',
    'class-admin-tests', 'class-admin-questions', 'class-admin-scoring',
    'class-admin-report-builder', 'class-admin-reports', 'class-admin-settings',
    'class-scoring-engine', 'class-report-generator', 'class-pdf',
    'class-notifications', 'class-frontend',
] as $f ) {
    require_once WP_PSYCHO_PATH . "includes/{$f}.php";
}

register_activation_hook( __FILE__, [ 'WP_Psycho_Activator', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'WP_Psycho_Activator', 'deactivate' ] );

add_action( 'plugins_loaded', function () {
    new WP_Psycho_Auth();
    new WP_Psycho_Admin_Tests();
    new WP_Psycho_Admin_Questions();
    new WP_Psycho_Admin_Scoring();
    new WP_Psycho_Admin_Report_Builder();
    new WP_Psycho_Admin_Reports();
    new WP_Psycho_Admin_Settings();
    new WP_Psycho_Frontend();
} );
