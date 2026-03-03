<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_Psycho_Scoring_Engine {

    public function __construct() {
        add_action( 'wp_ajax_nopriv_psycho_submit_test', [ $this, 'submit_test' ] );
        add_action( 'wp_ajax_psycho_submit_test',        [ $this, 'submit_test' ] );
    }

    public function submit_test() {
        check_ajax_referer( 'psycho_nonce', 'nonce' );

        $session_key = WP_Psycho_Auth::get_session();
        if ( ! $session_key ) {
            wp_send_json_error( [ 'msg' => __( 'Session expired. Please refresh.', 'wp-psycho' ) ] );
        }

        $participant = WP_Psycho_DB::get_participant( $session_key );
        if ( ! $participant ) {
            wp_send_json_error( [ 'msg' => __( 'Participant not found.', 'wp-psycho' ) ] );
        }

        $test_id   = absint( $_POST['test_id'] ?? 0 );
        $responses = isset( $_POST['responses'] ) ? (array) $_POST['responses'] : [];
        $time_taken = absint( $_POST['time_taken'] ?? 0 );

        if ( ! $test_id ) {
            wp_send_json_error( [ 'msg' => __( 'Invalid test.', 'wp-psycho' ) ] );
        }

        $test = WP_Psycho_DB::get_test( $test_id );
        if ( ! $test ) {
            wp_send_json_error( [ 'msg' => __( 'Test not found.', 'wp-psycho' ) ] );
        }

        global $wpdb;
        $p = WP_Psycho_DB::get_prefix();

        // Create attempt record
        $wpdb->insert( $p . 'attempts', [
            'participant_id' => $participant->id,
            'test_id'        => $test_id,
            'status'         => 'completed',
            'started_at'     => current_time( 'mysql' ),
            'completed_at'   => current_time( 'mysql' ),
            'time_taken'     => $time_taken,
        ] );
        $attempt_id = $wpdb->insert_id;

        if ( ! $attempt_id ) {
            wp_send_json_error( [ 'msg' => __( 'Could not save attempt.', 'wp-psycho' ) ] );
        }

        $total_score  = 0;
        $trait_scores = [];

        foreach ( $responses as $question_id => $option_id ) {
            $question_id = absint( $question_id );
            $option_id   = absint( $option_id );

            $option = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$p}options WHERE id = %d AND question_id = %d",
                $option_id, $question_id
            ) );

            if ( ! $option ) continue;

            $score = (int) $option->score;
            $total_score += $score;

            $question = $wpdb->get_row( $wpdb->prepare(
                "SELECT trait FROM {$p}questions WHERE id = %d", $question_id
            ) );

            if ( $question && ! empty( $question->trait ) ) {
                $trait = $question->trait;
                if ( ! isset( $trait_scores[ $trait ] ) ) {
                    $trait_scores[ $trait ] = 0;
                }
                $trait_scores[ $trait ] += $score;
            }

            $wpdb->insert( $p . 'responses', [
                'attempt_id'  => $attempt_id,
                'question_id' => $question_id,
                'option_id'   => $option_id,
                'score'       => $score,
            ] );
        }

        // Find matching scoring rule
        $rule = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$p}scoring_rules WHERE test_id = %d AND %d BETWEEN min_score AND max_score LIMIT 1",
            $test_id, $total_score
        ) );

        $result_label      = $rule ? $rule->label          : __( 'Completed', 'wp-psycho' );
        $result_desc       = $rule ? $rule->description     : '';
        $recommendation    = $rule ? $rule->recommendation  : '';
        $result_color      = $rule ? $rule->color           : '#6c63ff';
        $result_icon       = $rule ? $rule->icon            : '🏆';

        // Insert result
        $wpdb->insert( $p . 'results', [
            'attempt_id'     => $attempt_id,
            'participant_id' => $participant->id,
            'test_id'        => $test_id,
            'total_score'    => $total_score,
            'trait_scores'   => wp_json_encode( $trait_scores ),
            'result_label'   => $result_label,
            'result_desc'    => $result_desc,
            'recommendation' => $recommendation,
            'result_color'   => $result_color,
            'result_icon'    => $result_icon,
            'notified'       => 0,
            'created_at'     => current_time( 'mysql' ),
        ] );
        $result_id = $wpdb->insert_id;

        if ( ! $result_id ) {
            wp_send_json_error( [ 'msg' => __( 'Could not save result.', 'wp-psycho' ) ] );
        }

        // Generate HTML report
        WP_Psycho_PDF::generate( $result_id );

        // Send notifications
        WP_Psycho_Notifications::send_result( $result_id );

        wp_send_json_success( [
            'result_id'    => $result_id,
            'total_score'  => $total_score,
            'result_label' => $result_label,
            'result_color' => $result_color,
        ] );
    }
}

new WP_Psycho_Scoring_Engine();
