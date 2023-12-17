<?php
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
  die;
}

$options = get_option( 'mfrh_options', null );
$clean_uninstall = $options['clean_uninstall'] ?? false;
if ( !$clean_uninstall ) {
  return;
}

global $wpdb;

// Clean Options
$options = $wpdb->get_results( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE 'mfrh_%'" );
foreach ( $options as $option ) {
  delete_option( $option->option_name );
}

// Clean Post Meta
$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '_manual_file_renaming'" );
$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '_require_file_renaming'" );
$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '_original_filename'" );
