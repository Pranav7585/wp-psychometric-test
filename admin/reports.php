<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) return;

global $wpdb;
$p = WP_Psycho_DB::get_prefix();

// Handle delete
if ( isset( $_GET['delete_result'], $_GET['_wpnonce'] ) ) {
    $rid = intval( $_GET['delete_result'] );
    if ( wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'psycho_del_result_' . $rid ) ) {
        $wpdb->delete( "{$p}results", [ 'id' => $rid ] );
        echo '<div class="notice notice-success"><p>' . esc_html__( 'Result deleted.', 'wp-psycho' ) . '</p></div>';
    }
}

// CSV Export
if ( isset( $_GET['psycho_export_csv'], $_GET['_csv_nonce'] ) ) {
    if ( wp_verify_nonce( sanitize_key( $_GET['_csv_nonce'] ), 'psycho_csv_nonce' ) ) {
        $all = WP_Psycho_DB::get_all_results( [ 'per_page' => 9999, 'offset' => 0 ] );
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=psychometric-results-' . gmdate( 'Y-m-d' ) . '.csv' );
        $out = fopen( 'php://output', 'w' );
        fputcsv( $out, [ 'ID', 'Name', 'Email', 'Phone', 'Test', 'Score', 'Result', 'Date' ] );
        foreach ( $all as $r ) {
            fputcsv( $out, [
                $r->id,
                $r->participant_name,
                $r->participant_email,
                $r->participant_phone,
                $r->test_title,
                $r->total_score,
                $r->result_label,
                $r->created_at,
            ] );
        }
        fclose( $out );
        exit;
    }
}

// Filters
$filters  = [];
$test_id  = intval( $_GET['filter_test'] ?? 0 );
$search   = sanitize_text_field( wp_unslash( $_GET['search'] ?? '' ) );
$page_num = max( 1, intval( $_GET['paged'] ?? 1 ) );
$per_page = 20;
$offset   = ( $page_num - 1 ) * $per_page;

if ( $test_id ) $filters['test_id'] = $test_id;
if ( $search )  $filters['search']  = $search;

$filters['per_page'] = $per_page;
$filters['offset']   = $offset;

$results     = WP_Psycho_DB::get_all_results( $filters );
$total       = WP_Psycho_DB::count_results( $filters );
$total_pages = ceil( $total / $per_page );

$avg_filters         = $filters;
$avg_filters['per_page'] = 9999;
$avg_filters['offset']   = 0;
$all_for_avg         = WP_Psycho_DB::get_all_results( $avg_filters );
$avg_score           = $all_for_avg ? round( array_sum( array_column( $all_for_avg, 'total_score' ) ) / count( $all_for_avg ) ) : 0;

$tests    = WP_Psycho_DB::get_tests();
$csv_url  = wp_nonce_url( admin_url( 'admin.php?page=psycho-reports&psycho_export_csv=1' ), 'psycho_csv_nonce', '_csv_nonce' );
?>
<div class="wrap">
<h1 class="wp-heading-inline"><?php esc_html_e( 'Assessment Reports', 'wp-psycho' ); ?></h1>
<a href="<?php echo esc_url( $csv_url ); ?>" class="page-title-action"><?php esc_html_e( 'Export CSV', 'wp-psycho' ); ?></a>
<hr class="wp-header-end">

<div style="display:flex;gap:16px;margin:16px 0;padding:16px;background:#fff;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,.08);">
    <div style="flex:1;text-align:center;"><span style="font-size:28px;font-weight:700;color:#6c63ff;"><?php echo intval( WP_Psycho_DB::count_results() ); ?></span><br><span style="color:#718096;font-size:13px;"><?php esc_html_e( 'Total Assessments', 'wp-psycho' ); ?></span></div>
    <div style="flex:1;text-align:center;"><span style="font-size:28px;font-weight:700;color:#00c853;"><?php echo intval( $avg_score ); ?></span><br><span style="color:#718096;font-size:13px;"><?php esc_html_e( 'Avg Score (filtered)', 'wp-psycho' ); ?></span></div>
</div>

