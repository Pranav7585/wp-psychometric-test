<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_Psycho_Notifications {

    public static function send_result( $result_id ) {
        $result = WP_Psycho_DB::get_result( $result_id );
        if ( ! $result ) return;

        $uploads  = wp_psycho_uploads();
        $site_name = get_bloginfo( 'name' );
        $report_url = '';
        if ( ! empty( $result->pdf_path ) ) {
            $report_url = $uploads['url'] . basename( $result->pdf_path );
        } else {
            $report_url = home_url( '/?psycho_pdf=' . $result_id );
        }

        // Email to participant
        $to_participant  = $result->participant_email;
        $subject_participant = sprintf( __( 'Your %s Results — %s', 'wp-psycho' ), $result->test_title, $site_name );
        $body_participant = sprintf(
            __( "Hi %s,\n\nThank you for completing the \"%s\" assessment.\n\nYour Score: %s\nResult: %s\n\n%s\n\nDownload your report:\n%s\n\nBest regards,\n%s", 'wp-psycho' ),
            $result->participant_name,
            $result->test_title,
            $result->total_score,
            $result->result_label,
            $result->result_desc,
            $report_url,
            $site_name
        );

        wp_mail( $to_participant, $subject_participant, $body_participant );

        // Email to admin
        $admin_email    = get_option( 'psycho_admin_email', get_option( 'admin_email' ) );
        $subject_admin  = sprintf( __( 'New Assessment Completed — %s', 'wp-psycho' ), $site_name );
        $body_admin     = sprintf(
            __( "A participant has completed an assessment.\n\nParticipant: %s\nEmail: %s\nPhone: %s\nTest: %s\nScore: %s\nResult: %s\n\nView report:\n%s", 'wp-psycho' ),
            $result->participant_name,
            $result->participant_email,
            $result->participant_phone,
            $result->test_title,
            $result->total_score,
            $result->result_label,
            $report_url
        );

        wp_mail( $admin_email, $subject_admin, $body_admin );

        // Mark as notified
        global $wpdb;
        $p = WP_Psycho_DB::get_prefix();
        $wpdb->update( $p . 'results', [ 'notified' => 1 ], [ 'id' => $result_id ] );
    }
}
