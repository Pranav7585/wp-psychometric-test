<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_Psycho_Frontend {

    public function __construct() {
        add_shortcode( 'wp_psycho_portal', [ $this, 'render_portal' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
    }

    public function enqueue() {
        global $post;
        if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'wp_psycho_portal' ) ) {
            return;
        }

        wp_enqueue_style(
            'wp-psycho-style',
            WP_PSYCHO_URL . 'assets/css/style.css',
            [],
            WP_PSYCHO_VERSION
        );

        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
            [],
            '4.4.0',
            true
        );

        wp_enqueue_script(
            'wp-psycho-app',
            WP_PSYCHO_URL . 'assets/js/app.js',
            [ 'jquery', 'chartjs' ],
            WP_PSYCHO_VERSION,
            true
        );

        $session_key = WP_Psycho_Auth::get_session();
        $participant = $session_key ? WP_Psycho_DB::get_participant( $session_key ) : null;

        wp_localize_script( 'wp-psycho-app', 'PsychoApp', [
            'ajax_url'    => admin_url( 'admin-ajax.php' ),
            'nonce'       => wp_create_nonce( 'psycho_nonce' ),
            'brand_color' => get_option( 'psycho_brand_color', '#6c63ff' ),
            'session_key' => $session_key,
            'participant' => $participant ? [
                'name'  => $participant->name,
                'email' => $participant->email,
            ] : null,
            'result_base' => home_url( '/' ),
            'i18n'        => [
                'confirm_submit'   => __( 'Some questions are unanswered. Submit anyway?', 'wp-psycho' ),
                'submitting'       => __( 'Submitting…', 'wp-psycho' ),
                'error_generic'    => __( 'An error occurred. Please try again.', 'wp-psycho' ),
                'time_up'          => __( "Time's up! Submitting your answers…", 'wp-psycho' ),
            ],
        ] );
    }

    public function render_portal() {
        ob_start();
        ?>
        <div class="psycho-portal" id="psycho-portal">
            <div id="psycho-step-entry" class="psycho-step">
                <?php include WP_PSYCHO_PATH . 'templates/entry-form.php'; ?>
            </div>
            <div id="psycho-step-tests" class="psycho-step" style="display:none;">
                <?php include WP_PSYCHO_PATH . 'templates/test-list.php'; ?>
            </div>
            <div id="psycho-step-exam" class="psycho-step" style="display:none;">
                <?php include WP_PSYCHO_PATH . 'templates/take-test.php'; ?>
            </div>
            <div id="psycho-step-results" class="psycho-step" style="display:none;">
                <?php include WP_PSYCHO_PATH . 'templates/results.php'; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