<form method="get" style="margin-bottom:16px;display:flex;gap:12px;align-items:center;">
    <input type="hidden" name="page" value="psycho-reports">
    <select name="filter_test">
        <option value=""><?php esc_html_e( '— All Tests —', 'wp-psycho' ); ?></option>
        <?php foreach ( $tests as $t ) : ?>
        <option value="<?php echo intval( $t->id ); ?>" <?php selected( $test_id, $t->id ); ?>><?php echo esc_html( $t->title ); ?></option>
        <?php endforeach; ?>
    </select>
    <input type="text" name="search" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search name or email…', 'wp-psycho' ); ?>" class="regular-text">
    <button type="submit" class="button"><?php esc_html_e( 'Filter', 'wp-psycho' ); ?></button>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=psycho-reports' ) ); ?>" class="button"><?php esc_html_e( 'Clear', 'wp-psycho' ); ?></a>
</form>

<table class="wp-list-table widefat fixed striped">
    <thead>
        <tr>
            <th><?php esc_html_e( 'Name', 'wp-psycho' ); ?></th>
            <th><?php esc_html_e( 'Email', 'wp-psycho' ); ?></th>
            <th><?php esc_html_e( 'Phone', 'wp-psycho' ); ?></th>
            <th><?php esc_html_e( 'Test', 'wp-psycho' ); ?></th>
            <th><?php esc_html_e( 'Score', 'wp-psycho' ); ?></th>
            <th><?php esc_html_e( 'Result', 'wp-psycho' ); ?></th>
            <th><?php esc_html_e( 'Date', 'wp-psycho' ); ?></th>
            <th><?php esc_html_e( 'Actions', 'wp-psycho' ); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php if ( $results ) : foreach ( $results as $r ) :
        $del_url    = wp_nonce_url( admin_url( 'admin.php?page=psycho-reports&delete_result=' . $r->id ), 'psycho_del_result_' . $r->id );
        $uploads    = wp_psycho_uploads();
        $report_url = $r->pdf_path ? $uploads['url'] . basename( $r->pdf_path ) : '';
        $date       = date_i18n( get_option( 'date_format' ), strtotime( $r->created_at ) );
    ?>
        <tr>
            <td><?php echo esc_html( $r->participant_name ); ?></td>
            <td><?php echo esc_html( $r->participant_email ); ?></td>
            <td><?php echo esc_html( $r->participant_phone ); ?></td>
            <td><?php echo esc_html( $r->test_title ); ?></td>
            <td><strong><?php echo intval( $r->total_score ); ?></strong></td>
            <td><span style="background:<?php echo esc_attr( $r->result_color ); ?>;color:#fff;padding:2px 10px;border-radius:20px;font-size:12px;font-weight:600;"><?php echo esc_html( $r->result_label ); ?></span></td>
            <td><?php echo esc_html( $date ); ?></td>
            <td>
                <?php if ( $report_url ) : ?><a href="<?php echo esc_url( $report_url ); ?>" target="_blank" class="button button-small"><?php esc_html_e( 'Report', 'wp-psycho' ); ?></a><?php endif; ?>
                <a href="<?php echo esc_url( $del_url ); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e( 'Delete?', 'wp-psycho' ); ?>')"><?php esc_html_e( 'Delete', 'wp-psycho' ); ?></a>
            </td>
        </tr>
    <?php endforeach; else : ?>
        <tr><td colspan="8"><?php esc_html_e( 'No results found.', 'wp-psycho' ); ?></td></tr>
    <?php endif; ?>
    </tbody>
</table>

<?php if ( $total_pages > 1 ) :
    $base_url = admin_url( 'admin.php?page=psycho-reports' . ( $test_id ? '&filter_test=' . $test_id : '' ) . ( $search ? '&search=' . rawurlencode( $search ) : '' ) );
?>
<div class="tablenav bottom" style="margin-top:16px;">
    <div class="tablenav-pages">
        <?php for ( $i = 1; $i <= $total_pages; $i++ ) : ?>
        <a href="<?php echo esc_url( $base_url . '&paged=' . $i ); ?>" class="button <?php echo $i === $page_num ? 'button-primary' : ''; ?>"><?php echo intval( $i ); ?></a>
        <?php endfor; ?>
    </div>
</div>
<?php endif; ?>
</div>
