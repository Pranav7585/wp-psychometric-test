<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) return;

global $wpdb;
$p = WP_Psycho_DB::get_prefix();

// CSV Export
if ( isset( $_GET['psycho_export_csv'] ) && isset( $_GET['_wpnonce'] ) ) {
    if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'psycho_csv_nonce' ) ) {
        $all = WP_Psycho_DB::get_all_results( [ 'limit' => 9999 ] );
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="psycho-results-' . gmdate( 'Y-m-d' ) . '.csv"' );
        $out = fopen( 'php://output', 'w' );
        fputcsv( $out, [ 'ID', 'Name', 'Email', 'Phone', 'Test', 'Score', 'Result', 'Date' ] );
        foreach ( $all as $r ) {
            fputcsv( $out, [
                $r->id, $r->participant_name, $r->participant_email, $r->participant_phone,
                $r->test_title, $r->total_score, $r->result_label,
                date( 'Y-m-d H:i', strtotime( $r->created_at ) ),
            ] );
        }
        fclose( $out );
        exit;
    }
}

// Handle delete result
if ( isset( $_GET['delete_result'] ) && isset( $_GET['_wpnonce'] ) ) {
    $del_id = absint( $_GET['delete_result'] );
    if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'psycho_del_result_' . $del_id ) ) {
        $result = WP_Psycho_DB::get_result( $del_id );
        if ( $result && ! empty( $result->pdf_path ) && file_exists( $result->pdf_path ) ) {
            unlink( $result->pdf_path );
        }
        $wpdb->delete( $p . 'results', [ 'id' => $del_id ] );
        wp_redirect( admin_url( 'admin.php?page=psycho-reports&deleted=1' ) );
        exit;
    }
}

