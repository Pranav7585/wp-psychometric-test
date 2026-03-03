<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) return;

global $wpdb;
$p       = WP_Psycho_DB::get_prefix();
$test_id = isset( $_GET['test_id'] ) ? absint( $_GET['test_id'] ) : 0;
$test    = $test_id ? WP_Psycho_DB::get_test( $test_id ) : null;

if ( ! $test ) {
    echo '<div class="wrap"><p>' . esc_html__( 'No test selected.', 'wp-psycho' ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=psycho-tests' ) ) . '">' . esc_html__( 'Go back', 'wp-psycho' ) . '</a></p></div>';
    return;
}

// Handle delete question
if ( isset( $_GET['delete_q'] ) && isset( $_GET['_wpnonce'] ) ) {
    $del_id = absint( $_GET['delete_q'] );
    if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'psycho_del_q_' . $del_id ) ) {
        $wpdb->delete( $p . 'options',   [ 'question_id' => $del_id ] );
        $wpdb->delete( $p . 'questions', [ 'id' => $del_id ] );
        wp_redirect( admin_url( 'admin.php?page=psycho-questions&test_id=' . $test_id . '&deleted=1' ) );
        exit;
    }
}

// Handle save question
if ( isset( $_POST['psycho_save_question'] ) && isset( $_POST['_wpnonce'] ) ) {
    if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'psycho_q_nonce' ) ) {
        $q_id       = isset( $_POST['question_id'] ) ? absint( $_POST['question_id'] ) : 0;
        $q_data = [
            'test_id'   => $test_id,
            'question'  => sanitize_textarea_field( wp_unslash( $_POST['question_text'] ?? '' ) ),
            'type'      => sanitize_text_field( wp_unslash( $_POST['q_type'] ?? 'likert' ) ),
            'trait'     => sanitize_text_field( wp_unslash( $_POST['trait'] ?? '' ) ),
            'order_num' => absint( $_POST['order_num'] ?? 0 ),
            'required'  => isset( $_POST['required'] ) ? 1 : 0,
        ];

        if ( $q_id ) {
            $wpdb->update( $p . 'questions', $q_data, [ 'id' => $q_id ] );
        } else {
            $wpdb->insert( $p . 'questions', $q_data );
            $q_id = $wpdb->insert_id;
        }

        // Save options – delete old, insert new
        if ( $q_id ) {
            $wpdb->delete( $p . 'options', [ 'question_id' => $q_id ] );
            $opt_texts  = isset( $_POST['option_text'] )  ? (array) $_POST['option_text']  : [];
            $opt_scores = isset( $_POST['option_score'] ) ? (array) $_POST['option_score'] : [];
            foreach ( $opt_texts as $i => $opt_text ) {
                $opt_text = sanitize_text_field( wp_unslash( $opt_text ) );
                if ( $opt_text === '' ) continue;
                $wpdb->insert( $p . 'options', [
                    'question_id' => $q_id,
                    'option_text' => $opt_text,
                    'score'       => isset( $opt_scores[ $i ] ) ? intval( $opt_scores[ $i ] ) : 0,
                    'order_num'   => $i,
                ] );
            }
        }
        wp_redirect( admin_url( 'admin.php?page=psycho-questions&test_id=' . $test_id . '&saved=1' ) );
        exit;
    }
}

