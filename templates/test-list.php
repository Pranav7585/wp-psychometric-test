<?php if ( ! defined( 'ABSPATH' ) ) exit;
$tests = WP_Psycho_DB::get_tests( 1 );
?>
<!-- Welcome bar -->
<div class="psycho-welcome-bar">
    <div class="psycho-welcome-info">
        <span class="psycho-welcome-icon">👋</span>
        <span><?php esc_html_e( 'Welcome,', 'wp-psycho' ); ?> <strong id="psycho-welcome-name">&hellip;</strong></span>
    </div>
    <button type="button" id="psycho-change-info" class="psycho-btn psycho-btn-ghost psycho-btn-sm">
        ✏️ <?php esc_html_e( 'Change Info', 'wp-psycho' ); ?>
    </button>
</div>

<div class="psycho-card" style="padding:28px 32px 12px;">
    <h2 class="psycho-section-title"><?php esc_html_e( 'Available Assessments', 'wp-psycho' ); ?></h2>
    <p class="psycho-section-desc"><?php esc_html_e( 'Select a test to begin your assessment.', 'wp-psycho' ); ?></p>

    <?php if ( $tests ): ?>
    <div class="psycho-tests-grid">
        <?php foreach ( $tests as $test ):
            $has_passkey  = ! empty( $test->passkey );
            $icon         = '';
            switch ( $test->type ) {
                case 'personality':   $icon = '🧬'; break;
                case 'aptitude':      $icon = '🧠'; break;
                case 'multiple_choice': $icon = '📝'; break;
                case 'rating':        $icon = '⭐'; break;
                default:              $icon = '📊';
            }
            global $wpdb;
            $p       = WP_Psycho_DB::get_prefix();
            $q_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$p}questions WHERE test_id = %d", $test->id ) );
        ?>
        <div class="psycho-test-card"
             data-test-id="<?php echo esc_attr( $test->id ); ?>"
             data-test-name="<?php echo esc_attr( $test->title ); ?>"
             data-has-passkey="<?php echo esc_attr( $has_passkey ? '1' : '0' ); ?>"
             data-passkey-hint="<?php echo esc_attr( $test->passkey_hint ); ?>">
            <div class="psycho-test-card-inner">
                <div class="psycho-test-icon"><?php echo esc_html( $icon ); ?></div>
                <?php if ( $test->category ): ?>
                    <span class="psycho-tag"><?php echo esc_html( $test->category ); ?></span>
                <?php endif; ?>
                <h3 class="psycho-test-title"><?php echo esc_html( $test->title ); ?></h3>
                <?php if ( $test->description ): ?>
                    <p class="psycho-test-desc"><?php echo esc_html( wp_trim_words( $test->description, 18 ) ); ?></p>
                <?php endif; ?>
                <div class="psycho-test-meta">
                    <span class="psycho-meta-item">❓ <?php echo esc_html( $q_count ); ?> <?php esc_html_e( 'questions', 'wp-psycho' ); ?></span>
                    <?php if ( $test->time_limit ): ?>
                        <span class="psycho-meta-item">⏱ <?php echo esc_html( $test->time_limit ); ?> <?php esc_html_e( 'min', 'wp-psycho' ); ?></span>
                    <?php endif; ?>
                    <?php if ( $has_passkey ): ?>
                        <span class="psycho-meta-item psycho-meta-locked">🔒 <?php esc_html_e( 'Passkey required', 'wp-psycho' ); ?></span>
                    <?php endif; ?>
                </div>
                <button type="button"
                        class="psycho-btn psycho-btn-primary psycho-start-test"
                        data-test-id="<?php echo esc_attr( $test->id ); ?>">
                    <?php echo $has_passkey ? esc_html__( 'Enter Passkey', 'wp-psycho' ) : esc_html__( 'Start Test', 'wp-psycho' ); ?>
                    &rarr;
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="psycho-empty-state">
        <div class="psycho-empty-icon">📋</div>
        <h3><?php esc_html_e( 'No Tests Available', 'wp-psycho' ); ?></h3>
        <p><?php esc_html_e( 'There are no active assessments at the moment. Please check back later.', 'wp-psycho' ); ?></p>
    </div>
    <?php endif; ?>
</div>

<!-- Passkey Modal -->
<div id="psycho-passkey-overlay" class="psycho-overlay" style="display:none;">
    <div class="psycho-modal" role="dialog" aria-modal="true" aria-labelledby="psycho-modal-title">
        <div class="psycho-modal-inner">
            <div class="psycho-modal-icon">🔒</div>
            <h3 id="psycho-modal-title"><?php esc_html_e( 'Passkey Required', 'wp-psycho' ); ?></h3>
            <p id="psycho-modal-test-name" class="psycho-modal-test-name"></p>
            <p id="psycho-modal-hint" class="psycho-modal-hint" style="display:none;"></p>
            <div id="psycho-passkey-msg"></div>
            <div class="psycho-field">
                <input type="text" id="psycho-passkey-input" class="psycho-input psycho-input-center"
                       placeholder="<?php esc_attr_e( 'Enter passkey', 'wp-psycho' ); ?>"
                       autocomplete="off">
            </div>
            <div class="psycho-modal-actions">
                <button type="button" id="psycho-passkey-submit" class="psycho-btn psycho-btn-primary">
                    <?php esc_html_e( 'Start Test', 'wp-psycho' ); ?> &rarr;
                </button>
                <button type="button" id="psycho-passkey-cancel" class="psycho-btn psycho-btn-outline">
                    <?php esc_html_e( 'Cancel', 'wp-psycho' ); ?>
                </button>
            </div>
        </div>
    </div>
</div>
