<?php if ( ! defined( 'ABSPATH' ) ) exit;
$tests = WP_Psycho_DB::get_tests( 1 );
?>
<div id="psycho-welcome-bar" class="psycho-welcome-bar" style="display:none;">
    <div class="psycho-welcome-inner">
        <span class="psycho-welcome-text">&#x1F44B; <?php esc_html_e( 'Welcome back,', 'wp-psycho' ); ?> <strong id="psycho-welcome-name"></strong></span>
        <button type="button" id="psycho-change-info" class="psycho-btn psycho-btn-ghost psycho-btn-sm"><?php esc_html_e( 'Change Info', 'wp-psycho' ); ?></button>
    </div>
</div>

<div class="psycho-card" style="padding:32px 32px 24px;">
    <h2 class="psycho-section-title"><?php esc_html_e( 'Available Assessments', 'wp-psycho' ); ?></h2>
    <p class="psycho-section-sub"><?php esc_html_e( 'Select an assessment to begin. Some tests are passkey-protected.', 'wp-psycho' ); ?></p>

    <?php if ( $tests ) : ?>
    <div class="psycho-tests-grid">
    <?php foreach ( $tests as $t ) :
        global $wpdb;
        $p       = WP_Psycho_DB::get_prefix();
        $q_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$p}questions WHERE test_id=%d", $t->id ) );
        $has_pk  = ! empty( $t->passkey );
        $icons   = [ 'likert' => '📊', 'personality' => '🧠', 'aptitude' => '💡', 'skills' => '🎯', 'mixed' => '🔬' ];
        $icon    = $icons[ $t->type ] ?? '📋';
    ?>
    <div class="psycho-test-card"
         data-test-id="<?php echo intval( $t->id ); ?>"
         data-test-name="<?php echo esc_attr( $t->title ); ?>"
         data-has-passkey="<?php echo $has_pk ? '1' : '0'; ?>"
         data-passkey-hint="<?php echo esc_attr( $t->passkey_hint ); ?>">

        <div class="psycho-test-card-header">
            <span class="psycho-test-icon"><?php echo esc_html( $icon ); ?></span>
            <?php if ( $t->category ) : ?>
            <span class="psycho-tag"><?php echo esc_html( $t->category ); ?></span>
            <?php endif; ?>
            <?php if ( $has_pk ) : ?>
            <span class="psycho-meta-locked" title="<?php esc_attr_e( 'Passkey required', 'wp-psycho' ); ?>">&#x1F512;</span>
            <?php endif; ?>
        </div>

        <h3 class="psycho-test-title"><?php echo esc_html( $t->title ); ?></h3>

        <?php if ( $t->description ) : ?>
        <p class="psycho-test-desc"><?php echo esc_html( wp_trim_words( $t->description, 20 ) ); ?></p>
        <?php endif; ?>

        <div class="psycho-test-meta">
            <span class="psycho-meta-item">&#x2753; <?php echo intval( $q_count ); ?> <?php esc_html_e( 'questions', 'wp-psycho' ); ?></span>
            <?php if ( $t->time_limit ) : ?>
            <span class="psycho-meta-item">&#x23F1; <?php echo intval( $t->time_limit ); ?> <?php esc_html_e( 'min', 'wp-psycho' ); ?></span>
            <?php else : ?>
            <span class="psycho-meta-item">&#x23F1; <?php esc_html_e( 'No time limit', 'wp-psycho' ); ?></span>
            <?php endif; ?>
        </div>

        <button type="button" class="psycho-btn psycho-btn-primary psycho-btn-full psycho-start-test" style="margin-top:16px;">
            <?php if ( $has_pk ) : ?>
            &#x1F511; <?php esc_html_e( 'Enter Passkey', 'wp-psycho' ); ?>
            <?php else : ?>
            <?php esc_html_e( 'Start Test', 'wp-psycho' ); ?> &rarr;
            <?php endif; ?>
        </button>
    </div>
    <?php endforeach; ?>
    </div>
    <?php else : ?>
    <div class="psycho-empty-state">
        <div class="psycho-empty-icon">&#x1F4CB;</div>
        <h3><?php esc_html_e( 'No assessments available yet.', 'wp-psycho' ); ?></h3>
        <p><?php esc_html_e( 'Check back soon — new assessments are being added.', 'wp-psycho' ); ?></p>
    </div>
    <?php endif; ?>
</div>

<!-- Passkey Modal -->
<div id="psycho-passkey-overlay" class="psycho-modal-overlay" style="display:none;">
    <div class="psycho-modal-box" id="psycho-passkey-modal">
        <div class="psycho-modal-icon">&#x1F512;</div>
        <h3 id="psycho-modal-test-name"></h3>
        <p id="psycho-modal-hint" class="psycho-modal-hint" style="display:none;"></p>
        <div id="psycho-passkey-msg"></div>
        <input type="password" id="psycho-passkey-input" class="psycho-input" placeholder="<?php esc_attr_e( 'Enter passkey…', 'wp-psycho' ); ?>" autocomplete="off">
        <div class="psycho-modal-actions">
            <button type="button" id="psycho-passkey-submit" class="psycho-btn psycho-btn-primary"><?php esc_html_e( 'Unlock', 'wp-psycho' ); ?></button>
            <button type="button" id="psycho-passkey-cancel" class="psycho-btn psycho-btn-outline"><?php esc_html_e( 'Cancel', 'wp-psycho' ); ?></button>
        </div>
    </div>
</div>
