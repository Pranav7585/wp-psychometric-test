<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) return;

global $wpdb;
$p       = WP_Psycho_DB::get_prefix();
$tests   = WP_Psycho_DB::get_tests();
$test_id = isset( $_GET['test_id'] ) ? absint( $_GET['test_id'] ) : ( $tests ? $tests[0]->id : 0 );
$test    = $test_id ? WP_Psycho_DB::get_test( $test_id ) : null;

// Handle save
if ( isset( $_POST['psycho_save_builder'] ) && isset( $_POST['_wpnonce'] ) ) {
    if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'psycho_builder_nonce' ) ) {
        $data = [
            'test_id'     => $test_id,
            'header_text' => sanitize_textarea_field( wp_unslash( $_POST['header_text'] ?? '' ) ),
            'footer_text' => sanitize_textarea_field( wp_unslash( $_POST['footer_text'] ?? '' ) ),
            'logo_url'    => esc_url_raw( wp_unslash( $_POST['logo_url'] ?? '' ) ),
            'show_traits' => isset( $_POST['show_traits'] ) ? 1 : 0,
            'show_chart'  => isset( $_POST['show_chart'] )  ? 1 : 0,
        ];

        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$p}report_templates WHERE test_id = %d", $test_id
        ) );

        if ( $existing ) {
            $wpdb->update( $p . 'report_templates', $data, [ 'test_id' => $test_id ] );
        } else {
            $wpdb->insert( $p . 'report_templates', $data );
        }
        wp_redirect( admin_url( 'admin.php?page=psycho-builder&test_id=' . $test_id . '&saved=1' ) );
        exit;
    }
}

$template = $test_id ? $wpdb->get_row( $wpdb->prepare(
    "SELECT * FROM {$p}report_templates WHERE test_id = %d", $test_id
) ) : null;
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Report Template Builder', 'wp-psycho' ); ?></h1>

    <?php if ( isset( $_GET['saved'] ) ): ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Template saved.', 'wp-psycho' ); ?></p></div>
    <?php endif; ?>

    <!-- Test selector -->
    <form method="get" style="margin-bottom:20px;">
        <input type="hidden" name="page" value="psycho-builder">
        <label style="font-weight:600;"><?php esc_html_e( 'Select Test:', 'wp-psycho' ); ?></label>
        <select name="test_id" onchange="this.form.submit()">
            <?php foreach ( $tests as $t ): ?>
                <option value="<?php echo esc_attr( $t->id ); ?>" <?php selected( $test_id, $t->id ); ?>><?php echo esc_html( $t->title ); ?></option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ( ! $test ): ?>
        <p><?php esc_html_e( 'No tests found. Please create a test first.', 'wp-psycho' ); ?></p>
    <?php else: ?>
    <form method="post">
        <?php wp_nonce_field( 'psycho_builder_nonce' ); ?>
        <table class="form-table">
            <tr>
                <th><label><?php esc_html_e( 'Report Header Text', 'wp-psycho' ); ?></label></th>
                <td>
                    <textarea name="header_text" rows="3" class="large-text"><?php echo esc_textarea( $template->header_text ?? get_option( 'psycho_portal_title', 'Psychometric Assessment Report' ) ); ?></textarea>
                    <p class="description"><?php esc_html_e( 'Appears at the top of the report, after the logo.', 'wp-psycho' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label><?php esc_html_e( 'Report Footer Text', 'wp-psycho' ); ?></label></th>
                <td>
                    <textarea name="footer_text" rows="3" class="large-text"><?php echo esc_textarea( $template->footer_text ?? 'Thank you for completing the assessment.' ); ?></textarea>
                    <p class="description"><?php esc_html_e( 'Appears at the bottom of the report.', 'wp-psycho' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label><?php esc_html_e( 'Logo URL', 'wp-psycho' ); ?></label></th>
                <td>
                    <input type="url" name="logo_url" class="large-text" value="<?php echo esc_attr( $template->logo_url ?? get_option( 'psycho_logo_url', '' ) ); ?>">
                    <p class="description"><?php esc_html_e( 'Full URL to your logo image. Leave blank to hide.', 'wp-psycho' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Show Trait Breakdown', 'wp-psycho' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="show_traits" value="1" <?php checked( $template->show_traits ?? 1, 1 ); ?>>
                        <?php esc_html_e( 'Include trait breakdown table in the report', 'wp-psycho' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Show Chart', 'wp-psycho' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="show_chart" value="1" <?php checked( $template->show_chart ?? 1, 1 ); ?>>
                        <?php esc_html_e( 'Include radar chart visualization (online reports only)', 'wp-psycho' ); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php submit_button( __( 'Save Template', 'wp-psycho' ), 'primary', 'psycho_save_builder' ); ?>
    </form>

    <div style="background:#fff8e1;padding:16px;border-left:4px solid #ffc107;border-radius:0 8px 8px 0;margin-top:20px;">
        <strong><?php esc_html_e( 'Preview Note:', 'wp-psycho' ); ?></strong>
        <ul style="margin:.5em 0 0 1.5em;">
            <li><?php esc_html_e( 'Header Text – shown at the top of the report below the logo.', 'wp-psycho' ); ?></li>
            <li><?php esc_html_e( 'Footer Text – shown at the bottom of the report.', 'wp-psycho' ); ?></li>
            <li><?php esc_html_e( 'Logo URL – your organization logo displayed in the report header.', 'wp-psycho' ); ?></li>
            <li><?php esc_html_e( 'Trait Breakdown – shows a bar chart of each scoring trait.', 'wp-psycho' ); ?></li>
            <li><?php esc_html_e( 'Chart – shows an interactive radar chart on the results page.', 'wp-psycho' ); ?></li>
        </ul>
    </div>
    <?php endif; ?>
</div>