// Filters
$filter_test   = isset( $_GET['filter_test'] )   ? absint( $_GET['filter_test'] )                              : 0;
$filter_search = isset( $_GET['filter_search'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_search'] ) ) : '';
$page_num      = isset( $_GET['paged'] )          ? max( 1, absint( $_GET['paged'] ) )                          : 1;
$per_page      = 20;
$offset        = ( $page_num - 1 ) * $per_page;

$filters = [ 'limit' => $per_page, 'offset' => $offset ];
if ( $filter_test )   $filters['test_id'] = $filter_test;
if ( $filter_search ) $filters['search']  = $filter_search;

$results = WP_Psycho_DB::get_all_results( $filters );
$total   = WP_Psycho_DB::count_results( array_diff_key( $filters, [ 'limit' => 0, 'offset' => 0 ] ) );
$tests   = WP_Psycho_DB::get_tests();

$avg_score = 0;
if ( $total > 0 ) {
    $count_filters = $filters;
    unset( $count_filters['limit'], $count_filters['offset'] );
    $count_filters['limit'] = 9999;
    $all_for_avg = WP_Psycho_DB::get_all_results( $count_filters );
    if ( $all_for_avg ) {
        $avg_score = round( array_sum( array_column( $all_for_avg, 'total_score' ) ) / count( $all_for_avg ) );
    }
}

$total_pages = ceil( $total / $per_page );
?>
<div class="wrap">
    <h1><?php esc_html_e( 'All Reports', 'wp-psycho' ); ?></h1>

    <?php if ( isset( $_GET['deleted'] ) ): ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Result deleted.', 'wp-psycho' ); ?></p></div>
    <?php endif; ?>

    <!-- Filter form -->
    <form method="get" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;margin:16px 0;">
        <input type="hidden" name="page" value="psycho-reports">
        <div>
            <label style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e( 'Filter by Test', 'wp-psycho' ); ?></label>
            <select name="filter_test">
                <option value=""><?php esc_html_e( 'All Tests', 'wp-psycho' ); ?></option>
                <?php foreach ( $tests as $t ): ?>
                    <option value="<?php echo esc_attr( $t->id ); ?>" <?php selected( $filter_test, $t->id ); ?>><?php echo esc_html( $t->title ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e( 'Search (name/email)', 'wp-psycho' ); ?></label>
            <input type="text" name="filter_search" value="<?php echo esc_attr( $filter_search ); ?>" placeholder="<?php esc_attr_e( 'Search...', 'wp-psycho' ); ?>" class="regular-text">
        </div>
        <?php submit_button( __( 'Filter', 'wp-psycho' ), 'secondary', '', false ); ?>
        <?php if ( $filter_test || $filter_search ): ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=psycho-reports' ) ); ?>" class="button"><?php esc_html_e( 'Reset', 'wp-psycho' ); ?></a>
        <?php endif; ?>
    </form>

    <!-- Stats bar -->
    <div style="display:flex;gap:24px;background:#f7f8fc;padding:16px 20px;border-radius:8px;margin-bottom:16px;flex-wrap:wrap;">
        <div><strong><?php esc_html_e( 'Total Results:', 'wp-psycho' ); ?></strong> <?php echo esc_html( $total ); ?></div>
        <div><strong><?php esc_html_e( 'Average Score:', 'wp-psycho' ); ?></strong> <?php echo esc_html( $avg_score ); ?></div>
    </div>

    <!-- Export button -->
    <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=psycho-reports&psycho_export_csv=1' ), 'psycho_csv_nonce' ) ); ?>" class="button button-secondary" style="margin-bottom:12px;">
        ⬇ <?php esc_html_e( 'Export CSV', 'wp-psycho' ); ?>
    </a>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Name', 'wp-psycho' ); ?></th>
                <th><?php esc_html_e( 'Email', 'wp-psycho' ); ?></th>
                <th><?php esc_html_e( 'Phone', 'wp-psycho' ); ?></th>
                <th><?php esc_html_e( 'Test', 'wp-psycho' ); ?></th>
                <th style="width:70px;"><?php esc_html_e( 'Score', 'wp-psycho' ); ?></th>
                <th style="width:120px;"><?php esc_html_e( 'Result', 'wp-psycho' ); ?></th>
                <th style="width:100px;"><?php esc_html_e( 'Date', 'wp-psycho' ); ?></th>
                <th style="width:130px;"><?php esc_html_e( 'Actions', 'wp-psycho' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( $results ): foreach ( $results as $r ): ?>
                <tr>
                    <td><?php echo esc_html( $r->participant_name ); ?></td>
                    <td><?php echo esc_html( $r->participant_email ); ?></td>
                    <td><?php echo esc_html( $r->participant_phone ); ?></td>
                    <td><?php echo esc_html( $r->test_title ); ?></td>
                    <td><?php echo esc_html( $r->total_score ); ?></td>
                    <td>
                        <span style="background:<?php echo esc_attr( $r->result_color ); ?>;color:#fff;padding:2px 8px;border-radius:10px;font-size:.8em;font-weight:700;">
                            <?php echo esc_html( $r->result_label ); ?>
                        </span>
                    </td>
                    <td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $r->created_at ) ) ); ?></td>
                    <td>
                        <?php if ( ! empty( $r->pdf_path ) ): ?>
                            <a href="<?php echo esc_url( home_url( '/?psycho_pdf=' . $r->id ) ); ?>" target="_blank" class="button button-small"><?php esc_html_e( 'View Report', 'wp-psycho' ); ?></a>
                        <?php endif; ?>
                        <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=psycho-reports&delete_result=' . $r->id ), 'psycho_del_result_' . $r->id ) ); ?>"
                           class="button button-small button-link-delete"
                           onclick="return confirm('<?php esc_attr_e( 'Delete this result?', 'wp-psycho' ); ?>')"><?php esc_html_e( 'Delete', 'wp-psycho' ); ?></a>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="8"><?php esc_html_e( 'No results found.', 'wp-psycho' ); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ( $total_pages > 1 ):
        $base_url = admin_url( 'admin.php?page=psycho-reports' );
        if ( $filter_test )   $base_url .= '&filter_test='   . $filter_test;
        if ( $filter_search ) $base_url .= '&filter_search=' . urlencode( $filter_search );
    ?>
    <div class="tablenav">
        <div class="tablenav-pages" style="margin-top:12px;">
            <?php for ( $i = 1; $i <= $total_pages; $i++ ): ?>
                <a href="<?php echo esc_url( $base_url . '&paged=' . $i ); ?>"
                   class="button button-small<?php echo $i === $page_num ? ' button-primary' : ''; ?>"
                   style="margin:0 2px;"><?php echo esc_html( $i ); ?></a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
