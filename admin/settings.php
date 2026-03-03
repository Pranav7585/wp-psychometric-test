<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) return;

if ( isset( $_POST['psycho_save_settings'] ) && wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ?? '' ), 'psycho_settings_nonce' ) ) {
    update_option( 'psycho_portal_title',    sanitize_text_field( wp_unslash( $_POST['psycho_portal_title']    ?? '' ) ) );
    update_option( 'psycho_portal_subtitle', sanitize_text_field( wp_unslash( $_POST['psycho_portal_subtitle'] ?? '' ) ) );
    update_option( 'psycho_brand_color',     sanitize_hex_color( wp_unslash( $_POST['psycho_brand_color']     ?? '#6c63ff' ) ) ?: '#6c63ff' );
    update_option( 'psycho_admin_email',     sanitize_email( wp_unslash( $_POST['psycho_admin_email']      ?? '' ) ) );
    update_option( 'psycho_whatsapp_number', sanitize_text_field( wp_unslash( $_POST['psycho_whatsapp_number'] ?? '' ) ) );
    update_option( 'psycho_privacy_url',     esc_url_raw( wp_unslash( $_POST['psycho_privacy_url']   ?? '' ) ) );
    update_option( 'psycho_logo_url',        esc_url_raw( wp_unslash( $_POST['psycho_logo_url']      ?? '' ) ) );
    echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'wp-psycho' ) . '</p></div>';
}
?>
<div class="wrap">
<h1><?php esc_html_e( 'Plugin Settings', 'wp-psycho' ); ?></h1>

<form method="post">
<?php wp_nonce_field( 'psycho_settings_nonce' ); ?>

<h2><?php esc_html_e( 'Portal Display', 'wp-psycho' ); ?></h2>
<table class="form-table">
    <tr>
        <th><?php esc_html_e( 'Portal Title', 'wp-psycho' ); ?></th>
        <td><input type="text" name="psycho_portal_title" class="regular-text" value="<?php echo esc_attr( get_option( 'psycho_portal_title', 'Psychometric Assessment Portal' ) ); ?>">
        <p class="description"><?php esc_html_e( 'Main heading shown on the portal entry page.', 'wp-psycho' ); ?></p></td>
    </tr>
    <tr>
        <th><?php esc_html_e( 'Portal Subtitle', 'wp-psycho' ); ?></th>
        <td><input type="text" name="psycho_portal_subtitle" class="regular-text" value="<?php echo esc_attr( get_option( 'psycho_portal_subtitle', 'Discover your strengths, personality, and potential.' ) ); ?>">
        <p class="description"><?php esc_html_e( 'Subheading shown below the portal title.', 'wp-psycho' ); ?></p></td>
    </tr>
    <tr>
        <th><?php esc_html_e( 'Brand Color', 'wp-psycho' ); ?></th>
        <td><input type="color" name="psycho_brand_color" value="<?php echo esc_attr( get_option( 'psycho_brand_color', '#6c63ff' ) ); ?>">
        <p class="description"><?php esc_html_e( 'Primary accent color used throughout the portal.', 'wp-psycho' ); ?></p></td>
    </tr>
    <tr>
        <th><?php esc_html_e( 'Logo URL', 'wp-psycho' ); ?></th>
        <td><input type="url" name="psycho_logo_url" class="regular-text" value="<?php echo esc_attr( get_option( 'psycho_logo_url', '' ) ); ?>">
        <p class="description"><?php esc_html_e( 'URL to your logo image (used in reports).', 'wp-psycho' ); ?></p></td>
    </tr>
</table>

<h2><?php esc_html_e( 'Notifications', 'wp-psycho' ); ?></h2>
<table class="form-table">
    <tr>
        <th><?php esc_html_e( 'Admin Email', 'wp-psycho' ); ?></th>
        <td><input type="email" name="psycho_admin_email" class="regular-text" value="<?php echo esc_attr( get_option( 'psycho_admin_email', get_option( 'admin_email' ) ) ); ?>">
        <p class="description"><?php esc_html_e( 'Email address to receive new result notifications.', 'wp-psycho' ); ?></p></td>
    </tr>
</table>

<h2><?php esc_html_e( 'Contact & Privacy', 'wp-psycho' ); ?></h2>
<table class="form-table">
    <tr>
        <th><?php esc_html_e( 'WhatsApp Number', 'wp-psycho' ); ?></th>
        <td><input type="text" name="psycho_whatsapp_number" class="regular-text" value="<?php echo esc_attr( get_option( 'psycho_whatsapp_number', '' ) ); ?>">
        <p class="description"><?php esc_html_e( 'Include country code, no spaces (e.g. 919876543210).', 'wp-psycho' ); ?></p></td>
    </tr>
    <tr>
        <th><?php esc_html_e( 'Privacy Policy URL', 'wp-psycho' ); ?></th>
        <td><input type="url" name="psycho_privacy_url" class="regular-text" value="<?php echo esc_attr( get_option( 'psycho_privacy_url', '' ) ); ?>">
        <p class="description"><?php esc_html_e( 'Linked in the consent checkbox on the entry form.', 'wp-psycho' ); ?></p></td>
    </tr>
</table>

<p><button type="submit" name="psycho_save_settings" class="button button-primary"><?php esc_html_e( 'Save Settings', 'wp-psycho' ); ?></button></p>
</form>
</div>
