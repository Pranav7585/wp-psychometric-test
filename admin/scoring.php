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
if ( isset( $_GET['delete_rule'], $_GET['_wpnonce'] ) ) {
    if ( wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'psycho_del_rule_' . intval( $_GET['delete_rule'] ) ) ) {
        $wpdb->delete( "{$p}scoring_rules", [ 'id' => intval( $_GET['delete_rule'] ), 'test_id' => $test_id ] );
        echo '<div class="notice notice-success"><p>' . esc_html__( 'Rule deleted.', 'wp-psycho' ) . '</p></div>';
    }
}

// Handle save
$editing_r = null;
if ( isset( $_GET['edit_rule'] ) ) {
    $editing_r = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$p}scoring_rules WHERE id=%d AND test_id=%d", intval( $_GET['edit_rule'] ), $test_id ) );
}

if ( isset( $_POST['psycho_save_rule'] ) && wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ?? '' ), 'psycho_scoring_nonce' ) ) {
    $rule_data = [
        'test_id'        => $test_id,
        'label'          => sanitize_text_field( wp_unslash( $_POST['label'] ?? '' ) ),
        'description'    => sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ),
        'recommendation' => sanitize_textarea_field( wp_unslash( $_POST['recommendation'] ?? '' ) ),
        'min_score'      => intval( $_POST['min_score'] ?? 0 ),
        'max_score'      => intval( $_POST['max_score'] ?? 100 ),
        'color'          => sanitize_hex_color( wp_unslash( $_POST['color'] ?? '#6c63ff' ) ) ?: '#6c63ff',
        'icon'           => sanitize_text_field( wp_unslash( $_POST['icon'] ?? '' ) ),
    ];

    $edit_rid = intval( $_POST['edit_rid'] ?? 0 );
    if ( $edit_rid ) {
        $wpdb->update( "{$p}scoring_rules", $rule_data, [ 'id' => $edit_rid, 'test_id' => $test_id ] );
        echo '<div class="notice notice-success"><p>' . esc_html__( 'Rule updated.', 'wp-psycho' ) . '</p></div>';
    } else {
        $wpdb->insert( "{$p}scoring_rules", $rule_data );
        echo '<div class="notice notice-success"><p>' . esc_html__( 'Rule added.', 'wp-psycho' ) . '</p></div>';
    }
    $editing_r = null;
}

$rules    = WP_Psycho_DB::get_scoring_rules( $test_id );
$back_url = admin_url( 'admin.php?page=psycho-tests' );
?>
<div class="wrap">
<h1><?php printf( esc_html__( 'Scoring Rules: %s', 'wp-psycho' ), esc_html( $test->title ) ); ?></h1>
<p><a href="<?php echo esc_url( $back_url ); ?>" class="button">&larr; <?php esc_html_e( 'Back to Tests', 'wp-psycho' ); ?></a></p>

<div style="display:grid;grid-template-columns:1fr 380px;gap:24px;margin-top:16px;">
<div>
<table class="wp-list-table widefat fixed striped">
    <thead>
        <tr>
            <th><?php esc_html_e( 'Label', 'wp-psycho' ); ?></th>
            <th><?php esc_html_e( 'Score Range', 'wp-psycho' ); ?></th>
            <th><?php esc_html_e( 'Color', 'wp-psycho' ); ?></th>
            <th><?php esc_html_e( 'Icon', 'wp-psycho' ); ?></th>
            <th><?php esc_html_e( 'Actions', 'wp-psycho' ); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php if ( $rules ) : foreach ( $rules as $r ) :
        $ed_url  = admin_url( 'admin.php?page=psycho-scoring&test_id=' . $test_id . '&edit_rule=' . $r->id );
        $del_url = wp_nonce_url( admin_url( 'admin.php?page=psycho-scoring&test_id=' . $test_id . '&delete_rule=' . $r->id ), 'psycho_del_rule_' . $r->id );
    ?>
        <tr>
            <td><strong style="color:<?php echo esc_attr( $r->color ); ?>"><?php echo esc_html( $r->label ); ?></strong></td>
            <td><?php echo intval( $r->min_score ); ?> – <?php echo intval( $r->max_score ); ?></td>
            <td><span style="display:inline-block;width:20px;height:20px;border-radius:4px;background:<?php echo esc_attr( $r->color ); ?>;vertical-align:middle;"></span> <?php echo esc_html( $r->color ); ?></td>
            <td><?php echo esc_html( $r->icon ); ?></td>
            <td>
                <a href="<?php echo esc_url( $ed_url ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'wp-psycho' ); ?></a>
                <a href="<?php echo esc_url( $del_url ); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e( 'Delete?', 'wp-psycho' ); ?>')"><?php esc_html_e( 'Delete', 'wp-psycho' ); ?></a>
            </td>
        </tr>
    <?php endforeach; else : ?>
        <tr><td colspan="5"><?php esc_html_e( 'No scoring rules yet.', 'wp-psycho' ); ?></td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>

