<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_Psycho_Admin_Tests {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_menus' ] );
        add_action( 'admin_post_psycho_save_test',   [ $this, 'handle_save_test' ] );
        add_action( 'admin_post_psycho_delete_test', [ $this, 'handle_delete_test' ] );
    }

    public function register_menus() {
        add_menu_page(
            __( 'Psychometric Tests', 'wp-psycho' ),
            __( 'Psychometric Tests', 'wp-psycho' ),
            'manage_options',
            'psycho-tests',
            [ $this, 'tests_page' ],
            'dashicons-welcome-learn-more',
            26
        );

        add_submenu_page( 'psycho-tests', __( 'All Tests', 'wp-psycho' ),      __( 'All Tests', 'wp-psycho' ),      'manage_options', 'psycho-tests',          [ $this, 'tests_page' ] );
        add_submenu_page( 'psycho-tests', __( 'Questions', 'wp-psycho' ),       __( 'Questions', 'wp-psycho' ),       'manage_options', 'psycho-questions',      [ $this, 'questions_page' ] );
        add_submenu_page( 'psycho-tests', __( 'Scoring Rules', 'wp-psycho' ),   __( 'Scoring Rules', 'wp-psycho' ),   'manage_options', 'psycho-scoring',        [ $this, 'scoring_page' ] );
        add_submenu_page( 'psycho-tests', __( 'All Reports', 'wp-psycho' ),     __( 'All Reports', 'wp-psycho' ),     'manage_options', 'psycho-reports',        [ $this, 'reports_page' ] );
        add_submenu_page( 'psycho-tests', __( 'Report Builder', 'wp-psycho' ),  __( 'Report Builder', 'wp-psycho' ),  'manage_options', 'psycho-builder',        [ $this, 'builder_page' ] );
        add_submenu_page( 'psycho-tests', __( 'Settings', 'wp-psycho' ),        __( 'Settings', 'wp-psycho' ),        'manage_options', 'psycho-settings',       [ $this, 'settings_page' ] );
    }

    public function tests_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        global $wpdb;
        $p       = WP_Psycho_DB::get_prefix();
        $tests   = WP_Psycho_DB::get_tests();
        $edit_id = isset( $_GET['edit_id'] ) ? absint( $_GET['edit_id'] ) : 0;
        $editing = $edit_id ? WP_Psycho_DB::get_test( $edit_id ) : null;
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Psychometric Tests', 'wp-psycho' ); ?></h1>

            <?php if ( isset( $_GET['saved'] ) ): ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Test saved successfully.', 'wp-psycho' ); ?></p></div>
            <?php endif; ?>
            <?php if ( isset( $_GET['deleted'] ) ): ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Test deleted.', 'wp-psycho' ); ?></p></div>
            <?php endif; ?>

            <h2><?php echo $editing ? esc_html__( 'Edit Test', 'wp-psycho' ) : esc_html__( 'Add New Test', 'wp-psycho' ); ?></h2>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'psycho_test_nonce' ); ?>
                <input type="hidden" name="action" value="psycho_save_test">
                <?php if ( $editing ): ?>
                    <input type="hidden" name="test_id" value="<?php echo esc_attr( $editing->id ); ?>">
                <?php endif; ?>
                <table class="form-table">
                    <tr>
                        <th><label for="test_title"><?php esc_html_e( 'Title', 'wp-psycho' ); ?> *</label></th>
                        <td><input type="text" id="test_title" name="title" class="regular-text" required
                                   value="<?php echo esc_attr( $editing->title ?? '' ); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="test_desc"><?php esc_html_e( 'Description', 'wp-psycho' ); ?></label></th>
                        <td><textarea id="test_desc" name="description" rows="4" class="large-text"><?php echo esc_textarea( $editing->description ?? '' ); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="test_type"><?php esc_html_e( 'Type', 'wp-psycho' ); ?></label></th>
                        <td>
                            <select id="test_type" name="type">
                                <?php foreach ( [ 'likert' => 'Likert Scale', 'multiple_choice' => 'Multiple Choice', 'personality' => 'Personality', 'aptitude' => 'Aptitude', 'mixed' => 'Mixed' ] as $val => $label ): ?>
                                    <option value="<?php echo esc_attr( $val ); ?>" <?php selected( ($editing->type ?? 'likert'), $val ); ?>><?php echo esc_html( $label ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="test_cat"><?php esc_html_e( 'Category', 'wp-psycho' ); ?></label></th>
                        <td><input type="text" id="test_cat" name="category" class="regular-text"
                                   value="<?php echo esc_attr( $editing->category ?? '' ); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="test_time"><?php esc_html_e( 'Time Limit (minutes, 0=none)', 'wp-psycho' ); ?></label></th>
                        <td><input type="number" id="test_time" name="time_limit" min="0" class="small-text"
                                   value="<?php echo esc_attr( $editing->time_limit ?? 0 ); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="test_passkey"><?php esc_html_e( 'Passkey (leave blank = open test)', 'wp-psycho' ); ?></label></th>
                        <td>
                            <input type="text" id="test_passkey" name="passkey" class="regular-text"
                                   value="<?php echo esc_attr( $editing->passkey ?? '' ); ?>"
                                   placeholder="<?php esc_attr_e( 'e.g. MYTEST2024', 'wp-psycho' ); ?>">
                            <?php if ( ! $editing ): ?>
                                <p class="description"><?php esc_html_e( 'Leave blank for a new test to auto-generate a passkey.', 'wp-psycho' ); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="test_passkey_hint"><?php esc_html_e( 'Passkey Hint', 'wp-psycho' ); ?></label></th>
                        <td><input type="text" id="test_passkey_hint" name="passkey_hint" class="regular-text"
                                   value="<?php echo esc_attr( $editing->passkey_hint ?? '' ); ?>"
                                   placeholder="<?php esc_attr_e( 'Shown to participant if they enter wrong passkey', 'wp-psycho' ); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="test_max_attempts"><?php esc_html_e( 'Max Attempts (0=unlimited)', 'wp-psycho' ); ?></label></th>
                        <td><input type="number" id="test_max_attempts" name="max_attempts" min="0" class="small-text"
                                   value="<?php echo esc_attr( $editing->max_attempts ?? 0 ); ?>"></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Shuffle Questions', 'wp-psycho' ); ?></th>
                        <td><label><input type="checkbox" name="shuffle_q" value="1" <?php checked( $editing->shuffle_q ?? 0, 1 ); ?>>
                            <?php esc_html_e( 'Randomize question order for each participant', 'wp-psycho' ); ?></label></td>
                    </tr>
                    <tr>
                        <th><label for="test_status"><?php esc_html_e( 'Status', 'wp-psycho' ); ?></label></th>
                        <td>
                            <select id="test_status" name="status">
                                <option value="1" <?php selected( ($editing->status ?? 1), 1 ); ?>><?php esc_html_e( 'Active', 'wp-psycho' ); ?></option>
                                <option value="0" <?php selected( ($editing->status ?? 1), 0 ); ?>><?php esc_html_e( 'Inactive', 'wp-psycho' ); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button( $editing ? __( 'Update Test', 'wp-psycho' ) : __( 'Add Test', 'wp-psycho' ) ); ?>
                <?php if ( $editing ): ?>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=psycho-tests' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'wp-psycho' ); ?></a>
                <?php endif; ?>
            </form>

            <hr>
            <h2><?php esc_html_e( 'All Tests', 'wp-psycho' ); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Title', 'wp-psycho' ); ?></th>
                        <th><?php esc_html_e( 'Type', 'wp-psycho' ); ?></th>
                        <th><?php esc_html_e( 'Category', 'wp-psycho' ); ?></th>
                        <th><?php esc_html_e( 'Passkey', 'wp-psycho' ); ?></th>
                        <th><?php esc_html_e( 'Passkey Hint', 'wp-psycho' ); ?></th>
                        <th><?php esc_html_e( 'Time Limit', 'wp-psycho' ); ?></th>
                        <th><?php esc_html_e( 'Questions', 'wp-psycho' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'wp-psycho' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'wp-psycho' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $tests ): foreach ( $tests as $test ):
                        $q_count = (int) $wpdb->get_var( $wpdb->prepare(
                            "SELECT COUNT(*) FROM {$p}questions WHERE test_id = %d", $test->id
                        ) );
                        $q_url = admin_url( 'admin.php?page=psycho-questions&test_id=' . $test->id );
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html( $test->title ); ?></strong></td>
                            <td><?php echo esc_html( $test->type ); ?></td>
                            <td><?php echo esc_html( $test->category ); ?></td>
                            <td><?php echo $test->passkey ? '<code style="background:#f0f0f0;padding:2px 6px;border-radius:3px;">' . esc_html( $test->passkey ) . '</code>' : '<em>' . esc_html__( 'Open', 'wp-psycho' ) . '</em>'; ?></td>
                            <td><?php echo esc_html( $test->passkey_hint ); ?></td>
                            <td><?php echo $test->time_limit ? esc_html( $test->time_limit ) . ' ' . esc_html__( 'min', 'wp-psycho' ) : esc_html__( 'None', 'wp-psycho' ); ?></td>
                            <td><a href="<?php echo esc_url( $q_url ); ?>"><?php echo (int) $q_count; ?> <?php esc_html_e( 'questions', 'wp-psycho' ); ?></a></td>
                            <td><?php echo $test->status ? '<span style="color:green;">' . esc_html__( 'Active', 'wp-psycho' ) . '</span>' : '<span style="color:gray;">' . esc_html__( 'Inactive', 'wp-psycho' ) . '</span>'; ?></td>
                            <td>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=psycho-tests&edit_id=' . $test->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'wp-psycho' ); ?></a>
                                <a href="<?php echo esc_url( $q_url ); ?>" class="button button-small"><?php esc_html_e( 'Questions', 'wp-psycho' ); ?></a>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=psycho-scoring&test_id=' . $test->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Scoring', 'wp-psycho' ); ?></a>
                                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=psycho_delete_test&test_id=' . $test->id ), 'psycho_delete_test_' . $test->id ) ); ?>"
                                   class="button button-small button-link-delete"
                                   onclick="return confirm('<?php esc_attr_e( 'Delete this test and all its data?', 'wp-psycho' ); ?>')"><?php esc_html_e( 'Delete', 'wp-psycho' ); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="9"><?php esc_html_e( 'No tests found. Add your first test above.', 'wp-psycho' ); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function handle_save_test() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( __( 'Unauthorized', 'wp-psycho' ) );
        check_admin_referer( 'psycho_test_nonce' );

        global $wpdb;
        $p       = WP_Psycho_DB::get_prefix();
        $test_id = isset( $_POST['test_id'] ) ? absint( $_POST['test_id'] ) : 0;

        $passkey = sanitize_text_field( wp_unslash( $_POST['passkey'] ?? '' ) );
        if ( ! $test_id && empty( $passkey ) ) {
            $passkey = strtoupper( wp_generate_password( 8, false ) );
        }

        $data = [
            'title'        => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
            'description'  => sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ),
            'category'     => sanitize_text_field( wp_unslash( $_POST['category'] ?? '' ) ),
            'type'         => sanitize_text_field( wp_unslash( $_POST['type'] ?? 'likert' ) ),
            'time_limit'   => absint( $_POST['time_limit'] ?? 0 ),
            'passkey'      => $passkey,
            'passkey_hint' => sanitize_text_field( wp_unslash( $_POST['passkey_hint'] ?? '' ) ),
            'max_attempts' => absint( $_POST['max_attempts'] ?? 0 ),
            'shuffle_q'    => isset( $_POST['shuffle_q'] ) ? 1 : 0,
            'status'       => isset( $_POST['status'] ) ? absint( $_POST['status'] ) : 1,
        ];

        if ( $test_id ) {
            $wpdb->update( $p . 'tests', $data, [ 'id' => $test_id ] );
        } else {
            $data['created_at'] = current_time( 'mysql' );
            $wpdb->insert( $p . 'tests', $data );
        }

        wp_redirect( admin_url( 'admin.php?page=psycho-tests&saved=1' ) );
        exit;
    }

    public function handle_delete_test() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( __( 'Unauthorized', 'wp-psycho' ) );
        $test_id = absint( $_GET['test_id'] ?? 0 );
        check_admin_referer( 'psycho_delete_test_' . $test_id );

        global $wpdb;
        $p = WP_Psycho_DB::get_prefix();

        $question_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$p}questions WHERE test_id = %d", $test_id ) );
        if ( $question_ids ) {
            // IDs come from DB so they are integers; intval ensures safety
            $ids_in       = implode( ',', array_map( 'intval', $question_ids ) );
            $wpdb->query( "DELETE FROM {$p}options WHERE question_id IN ($ids_in)" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        }
        $wpdb->delete( $p . 'questions',     [ 'test_id' => $test_id ] );
        $wpdb->delete( $p . 'scoring_rules', [ 'test_id' => $test_id ] );
        $wpdb->delete( $p . 'report_templates', [ 'test_id' => $test_id ] );
        $wpdb->delete( $p . 'tests',         [ 'id' => $test_id ] );

        wp_redirect( admin_url( 'admin.php?page=psycho-tests&deleted=1' ) );
        exit;
    }

    public function questions_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        include WP_PSYCHO_PATH . 'admin/questions.php';
    }

    public function scoring_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        include WP_PSYCHO_PATH . 'admin/scoring.php';
    }

    public function reports_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        include WP_PSYCHO_PATH . 'admin/reports.php';
    }

    public function builder_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        include WP_PSYCHO_PATH . 'admin/builder.php';
    }

    public function settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        include WP_PSYCHO_PATH . 'admin/settings.php';
    }
}