$edit_id  = isset( $_GET['edit_q'] ) ? absint( $_GET['edit_q'] ) : 0;
$editing  = $edit_id ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$p}questions WHERE id = %d", $edit_id ) ) : null;
$edit_opts = $editing ? WP_Psycho_DB::get_options( $edit_id ) : [];
$questions = WP_Psycho_DB::get_questions( $test_id );
?>
<div class="wrap">
    <h1><?php printf( esc_html__( 'Questions — %s', 'wp-psycho' ), esc_html( $test->title ) ); ?></h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=psycho-tests' ) ); ?>" class="button">&larr; <?php esc_html_e( 'Back to Tests', 'wp-psycho' ); ?></a>
    &nbsp;
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=psycho-scoring&test_id=' . $test_id ) ); ?>" class="button"><?php esc_html_e( 'Scoring Rules', 'wp-psycho' ); ?></a>

    <?php if ( isset( $_GET['saved'] ) ): ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Question saved.', 'wp-psycho' ); ?></p></div>
    <?php endif; ?>
    <?php if ( isset( $_GET['deleted'] ) ): ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Question deleted.', 'wp-psycho' ); ?></p></div>
    <?php endif; ?>

    <h2><?php echo $editing ? esc_html__( 'Edit Question', 'wp-psycho' ) : esc_html__( 'Add Question', 'wp-psycho' ); ?></h2>
    <form method="post">
        <?php wp_nonce_field( 'psycho_q_nonce' ); ?>
        <?php if ( $editing ): ?><input type="hidden" name="question_id" value="<?php echo esc_attr( $editing->id ); ?>"><?php endif; ?>
        <table class="form-table">
            <tr>
                <th><label><?php esc_html_e( 'Question', 'wp-psycho' ); ?> *</label></th>
                <td><textarea name="question_text" rows="3" class="large-text" required><?php echo esc_textarea( $editing->question ?? '' ); ?></textarea></td>
            </tr>
            <tr>
                <th><label><?php esc_html_e( 'Type', 'wp-psycho' ); ?></label></th>
                <td>
                    <select name="q_type" id="psycho_q_type">
                        <?php foreach ( [ 'likert' => 'Likert Scale', 'multiple_choice' => 'Multiple Choice', 'true_false' => 'True/False', 'rating' => 'Rating' ] as $val => $lbl ): ?>
                            <option value="<?php echo esc_attr( $val ); ?>" <?php selected( ($editing->type ?? 'likert'), $val ); ?>><?php echo esc_html( $lbl ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label><?php esc_html_e( 'Trait (for scoring)', 'wp-psycho' ); ?></label></th>
                <td><input type="text" name="trait" class="regular-text" value="<?php echo esc_attr( $editing->trait ?? '' ); ?>" placeholder="e.g. Openness, Leadership, Verbal Ability"></td>
            </tr>
            <tr>
                <th><label><?php esc_html_e( 'Order', 'wp-psycho' ); ?></label></th>
                <td><input type="number" name="order_num" class="small-text" value="<?php echo esc_attr( $editing->order_num ?? 0 ); ?>" min="0"></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Required', 'wp-psycho' ); ?></th>
                <td><label><input type="checkbox" name="required" value="1" <?php checked( $editing->required ?? 1, 1 ); ?>> <?php esc_html_e( 'Participant must answer this question', 'wp-psycho' ); ?></label></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Options', 'wp-psycho' ); ?></th>
                <td>
                    <table id="psycho-options-table" class="widefat" style="max-width:600px;">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Option Text', 'wp-psycho' ); ?></th>
                                <th style="width:80px;"><?php esc_html_e( 'Score', 'wp-psycho' ); ?></th>
                                <th style="width:60px;"></th>
                            </tr>
                        </thead>
                        <tbody id="psycho-options-body">
                        <?php
                        if ( $edit_opts ) {
                            foreach ( $edit_opts as $opt ) {
                                echo '<tr>';
                                echo '<td><input type="text" name="option_text[]" class="regular-text" value="' . esc_attr( $opt->option_text ) . '" required></td>';
                                echo '<td><input type="number" name="option_score[]" class="small-text" value="' . esc_attr( $opt->score ) . '"></td>';
                                echo '<td><button type="button" class="button psycho-remove-opt">&times;</button></td>';
                                echo '</tr>';
                            }
                        } else {
                            $defaults = [
                                [ 'Strongly Agree', 5 ], [ 'Agree', 4 ], [ 'Neutral', 3 ],
                                [ 'Disagree', 2 ], [ 'Strongly Disagree', 1 ],
                            ];
                            foreach ( $defaults as $d ) {
                                echo '<tr>';
                                echo '<td><input type="text" name="option_text[]" class="regular-text" value="' . esc_attr( $d[0] ) . '" required></td>';
                                echo '<td><input type="number" name="option_score[]" class="small-text" value="' . esc_attr( $d[1] ) . '"></td>';
                                echo '<td><button type="button" class="button psycho-remove-opt">&times;</button></td>';
                                echo '</tr>';
                            }
                        }
                        ?>
                        </tbody>
                    </table>
                    <button type="button" id="psycho-add-opt" class="button" style="margin-top:8px;">+ <?php esc_html_e( 'Add Option', 'wp-psycho' ); ?></button>
                </td>
            </tr>
        </table>
        <input type="submit" name="psycho_save_question" class="button button-primary" value="<?php echo $editing ? esc_attr__( 'Update Question', 'wp-psycho' ) : esc_attr__( 'Add Question', 'wp-psycho' ); ?>">
        <?php if ( $editing ): ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=psycho-questions&test_id=' . $test_id ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'wp-psycho' ); ?></a>
        <?php endif; ?>
    </form>

    <hr>
    <h2><?php esc_html_e( 'Questions', 'wp-psycho' ); ?> (<?php echo count( $questions ); ?>)</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width:40px;">#</th>
                <th><?php esc_html_e( 'Question', 'wp-psycho' ); ?></th>
                <th style="width:100px;"><?php esc_html_e( 'Type', 'wp-psycho' ); ?></th>
                <th style="width:120px;"><?php esc_html_e( 'Trait', 'wp-psycho' ); ?></th>
                <th style="width:80px;"><?php esc_html_e( 'Required', 'wp-psycho' ); ?></th>
                <th style="width:130px;"><?php esc_html_e( 'Actions', 'wp-psycho' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( $questions ): foreach ( $questions as $i => $q ): ?>
                <tr>
                    <td><?php echo $i + 1; ?></td>
                    <td><?php echo esc_html( wp_trim_words( $q->question, 15 ) ); ?></td>
                    <td><?php echo esc_html( $q->type ); ?></td>
                    <td><?php echo esc_html( $q->trait ); ?></td>
                    <td><?php echo $q->required ? esc_html__( 'Yes', 'wp-psycho' ) : esc_html__( 'No', 'wp-psycho' ); ?></td>
                    <td>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=psycho-questions&test_id=' . $test_id . '&edit_q=' . $q->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'wp-psycho' ); ?></a>
                        <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=psycho-questions&test_id=' . $test_id . '&delete_q=' . $q->id ), 'psycho_del_q_' . $q->id ) ); ?>"
                           class="button button-small button-link-delete"
                           onclick="return confirm('<?php esc_attr_e( 'Delete this question?', 'wp-psycho' ); ?>')"><?php esc_html_e( 'Delete', 'wp-psycho' ); ?></a>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="6"><?php esc_html_e( 'No questions yet.', 'wp-psycho' ); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
jQuery(function($){
    $('#psycho-add-opt').on('click', function(){
        $('#psycho-options-body').append(
            '<tr>' +
            '<td><input type="text" name="option_text[]" class="regular-text" required></td>' +
            '<td><input type="number" name="option_score[]" class="small-text" value="0"></td>' +
            '<td><button type="button" class="button psycho-remove-opt">&times;</button></td>' +
            '</tr>'
        );
    });
    $(document).on('click', '.psycho-remove-opt', function(){
        $(this).closest('tr').remove();
    });
});
</script>