<div>
<div class="postbox">
    <div class="postbox-header"><h2><?php echo $editing_r ? esc_html__( 'Edit Rule', 'wp-psycho' ) : esc_html__( 'Add Rule', 'wp-psycho' ); ?></h2></div>
    <div class="inside">
    <form method="post">
        <?php wp_nonce_field( 'psycho_scoring_nonce' ); ?>
        <?php if ( $editing_r ) : ?><input type="hidden" name="edit_rid" value="<?php echo intval( $editing_r->id ); ?>"><?php endif; ?>
        <table class="form-table" style="margin:0;">
            <tr><th><?php esc_html_e( 'Label', 'wp-psycho' ); ?> *</th>
                <td><input type="text" name="label" class="widefat" required value="<?php echo esc_attr( $editing_r->label ?? '' ); ?>" placeholder="e.g. High Achiever"></td></tr>
            <tr><th><?php esc_html_e( 'Description', 'wp-psycho' ); ?></th>
                <td><textarea name="description" class="widefat" rows="3"><?php echo esc_textarea( $editing_r->description ?? '' ); ?></textarea></td></tr>
            <tr><th><?php esc_html_e( 'Recommendation', 'wp-psycho' ); ?></th>
                <td><textarea name="recommendation" class="widefat" rows="3"><?php echo esc_textarea( $editing_r->recommendation ?? '' ); ?></textarea></td></tr>
            <tr><th><?php esc_html_e( 'Min Score', 'wp-psycho' ); ?></th>
                <td><input type="number" name="min_score" class="small-text" value="<?php echo intval( $editing_r->min_score ?? 0 ); ?>"></td></tr>
            <tr><th><?php esc_html_e( 'Max Score', 'wp-psycho' ); ?></th>
                <td><input type="number" name="max_score" class="small-text" value="<?php echo intval( $editing_r->max_score ?? 100 ); ?>"></td></tr>
            <tr><th><?php esc_html_e( 'Color', 'wp-psycho' ); ?></th>
                <td><input type="color" name="color" value="<?php echo esc_attr( $editing_r->color ?? '#6c63ff' ); ?>"></td></tr>
            <tr><th><?php esc_html_e( 'Icon (emoji)', 'wp-psycho' ); ?></th>
                <td><input type="text" name="icon" class="small-text" value="<?php echo esc_attr( $editing_r->icon ?? '' ); ?>" placeholder="🏆"></td></tr>
        </table>
        <p>
            <button type="submit" name="psycho_save_rule" class="button button-primary"><?php echo $editing_r ? esc_html__( 'Update Rule', 'wp-psycho' ) : esc_html__( 'Add Rule', 'wp-psycho' ); ?></button>
            <?php if ( $editing_r ) : ?><a href="<?php echo esc_url( admin_url( 'admin.php?page=psycho-scoring&test_id=' . $test_id ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'wp-psycho' ); ?></a><?php endif; ?>
        </p>
    </form>
    </div>
</div>
</div>
</div>
</div>
