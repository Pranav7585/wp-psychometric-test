<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_Psycho_Notifications {

    public static function send_result( $result_id ) {
        $result = WP_Psycho_DB::get_result( $result_id );
        if ( ! $result ) {
            return false;
        }

        $uploads   = wp_psycho_uploads();
        $report_url = '';
        if ( $result->pdf_path ) {
            $report_url = $uploads['url'] . basename( $result->pdf_path );
        }

        $site_name   = get_bloginfo( 'name' );
        $admin_email = get_option( 'psycho_admin_email', get_option( 'admin_email' ) );
        $brand_color = get_option( 'psycho_brand_color', '#6c63ff' );

        // Email to participant
        $to      = $result->participant_email;
        $subject = sprintf( __( 'Your Assessment Results — %s', 'wp-psycho' ), $result->test_title );
        $message = self::build_email_html(
            $result->participant_name,
            $result->test_title,
            $result->total_score,
            $result->result_label,
            $result->result_desc,
            $result->recommendation,
            $report_url,
            $brand_color,
            $site_name
        );
        $headers = [ 'Content-Type: text/html; charset=UTF-8' ];
        wp_mail( $to, $subject, $message, $headers );

        // Email to admin
        $admin_subject = sprintf(
            __( 'New Assessment Completed: %s by %s', 'wp-psycho' ),
            $result->test_title,
            $result->participant_name
        );
        $admin_message = self::build_admin_email_html( $result, $report_url, $brand_color, $site_name );
        wp_mail( $admin_email, $admin_subject, $admin_message, $headers );

        // Mark as notified
        global $wpdb;
        $p = WP_Psycho_DB::get_prefix();
        $wpdb->update( "{$p}results", [ 'notified' => 1 ], [ 'id' => $result_id ] );

        return true;
    }

    private static function build_email_html( $name, $test_title, $score, $label, $desc, $recommendation, $report_url, $brand_color, $site_name ) {
        $report_link = $report_url ? '<p style="text-align:center;margin:24px 0;"><a href="' . esc_url( $report_url ) . '" style="background:' . esc_attr( $brand_color ) . ';color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:600;">' . esc_html__( 'Download Your Report', 'wp-psycho' ) . '</a></p>' : '';
        $rec_section = $recommendation ? '<div style="background:#f0f4ff;border-left:4px solid ' . esc_attr( $brand_color ) . ';padding:16px 20px;border-radius:0 8px 8px 0;margin:16px 0;"><strong>' . esc_html__( 'Recommendation:', 'wp-psycho' ) . '</strong><br>' . esc_html( $recommendation ) . '</div>' : '';
        return '<!DOCTYPE html><html><body style="font-family:Arial,sans-serif;background:#f7f8fc;margin:0;padding:20px;">
<div style="max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;">
  <div style="background:' . esc_attr( $brand_color ) . ';padding:32px;text-align:center;color:#fff;">
    <h1 style="margin:0;font-size:22px;">' . esc_html__( 'Your Assessment Results', 'wp-psycho' ) . '</h1>
  </div>
  <div style="padding:32px;">
    <p>' . sprintf( esc_html__( 'Dear %s,', 'wp-psycho' ), esc_html( $name ) ) . '</p>
    <p>' . esc_html__( 'Thank you for completing the assessment. Here are your results:', 'wp-psycho' ) . '</p>
    <div style="background:#f7f8fc;border-radius:10px;padding:20px;text-align:center;margin:20px 0;">
      <div style="font-size:48px;font-weight:800;color:' . esc_attr( $brand_color ) . ';">' . intval( $score ) . '</div>
      <div style="font-size:20px;font-weight:700;margin-top:8px;">' . esc_html( $label ) . '</div>
      ' . ( $desc ? '<p style="color:#718096;margin-top:8px;">' . esc_html( $desc ) . '</p>' : '' ) . '
    </div>
    ' . $rec_section . '
    ' . $report_link . '
    <p style="color:#718096;font-size:13px;">' . sprintf( esc_html__( 'Assessment: %s', 'wp-psycho' ), esc_html( $test_title ) ) . '</p>
  </div>
  <div style="background:#f7f8fc;padding:16px 32px;text-align:center;color:#a0aec0;font-size:12px;">
    ' . esc_html( $site_name ) . '
  </div>
</div>
</body></html>';
    }

    private static function build_admin_email_html( $result, $report_url, $brand_color, $site_name ) {
        $admin_url   = admin_url( 'admin.php?page=psycho-reports' );
        $report_link = $report_url ? '<a href="' . esc_url( $report_url ) . '">' . esc_html__( 'Download Report', 'wp-psycho' ) . '</a>' : '';
        return '<!DOCTYPE html><html><body style="font-family:Arial,sans-serif;background:#f7f8fc;margin:0;padding:20px;">
<div style="max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;">
  <div style="background:' . esc_attr( $brand_color ) . ';padding:24px;text-align:center;color:#fff;">
    <h1 style="margin:0;font-size:20px;">' . esc_html__( 'New Assessment Completed', 'wp-psycho' ) . '</h1>
  </div>
  <div style="padding:32px;">
    <table style="width:100%;border-collapse:collapse;">
      <tr><td style="padding:8px;color:#718096;">' . esc_html__( 'Participant:', 'wp-psycho' ) . '</td><td style="padding:8px;font-weight:600;">' . esc_html( $result->participant_name ) . '</td></tr>
      <tr><td style="padding:8px;color:#718096;">' . esc_html__( 'Email:', 'wp-psycho' ) . '</td><td style="padding:8px;">' . esc_html( $result->participant_email ) . '</td></tr>
      <tr><td style="padding:8px;color:#718096;">' . esc_html__( 'Phone:', 'wp-psycho' ) . '</td><td style="padding:8px;">' . esc_html( $result->participant_phone ) . '</td></tr>
      <tr><td style="padding:8px;color:#718096;">' . esc_html__( 'Assessment:', 'wp-psycho' ) . '</td><td style="padding:8px;">' . esc_html( $result->test_title ) . '</td></tr>
      <tr><td style="padding:8px;color:#718096;">' . esc_html__( 'Score:', 'wp-psycho' ) . '</td><td style="padding:8px;font-weight:700;color:' . esc_attr( $result->result_color ) . ';">' . intval( $result->total_score ) . '</td></tr>
      <tr><td style="padding:8px;color:#718096;">' . esc_html__( 'Result:', 'wp-psycho' ) . '</td><td style="padding:8px;">' . esc_html( $result->result_label ) . '</td></tr>
    </table>
    <p style="margin-top:20px;">' . $report_link . ' &bull; <a href="' . esc_url( $admin_url ) . '">' . esc_html__( 'View All Results', 'wp-psycho' ) . '</a></p>
  </div>
  <div style="background:#f7f8fc;padding:16px 32px;text-align:center;color:#a0aec0;font-size:12px;">' . esc_html( $site_name ) . '</div>
</div>
</body></html>';
    }
}
