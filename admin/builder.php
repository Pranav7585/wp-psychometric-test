<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) return;

global $wpdb;
$p       = WP_Psycho_DB::get_prefix();
$tests   = WP_Psycho_DB::get_tests();
$test_id = intval( $_GET['test_id'] ?? 0 );

// Handle save
if ( isset( $_POST['psycho_save_template'] ) && wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ?? '' ), 'psycho_builder_nonce' ) ) {
    $tmpl_data = [
        'test_id'     => $test_id,
        'header_text' => sanitize_textarea_field( wp_unslash( $_POST['header_text'] ?? '' ) ),
        'footer_text' => sanitize_textarea_field( wp_unslash( $_POST['footer_text'] ?? '' ) ),
        'logo_url'    => esc_url_raw( wp_unslash( $_POST['logo_url'] ?? '' ) ),
        'show_traits' => isset( $_POST['show_traits'] ) ? 1 : 0,
        'show_chart'  => isset( $_POST['show_chart'] ) ? 1 : 0,
        'updated_at'  => current_time( 'mysql' ),
    ];

    $existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$p}report_templates WHERE test_id=%d", $test_id ) );
    if ( $existing ) {
        $wpdb->update( "{$p}report_templates", $tmpl_data, [ 'test_id' => $test_id ] );
    } else {
        $wpdb->insert( "{$p}report_templates", $tmpl_data );
    }
    echo '<div class="notice notice-success"><p>' . esc_html__( 'Template saved.', 'wp-psycho' ) . '</p></div>';
}

$template = $test_id ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$p}report_templates WHERE test_id=%d LIMIT 1", $test_id ) ) : null;
?>
<div class="wrap">
<h1><?php esc_html_e( 'Report Template Builder', 'wp-psycho' ); ?></h1>

<form method="get" style="margin:16px 0;">
    <input type="hidden" name="page" value="psycho-builder">
    <label><?php esc_html_e( 'Select Test:', 'wp-psycho' ); ?></label>
    <select name="test_id" onchange="this.form.submit()">
        <option value=""><?php esc_html_e( '— Select a test —', 'wp-psycho' ); ?></option>
        <?php foreach ( $tests as $t ) : ?>
        <option value="<?php echo intval( $t->id ); ?>" <?php selected( $test_id, $t->id ); ?>><?php echo esc_html( $t->title ); ?></option>
        <?php endforeach; ?>
    </select>
</form>

<?php if ( $test_id ) : ?>
<div class="postbox" style="max-width:700px;">
    <div class="postbox-header"><h2><?php esc_html_e( 'Template Settings', 'wp-psycho' ); ?></h2></div>
    <div class="inside">
    <form method="post">
        <?php wp_nonce_field( 'psycho_builder_nonce' ); ?>
        <table class="form-table">
            <tr><th><?php esc_html_e( 'Header Text', 'wp-psycho' ); ?></th>
                <td><textarea name="header_text" class="widefat" rows="2"><?php echo esc_textarea( $template->header_text ?? ( get_bloginfo( 'name' ) . ' — Psychometric Assessment Report' ) ); ?></textarea>
                <p class="description"><?php esc_html_e( 'Shown at the top of the report as the main heading.', 'wp-psycho' ); ?></p></td></tr>
            <tr><th><?php esc_html_e( 'Footer Text', 'wp-psycho' ); ?></th>
                <td><textarea name="footer_text" class="widefat" rows="2"><?php echo esc_textarea( $template->footer_text ?? 'Thank you for completing this assessment.' ); ?></textarea>
                <p class="description"><?php esc_html_e( 'Message displayed at the bottom of every report for this test.', 'wp-psycho' ); ?></p></td></tr>
            <tr><th><?php esc_html_e( 'Logo URL', 'wp-psycho' ); ?></th>
                <td><input type="url" name="logo_url" class="widefat" value="<?php echo esc_attr( $template->logo_url ?? get_option( 'psycho_logo_url', '' ) ); ?>">
                <p class="description"><?php esc_html_e( 'URL of the logo image shown in the report header.', 'wp-psycho' ); ?></p></td></tr>
            <tr><th><?php esc_html_e( 'Show Trait Breakdown', 'wp-psycho' ); ?></th>
                <td><input type="checkbox" name="show_traits" value="1" <?php checked( isset( $template ) ? $template->show_traits : 1, 1 ); ?>>
                <p class="description"><?php esc_html_e( 'Display a breakdown of scores by trait in the report.', 'wp-psycho' ); ?></p></td></tr>
            <tr><th><?php esc_html_e( 'Show Chart', 'wp-psycho' ); ?></th>
                <td><input type="checkbox" name="show_chart" value="1" <?php checked( isset( $template ) ? $template->show_chart : 1, 1 ); ?>>
                <p class="description"><?php esc_html_e( 'Include a visual chart of trait scores in the report (frontend only).', 'wp-psycho' ); ?></p></td></tr>
        </table>
        <p><button type="submit" name="psycho_save_template" class="button button-primary"><?php esc_html_e( 'Save Template', 'wp-psycho' ); ?></button></p>
    </form>
    </div>
</div>
<?php else : ?>
<p><?php esc_html_e( 'Please select a test to configure its report template.', 'wp-psycho' ); ?></p>
<?php endif; ?>
</div>
