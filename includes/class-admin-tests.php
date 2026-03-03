<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_Psycho_Admin_Tests {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_menus' ] );
    }

    public function register_menus() {
        add_menu_page(
            __( 'Psycho Tests', 'wp-psycho' ),
            __( 'Psycho Tests', 'wp-psycho' ),
            'manage_options',
            'psycho-tests',
            [ $this, 'tests_page' ],
            'dashicons-welcome-learn-more',
            26
        );
        add_submenu_page( 'psycho-tests', __( 'Tests', 'wp-psycho' ),           __( 'Tests', 'wp-psycho' ),           'manage_options', 'psycho-tests',     [ $this, 'tests_page' ] );
        add_submenu_page( 'psycho-tests', __( 'Questions', 'wp-psycho' ),        __( 'Questions', 'wp-psycho' ),        'manage_options', 'psycho-questions', [ $this, 'questions_page' ] );
        add_submenu_page( 'psycho-tests', __( 'Scoring Rules', 'wp-psycho' ),    __( 'Scoring Rules', 'wp-psycho' ),    'manage_options', 'psycho-scoring',   [ $this, 'scoring_page' ] );
        add_submenu_page( 'psycho-tests', __( 'All Reports', 'wp-psycho' ),      __( 'All Reports', 'wp-psycho' ),      'manage_options', 'psycho-reports',   [ $this, 'reports_page' ] );
        add_submenu_page( 'psycho-tests', __( 'Report Builder', 'wp-psycho' ),   __( 'Report Builder', 'wp-psycho' ),   'manage_options', 'psycho-builder',   [ $this, 'builder_page' ] );
        add_submenu_page( 'psycho-tests', __( 'Settings', 'wp-psycho' ),         __( 'Settings', 'wp-psycho' ),         'manage_options', 'psycho-settings',  [ $this, 'settings_page' ] );
    }

    public function tests_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        global $wpdb;
        $p = WP_Psycho_DB::get_prefix();

        // Handle delete
        if ( isset( $_GET['delete_test'], $_GET['_wpnonce'] ) ) {
            if ( wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'psycho_del_test_' . intval( $_GET['delete_test'] ) ) ) {
                $tid = intval( $_GET['delete_test'] );
                $wpdb->delete( "{$p}tests", [ 'id' => $tid ] );
                $wpdb->delete( "{$p}scoring_rules", [ 'test_id' => $tid ] );
                $questions = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$p}questions WHERE test_id = %d", $tid ) );
                foreach ( $questions as $qid ) {
                    $wpdb->delete( "{$p}options", [ 'question_id' => $qid ] );
                }
                $wpdb->delete( "{$p}questions", [ 'test_id' => $tid ] );
                echo '<div class="notice notice-success"><p>' . esc_html__( 'Test deleted.', 'wp-psycho' ) . '</p></div>';
            }
        }

        // Handle save
        $editing = null;
        if ( isset( $_GET['edit_test'] ) ) {
            $editing = WP_Psycho_DB::get_test( intval( $_GET['edit_test'] ) );
        }

        if ( isset( $_POST['psycho_save_test'] ) && wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ?? '' ), 'psycho_test_nonce' ) ) {
            $data = [
                'title'        => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
                'description'  => sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ),
                'category'     => sanitize_text_field( wp_unslash( $_POST['category'] ?? '' ) ),
                'type'         => sanitize_text_field( wp_unslash( $_POST['type'] ?? 'likert' ) ),
                'time_limit'   => intval( $_POST['time_limit'] ?? 0 ),
                'passkey'      => sanitize_text_field( wp_unslash( $_POST['passkey'] ?? '' ) ),
                'passkey_hint' => sanitize_text_field( wp_unslash( $_POST['passkey_hint'] ?? '' ) ),
                'max_attempts' => intval( $_POST['max_attempts'] ?? 0 ),
                'shuffle_q'    => isset( $_POST['shuffle_q'] ) ? 1 : 0,
                'status'       => intval( $_POST['status'] ?? 1 ),
            ];

            $edit_id = intval( $_POST['edit_id'] ?? 0 );
            if ( $edit_id ) {
                $wpdb->update( "{$p}tests", $data, [ 'id' => $edit_id ] );
                echo '<div class="notice notice-success"><p>' . esc_html__( 'Test updated.', 'wp-psycho' ) . '</p></div>';
                $editing = WP_Psycho_DB::get_test( $edit_id );
            } else {
                if ( empty( $data['passkey'] ) ) {
                    $data['passkey'] = wp_generate_password( 8, false );
                }
                $data['created_at'] = current_time( 'mysql' );
                $wpdb->insert( "{$p}tests", $data );
                echo '<div class="notice notice-success"><p>' . esc_html__( 'Test created.', 'wp-psycho' ) . '</p></div>';
                $editing = null;
            }
        }

        $tests = WP_Psycho_DB::get_tests();
        ?>
        <div class="wrap">
        <h1 class="wp-heading-inline"><?php esc_html_e( 'Psychometric Tests', 'wp-psycho' ); ?></h1>
        <hr class="wp-header-end">

        <div style="display:grid;grid-template-columns:1fr 380px;gap:24px;margin-top:20px;">
        <div>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Title', 'wp-psycho' ); ?></th>
                    <th><?php esc_html_e( 'Type', 'wp-psycho' ); ?></th>
                    <th><?php esc_html_e( 'Category', 'wp-psycho' ); ?></th>
                    <th><?php esc_html_e( 'Passkey', 'wp-psycho' ); ?></th>
                    <th><?php esc_html_e( 'Hint', 'wp-psycho' ); ?></th>
                    <th><?php esc_html_e( 'Time', 'wp-psycho' ); ?></th>
                    <th><?php esc_html_e( 'Questions', 'wp-psycho' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'wp-psycho' ); ?></th>
                    <th><?php esc_html_e( 'Actions', 'wp-psycho' ); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php if ( $tests ) : foreach ( $tests as $t ) :
                $q_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$p}questions WHERE test_id=%d", $t->id ) );
                $q_url   = admin_url( 'admin.php?page=psycho-questions&test_id=' . $t->id );
                $sc_url  = admin_url( 'admin.php?page=psycho-scoring&test_id=' . $t->id );
                $ed_url  = admin_url( 'admin.php?page=psycho-tests&edit_test=' . $t->id );
                $del_url = wp_nonce_url( admin_url( 'admin.php?page=psycho-tests&delete_test=' . $t->id ), 'psycho_del_test_' . $t->id );
            ?>
                <tr>
                    <td><strong><?php echo esc_html( $t->title ); ?></strong></td>
                    <td><?php echo esc_html( $t->type ); ?></td>
                    <td><?php echo esc_html( $t->category ); ?></td>
                    <td><?php if ( $t->passkey ) echo '<code style="background:#f0f4ff;padding:2px 6px;border-radius:4px;">' . esc_html( $t->passkey ) . '</code>'; ?></td>
                    <td><?php echo esc_html( $t->passkey_hint ); ?></td>
                    <td><?php echo $t->time_limit ? intval( $t->time_limit ) . ' ' . esc_html__( 'min', 'wp-psycho' ) : '—'; ?></td>
                    <td><a href="<?php echo esc_url( $q_url ); ?>"><?php echo intval( $q_count ); ?></a></td>
                    <td><?php echo $t->status ? '<span style="color:green;">&#9679; ' . esc_html__( 'Active', 'wp-psycho' ) . '</span>' : '<span style="color:#aaa;">&#9679; ' . esc_html__( 'Draft', 'wp-psycho' ) . '</span>'; ?></td>
                    <td>
                        <a href="<?php echo esc_url( $ed_url ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'wp-psycho' ); ?></a>
                        <a href="<?php echo esc_url( $q_url ); ?>" class="button button-small"><?php esc_html_e( 'Questions', 'wp-psycho' ); ?></a>
                        <a href="<?php echo esc_url( $sc_url ); ?>" class="button button-small"><?php esc_html_e( 'Scoring', 'wp-psycho' ); ?></a>
                        <a href="<?php echo esc_url( $del_url ); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e( 'Delete this test?', 'wp-psycho' ); ?>')"><?php esc_html_e( 'Delete', 'wp-psycho' ); ?></a>
                    </td>
                </tr>
            <?php endforeach; else : ?>
                <tr><td colspan="9"><?php esc_html_e( 'No tests yet.', 'wp-psycho' ); ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>

        <div>
        <div class="postbox">
            <div class="postbox-header"><h2><?php echo $editing ? esc_html__( 'Edit Test', 'wp-psycho' ) : esc_html__( 'Add New Test', 'wp-psycho' ); ?></h2></div>
            <div class="inside">
            <form method="post">
                <?php wp_nonce_field( 'psycho_test_nonce' ); ?>
                <?php if ( $editing ) : ?>
                <input type="hidden" name="edit_id" value="<?php echo intval( $editing->id ); ?>">
                <?php endif; ?>
                <table class="form-table" style="margin:0;">
                    <tr><th><?php esc_html_e( 'Title', 'wp-psycho' ); ?> *</th>
                        <td><input type="text" name="title" class="widefat" value="<?php echo esc_attr( $editing->title ?? '' ); ?>" required></td></tr>
                    <tr><th><?php esc_html_e( 'Description', 'wp-psycho' ); ?></th>
                        <td><textarea name="description" class="widefat" rows="3"><?php echo esc_textarea( $editing->description ?? '' ); ?></textarea></td></tr>
                    <tr><th><?php esc_html_e( 'Type', 'wp-psycho' ); ?></th>
                        <td><select name="type" class="widefat">
                            <?php foreach ( [ 'likert' => 'Likert Scale', 'personality' => 'Personality', 'aptitude' => 'Aptitude', 'skills' => 'Skills', 'mixed' => 'Mixed' ] as $val => $lbl ) : ?>
                            <option value="<?php echo esc_attr( $val ); ?>" <?php selected( ( $editing->type ?? '' ), $val ); ?>><?php echo esc_html( $lbl ); ?></option>
                            <?php endforeach; ?>
                        </select></td></tr>
                    <tr><th><?php esc_html_e( 'Category', 'wp-psycho' ); ?></th>
                        <td><input type="text" name="category" class="widefat" value="<?php echo esc_attr( $editing->category ?? '' ); ?>"></td></tr>
                    <tr><th><?php esc_html_e( 'Time Limit (min)', 'wp-psycho' ); ?></th>
                        <td><input type="number" name="time_limit" class="small-text" min="0" value="<?php echo intval( $editing->time_limit ?? 0 ); ?>"> <span class="description"><?php esc_html_e( '0 = unlimited', 'wp-psycho' ); ?></span></td></tr>
                    <tr><th><?php esc_html_e( 'Passkey', 'wp-psycho' ); ?></th>
                        <td><input type="text" name="passkey" class="widefat" value="<?php echo esc_attr( $editing->passkey ?? '' ); ?>">
                        <p class="description"><?php esc_html_e( 'Leave blank to auto-generate on create. Clear to remove.', 'wp-psycho' ); ?></p></td></tr>
                    <tr><th><?php esc_html_e( 'Passkey Hint', 'wp-psycho' ); ?></th>
                        <td><input type="text" name="passkey_hint" class="widefat" value="<?php echo esc_attr( $editing->passkey_hint ?? '' ); ?>"></td></tr>
                    <tr><th><?php esc_html_e( 'Max Attempts', 'wp-psycho' ); ?></th>
                        <td><input type="number" name="max_attempts" class="small-text" min="0" value="<?php echo intval( $editing->max_attempts ?? 0 ); ?>"> <span class="description"><?php esc_html_e( '0 = unlimited', 'wp-psycho' ); ?></span></td></tr>
                    <tr><th><?php esc_html_e( 'Shuffle Questions', 'wp-psycho' ); ?></th>
                        <td><input type="checkbox" name="shuffle_q" value="1" <?php checked( $editing->shuffle_q ?? 0, 1 ); ?>></td></tr>
                    <tr><th><?php esc_html_e( 'Status', 'wp-psycho' ); ?></th>
                        <td><select name="status" class="widefat">
                            <option value="1" <?php selected( $editing->status ?? 1, 1 ); ?>><?php esc_html_e( 'Active', 'wp-psycho' ); ?></option>
                            <option value="0" <?php selected( $editing->status ?? 1, 0 ); ?>><?php esc_html_e( 'Draft', 'wp-psycho' ); ?></option>
                        </select></td></tr>
                </table>
                <p>
                    <button type="submit" name="psycho_save_test" class="button button-primary"><?php echo $editing ? esc_html__( 'Update Test', 'wp-psycho' ) : esc_html__( 'Create Test', 'wp-psycho' ); ?></button>
                    <?php if ( $editing ) : ?><a href="<?php echo esc_url( admin_url( 'admin.php?page=psycho-tests' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'wp-psycho' ); ?></a><?php endif; ?>
                </p>
            </form>
            </div>
        </div>
        </div>
        </div>
        </div>
        <?php
    }

    public function questions_page() {
        include WP_PSYCHO_PATH . 'admin/questions.php';
    }

    public function scoring_page() {
        include WP_PSYCHO_PATH . 'admin/scoring.php';
    }

    public function reports_page() {
        include WP_PSYCHO_PATH . 'admin/reports.php';
    }

    public function builder_page() {
        include WP_PSYCHO_PATH . 'admin/builder.php';
    }

    public function settings_page() {
        include WP_PSYCHO_PATH . 'admin/settings.php';
    }
}
