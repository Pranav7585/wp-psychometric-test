<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_Psycho_Report_Generator {

    public function __construct() {
        add_action( 'wp_ajax_nopriv_psycho_get_test_data', [ $this, 'get_test_data' ] );
        add_action( 'wp_ajax_psycho_get_test_data',        [ $this, 'get_test_data' ] );
        add_action( 'wp_ajax_nopriv_psycho_get_result',    [ $this, 'get_result' ] );
        add_action( 'wp_ajax_psycho_get_result',           [ $this, 'get_result' ] );
    }

    public function get_test_data() {
        check_ajax_referer( 'psycho_nonce', 'nonce' );

        $test_id = absint( $_POST['test_id'] ?? 0 );
        $test    = WP_Psycho_DB::get_test( $test_id );

        if ( ! $test ) {
            wp_send_json_error( [ 'msg' => __( 'Test not found.', 'wp-psycho' ) ] );
        }

        $questions = WP_Psycho_DB::get_questions( $test_id );

        if ( $test->shuffle_q ) {
            shuffle( $questions );
        }

        $qs = [];
        foreach ( $questions as $q ) {
            $options = WP_Psycho_DB::get_options( $q->id );
            $opts    = [];
            foreach ( $options as $o ) {
                $opts[] = [
                    'id'   => (int) $o->id,
                    'text' => $o->option_text,
                    // Do not expose score to frontend
                ];
            }
            $qs[] = [
                'id'       => (int) $q->id,
                'question' => $q->question,
                'type'     => $q->type,
                'trait'    => $q->trait,
                'required' => (bool) $q->required,
                'options'  => $opts,
            ];
        }

        wp_send_json_success( [
            'test' => [
                'id'          => (int) $test->id,
                'title'       => $test->title,
                'category'    => $test->category,
                'time_limit'  => (int) $test->time_limit,
                'description' => $test->description,
            ],
            'questions' => $qs,
        ] );
    }

    public function get_result() {
        check_ajax_referer( 'psycho_nonce', 'nonce' );

        $result_id = absint( $_POST['result_id'] ?? 0 );
        $result    = WP_Psycho_DB::get_result( $result_id );

        if ( ! $result ) {
            wp_send_json_error( [ 'msg' => __( 'Result not found.', 'wp-psycho' ) ] );
        }

        $uploads     = wp_psycho_uploads();
        $trait_scores = [];
        if ( ! empty( $result->trait_scores ) ) {
            $trait_scores = json_decode( $result->trait_scores, true ) ?: [];
        }

        // Calculate max possible score per trait for percentage display
        $max_score = 0;
        $questions = WP_Psycho_DB::get_questions( $result->test_id );
        foreach ( $questions as $q ) {
            $options = WP_Psycho_DB::get_options( $q->id );
            if ( $options ) {
                $scores = array_map( function( $o ) { return (int) $o->score; }, $options );
                $max_score += max( $scores );
            }
        }

        wp_send_json_success( [
            'result_id'        => (int) $result->id,
            'participant_name' => $result->participant_name,
            'test_title'       => $result->test_title,
            'total_score'      => (int) $result->total_score,
            'max_score'        => $max_score,
            'result_label'     => $result->result_label,
            'result_desc'      => $result->result_desc,
            'recommendation'   => $result->recommendation,
            'result_color'     => $result->result_color,
            'result_icon'      => $result->result_icon,
            'trait_scores'     => $trait_scores,
            'pdf_url'          => $result->pdf_path ? $uploads['url'] . basename( $result->pdf_path ) : '',
        ] );
    }
}

new WP_Psycho_Report_Generator();
