<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_Psycho_Activator {

    public static function activate() {
        WP_Psycho_DB::install();

        $uploads = wp_psycho_uploads();
        if ( ! file_exists( $uploads['path'] ) ) {
            wp_mkdir_p( $uploads['path'] );
        }
        $index = $uploads['path'] . 'index.php';
        if ( ! file_exists( $index ) ) {
            global $wp_filesystem;
            if ( empty( $wp_filesystem ) ) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
                WP_Filesystem();
            }
            $wp_filesystem->put_contents( $index, '<?php // Silence is golden.', FS_CHMOD_FILE );
        }

        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }
}
