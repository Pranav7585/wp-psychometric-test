<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_Psycho_Activator {

    public static function activate() {
        WP_Psycho_DB::install();

        $uploads = wp_psycho_uploads();
        if ( ! file_exists( $uploads['path'] ) ) {
            wp_mkdir_p( $uploads['path'] );
        }

        $index_file = $uploads['path'] . 'index.php';
        if ( ! file_exists( $index_file ) ) {
            file_put_contents( $index_file, '<?php // Silence is golden.' );
        }

        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }
}
