<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) return;

global $wpdb;
$p       = WP_Psycho_DB::get_prefix();
$test_id = intval( $_GET['test_id'] ?? 0 );

if ( ! $test_id ) {
    echo '<div class="notice notice-error"><p>' . esc_html__( 'No test selected.', 'wp-psycho' ) . '</p></div>';
    return;
}

$test = WP_Psycho_DB::get_test( $test_id );
if ( ! $test ) {
    echo '<div class="notice notice-error"><p>' . esc_html__( 'Test not found.', 'wp-psycho' ) . '</p></div>';
    return;
}

// Handle delete
if ( isset( $_GET['delete_q'], $_GET['_wpnonce'] ) ) {
    if ( wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'psycho_del_q_' . intval( $_GET['delete_q'] ) ) ) {
        $qid = intval( $_GET['delete_q'] );
        $wpdb->delete( "{$p}options", [ 'question_id' => $qid ] );
        $wpdb->delete( "{$p}questions", [ 'id' => $qid ] );
        echo '<div class="notice notice-success"><p>' . esc_html__( 'Question deleted.', 'wp-psycho' ) . '</p></div>';
    }
}

// Handle save
$editing_q = null;
if ( isset( $_GET['edit_q'] ) ) {
    $editing_q = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$p}questions WHERE id=%d AND test_id=%d", intval( $_GET['edit_q'] ), $test_id ) );
}

if ( isset( $_POST['psycho_save_question'] ) && wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ?? '' ), 'psycho_q_nonce' ) ) {
    $q_data = [
        'test_id'    => $test_id,
        'question'   => sanitize_textarea_field( wp_unslash( $_POST['question'] ?? '' ) ),
        'type'       => sanitize_text_field( wp_unslash( $_POST['q_type'] ?? 'likert' ) ),
        'trait'      => sanitize_text_field( wp_unslash( $_POST['trait'] ?? '' ) ),
        'order_num'  => intval( $_POST['order_num'] ?? 0 ),
        'required'   => isset( $_POST['required'] ) ? 1 : 0,
    ];

    $edit_qid = intval( $_POST['edit_qid'] ?? 0 );
    if ( $edit_qid ) {
        $wpdb->update( "{$p}questions", $q_data, [ 'id' => $edit_qid, 'test_id' => $test_id ] );
        $new_qid = $edit_qid;
    } else {
        $wpdb->insert( "{$p}questions", $q_data );
        $new_qid = $wpdb->insert_id;
    }

    if ( $new_qid ) {
        // Delete old options and re-insert
        $wpdb->delete( "{$p}options", [ 'question_id' => $new_qid ] );
        $opt_texts  = $_POST['opt_text']  ?? [];
        $opt_scores = $_POST['opt_score'] ?? [];
        foreach ( $opt_texts as $i => $opt_text ) {
            $opt_text = sanitize_text_field( wp_unslash( $opt_text ) );
            if ( '' === $opt_text ) continue;
            $wpdb->insert( "{$p}options", [
                'question_id' => $new_qid,
                'option_text' => $opt_text,
                'score'       => intval( $opt_scores[ $i ] ?? 0 ),
                'order_num'   => $i,
            ] );
        }
        echo '<div class="notice notice-success"><p>' . esc_html__( 'Question saved.', 'wp-psycho' ) . '</p></div>';
        $editing_q = null;
    }
}

$questions   = WP_Psycho_DB::get_questions( $test_id );
$back_url    = admin_url( 'admin.php?page=psycho-tests' );
$edit_q_opts = [];
if ( $editing_q ) {
    $edit_q_opts = WP_Psycho_DB::get_options( $editing_q->id );
}
$default_opts = [
    [ 'text' => 'Strongly Agree',    'score' => 5 ],
    [ 'text' => 'Agree',             'score' => 4 ],
    [ 'text' => 'Neutral',           'score' => 3 ],
    [ 'text' => 'Disagree',          'score' => 2 ],
    [ 'text' => 'Strongly Disagree', 'score' => 1 ],
];
?>
<div class="wrap">
<h1><?php printf( esc_html__( 'Questions: %s', 'wp-psycho' ), esc_html( $test->title ) ); ?></h1>
<p><a href="<?php echo esc_url( $back_url ); ?>" class="button">&larr; <?php esc_html_e( 'Back to Tests', 'wp-psycho' ); ?></a></p>

<div style="display:grid;grid-template-columns:1fr 420px;gap:24px;margin-top:16px;">
<div>
<table class="wp-list-table widefat fixed striped">
    <thead>
        <tr>
            <th style="width:40px;">#</th>
            <th><?php esc_html_e( 'Question', 'wp-psycho' ); ?></th>
            <th><?php esc_html_e( 'Type', 'wp-psycho' ); ?></th>
            <th><?php esc_html_e( 'Trait', 'wp-psycho' ); ?></th>
            <th><?php esc_html_e( 'Options', 'wp-psycho' ); ?></th>
            <th><?php esc_html_e( 'Actions', 'wp-psycho' ); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php if ( $questions ) : foreach ( $questions as $q ) :
        $opts     = WP_Psycho_DB::get_options( $q->id );
        $ed_url   = admin_url( 'admin.php?page=psycho-questions&test_id=' . $test_id . '&edit_q=' . $q->id );
        $del_url  = wp_nonce_url( admin_url( 'admin.php?page=psycho-questions&test_id=' . $test_id . '&delete_q=' . $q->id ), 'psycho_del_q_' . $q->id );
    ?>
        <tr>
            <td><?php echo intval( $q->order_num ); ?></td>
            <td><?php echo esc_html( wp_trim_words( $q->question, 12 ) ); ?></td>
            <td><?php echo esc_html( $q->type ); ?></td>
            <td><?php echo esc_html( $q->trait ); ?></td>
            <td><?php echo intval( count( $opts ) ); ?></td>
            <td>
                <a href="<?php echo esc_url( $ed_url ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'wp-psycho' ); ?></a>
                <a href="<?php echo esc_url( $del_url ); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e( 'Delete?', 'wp-psycho' ); ?>')"><?php esc_html_e( 'Delete', 'wp-psycho' ); ?></a>
            </td>
        </tr>
    <?php endforeach; else : ?>
        <tr><td colspan="6"><?php esc_html_e( 'No questions yet.', 'wp-psycho' ); ?></td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>

