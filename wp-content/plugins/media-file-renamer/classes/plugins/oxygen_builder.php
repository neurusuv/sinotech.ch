<?php
  //add_action( 'mfrh_url_renamed', 'mfrh_oxygen_metadata', 10, 3 );
  //add_action( 'mfrh_media_renamed', 'mfrh_oxygen_reset_cache', 10, 3 );

  function mfrh_oxygen_metadata( $post, $orig_image_url, $new_image_url ) {
    global $wpdb;
    $table_meta = $wpdb->postmeta;
    $orig_image_url = esc_sql( $orig_image_url );
    $new_image_url = esc_sql( $new_image_url );
    $orig_image_url = str_replace( '/', '\/', $orig_image_url );
    $new_image_url = str_replace( '/', '\/', $new_image_url );
    $searchValue = '%' . str_replace( '\/', '\\\/', $orig_image_url ) . '%';
    //error_log("$orig_image_url => $new_image_url (Search for $searchValue)");
    //error_log( 'Search Value: ' . $searchValue );
    $query = "UPDATE {$table_meta}
      SET meta_value = REPLACE(meta_value, '{$orig_image_url}', '{$new_image_url}')
      WHERE meta_key = 'ct_builder_shortcodes'
      AND meta_value LIKE '%{$searchValue}%'";
    //error_log( $query );
    $res = $wpdb->query( $query );
  }

  function mfrh_oxygen_reset_cache( $post, $orig_image_url, $new_image_url ) {
    // global $wpdb;
    // $query = "DELETE FROM $wpdb->postmeta WHERE meta_key = 'ct_css_cache'";
    // $res = $wpdb->query( $query );
  }

?>