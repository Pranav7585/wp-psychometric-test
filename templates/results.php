<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div id="psycho-confetti-wrap" class="psycho-confetti-wrap"></div>

<div class="psycho-result-card psycho-card">
    <div class="psycho-result-emoji" id="psycho-result-emoji">🎯</div>

    <div class="psycho-result-meta">
        <p class="psycho-result-name-label"><?php esc_html_e( 'Results for', 'wp-psycho' ); ?> <strong id="psycho-result-participant-name"></strong></p>
        <p class="psycho-result-test-name" id="psycho-result-test-name"></p>
    </div>

    <div class="psycho-score-ring-wrap">
        <svg class="psycho-score-ring" viewBox="0 0 120 120" width="140" height="140">
            <circle class="psycho-ring-bg" cx="60" cy="60" r="52" fill="none" stroke="#e2e8f0" stroke-width="10"/>
            <circle class="psycho-ring-fill" id="psycho-ring-fill" cx="60" cy="60" r="52" fill="none"
                    stroke-width="10" stroke-linecap="round"
                    stroke-dasharray="326.73" stroke-dashoffset="326.73"
                    transform="rotate(-90 60 60)"/>
        </svg>
        <div class="psycho-score-center">
            <span class="psycho-score-number" id="psycho-score-number">0</span>
            <span class="psycho-score-label-sm"><?php esc_html_e( 'Score', 'wp-psycho' ); ?></span>
        </div>
    </div>

    <div class="psycho-result-label" id="psycho-result-label"></div>
    <div class="psycho-result-desc" id="psycho-result-desc"></div>

    <div class="psycho-recommendation-box" id="psycho-recommendation-box" style="display:none;">
        <h4>&#x1F4A1; <?php esc_html_e( 'Recommendation', 'wp-psycho' ); ?></h4>
        <p id="psycho-recommendation-text"></p>
    </div>
</div>

<div class="psycho-traits-card psycho-card" id="psycho-traits-card" style="display:none;">
    <h3><?php esc_html_e( 'Trait Breakdown', 'wp-psycho' ); ?></h3>
    <div class="psycho-traits-list" id="psycho-traits-list"></div>
    <div class="psycho-chart-wrap">
        <canvas id="psycho-radar-chart"></canvas>
    </div>
</div>

<div class="psycho-result-actions">
    <a href="#" id="psycho-pdf-btn" class="psycho-btn psycho-btn-primary psycho-btn-lg" target="_blank" style="display:none;">
        &#x1F4E5; <?php esc_html_e( 'Download Report', 'wp-psycho' ); ?>
    </a>
    <button type="button" id="psycho-retake-btn" class="psycho-btn psycho-btn-outline psycho-btn-lg">
        &#x21BA; <?php esc_html_e( 'Take Another Test', 'wp-psycho' ); ?>
    </button>
</div>