<div>
<div class="postbox">
    <div class="postbox-header"><h2><?php echo $editing_q ? esc_html__( 'Edit Question', 'wp-psycho' ) : esc_html__( 'Add Question', 'wp-psycho' ); ?></h2></div>
    <div class="inside">
    <form method="post">
        <?php wp_nonce_field( 'psycho_q_nonce' ); ?>
        <?php if ( $editing_q ) : ?><input type="hidden" name="edit_qid" value="<?php echo intval( $editing_q->id ); ?>"><?php endif; ?>
        <table class="form-table" style="margin:0;">
            <tr><th><?php esc_html_e( 'Question', 'wp-psycho' ); ?> *</th>
                <td><textarea name="question" class="widefat" rows="3" required><?php echo esc_textarea( $editing_q->question ?? '' ); ?></textarea></td></tr>
            <tr><th><?php esc_html_e( 'Type', 'wp-psycho' ); ?></th>
                <td><select name="q_type" class="widefat">
                    <?php foreach ( [ 'likert' => 'Likert', 'multiple_choice' => 'Multiple Choice', 'true_false' => 'True/False', 'rating' => 'Rating' ] as $v => $l ) : ?>
                    <option value="<?php echo esc_attr( $v ); ?>" <?php selected( ( $editing_q->type ?? 'likert' ), $v ); ?>><?php echo esc_html( $l ); ?></option>
                    <?php endforeach; ?>
                </select></td></tr>
            <tr><th><?php esc_html_e( 'Trait', 'wp-psycho' ); ?></th>
                <td><input type="text" name="trait" class="widefat" value="<?php echo esc_attr( $editing_q->trait ?? '' ); ?>" placeholder="e.g. Extraversion"></td></tr>
            <tr><th><?php esc_html_e( 'Order', 'wp-psycho' ); ?></th>
                <td><input type="number" name="order_num" class="small-text" value="<?php echo intval( $editing_q->order_num ?? 0 ); ?>"></td></tr>
            <tr><th><?php esc_html_e( 'Required', 'wp-psycho' ); ?></th>
                <td><input type="checkbox" name="required" value="1" <?php checked( $editing_q->required ?? 1, 1 ); ?>></td></tr>
        </table>

        <h3 style="margin:16px 0 8px;"><?php esc_html_e( 'Answer Options', 'wp-psycho' ); ?></h3>
        <table class="widefat" id="psycho-opts-table">
            <thead><tr>
                <th><?php esc_html_e( 'Option Text', 'wp-psycho' ); ?></th>
                <th style="width:80px;"><?php esc_html_e( 'Score', 'wp-psycho' ); ?></th>
                <th style="width:40px;"></th>
            </tr></thead>
            <tbody id="psycho-opts-body">
            <?php
            $display_opts = $editing_q && $edit_q_opts ? $edit_q_opts : $default_opts;
            foreach ( $display_opts as $i => $opt ) :
                $text  = is_object( $opt ) ? $opt->option_text : $opt['text'];
                $score = is_object( $opt ) ? $opt->score       : $opt['score'];
            ?>
            <tr>
                <td><input type="text" name="opt_text[]" class="widefat" value="<?php echo esc_attr( $text ); ?>"></td>
                <td><input type="number" name="opt_score[]" class="small-text" value="<?php echo intval( $score ); ?>"></td>
                <td><button type="button" class="button button-small psycho-remove-opt">✕</button></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p><button type="button" id="psycho-add-opt" class="button"><?php esc_html_e( '+ Add Option', 'wp-psycho' ); ?></button></p>

        <p>
            <button type="submit" name="psycho_save_question" class="button button-primary"><?php echo $editing_q ? esc_html__( 'Update Question', 'wp-psycho' ) : esc_html__( 'Add Question', 'wp-psycho' ); ?></button>
            <?php if ( $editing_q ) : ?><a href="<?php echo esc_url( admin_url( 'admin.php?page=psycho-questions&test_id=' . $test_id ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'wp-psycho' ); ?></a><?php endif; ?>
        </p>
    </form>
    </div>
</div>
</div>
</div>

<script>
(function($){
    $('#psycho-add-opt').on('click', function(){
        $('#psycho-opts-body').append('<tr><td><input type="text" name="opt_text[]" class="widefat"></td><td><input type="number" name="opt_score[]" class="small-text" value="0"></td><td><button type="button" class="button button-small psycho-remove-opt">✕</button></td></tr>');
    });
    $(document).on('click', '.psycho-remove-opt', function(){
        $(this).closest('tr').remove();
    });
}(jQuery));
</script>
</div>
