<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_Psycho_PDF {

    public function __construct() {
        add_action( 'init', [ $this, 'handle_stream' ] );
    }

    public static function generate( $result_id ) {
        $result = WP_Psycho_DB::get_result( $result_id );
        if ( ! $result ) {
            return false;
        }

        $uploads = wp_psycho_uploads();
        if ( ! file_exists( $uploads['path'] ) ) {
            wp_mkdir_p( $uploads['path'] );
        }

        $trait_scores = [];
        if ( $result->trait_scores ) {
            $decoded = json_decode( $result->trait_scores, true );
            if ( is_array( $decoded ) ) {
                $trait_scores = $decoded;
            }
        }

        // Load template settings
        global $wpdb;
        $p        = WP_Psycho_DB::get_prefix();
        $template = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$p}report_templates WHERE test_id = %d LIMIT 1",
            $result->test_id
        ) );

        $header_text  = $template ? $template->header_text  : get_bloginfo( 'name' ) . ' — Psychometric Assessment Report';
        $footer_text  = $template ? $template->footer_text  : 'Thank you for completing this assessment.';
        $show_traits  = $template ? (bool) $template->show_traits : true;
        $logo_url     = $template && $template->logo_url ? $template->logo_url : get_option( 'psycho_logo_url', '' );
        $brand_color  = get_option( 'psycho_brand_color', '#6c63ff' );
        $date         = date_i18n( get_option( 'date_format' ), strtotime( $result->created_at ) );

        ob_start();
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo esc_html( $result->test_title ); ?> — Report</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', Arial, sans-serif; background: #f7f8fc; color: #2d3748; }
  .report-wrap { max-width: 800px; margin: 0 auto; background: #fff; }
  .report-header { background: <?php echo esc_attr( $brand_color ); ?>; color: #fff; padding: 40px; text-align: center; }
  .report-header img { max-height: 60px; margin-bottom: 16px; }
  .report-header h1 { font-size: 26px; margin-bottom: 8px; }
  .report-header p { opacity: 0.85; font-size: 15px; }
  .report-body { padding: 40px; }
  .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 32px; }
  .info-card { background: #f7f8fc; border-radius: 10px; padding: 16px; }
  .info-card .label { font-size: 12px; color: #718096; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
  .info-card .value { font-size: 16px; font-weight: 600; color: #2d3748; }
  .score-section { text-align: center; margin: 32px 0; }
  .score-circle { display: inline-block; width: 120px; height: 120px; border-radius: 50%;
    background: <?php echo esc_attr( $result->result_color ); ?>; color: #fff;
    line-height: 120px; font-size: 36px; font-weight: 800; margin-bottom: 16px; }
  .result-label { font-size: 24px; font-weight: 700; color: <?php echo esc_attr( $result->result_color ); ?>; margin-bottom: 8px; }
  .result-desc { font-size: 15px; color: #4a5568; max-width: 560px; margin: 0 auto 16px; line-height: 1.7; }
  .recommendation-box { background: #f0f4ff; border-left: 4px solid <?php echo esc_attr( $brand_color ); ?>;
    padding: 16px 20px; border-radius: 0 8px 8px 0; margin: 24px 0; }
  .recommendation-box h3 { font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;
    color: <?php echo esc_attr( $brand_color ); ?>; margin-bottom: 8px; }
  .recommendation-box p { font-size: 15px; color: #4a5568; line-height: 1.7; }
  .traits-section { margin: 32px 0; }
  .traits-section h2 { font-size: 18px; font-weight: 700; margin-bottom: 20px; color: #2d3748; }
  .trait-row { margin-bottom: 16px; }
  .trait-row .trait-name { font-size: 14px; font-weight: 600; margin-bottom: 6px; display: flex; justify-content: space-between; }
  .trait-bar-bg { background: #e2e8f0; border-radius: 8px; height: 10px; }
  .trait-bar-fill { height: 10px; border-radius: 8px; background: <?php echo esc_attr( $brand_color ); ?>; }
  .report-footer { background: #f7f8fc; padding: 24px 40px; text-align: center; color: #718096; font-size: 13px; border-top: 1px solid #e2e8f0; }
</style>
</head>
<body>
<div class="report-wrap">
  <div class="report-header">
    <?php if ( $logo_url ) : ?>
      <img src="<?php echo esc_url( $logo_url ); ?>" alt="Logo">
    <?php endif; ?>
    <h1><?php echo esc_html( $header_text ); ?></h1>
    <p><?php echo esc_html( $date ); ?></p>
  </div>
  <div class="report-body">
    <div class="info-grid">
      <div class="info-card">
        <div class="label"><?php esc_html_e( 'Participant', 'wp-psycho' ); ?></div>
        <div class="value"><?php echo esc_html( $result->participant_name ); ?></div>
      </div>
      <div class="info-card">
        <div class="label"><?php esc_html_e( 'Assessment', 'wp-psycho' ); ?></div>
        <div class="value"><?php echo esc_html( $result->test_title ); ?></div>
      </div>
      <div class="info-card">
        <div class="label"><?php esc_html_e( 'Email', 'wp-psycho' ); ?></div>
        <div class="value"><?php echo esc_html( $result->participant_email ); ?></div>
      </div>
      <div class="info-card">
        <div class="label"><?php esc_html_e( 'Date', 'wp-psycho' ); ?></div>
        <div class="value"><?php echo esc_html( $date ); ?></div>
      </div>
    </div>

    <div class="score-section">
      <div class="score-circle"><?php echo intval( $result->total_score ); ?></div>
      <div class="result-label"><?php echo esc_html( $result->result_label ); ?></div>
      <?php if ( $result->result_desc ) : ?>
        <div class="result-desc"><?php echo esc_html( $result->result_desc ); ?></div>
      <?php endif; ?>
    </div>

    <?php if ( $result->recommendation ) : ?>
    <div class="recommendation-box">
      <h3><?php esc_html_e( 'Recommendation', 'wp-psycho' ); ?></h3>
      <p><?php echo esc_html( $result->recommendation ); ?></p>
    </div>
    <?php endif; ?>

    <?php if ( $show_traits && ! empty( $trait_scores ) ) :
        $max_trait = max( array_values( $trait_scores ) );
        $max_trait = $max_trait > 0 ? $max_trait : 1;
    ?>
    <div class="traits-section">
      <h2><?php esc_html_e( 'Trait Breakdown', 'wp-psycho' ); ?></h2>
      <?php foreach ( $trait_scores as $trait => $score ) :
          $pct = min( 100, round( $score / $max_trait * 100 ) );
      ?>
      <div class="trait-row">
        <div class="trait-name">
          <span><?php echo esc_html( $trait ); ?></span>
          <span><?php echo intval( $score ); ?></span>
        </div>
        <div class="trait-bar-bg">
          <div class="trait-bar-fill" style="width:<?php echo intval( $pct ); ?>%"></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
  <div class="report-footer">
    <p><?php echo esc_html( $footer_text ); ?></p>
    <p style="margin-top:8px;"><?php echo esc_html( get_bloginfo( 'name' ) ); ?> &bull; <?php echo esc_html( get_bloginfo( 'url' ) ); ?></p>
  </div>
</div>
</body>
</html>
        <?php
        $html     = ob_get_clean();
        $filename = 'report-' . $result_id . '-' . time() . '.html';
        $filepath = $uploads['path'] . $filename;

        global $wp_filesystem;
        if ( empty( $wp_filesystem ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }
        $wp_filesystem->put_contents( $filepath, $html, FS_CHMOD_FILE );

        // Update result with pdf_path
        global $wpdb;
        $p = WP_Psycho_DB::get_prefix();
        $wpdb->update( "{$p}results", [ 'pdf_path' => $filename ], [ 'id' => $result_id ] );

        return $uploads['url'] . $filename;
    }

    public function handle_stream() {
        if ( ! isset( $_GET['psycho_pdf'] ) ) {
            return;
        }
        $result_id = intval( $_GET['psycho_pdf'] );
        if ( ! $result_id ) {
            return;
        }

        $result = WP_Psycho_DB::get_result( $result_id );
        if ( ! $result || ! $result->pdf_path ) {
            wp_die( esc_html__( 'Report not found.', 'wp-psycho' ) );
        }

        $uploads = wp_psycho_uploads();
        $url     = $uploads['url'] . basename( $result->pdf_path );
        wp_redirect( esc_url_raw( $url ) );
        exit;
    }
}

new WP_Psycho_PDF();
