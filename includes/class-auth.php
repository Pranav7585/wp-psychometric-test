<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_Psycho_Auth {

    public function __construct() {
        add_action( 'wp_ajax_nopriv_psycho_register_participant', [ $this, 'register_participant' ] );
        add_action( 'wp_ajax_psycho_register_participant',        [ $this, 'register_participant' ] );
        add_action( 'wp_ajax_nopriv_psycho_verify_passkey',       [ $this, 'verify_passkey' ] );
        add_action( 'wp_ajax_psycho_verify_passkey',              [ $this, 'verify_passkey' ] );
    }

    public function register_participant() {
        check_ajax_referer( 'psycho_nonce', 'nonce' );

        $name  = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
        $email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
        $phone = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );

        if ( empty( $name ) || empty( $email ) || empty( $phone ) ) {
            wp_send_json_error( [ 'msg' => __( 'All fields are required.', 'wp-psycho' ) ] );
        }

        if ( ! is_email( $email ) ) {
            wp_send_json_error( [ 'msg' => __( 'Please enter a valid email address.', 'wp-psycho' ) ] );
        }

        global $wpdb;
        $p           = WP_Psycho_DB::get_prefix();
        $session_key = wp_generate_password( 40, false );

        $wpdb->insert( $p . 'participants', [
            'name'        => $name,
            'email'       => $email,
            'phone'       => $phone,
            'session_key' => $session_key,
            'created_at'  => current_time( 'mysql' ),
        ] );

        if ( ! $wpdb->insert_id ) {
            wp_send_json_error( [ 'msg' => __( 'Could not register. Please try again.', 'wp-psycho' ) ] );
        }

        setcookie( 'psycho_session', $session_key, [
            'expires'  => time() + DAY_IN_SECONDS * 7,
            'path'     => COOKIEPATH,
            'domain'   => COOKIE_DOMAIN,
            'secure'   => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ] );

        wp_send_json_success( [
            'session_key' => $session_key,
            'name'        => $name,
        ] );
    }

    public function verify_passkey() {
        check_ajax_referer( 'psycho_nonce', 'nonce' );

        $session_key = self::get_session();
        $test_id     = absint( $_POST['test_id'] ?? 0 );
        $passkey     = sanitize_text_field( wp_unslash( $_POST['passkey'] ?? '' ) );

        if ( ! $session_key ) {
            wp_send_json_error( [ 'msg' => __( 'Session expired. Please refresh.', 'wp-psycho' ) ] );
        }

        $test = WP_Psycho_DB::get_test( $test_id );
        if ( ! $test ) {
            wp_send_json_error( [ 'msg' => __( 'Test not found.', 'wp-psycho' ) ] );
        }

        if ( ! hash_equals( $test->passkey, $passkey ) ) {
            wp_send_json_error( [ 'msg' => __( 'Incorrect passkey. Please try again.', 'wp-psycho' ) ] );
        }

        if ( $test->max_attempts > 0 ) {
            global $wpdb;
            $p           = WP_Psycho_DB::get_prefix();
            $participant = WP_Psycho_DB::get_participant( $session_key );
            if ( $participant ) {
                $attempts = (int) $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$p}attempts WHERE participant_id = %d AND test_id = %d AND status = 'completed'",
                    $participant->id, $test_id
                ) );
                if ( $attempts >= $test->max_attempts ) {
                    wp_send_json_error( [ 'msg' => __( 'You have reached the maximum number of attempts for this test.', 'wp-psycho' ) ] );
                }
            }
        }

        wp_send_json_success( [ 'msg' => __( 'Passkey verified.', 'wp-psycho' ) ] );
    }

    public static function get_session() {
        if ( isset( $_COOKIE['psycho_session'] ) ) {
            return sanitize_text_field( wp_unslash( $_COOKIE['psycho_session'] ) );
        }
        return '';
    }
}
