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

// Handle delete rule
if ( isset( $_GET['delete_rule'] ) && isset( $_GET['_wpnonce'] ) ) {
    $del_id = absint( $_GET['delete_rule'] );
    if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'psycho_del_rule_' . $del_id ) ) {
        $wpdb->delete( $p . 'scoring_rules', [ 'id' => $del_id ] );
        wp_redirect( admin_url( 'admin.php?page=psycho-scoring&test_id=' . $test_id . '&deleted=1' ) );
        exit;
    }
}

// Handle save
if ( isset( $_POST['psycho_save_scoring'] ) && isset( $_POST['_wpnonce'] ) ) {
    if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'psycho_scoring_nonce' ) ) {
        $rule_id = isset( $_POST['rule_id'] ) ? absint( $_POST['rule_id'] ) : 0;
        $data = [
            'test_id'        => $test_id,
            'label'          => sanitize_text_field( wp_unslash( $_POST['label'] ?? '' ) ),
            'description'    => sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ),
            'recommendation' => sanitize_textarea_field( wp_unslash( $_POST['recommendation'] ?? '' ) ),
            'min_score'      => intval( $_POST['min_score'] ?? 0 ),
            'max_score'      => intval( $_POST['max_score'] ?? 100 ),
            'color'          => sanitize_hex_color( wp_unslash( $_POST['color'] ?? '#6c63ff' ) ) ?: '#6c63ff',
            'icon'           => sanitize_text_field( wp_unslash( $_POST['icon'] ?? '' ) ),
        ];

        if ( $rule_id ) {
            $wpdb->update( $p . 'scoring_rules', $data, [ 'id' => $rule_id ] );
        } else {
            $wpdb->insert( $p . 'scoring_rules', $data );
        }
        wp_redirect( admin_url( 'admin.php?page=psycho-scoring&test_id=' . $test_id . '&saved=1' ) );
        exit;
    }
}

