<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div id="psycho-confetti-wrap" class="psycho-confetti-wrap"></div>

<div class="psycho-result-card psycho-card">
    <div class="psycho-result-top">
        <div id="psycho-result-emoji" class="psycho-result-emoji">🏆</div>
        <p class="psycho-result-name-line">
            <?php esc_html_e( 'Great job,', 'wp-psycho' ); ?>
            <strong id="psycho-result-participant-name"></strong>!
        </p>
        <p class="psycho-result-test-name" id="psycho-result-test-name"></p>

        <!-- Score ring -->
        <div class="psycho-score-ring-wrap">
            <svg class="psycho-score-ring" viewBox="0 0 120 120" width="150" height="150" aria-hidden="true">
                <circle class="psycho-ring-bg"  cx="60" cy="60" r="52" fill="none" stroke-width="10"/>
                <circle class="psycho-ring-fill" cx="60" cy="60" r="52" fill="none" stroke-width="10"
                        stroke-dasharray="326.7"
                        stroke-dashoffset="326.7"
                        id="psycho-ring-fill"/>
            </svg>
            <div class="psycho-ring-inner">
                <span id="psycho-score-num" class="psycho-score-num">0</span>
                <span class="psycho-score-label"><?php esc_html_e( 'pts', 'wp-psycho' ); ?></span>
            </div>
        </div>

        <div id="psycho-result-label" class="psycho-result-label"></div>
        <p id="psycho-result-desc" class="psycho-result-desc"></p>
    </div>

    <!-- Recommendation -->
    <div id="psycho-recommendation-box" class="psycho-recommendation-box" style="display:none;">
        <h4>💡 <?php esc_html_e( 'Recommendation', 'wp-psycho' ); ?></h4>
        <p id="psycho-recommendation-text"></p>
    </div>

    <!-- Traits -->
    <div id="psycho-traits-card" class="psycho-traits-card" style="display:none;">
        <h3 class="psycho-traits-title"><?php esc_html_e( 'Trait Breakdown', 'wp-psycho' ); ?></h3>
        <div id="psycho-trait-bars" class="psycho-trait-bars"></div>
        <div class="psycho-chart-wrap">
            <canvas id="psycho-radar-chart" width="320" height="320"></canvas>
        </div>
    </div>

    <!-- Action buttons -->
    <div class="psycho-result-actions">
        <a id="psycho-download-report" href="#" target="_blank" class="psycho-btn psycho-btn-primary psycho-btn-lg">
            ⬇ <?php esc_html_e( 'Download Report', 'wp-psycho' ); ?>
        </a>
        <button type="button" id="psycho-retake-btn" class="psycho-btn psycho-btn-outline psycho-btn-lg">
            🔄 <?php esc_html_e( 'Take Another Test', 'wp-psycho' ); ?>
        </button>
    </div>
</div>
