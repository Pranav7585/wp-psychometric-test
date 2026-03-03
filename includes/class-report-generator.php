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

        $test_id = intval( $_POST['test_id'] ?? 0 );
        if ( ! $test_id ) {
            wp_send_json_error( [ 'message' => __( 'Invalid test.', 'wp-psycho' ) ] );
        }

        $test = WP_Psycho_DB::get_test( $test_id );
        if ( ! $test || ! $test->status ) {
            wp_send_json_error( [ 'message' => __( 'Test not available.', 'wp-psycho' ) ] );
        }

        $questions = WP_Psycho_DB::get_questions( $test_id );
        if ( $test->shuffle_q ) {
            shuffle( $questions );
        }

        $q_data = [];
        foreach ( $questions as $q ) {
            $options = WP_Psycho_DB::get_options( $q->id );
            $opts    = [];
            foreach ( $options as $opt ) {
                $opts[] = [
                    'id'   => intval( $opt->id ),
                    'text' => $opt->option_text,
                ];
            }
            $q_data[] = [
                'id'       => intval( $q->id ),
                'question' => $q->question,
                'type'     => $q->type,
                'trait'    => $q->trait,
                'required' => (bool) $q->required,
                'options'  => $opts,
            ];
        }

        wp_send_json_success( [
            'test' => [
                'id'          => intval( $test->id ),
                'title'       => $test->title,
                'description' => $test->description,
                'category'    => $test->category,
                'type'        => $test->type,
                'time_limit'  => intval( $test->time_limit ),
            ],
            'questions' => $q_data,
        ] );
    }

    public function get_result() {
        check_ajax_referer( 'psycho_nonce', 'nonce' );

        $result_id = intval( $_POST['result_id'] ?? 0 );
        if ( ! $result_id ) {
            wp_send_json_error( [ 'message' => __( 'Invalid result.', 'wp-psycho' ) ] );
        }

        $result = WP_Psycho_DB::get_result( $result_id );
        if ( ! $result ) {
            wp_send_json_error( [ 'message' => __( 'Result not found.', 'wp-psycho' ) ] );
        }

        $trait_scores = [];
        if ( $result->trait_scores ) {
            $decoded = json_decode( $result->trait_scores, true );
            if ( is_array( $decoded ) ) {
                $trait_scores = $decoded;
            }
        }

        $uploads  = wp_psycho_uploads();
        $pdf_url  = '';
        if ( $result->pdf_path ) {
            $pdf_url = $uploads['url'] . basename( $result->pdf_path );
        }

        wp_send_json_success( [
            'result_id'       => intval( $result->id ),
            'participant_name' => $result->participant_name,
            'test_title'      => $result->test_title,
            'total_score'     => intval( $result->total_score ),
            'result_label'    => $result->result_label,
            'result_desc'     => $result->result_desc,
            'recommendation'  => $result->recommendation,
            'result_color'    => $result->result_color,
            'result_icon'     => $result->result_icon,
            'trait_scores'    => $trait_scores,
            'pdf_url'         => $pdf_url,
        ] );
    }
}

new WP_Psycho_Report_Generator();