$edit_id = isset( $_GET['edit_rule'] ) ? absint( $_GET['edit_rule'] ) : 0;
$editing = $edit_id ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$p}scoring_rules WHERE id = %d", $edit_id ) ) : null;
$rules   = WP_Psycho_DB::get_scoring_rules( $test_id );
?>
<div class="wrap">
    <h1><?php printf( esc_html__( 'Scoring Rules — %s', 'wp-psycho' ), esc_html( $test->title ) ); ?></h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=psycho-tests' ) ); ?>" class="button">&larr; <?php esc_html_e( 'Back to Tests', 'wp-psycho' ); ?></a>
    &nbsp;
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=psycho-questions&test_id=' . $test_id ) ); ?>" class="button"><?php esc_html_e( 'Questions', 'wp-psycho' ); ?></a>

    <?php if ( isset( $_GET['saved'] ) ): ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Scoring rule saved.', 'wp-psycho' ); ?></p></div>
    <?php endif; ?>
    <?php if ( isset( $_GET['deleted'] ) ): ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Scoring rule deleted.', 'wp-psycho' ); ?></p></div>
    <?php endif; ?>

    <h2><?php echo $editing ? esc_html__( 'Edit Scoring Rule', 'wp-psycho' ) : esc_html__( 'Add Scoring Rule', 'wp-psycho' ); ?></h2>
    <form method="post">
        <?php wp_nonce_field( 'psycho_scoring_nonce' ); ?>
        <?php if ( $editing ): ?><input type="hidden" name="rule_id" value="<?php echo esc_attr( $editing->id ); ?>"><?php endif; ?>
        <table class="form-table">
            <tr>
                <th><label><?php esc_html_e( 'Label', 'wp-psycho' ); ?> *</label></th>
                <td><input type="text" name="label" class="regular-text" required value="<?php echo esc_attr( $editing->label ?? '' ); ?>" placeholder="e.g. High Potential"></td>
            </tr>
            <tr>
                <th><label><?php esc_html_e( 'Description', 'wp-psycho' ); ?></label></th>
                <td><textarea name="description" rows="4" class="large-text"><?php echo esc_textarea( $editing->description ?? '' ); ?></textarea></td>
            </tr>
            <tr>
                <th><label><?php esc_html_e( 'Recommendation', 'wp-psycho' ); ?></label></th>
                <td><textarea name="recommendation" rows="4" class="large-text"><?php echo esc_textarea( $editing->recommendation ?? '' ); ?></textarea></td>
            </tr>
            <tr>
                <th><label><?php esc_html_e( 'Min Score', 'wp-psycho' ); ?></label></th>
                <td><input type="number" name="min_score" class="small-text" value="<?php echo esc_attr( $editing->min_score ?? 0 ); ?>"></td>
            </tr>
            <tr>
                <th><label><?php esc_html_e( 'Max Score', 'wp-psycho' ); ?></label></th>
                <td><input type="number" name="max_score" class="small-text" value="<?php echo esc_attr( $editing->max_score ?? 100 ); ?>"></td>
            </tr>
            <tr>
                <th><label><?php esc_html_e( 'Color', 'wp-psycho' ); ?></label></th>
                <td><input type="color" name="color" value="<?php echo esc_attr( $editing->color ?? '#6c63ff' ); ?>"></td>
            </tr>
            <tr>
                <th><label><?php esc_html_e( 'Icon (emoji)', 'wp-psycho' ); ?></label></th>
                <td><input type="text" name="icon" class="small-text" value="<?php echo esc_attr( $editing->icon ?? '' ); ?>" placeholder="🏆" maxlength="5"></td>
            </tr>
        </table>
        <input type="submit" name="psycho_save_scoring" class="button button-primary" value="<?php echo $editing ? esc_attr__( 'Update Rule', 'wp-psycho' ) : esc_attr__( 'Add Rule', 'wp-psycho' ); ?>">
        <?php if ( $editing ): ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=psycho-scoring&test_id=' . $test_id ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'wp-psycho' ); ?></a>
        <?php endif; ?>
    </form>

    <hr>
    <h2><?php esc_html_e( 'Scoring Rules', 'wp-psycho' ); ?> (<?php echo count( $rules ); ?>)</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Label', 'wp-psycho' ); ?></th>
                <th style="width:80px;"><?php esc_html_e( 'Min', 'wp-psycho' ); ?></th>
                <th style="width:80px;"><?php esc_html_e( 'Max', 'wp-psycho' ); ?></th>
                <th style="width:80px;"><?php esc_html_e( 'Color', 'wp-psycho' ); ?></th>
                <th style="width:60px;"><?php esc_html_e( 'Icon', 'wp-psycho' ); ?></th>
                <th style="width:130px;"><?php esc_html_e( 'Actions', 'wp-psycho' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( $rules ): foreach ( $rules as $rule ): ?>
                <tr>
                    <td><strong><?php echo esc_html( $rule->label ); ?></strong></td>
                    <td><?php echo esc_html( $rule->min_score ); ?></td>
                    <td><?php echo esc_html( $rule->max_score ); ?></td>
                    <td><span style="display:inline-block;width:24px;height:24px;background:<?php echo esc_attr( $rule->color ); ?>;border-radius:4px;vertical-align:middle;"></span> <?php echo esc_html( $rule->color ); ?></td>
                    <td><?php echo esc_html( $rule->icon ); ?></td>
                    <td>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=psycho-scoring&test_id=' . $test_id . '&edit_rule=' . $rule->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'wp-psycho' ); ?></a>
                        <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=psycho-scoring&test_id=' . $test_id . '&delete_rule=' . $rule->id ), 'psycho_del_rule_' . $rule->id ) ); ?>"
                           class="button button-small button-link-delete"
                           onclick="return confirm('<?php esc_attr_e( 'Delete this rule?', 'wp-psycho' ); ?>')"><?php esc_html_e( 'Delete', 'wp-psycho' ); ?></a>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="6"><?php esc_html_e( 'No scoring rules yet.', 'wp-psycho' ); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
