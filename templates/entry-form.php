<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="psycho-card psycho-entry-card">
    <div class="psycho-entry-hero">
        <div class="psycho-hero-icon">&#x1F9E0;</div>
        <h2><?php echo esc_html( get_option( 'psycho_portal_title', 'Psychometric Assessment Portal' ) ); ?></h2>
        <p><?php echo esc_html( get_option( 'psycho_portal_subtitle', 'Discover your strengths, personality, and potential.' ) ); ?></p>
    </div>
    <div id="psycho-entry-msg"></div>
    <form id="psycho-entry-form" class="psycho-form" autocomplete="off" novalidate>
        <div class="psycho-field">
            <label class="psycho-label">
                <span class="psycho-label-icon">&#x1F464;</span>
                <?php esc_html_e( 'Full Name', 'wp-psycho' ); ?> <span class="req">*</span>
            </label>
            <input type="text" id="psycho_name" class="psycho-input" required
                   placeholder="<?php esc_attr_e( 'Enter your full name', 'wp-psycho' ); ?>">
        </div>
        <div class="psycho-field-row psycho-two-col">
            <div class="psycho-field">
                <label class="psycho-label">
                    <span class="psycho-label-icon">&#x1F4E7;</span>
                    <?php esc_html_e( 'Email Address', 'wp-psycho' ); ?> <span class="req">*</span>
                </label>
                <input type="email" id="psycho_email" class="psycho-input" required
                       placeholder="you@example.com">
            </div>
            <div class="psycho-field">
                <label class="psycho-label">
                    <span class="psycho-label-icon">&#x1F4F1;</span>
                    <?php esc_html_e( 'Phone Number', 'wp-psycho' ); ?> <span class="req">*</span>
                </label>
                <input type="tel" id="psycho_phone" class="psycho-input" required
                       placeholder="+91 98765 43210">
            </div>
        </div>
        <div class="psycho-consent">
            <label class="psycho-checkbox-label">
                <input type="checkbox" id="psycho_consent" required>
                <span class="psycho-checkmark"></span>
                <?php printf(
                    wp_kses( __( 'I agree to the <a href="%s" target="_blank">Privacy Policy</a> and consent to my data being used for assessment purposes.', 'wp-psycho' ), [ 'a' => [ 'href' => [], 'target' => [] ] ] ),
                    esc_url( get_option( 'psycho_privacy_url', get_privacy_policy_url() ?: '#' ) )
                ); ?>
            </label>
        </div>
        <button type="submit" class="psycho-btn psycho-btn-primary psycho-btn-full" id="psycho-entry-btn">
            <span class="btn-text"><?php esc_html_e( 'Continue to Tests', 'wp-psycho' ); ?> &rarr;</span>
            <span class="btn-loader" style="display:none;">&#x23F3; <?php esc_html_e( 'Please wait...', 'wp-psycho' ); ?></span>
        </button>
    </form>
</div>
