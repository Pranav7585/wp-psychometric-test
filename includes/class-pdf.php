<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_Psycho_PDF {

    public static function generate( $result_id ) {
        $result = WP_Psycho_DB::get_result( $result_id );
        if ( ! $result ) return '';

        $uploads = wp_psycho_uploads();
        if ( ! file_exists( $uploads['path'] ) ) {
            wp_mkdir_p( $uploads['path'] );
        }

        $trait_scores = [];
        if ( ! empty( $result->trait_scores ) ) {
            $trait_scores = json_decode( $result->trait_scores, true ) ?: [];
        }

        // Get report template
        global $wpdb;
        $p        = WP_Psycho_DB::get_prefix();
        $template = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$p}report_templates WHERE test_id = %d", $result->test_id
        ) );

        $header_text  = $template ? $template->header_text : get_option( 'psycho_portal_title', 'Psychometric Assessment Report' );
        $footer_text  = $template ? $template->footer_text : 'Thank you for completing the assessment.';
        $show_traits  = $template ? (bool) $template->show_traits : true;
        $logo_url     = $template && $template->logo_url ? $template->logo_url : get_option( 'psycho_logo_url', '' );
        $brand_color  = get_option( 'psycho_brand_color', '#6c63ff' );

        $score_pct = 0;
        $max_score = 0;
        $questions = WP_Psycho_DB::get_questions( $result->test_id );
        foreach ( $questions as $q ) {
            $options = WP_Psycho_DB::get_options( $q->id );
            if ( $options ) {
                $scores = array_map( function( $o ) { return (int) $o->score; }, $options );
                $max_score += max( $scores );
            }
        }
        if ( $max_score > 0 ) {
            $score_pct = round( ( $result->total_score / $max_score ) * 100 );
        }

        ob_start();
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo esc_html( $result->test_title ); ?> — Report</title>
<style>
  body { font-family: 'Segoe UI', Arial, sans-serif; background: #f7f8fc; margin: 0; padding: 0; color: #2d3748; }
  .report-wrap { max-width: 800px; margin: 0 auto; background: #fff; box-shadow: 0 8px 32px rgba(0,0,0,.10); }
  .report-header { background: <?php echo esc_attr( $brand_color ); ?>; color: #fff; padding: 36px 48px; }
  .report-header img { max-height: 60px; margin-bottom: 12px; display: block; }
  .report-header h1 { margin: 0 0 6px; font-size: 2rem; }
  .report-header p { margin: 0; opacity: .88; font-size: 1rem; }
  .report-body { padding: 40px 48px; }
  .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 32px; margin-bottom: 32px; }
  .info-item label { font-size: .8rem; font-weight: 600; text-transform: uppercase; color: #718096; display: block; margin-bottom: 2px; }
  .info-item span { font-size: 1rem; font-weight: 500; }
  .score-section { text-align: center; margin: 32px 0; }
  .score-circle { display: inline-flex; flex-direction: column; align-items: center; justify-content: center; width: 140px; height: 140px; border-radius: 50%; background: <?php echo esc_attr( $brand_color ); ?>; color: #fff; margin: 0 auto; }
  .score-circle .score-num { font-size: 2.8rem; font-weight: 800; line-height: 1; }
  .score-circle .score-lbl { font-size: .8rem; opacity: .9; }
  .result-badge { display: inline-block; background: <?php echo esc_attr( $result->result_color ); ?>; color: #fff; padding: 6px 18px; border-radius: 20px; font-weight: 700; font-size: 1.1rem; margin-top: 12px; }
  .result-desc { background: #f7f8fc; border-left: 4px solid <?php echo esc_attr( $brand_color ); ?>; padding: 16px 20px; border-radius: 0 8px 8px 0; margin: 24px 0; }
  .recommendation { background: #fff8e1; border-left: 4px solid #ffc107; padding: 16px 20px; border-radius: 0 8px 8px 0; margin: 16px 0; }
  .recommendation h3 { margin: 0 0 8px; color: #e65100; }
  .traits-section h2 { font-size: 1.2rem; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 16px; }
  .trait-row { margin-bottom: 12px; }
  .trait-row .trait-name { font-weight: 600; margin-bottom: 4px; font-size: .9rem; }
  .trait-bar-bg { background: #e2e8f0; border-radius: 6px; height: 12px; overflow: hidden; }
  .trait-bar-fill { background: <?php echo esc_attr( $brand_color ); ?>; height: 100%; border-radius: 6px; }
  .trait-score-text { font-size: .8rem; color: #718096; margin-top: 2px; }
  .report-footer { background: #f7f8fc; padding: 20px 48px; text-align: center; color: #718096; font-size: .85rem; border-top: 1px solid #e2e8f0; }
</style>
</head>
<body>
<div class="report-wrap">
  <div class="report-header">
    <?php if ( $logo_url ): ?>
      <img src="<?php echo esc_url( $logo_url ); ?>" alt="Logo">
    <?php endif; ?>
    <h1><?php echo esc_html( $header_text ); ?></h1>
    <p><?php echo esc_html( $result->test_title ); ?></p>
  </div>

  <div class="report-body">
    <div class="info-grid">
      <div class="info-item"><label><?php esc_html_e( 'Participant', 'wp-psycho' ); ?></label><span><?php echo esc_html( $result->participant_name ); ?></span></div>
      <div class="info-item"><label><?php esc_html_e( 'Email', 'wp-psycho' ); ?></label><span><?php echo esc_html( $result->participant_email ); ?></span></div>
      <div class="info-item"><label><?php esc_html_e( 'Phone', 'wp-psycho' ); ?></label><span><?php echo esc_html( $result->participant_phone ); ?></span></div>
      <div class="info-item"><label><?php esc_html_e( 'Date', 'wp-psycho' ); ?></label><span><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $result->created_at ) ) ); ?></span></div>
    </div>

    <div class="score-section">
      <div class="score-circle">
        <div class="score-num"><?php echo esc_html( $result->total_score ); ?></div>
        <div class="score-lbl"><?php echo $max_score ? esc_html__( 'out of', 'wp-psycho' ) . ' ' . esc_html( $max_score ) : ''; ?></div>
      </div>
      <br>
      <div class="result-badge"><?php echo esc_html( $result->result_icon . ' ' . $result->result_label ); ?></div>
    </div>

    <?php if ( ! empty( $result->result_desc ) ): ?>
    <div class="result-desc"><?php echo nl2br( esc_html( $result->result_desc ) ); ?></div>
    <?php endif; ?>

    <?php if ( ! empty( $result->recommendation ) ): ?>
    <div class="recommendation">
      <h3>💡 <?php esc_html_e( 'Recommendation', 'wp-psycho' ); ?></h3>
      <?php echo nl2br( esc_html( $result->recommendation ) ); ?>
    </div>
    <?php endif; ?>

    <?php if ( $show_traits && ! empty( $trait_scores ) ): ?>
    <div class="traits-section">
      <h2><?php esc_html_e( 'Trait Breakdown', 'wp-psycho' ); ?></h2>
      <?php
        $trait_max = max( array_values( $trait_scores ) );
        foreach ( $trait_scores as $trait => $score ):
          $pct = $trait_max > 0 ? round( ( $score / $trait_max ) * 100 ) : 0;
      ?>
      <div class="trait-row">
        <div class="trait-name"><?php echo esc_html( $trait ); ?></div>
        <div class="trait-bar-bg"><div class="trait-bar-fill" style="width:<?php echo esc_attr( $pct ); ?>%"></div></div>
        <div class="trait-score-text"><?php echo esc_html( $score ); ?> pts (<?php echo esc_html( $pct ); ?>%)</div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <div class="report-footer">
    <p><?php echo esc_html( $footer_text ); ?></p>
    <p><?php echo esc_html__( 'Generated on', 'wp-psycho' ); ?> <?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ); ?></p>
  </div>
</div>
</body>
</html>
        <?php
        $html     = ob_get_clean();
        $filename = 'report-' . $result_id . '-' . time() . '.html';
        $filepath = $uploads['path'] . $filename;

        if ( ! is_writable( $uploads['path'] ) ) {
            return '';
        }

        $written = file_put_contents( $filepath, $html );
        if ( false === $written ) {
            return '';
        }

        // Update result with pdf_path (we store HTML path in pdf_path field)
        global $wpdb;
        $p = WP_Psycho_DB::get_prefix();
        $wpdb->update( $p . 'results', [ 'pdf_path' => $filepath ], [ 'id' => $result_id ] );

        return $uploads['url'] . $filename;
    }

    public static function stream( $result_id ) {
        $result = WP_Psycho_DB::get_result( $result_id );
        if ( ! $result || empty( $result->pdf_path ) ) {
            wp_die( __( 'Report not found.', 'wp-psycho' ) );
        }

        $uploads = wp_psycho_uploads();
        $url     = $uploads['url'] . basename( $result->pdf_path );
        wp_redirect( $url );
        exit;
    }
}

add_action( 'init', function () {
    if ( isset( $_GET['psycho_pdf'] ) ) {
        $result_id = absint( $_GET['psycho_pdf'] );
        if ( $result_id ) {
            WP_Psycho_PDF::stream( $result_id );
        }
    }
} );
