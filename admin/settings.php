<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) return;

// Handle save
if ( isset( $_POST['psycho_save_settings'] ) && isset( $_POST['_wpnonce'] ) ) {
    if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'psycho_settings_nonce' ) ) {
        $fields = [
            'psycho_portal_title'    => 'sanitize_text_field',
            'psycho_portal_subtitle' => 'sanitize_text_field',
            'psycho_brand_color'     => 'sanitize_hex_color',
            'psycho_admin_email'     => 'sanitize_email',
            'psycho_whatsapp_number' => 'sanitize_text_field',
            'psycho_privacy_url'     => 'esc_url_raw',
            'psycho_logo_url'        => 'esc_url_raw',
        ];

        foreach ( $fields as $key => $sanitizer ) {
            if ( isset( $_POST[ $key ] ) ) {
                $val = wp_unslash( $_POST[ $key ] );
                update_option( $key, $sanitizer( $val ) );
            }
        }
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'wp-psycho' ) . '</p></div>';
    }
}
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Plugin Settings', 'wp-psycho' ); ?></h1>
    <form method="post">
        <?php wp_nonce_field( 'psycho_settings_nonce' ); ?>

        <h2><?php esc_html_e( 'Portal Appearance', 'wp-psycho' ); ?></h2>
        <table class="form-table">
            <tr>
                <th><label for="psycho_portal_title"><?php esc_html_e( 'Portal Title', 'wp-psycho' ); ?></label></th>
                <td>
                    <input type="text" id="psycho_portal_title" name="psycho_portal_title" class="large-text"
                           value="<?php echo esc_attr( get_option( 'psycho_portal_title', 'Psychometric Assessment Portal' ) ); ?>">
                    <p class="description"><?php esc_html_e( 'Shown in the entry form hero area.', 'wp-psycho' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="psycho_portal_subtitle"><?php esc_html_e( 'Portal Subtitle', 'wp-psycho' ); ?></label></th>
                <td>
                    <input type="text" id="psycho_portal_subtitle" name="psycho_portal_subtitle" class="large-text"
                           value="<?php echo esc_attr( get_option( 'psycho_portal_subtitle', 'Discover your strengths, personality, and potential.' ) ); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="psycho_brand_color"><?php esc_html_e( 'Brand Color', 'wp-psycho' ); ?></label></th>
                <td>
                    <input type="color" id="psycho_brand_color" name="psycho_brand_color"
                           value="<?php echo esc_attr( get_option( 'psycho_brand_color', '#6c63ff' ) ); ?>">
                    <p class="description"><?php esc_html_e( 'Primary brand color used throughout the portal.', 'wp-psycho' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="psycho_logo_url"><?php esc_html_e( 'Logo URL', 'wp-psycho' ); ?></label></th>
                <td>
                    <input type="url" id="psycho_logo_url" name="psycho_logo_url" class="large-text"
                           value="<?php echo esc_attr( get_option( 'psycho_logo_url', '' ) ); ?>">
                    <p class="description"><?php esc_html_e( 'Used in generated reports. Leave blank to hide.', 'wp-psycho' ); ?></p>
                </td>
            </tr>
        </table>

        <h2><?php esc_html_e( 'Notifications', 'wp-psycho' ); ?></h2>
        <table class="form-table">
            <tr>
                <th><label for="psycho_admin_email"><?php esc_html_e( 'Admin Notification Email', 'wp-psycho' ); ?></label></th>
                <td>
                    <input type="email" id="psycho_admin_email" name="psycho_admin_email" class="regular-text"
                           value="<?php echo esc_attr( get_option( 'psycho_admin_email', get_option( 'admin_email' ) ) ); ?>">
                    <p class="description"><?php esc_html_e( 'Receives a notification when a participant completes a test.', 'wp-psycho' ); ?></p>
                </td>
            </tr>
        </table>

        <h2><?php esc_html_e( 'Integrations', 'wp-psycho' ); ?></h2>
        <table class="form-table">
            <tr>
                <th><label for="psycho_whatsapp_number"><?php esc_html_e( 'WhatsApp Number', 'wp-psycho' ); ?></label></th>
                <td>
                    <input type="text" id="psycho_whatsapp_number" name="psycho_whatsapp_number" class="regular-text"
                           value="<?php echo esc_attr( get_option( 'psycho_whatsapp_number', '' ) ); ?>"
                           placeholder="+919876543210">
                    <p class="description"><?php esc_html_e( 'Include country code, no spaces or dashes (e.g. +919876543210).', 'wp-psycho' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="psycho_privacy_url"><?php esc_html_e( 'Privacy Policy URL', 'wp-psycho' ); ?></label></th>
                <td>
                    <input type="url" id="psycho_privacy_url" name="psycho_privacy_url" class="large-text"
                           value="<?php echo esc_attr( get_option( 'psycho_privacy_url', '' ) ); ?>">
                    <p class="description"><?php esc_html_e( 'Linked in the participant consent checkbox.', 'wp-psycho' ); ?></p>
                </td>
            </tr>
        </table>

        <?php submit_button( __( 'Save Settings', 'wp-psycho' ), 'primary', 'psycho_save_settings' ); ?>
    </form>

    <hr>
    <h2><?php esc_html_e( 'Shortcode', 'wp-psycho' ); ?></h2>
    <p><?php esc_html_e( 'Place this shortcode on any page to display the psychometric assessment portal:', 'wp-psycho' ); ?></p>
    <code style="font-size:1.1em;padding:6px 12px;background:#f0f0f0;border-radius:4px;">[wp_psycho_portal]</code>
</div>
