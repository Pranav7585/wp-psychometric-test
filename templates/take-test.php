<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="psycho-card psycho-exam-card">
    <div class="psycho-exam-header">
        <div class="psycho-exam-header-inner">
            <div>
                <h2 id="psycho-exam-title" class="psycho-exam-title"></h2>
                <span id="psycho-exam-category" class="psycho-tag" style="display:none;"></span>
            </div>
            <div id="psycho-timer-box" class="psycho-timer-box" style="display:none;">
                <span class="psycho-timer-icon">&#x23F1;</span>
                <span id="psycho-timer-display">00:00</span>
            </div>
        </div>
    </div>

    <div class="psycho-progress-wrap">
        <div class="psycho-progress-bar">
            <div class="psycho-progress-fill" id="psycho-progress-fill" style="width:0%"></div>
        </div>
        <div class="psycho-progress-labels">
            <span id="psycho-progress-text"><?php esc_html_e( '0 of 0 answered', 'wp-psycho' ); ?></span>
            <span id="psycho-progress-pct">0%</span>
        </div>
    </div>

    <div id="psycho-questions-container" class="psycho-questions-container"></div>

    <div class="psycho-exam-footer">
        <p id="psycho-unanswered-note" class="psycho-unanswered-note" style="display:none;"></p>
        <button type="button" id="psycho-submit-test" class="psycho-btn psycho-btn-primary psycho-btn-lg" disabled>
            <span class="btn-text"><?php esc_html_e( 'Submit Assessment', 'wp-psycho' ); ?></span>
            <span class="btn-loader" style="display:none;">&#x23F3; <?php esc_html_e( 'Processing…', 'wp-psycho' ); ?></span>
        </button>
    </div>
</div>
