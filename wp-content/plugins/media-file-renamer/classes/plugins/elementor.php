<?php
  add_action( 'mfrh_url_renamed', 'mfrh_elementor_metadata', 10, 3 );
  add_action( 'mfrh_media_renamed', 'mfrh_elementor_reset_css', 10, 3 );

  function mfrh_elementor_metadata( $post, $orig_image_url, $new_image_url ) {
    global $wpdb;
    $table_meta = $wpdb->postmeta;
    $orig_image_url = esc_sql( $orig_image_url );
    $new_image_url = esc_sql( $new_image_url );
    $orig_image_url = str_replace( '/', '\/', $orig_image_url );
    $new_image_url = str_replace( '/', '\/', $new_image_url );
    $searchValue = '%' . str_replace( '\/', '\\\/', $orig_image_url ) . '%';
    //error_log("$orig_image_url => $new_image_url (Search for $searchValue)");
    //error_log( 'Search Value: ' . $searchValue );
    $query = $wpdb->prepare( "UPDATE {$table_meta}
      SET meta_value = REPLACE(meta_value, %s, %s)
      WHERE meta_key = '_elementor_data'
      AND meta_value LIKE %s", $orig_image_url, $new_image_url, $searchValue
    );
    $res = $wpdb->query( $query );
  }

  // Forces Elementor to regenerate all the CSS files
  function mfrh_elementor_reset_css( $post, $orig_image_url, $new_image_url ) {
    global $wpdb;
    $query = "DELETE FROM $wpdb->postmeta WHERE meta_key = '_elementor_css'";
    $res = $wpdb->query( $query );
  }

?>