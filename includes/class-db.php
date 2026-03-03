<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_Psycho_DB {

    public static function get_prefix() {
        global $wpdb;
        return $wpdb->prefix . 'psycho_';
    }

    public static function install() {
        global $wpdb;
        $p      = self::get_prefix();
        $cs     = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta( "CREATE TABLE {$p}tests (
            id            bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title         varchar(255) NOT NULL DEFAULT '',
            description   text,
            category      varchar(100) DEFAULT '',
            type          varchar(50)  DEFAULT 'likert',
            time_limit    int(11)      DEFAULT 0,
            passkey       varchar(100) DEFAULT '',
            passkey_hint  varchar(255) DEFAULT '',
            status        tinyint(1)   DEFAULT 1,
            shuffle_q     tinyint(1)   DEFAULT 0,
            max_attempts  int(11)      DEFAULT 0,
            created_at    datetime     DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $cs;" );

        dbDelta( "CREATE TABLE {$p}questions (
            id         bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            test_id    bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            question   text NOT NULL,
            type       varchar(50) DEFAULT 'likert',
            trait      varchar(100) DEFAULT '',
            order_num  int(11) DEFAULT 0,
            required   tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            KEY test_id (test_id)
        ) $cs;" );

        dbDelta( "CREATE TABLE {$p}options (
            id          bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            question_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            option_text varchar(255) NOT NULL DEFAULT '',
            score       int(11) DEFAULT 0,
            order_num   int(11) DEFAULT 0,
            PRIMARY KEY (id),
            KEY question_id (question_id)
        ) $cs;" );

        dbDelta( "CREATE TABLE {$p}scoring_rules (
            id             bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            test_id        bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            label          varchar(100) DEFAULT '',
            description    text,
            recommendation text,
            min_score      int(11) DEFAULT 0,
            max_score      int(11) DEFAULT 100,
            color          varchar(20) DEFAULT '#6c63ff',
            icon           varchar(10)  DEFAULT '',
            PRIMARY KEY (id),
            KEY test_id (test_id)
        ) $cs;" );

        dbDelta( "CREATE TABLE {$p}participants (
            id          bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name        varchar(255) NOT NULL DEFAULT '',
            email       varchar(255) NOT NULL DEFAULT '',
            phone       varchar(50)  DEFAULT '',
            session_key varchar(64)  DEFAULT '',
            created_at  datetime     DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_key (session_key)
        ) $cs;" );

        dbDelta( "CREATE TABLE {$p}attempts (
            id             bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            participant_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            test_id        bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            status         varchar(20) DEFAULT 'started',
            started_at     datetime DEFAULT CURRENT_TIMESTAMP,
            completed_at   datetime DEFAULT NULL,
            time_taken     int(11) DEFAULT 0,
            PRIMARY KEY (id),
            KEY participant_id (participant_id),
            KEY test_id (test_id)
        ) $cs;" );

        dbDelta( "CREATE TABLE {$p}responses (
            id          bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            attempt_id  bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            question_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            option_id   bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            score       int(11) DEFAULT 0,
            PRIMARY KEY (id),
            KEY attempt_id (attempt_id)
        ) $cs;" );

        dbDelta( "CREATE TABLE {$p}results (
            id             bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            attempt_id     bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            participant_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            test_id        bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            total_score    int(11) DEFAULT 0,
            trait_scores   longtext DEFAULT '',
            result_label   varchar(100) DEFAULT '',
            result_desc    text,
            recommendation text,
            result_color   varchar(20) DEFAULT '#6c63ff',
            result_icon    varchar(10) DEFAULT '',
            pdf_path       varchar(500) DEFAULT '',
            notified       tinyint(1) DEFAULT 0,
            created_at     datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY participant_id (participant_id),
            KEY test_id (test_id)
        ) $cs;" );

        dbDelta( "CREATE TABLE {$p}report_templates (
            id          bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            test_id     bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            header_text text,
            footer_text text,
            show_traits tinyint(1) DEFAULT 1,
            show_chart  tinyint(1) DEFAULT 1,
            logo_url    varchar(500) DEFAULT '',
            updated_at  datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY test_id (test_id)
        ) $cs;" );
    }

    public static function get_tests( $status = null ) {
        global $wpdb;
        $p = self::get_prefix();
        if ( $status !== null ) {
            return $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM {$p}tests WHERE status = %d ORDER BY created_at DESC",
                $status
            ) );
        }
        return $wpdb->get_results( "SELECT * FROM {$p}tests ORDER BY created_at DESC" );
    }

    public static function get_test( $id ) {
        global $wpdb;
        $p = self::get_prefix();
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$p}tests WHERE id = %d", $id ) );
    }

    public static function get_questions( $test_id ) {
        global $wpdb;
        $p = self::get_prefix();
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$p}questions WHERE test_id = %d ORDER BY order_num ASC, id ASC",
            $test_id
        ) );
    }

    public static function get_options( $question_id ) {
        global $wpdb;
        $p = self::get_prefix();
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$p}options WHERE question_id = %d ORDER BY order_num ASC, id ASC",
            $question_id
        ) );
    }

    public static function get_participant( $session_key ) {
        global $wpdb;
        $p = self::get_prefix();
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$p}participants WHERE session_key = %s",
            sanitize_text_field( $session_key )
        ) );
    }

    public static function get_result( $result_id ) {
        global $wpdb;
        $p = self::get_prefix();
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT r.*, p.name AS participant_name, p.email AS participant_email,
                    p.phone AS participant_phone, t.title AS test_title
             FROM {$p}results r
             JOIN {$p}participants p ON p.id = r.participant_id
             JOIN {$p}tests t ON t.id = r.test_id
             WHERE r.id = %d",
            $result_id
        ) );
    }

    public static function get_scoring_rules( $test_id ) {
        global $wpdb;
        $p = self::get_prefix();
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$p}scoring_rules WHERE test_id = %d ORDER BY min_score ASC",
            $test_id
        ) );
    }

    public static function get_all_results( $filters = [] ) {
        global $wpdb;
        $p     = self::get_prefix();
        $where = '1=1';
        $args  = [];

        if ( ! empty( $filters['test_id'] ) ) {
            $where .= ' AND r.test_id = %d';
            $args[] = intval( $filters['test_id'] );
        }
        if ( ! empty( $filters['search'] ) ) {
            $where .= ' AND (p.name LIKE %s OR p.email LIKE %s)';
            $like   = '%' . $wpdb->esc_like( $filters['search'] ) . '%';
            $args[] = $like;
            $args[] = $like;
        }

        $limit  = isset( $filters['limit'] ) ? intval( $filters['limit'] ) : 20;
        $offset = isset( $filters['offset'] ) ? intval( $filters['offset'] ) : 0;

        $sql = "SELECT r.*, p.name AS participant_name, p.email AS participant_email,
                       p.phone AS participant_phone, t.title AS test_title
                FROM {$p}results r
                JOIN {$p}participants p ON p.id = r.participant_id
                JOIN {$p}tests t ON t.id = r.test_id
                WHERE $where
                ORDER BY r.created_at DESC
                LIMIT %d OFFSET %d";

        $args[] = $limit;
        $args[] = $offset;

        return $wpdb->get_results( $wpdb->prepare( $sql, ...$args ) );
    }

    public static function count_results( $filters = [] ) {
        global $wpdb;
        $p     = self::get_prefix();
        $where = '1=1';
        $args  = [];

        if ( ! empty( $filters['test_id'] ) ) {
            $where .= ' AND r.test_id = %d';
            $args[] = intval( $filters['test_id'] );
        }
        if ( ! empty( $filters['search'] ) ) {
            $where .= ' AND (p.name LIKE %s OR p.email LIKE %s)';
            $like   = '%' . $wpdb->esc_like( $filters['search'] ) . '%';
            $args[] = $like;
            $args[] = $like;
        }

        $sql = "SELECT COUNT(*) FROM {$p}results r
                JOIN {$p}participants p ON p.id = r.participant_id
                JOIN {$p}tests t ON t.id = r.test_id
                WHERE $where";

        if ( ! empty( $args ) ) {
            return (int) $wpdb->get_var( $wpdb->prepare( $sql, ...$args ) );
        }
        return (int) $wpdb->get_var( $sql );
    }
}
