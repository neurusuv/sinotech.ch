<?php

define( 'MFRH_OPTIONS', [
	'auto_rename' => 'none',
	'on_upload' => false,
	'rename_slug' => false,
	'convert_to_ascii' => false,
	'update_posts' => true,
	'update_excerpts' => false,
	'update_postmeta' => false,
	'update_elementor' => false,
	'undo' => false,
	'move' => false,
	'manual_rename' => false,
	'manual_rename_ai' => false,
	'vision_rename_ai' => false,
	'vision_rename_ai_on_upload' => false,
	'manual_sanitize' => false,
	'numbered_files' => false,
	'sync_alt' => false,
	'sync_media_title' => false,
	'force_rename' => false,
	'log' => false,
	'logsql' => false,
	'rename_guid' => false,
	'case_insensitive_check' => false,
	'rename_on_save' => false,
	'clean_upload' => false,
	'acf_field_name' => false,
	'images_only' => false,
	'featured_only' => false,
	'posts_per_page' => 10,
	'lock' => false,
	'autolock_auto' => false,
	'autolock_manual' => true,
	'delay' => 100,
	'clean_uninstall' => false,
	'mode' => 'rename', // rename or move
	'dashboard' => true,
	'alt_field' => false,
	'attached_to' => true,
	'logs_path' => null,
	'metadata_title' => true,
	'metadata_alt' => true,
	'metadata_description' => false,
]);

class Meow_MFRH_Core {

	public $admin = null;
	public $engine = null;
	public $pro = false;
	public $is_rest = false;
	public $is_cli = false;
	public $method = 'media_title';
	public $upload_folder = null;
	public $site_url = null;
	public $currently_uploading = false;
	public $contentDir = null; // becomes 'wp-content/uploads'
	public $allow_usage = null;
	public $allow_setup = null;
	public $images_only = false;
	public $featured_only = false;
	public $images_mime_types = array(
		'image/jpeg', 
		'image/gif', 
		'image/png', 
		'image/bmp',
		'image/tiff', 
		'image/x-icon', 
		'image/webp', 
		'image/svg+xml'
	);

	private $option_name = 'mfrh_options';
	private $log_file = 'media-file-renamer.log';
	static private $plugin_option_name = 'mfrh_options';

	public function __construct() {
		$this->site_url = get_site_url();
		$this->upload_folder = wp_upload_dir();
		$this->contentDir = substr( $this->upload_folder['baseurl'], 1 + strlen( $this->site_url ) );
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	function init() {

		// Before use get_option function, it has to set up Meow_MFRH_Admin.
		// Part of the core, settings and stuff
		$this->is_cli = defined( 'WP_CLI' ) && WP_CLI;
		$this->allow_setup = apply_filters( 'mfrh_allow_setup', current_user_can( 'manage_options' ) );
		$this->admin = new Meow_MFRH_Admin( $this->allow_setup, $this );
		$this->engine = new Meow_MFRH_Engine( $this );
		if ( class_exists( 'MeowPro_MFRH_Core' ) ) {
			new MeowPro_MFRH_Core( $this, $this->admin, $this->engine );
			$this->pro = true;
		}

		// This should be checked after the init (is_rest checks the capacities)
		$this->is_rest = MeowCommon_Helpers::is_rest();
		$this->images_only = $this->get_option( 'images_only', false ) == 1;
		$this->featured_only = $this->get_option( 'featured_only', false ) == 1;

		// Check the roles
		$this->allow_usage = apply_filters( 'mfrh_allow_usage', current_user_can( 'administrator' ) );
		if ( !$this->is_cli && !$this->allow_usage ) {
			return;
		}

		// Languages
		load_plugin_textdomain( MFRH_DOMAIN, false, basename( MFRH_PATH ) . '/languages' );

		// Initialize
		$this->method = apply_filters( 'mfrh_method', $this->get_option( 'auto_rename', 'media_title' ) );
		add_filter( 'attachment_fields_to_save', array( $this, 'attachment_fields_to_save' ), 20, 2 );

		// Only for REST
		if ( $this->is_rest ) {
			new Meow_MFRH_Rest( $this );
		}

		// Side-updates should be ran for CLI and REST
		if ( is_admin() || $this->is_rest || $this->is_cli ) {
			new Meow_MFRH_Updates( $this );
		}

		// Admin screens
		if ( is_admin() ) {
			$clean_upload = $this->get_option( 'clean_upload', false );
			$vision_upload = $this->get_option( 'vision_rename_ai_on_upload', false );

			new Meow_MFRH_UI( $this );
			if ( $this->get_option( 'rename_on_save', false ) ) {
				add_action( 'save_post', array( $this, 'save_post' ) );
			}

			if ( $clean_upload ) {
				add_action( 'add_attachment', array( $this, 'clean_upload' ) );
			}
			else if ( $vision_upload ) {
				add_action( 'add_attachment', array( $this, 'vision_rename_ai_on_upload' ) );
			}

			if ( $this->get_option( 'on_upload', false ) ) {
				add_filter( 'wp_generate_attachment_metadata', array( $this, 'after_image_upload' ), 10, 3 );
				add_filter( 'wp_handle_upload_prefilter', array( $this, 'wp_handle_upload_prefilter' ), 10, 2 );
			}
		}
	}

	/**
	 *
	 * TOOLS / HELPERS
	 *
	 */
	static function get_plugin_option( $option, $default ) {
		$options = get_option( Meow_MFRH_Core::$plugin_option_name, null );
		return $options[$option] ?? $default;
	}

	// Check if the file exists, if it is, return the real path for it
	// https://stackoverflow.com/questions/3964793/php-case-insensitive-version-of-file-exists
	static function sensitive_file_exists( $filename ) {

		$original_filename = $filename;
		$caseInsensitive = Meow_MFRH_Core::get_plugin_option( 'case_insensitive_check', false );
		// if ( !$sensitive_check ) {
		// 	$exists = file_exists( $filename );
		// 	return $exists ? $filename : null;
		// }

		$output = false;
		$directoryName = mfrh_dirname( $filename );
		$fileArray = glob( $directoryName . '/*', GLOB_NOSORT );
		$i = ( $caseInsensitive ) ? "i" : "";

		// Check if \ is in the string
		if ( preg_match( "/\\\|\//", $filename) ) {
			$array = preg_split("/\\\|\//", $filename);
			$filename = $array[count( $array ) -1];
		}
		// Compare filenames
		foreach ( $fileArray as $file ) {
			if ( preg_match( "/\/" . preg_quote( $filename ) . "$/{$i}", $file ) ) {
				$output = $file;
				break;
			}
		}

		return $output;
	}

	static function rmdir_recursive( $directory ) {
		foreach ( glob( "{$directory}/*" ) as $file ) {
			if ( is_dir( $file ) )
				Meow_MFRH_Core::rmdir_recursive( $file );
			else
				unlink( $file );
		}
		rmdir( $directory );
	}

	function wpml_media_is_installed() {
		return defined( 'WPML_MEDIA_VERSION' );
	}

	// To avoid issue with WPML Media for instance
	function is_real_media( $id ) {
		if ( $this->wpml_media_is_installed() ) {
			global $sitepress;
			$language = $sitepress->get_default_language( $id );
			return icl_object_id( $id, 'attachment', true, $language ) == $id;
		}
		return true;
	}

	function is_header_image( $id ) {
		static $headers = false;
		if ( $headers == false ) {
			global $wpdb;
			$headers = $wpdb->get_col( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_attachment_is_custom_header'" );
		}
		return in_array( $id, $headers );
	}

	function generate_unique_filename( $actual, $dirname, $filename, $counter = null ) {
		$new_filename = $filename;
		if ( !is_null( $counter ) ) {
			$whereisdot = strrpos( $new_filename, '.' );
			$new_filename = substr( $new_filename, 0, $whereisdot ) . '-' . $counter
				. '.' . substr( $new_filename, $whereisdot + 1 );
		}
		if ( $actual == $new_filename )
			return false;
		if ( file_exists( $dirname . "/" . $new_filename ) )
			return $this->generate_unique_filename( $actual, $dirname, $filename,
				is_null( $counter ) ? 2 : $counter + 1 );
		return $new_filename;
	}

	function get_uploads_directory_hierarchy() {
		$uploads_dir = wp_upload_dir();
		$base_dir = $uploads_dir['basedir'];
		$directories = array();

		// Get all subdirectories of the base directory
		$dir_iterator = new RecursiveDirectoryIterator( $base_dir, FilesystemIterator::KEY_AS_PATHNAME|FilesystemIterator::CURRENT_AS_FILEINFO|FilesystemIterator::SKIP_DOTS );
		$iterator = new RecursiveIteratorIterator( $dir_iterator, RecursiveIteratorIterator::SELF_FIRST );
		foreach ( $iterator as $file ) {
			if ($file->isDir()) {
				// Remove base_dir from path
				$directory = str_replace( $base_dir, '', $file->getPathname() );
				if ( $directory ) {
					$directories[] = $directory;
				}
			}
		}

		// Return the hierarchy as a JSON file
		return json_encode($directories);
	}

	/**
	 * Returns all the media sharing the same file
	 * @param string $file The attached file path
	 * @param int|array $excludes The post ID(s) to exclude from the results
	 * @return array An array of IDs
	 */
	function get_posts_by_attached_file( $file, $excludes = null ) {
		global $wpdb;
		$r = array ();
		$q = <<< SQL
SELECT post_id
FROM {$wpdb->postmeta}
WHERE meta_key = '%s'
AND meta_value = '%s'
SQL;
		$rows = $wpdb->get_results( $wpdb->prepare( $q, '_wp_attached_file', _wp_relative_upload_path( $file ) ), OBJECT );
		if ( $rows && is_array( $rows ) ) {
			if ( !is_array( $excludes ) )
				$excludes = $excludes ? array ( (int) $excludes ) : array ();

			foreach ( $rows as $item ) {
				$id = (int) $item->post_id;
				if ( in_array( $id, $excludes ) ) continue;
				$r[] = $id;
			}
			$r = array_unique( $r );
		}
		return $r;
	}

	/*****************************************************************************
		RENAME ON UPLOAD
	*****************************************************************************/

	function after_image_upload( $metadata, $attachment_id, $context ) {
		if ( $this->currently_uploading ) {
			$metadata = apply_filters( 'mfrh_after_upload', $metadata, $attachment_id );
		}
		return $metadata;
	}

	function wp_handle_upload_prefilter( $file ) {
		$this->log( "⏰ Event: New Upload (" . $file['name'] . ")" );
		$pp = mfrh_pathinfo( $file['name'] );
		$this->currently_uploading = true; 

		// If everything's fine, renames in based on the Title in the EXIF
		switch ( $this->method ) {
			case 'media_title':
				$this->log( "🗒️ Trying Media Title" );
				$exif = wp_read_image_metadata( $file['tmp_name'] );
				if ( !empty( $exif ) && isset( $exif[ 'title' ] ) && !empty( $exif[ 'title' ] ) ) {
					$new_filename = $this->engine->new_filename( $exif[ 'title' ], $file['name'] );
					if ( !is_null( $new_filename ) ) {
						$file['name'] = $new_filename;
						$this->log( "👌 Title EXIF found." );
						$this->log( "New file should be: " . $file['name'] );
					}					
					return $file;
				}else{
					$this->log( "😭 Title EXIF not found." );
				}
			break;
			case 'post_title':
				$this->log( "🗒️ Trying Post Title" );
				if ( !isset( $_POST['post_id'] ) || $_POST['post_id'] < 1 ) break;
				$post = get_post( $_POST['post_id'] );
				if ( !empty( $post ) && !empty( $post->post_title ) ) {
					$new_filename = $this->engine->new_filename( $post->post_title, $file['name'] );
					if ( !is_null( $new_filename ) ) {
						$file['name'] = $new_filename;
						$this->log( "👌 Post Title found." );
						$this->log( "New file should be: " . $file['name'] );
					}
					return $file;
				}else{
					$this->log( "😭 Post Title not found." );
				}
			case 'post_acf_field':
				if ( !isset( $_POST['post_id'] ) || $_POST['post_id'] < 1 ) break;
				$acf_field_name = $this->get_option('acf_field_name', false);
				if ($acf_field_name) {
					$new_filename = $this->engine->new_filename( get_field($acf_field_name, $_POST['post_id']), $file['name'] );
					if ( !is_null( $new_filename ) ) {
						$file['name'] = $new_filename;
						$this->log( "New file should be: " . $file['name'] );
					}
					return $file;
				}
			break;
		}
		// Otherwise, let's do the basics based on the filename

		// The name will be modified at this point so let's keep it in a global
		// and re-inject it later
		global $mfrh_title_override;
		$mfrh_title_override = $pp['filename'];
		add_filter( 'wp_read_image_metadata', array( $this, 'wp_read_image_metadata' ), 10, 2 );

		// Modify the filename
		$pp = mfrh_pathinfo( $file['name'] );
		$new_filename = $this->engine->new_filename( $pp['filename'], $file['name'] );
		if ( !is_null( $new_filename ) ) {
			$file['name'] = $new_filename;
		}
		return $file;
	}

	function wp_read_image_metadata( $meta, $file ) {
		// Override the title, without this it is using the new filename
		global $mfrh_title_override;
    $meta['title'] = $mfrh_title_override;
    return $meta;
	}

	/****************************************************************************/

	// Return false if everything is fine, otherwise return true with an output
	// which details the conditions and results about the renaming.
	function check_attachment( $post, &$output = array(), $manual_filename = null, $force_rename = false ) {
		$id = $post['ID'];
		$old_filepath = get_attached_file( $id );

		if( PHP_OS_FAMILY == 'Windows' ) {
			$old_filepath = str_replace( '\\', '/', $old_filepath );	
		}

		$old_filepath = !$force_rename ? Meow_MFRH_Core::sensitive_file_exists( $old_filepath ): $old_filepath;
		$path_parts = mfrh_pathinfo( $old_filepath );

		if ( $this->images_only ) {
			$is_image = in_array( $post['post_mime_type'], $this->images_mime_types );
			if ( !$is_image ) {
				return false;
			}
		}

		// If the file doesn't exist, let's not go further.
		if ( !$force_rename && ( !isset( $path_parts['dirname'] ) || !isset( $path_parts['basename'] ) ) ) {
			return false;
		}

		//print_r( $path_parts );
		$directory = isset( $path_parts['dirname'] ) ? $path_parts['dirname'] : null;
		$old_filename = isset( $path_parts['basename'] ) ? $path_parts['basename'] : null;

		// Check if media/file is dead
		if ( !$force_rename && ( !$old_filepath || !file_exists( $old_filepath ) ) ) {
			delete_post_meta( $id, '_require_file_renaming' );
			return false;
		}

		// Is it forced/manual
		// Check mfrh_new_filename (coming from manual input) if it is different than previous filename
		if ( empty( $manual_filename ) && isset( $post['mfrh_new_filename'] ) ) {
			if ( strtolower( $post['mfrh_new_filename'] ) != strtolower( $old_filename ) )
				$manual_filename =  $post['mfrh_new_filename'];
		}

		if ( $force_rename ) {
			$new_filename = $manual_filename;
			$output['manual'] = true;
		}
		else if ( !empty( $manual_filename ) ) {
			// Through the new_filename function to rename when the sanitize option is enabled.
			// To validate the filename (i.g. space will be “-“), use the $manual_filename as the first argument $text.
			$new_filename = $this->get_option( 'manual_sanitize', false )
				? $this->engine->new_filename( $manual_filename, $old_filename, null, $post )
				: $manual_filename;
			$output['manual'] = true;
		}
		else {
			if ( $this->method === 'none') {
				delete_post_meta( $id, '_require_file_renaming' );
				return false;
			}
			if ( get_post_meta( $id, '_manual_file_renaming', true ) ) {
				return false;
			}

			// Skip header images
			if ( $this->is_header_image( $id ) ) {
				delete_post_meta( $id, '_require_file_renaming' );
				return false;
			}

			$base_for_rename = apply_filters( 'mfrh_base_for_rename', $post['post_title'], $id );
			$new_filename = $this->engine->new_filename( $base_for_rename, $old_filename, null, $post );
			if ( is_null( $new_filename ) ) {
				return false; // Leave it as it is
			}
		}

		// If a filename has a counter, and the ideal is without the counter, let's ignore it
		$ideal = preg_replace( '/-[1-9]{1,10}\./', '$1.', $old_filename );
		if ( !$manual_filename ) {
			if ( $ideal == $new_filename ) {
				delete_post_meta( $id, '_require_file_renaming' );
				return false;
			}
		}

		// Filename is equal to sanitized title
		if ( $new_filename == $old_filename ) {
			delete_post_meta( $id, '_require_file_renaming' );
			return false;
		}

		// Check for case issue, numbering
		//if ( !$force_rename ) {
			$ideal_filename = $new_filename;
			$new_filepath = trailingslashit( $directory ) . $new_filename;
			$existing_file = Meow_MFRH_Core::sensitive_file_exists( $new_filepath );
			$case_issue = strtolower( $old_filename ) == strtolower( $new_filename );
			if ( !$force_rename && $existing_file && !$case_issue ) {
				$is_numbered = apply_filters( 'mfrh_numbered', false );
				if ( $is_numbered ) {
					$new_filename = $this->generate_unique_filename( $ideal, $directory, $new_filename );
					if ( !$new_filename ) {
						delete_post_meta( $id, '_require_file_renaming' );
						return false;
					}
					$new_filepath = trailingslashit( $directory ) . $new_filename;
					$existing_file = Meow_MFRH_Core::sensitive_file_exists( $new_filepath );
				}
			}
		//}

		// Send info to the requester function
		$output['post_id'] = $id;
		$output['post_name'] = $post['post_name'];
		$output['post_title'] = $post['post_title'];
		$output['current_filename'] = $old_filename;
		$output['current_filepath'] = $old_filepath;
		$output['ideal_filename'] = $ideal_filename;
		$output['proposed_filename'] = $new_filename;
		$output['desired_filepath'] = $new_filepath;
		$output['case_issue'] = $case_issue;
		$output['manual'] = !empty( $manual_filename );
		$output['locked'] = get_post_meta( $id, '_manual_file_renaming', true );
		$output['proposed_filename_exists'] = !!$existing_file;
		$output['original_image'] = null;
		
		// If the ideal filename already exists
		// Maybe that's the original_image! If yes, we should let it go through
		// as the original_rename will be renamed into another filename anyway.
		if ( !!$existing_file ) {
			$meta = wp_get_attachment_metadata( $id );
			if ( isset( $meta['original_image'] ) && $new_filename === $meta['original_image'] ) {
				$output['original_image'] = $meta['original_image'];
				$output['proposed_filename_exists'] = false;
			}
		}

		// Set the '_require_file_renaming', even though it's not really used at this point (but will be,
		// with the new UI).
		if ( !get_post_meta( $post['ID'], '_require_file_renaming', true ) && !$output['locked']) {
			add_post_meta( $post['ID'], '_require_file_renaming', true, true );
		}
		return true;
	}

	function check_text() {
		$issues = array();
		global $wpdb;
		$ids = $wpdb->get_col( "
			SELECT p.ID
			FROM $wpdb->posts p
			WHERE post_status = 'inherit'
			AND post_type = 'attachment'
		" );
		foreach ( $ids as $id )
			if ( $this->check_attachment( get_post( $id, ARRAY_A ), $output ) )
				array_push( $issues, $output );
		return $issues;
	}

	/**
	 *
	 * RENAME ON SAVE / PUBLISH
	 * Originally proposed by Ben Heller
	 * Added and modified by Jordy Meow
	 */

	function save_post( $post_id ) {
		$status = get_post_status( $post_id );
		if ( !in_array( $status, array( 'publish', 'draft', 'future', 'private' ) ) )
			return;
		$args = array( 'post_type' => 'attachment', 'numberposts' => -1, 'post_status' =>'any', 'post_parent' => $post_id );
		$medias = get_posts( $args );
		if ( $medias ) {
			$this->log( '⏰ Event: Save Post' );
			foreach ( $medias as $attach ) {
				// In the past, I used this to detect if the Media Library is NOT used:
				// isset( $attachment['image-size'] );
				$this->engine->rename( $attach->ID );
			}
		}
	}

	/**
	 * Originally from FRANCISCO RUIZ 
	 * (source: brutalbusiness.com)
	 * Modified by Valentin
	 */
	function clean_upload( $post_ID ) {
		if ( !wp_attachment_is_image( $post_ID ) ) {
			return;
		}

		$post = get_post( $post_ID );
		if(!$post) {
			return;
		}

		$my_image_title = preg_replace('%\s*[-_\s]+\s*%', ' ', $post->post_title);
		$my_image_title = ucwords(strtolower($my_image_title));

		$my_image_title = apply_filters( 'mfrh_clean_upload', $my_image_title );
	
		update_post_meta($post_ID, '_wp_attachment_image_alt', $my_image_title);
	
		$my_image_meta = array(
			'ID'            => $post_ID,
			'post_title'    => $my_image_title,
			'post_excerpt'  => $my_image_title,
			'post_content'  => $my_image_title,
		);

		wp_update_post($my_image_meta);
	}

	function vision_rename_ai_on_upload( $post_ID ){
		if ( !wp_attachment_is_image( $post_ID ) ) {
			return;
		}

		$post = get_post( $post_ID );
		if(!$post) {
			return;
		}

		$lengths = [
			'alternative text' => '16 words',
			'title' => '60 characters',
			'description' => '155 characters',
		];

		$results = [];

		foreach ($lengths as $metadataType => $length) {
			$prompt = "Suggest a lowercase $metadataType under $length characters, SEO-optimized, easy to read, fitting for WordPress. Limit response to $metadataType.";
			$newMetadata = apply_filters( 'mfrh_vision_suggestion', null, $post_ID, $prompt );

			$newMetadata = trim( $newMetadata );
			$newMetadata = str_replace( '"', '', $newMetadata );
			$newMetadata = str_replace( "'", '', $newMetadata );

			if ( $newMetadata ) {
				$results[$metadataType] = $newMetadata;
			}
		}
	
		update_post_meta($post_ID, '_wp_attachment_image_alt', $results['alternative text']);
	
		$my_image_meta = array(
			'ID'            => $post_ID,
			'post_title'    => $results['title'],
			'post_excerpt'  => $results['description'],
			'post_content'  => $results['description'],

		);

		wp_update_post($my_image_meta);
	}

	/**
	 *
	 * EDITOR
	 *
	 */

	function attachment_fields_to_save( $post, $attachment ) {
		$this->log( '⏰ Event: Save Attachment' );
		$post = $this->engine->rename( $post );
		return $post;
	}

	function log_sql( $data, $antidata ) {
		if ( !$this->get_option( 'logsql' ) || !$this->admin->is_registered() )
			return;
		$dir = wp_upload_dir();
		$dir = $dir['basedir'];
		$fh = fopen( trailingslashit( $dir ) . 'mfrh_sql.log', 'a' );
		$fh_anti = fopen( trailingslashit( $dir ) . 'mfrh_sql_revert.log', 'a' );
		fwrite( $fh, "{$data}\n" );
		fwrite( $fh_anti, "{$antidata}\n" );
		fclose( $fh );
		fclose( $fh_anti );
	}

	function get_logs_path() {
    $path = $this->get_option( 'logs_path' );
    if ( $path && file_exists( $path ) ) {
      return $path;
    }
    $uploads_dir = wp_upload_dir();
    $path = trailingslashit( $uploads_dir['basedir'] ) . MFRH_PREFIX . "_" .
			$this->random_ascii_chars() . ".log";
		if ( !file_exists( $path ) ) {
			touch( $path );
		}
    $options = $this->get_all_options();
    $options['logs_path'] = $path;
    $this->update_options( $options );
    return $path;
	}

	function log( $data = null ) {
		error_log( $data );
		$log_file_path = $this->get_logs_path();
		$fh = @fopen( $log_file_path, 'a' );
		if ( !$fh ) { return false; }
		$date = date( "Y-m-d H:i:s" );
		if ( is_null( $data ) ) {
			fwrite( $fh, "\n" );
		}
		else {
			fwrite( $fh, "$date: {$data}\n" );
		}
		fclose( $fh );
		return true;
	}

	function get_logs() {
		$log_file_path = $this->get_logs_path();
		$content = file_get_contents( $log_file_path );
		$lines = explode( "\n", $content );
		$lines = array_filter( $lines );
		$lines = array_reverse( $lines );
		$content = implode( "\n", $lines );
		return $content;
	}

	function clear_logs() {
		unlink( $this->get_logs_path() );
	}

	// Only replace the first occurence
	function str_replace( $needle, $replace, $haystack ) {
		if ( empty( $needle ) || empty( $haystack ) ) {
			return $haystack;
		}
		$pos = strpos( $haystack, $needle );
		if ( $pos !== false )
			$haystack = substr_replace( $haystack, $replace, $pos, strlen( $needle ) );
		return $haystack;
	}

	/**
	 *
	 * RENAME FILES + COFFEE TIME
	 */
	// From a url to the shortened and cleaned url (for example '2025/02/file.png')
	function clean_url( $url ) {
		$dirIndex = strpos( $url, $this->contentDir );
		if ( empty( $url ) || $dirIndex === false ) {
			$finalUrl =  null;
		}
		else {
			$finalUrl = urldecode( substr( $url, 1 + strlen( $this->contentDir ) + $dirIndex ) );
		}
		return $finalUrl;
	}

	function call_hooks_rename_url( $post, $orig_image_url, $new_image_url, $size = 'N/A'  ) {
		// With the full URLs
		// 2021/11/03: I am not sure we need this, since the clean URLs would also match
		// do_action( 'mfrh_url_renamed', $post, $orig_image_url, $new_image_url );

		// With clean URLs relative to /uploads
		$cleaned_orig_image_url = $this->clean_url( $orig_image_url );
		$cleaned_new_image_url = $this->clean_url( $new_image_url );
		if ( !empty( $cleaned_orig_image_url ) && !empty( $cleaned_new_image_url ) ) {
		do_action( 'mfrh_url_renamed', $post, $cleaned_orig_image_url, $cleaned_new_image_url, $size );
		}

		// With DB URLs (honestly, not sure about this...)
		//  $upload_dir = wp_upload_dir();
		//  do_action( 'mfrh_url_renamed', $post, str_replace( $upload_dir, "", $orig_image_url ),
		//  	str_replace( $upload_dir, "", $new_image_url ) );
	}

	function create_folder( $directory_path ) {
		$upload_dir = wp_upload_dir();
		$new_directory_path = trailingslashit( $upload_dir['basedir'] ) . trim( $directory_path, '/' );

		if ( file_exists( $new_directory_path ) ) {
			$this->log( "🚫 The directory already existed: $new_directory_path" );
			throw new Exception( __( 'The directory already existed.', 'media-file-renamer') );
		}

		if ( !mkdir( $new_directory_path, 0777, true ) ) {
			$this->log( "🚫 The directory couldn't be created: $new_directory_path" );
			throw new Exception( __( "The directory couldn't be created.", 'media-file-renamer') );
		}
		$this->log( "✅ The directory was created: $new_directory_path" );
	}

	function move( $media, $newPath ) {
		$id = null;
		$post = null;

		if ( PHP_OS_FAMILY == 'Windows' ) {
			$newPath = str_replace( '\\', '/', $newPath );
		}

		// Check the arguments
		if ( is_numeric( $media ) ) {
			$id = $media;
			$post = get_post( $media, ARRAY_A );
		}
		else if ( is_array( $media ) ) {
			$id = $media['ID'];
			$post = $media;
		}
		else {
			die( 'Media File Renamer: move() requires the ID or the array for the media.' );
		}

		// Prepare the variables
		$orig_attachment_url = null;

		$old_filepath = get_attached_file( $id );
		if ( PHP_OS_FAMILY == 'Windows' ) {
			$old_filepath = str_replace( '\\', '/', $old_filepath );
		}

		$path_parts = mfrh_pathinfo( $old_filepath );
		$old_ext = $path_parts['extension'];
		$upload_dir = wp_upload_dir();
		if ( PHP_OS_FAMILY == 'Windows' ) {
			$upload_dir['basedir'] = str_replace( '\\', '/', $upload_dir['basedir'] );
		}

		$old_directory = trim( str_replace( $upload_dir['basedir'], '', $path_parts['dirname'] ), '/' ); // '2011/01'
		$new_directory = trim( $newPath, '/' );
		$filename = $path_parts['basename']; // 'whatever.jpeg'
		$new_filepath = trailingslashit( trailingslashit( $upload_dir['basedir'] ) . $new_directory ) . $filename;

		$this->log( "🏁 Move Media: " . $filename );
		$this->log( "The new directory will be: " . mfrh_dirname( $new_filepath ) );

		// Create the directory if it does not exist
		if ( !file_exists( mfrh_dirname( $new_filepath ) ) ) {
			mkdir( mfrh_dirname( $new_filepath ), 0777, true );
		}

		// There is no support for UNDO (as the current process of Media File Renamer doesn't keep the path for the undo, only the filename... so the move breaks this - let's deal with this later).

		// Move the main media file
		if ( !$this->engine->rename_file( $old_filepath, $new_filepath ) ) {
			$this->log( "🚫 File $old_filepath ➡️ $new_filepath" );
			return false;
		}
		$this->log( "✅ File $old_filepath ➡️ $new_filepath" );
		do_action( 'mfrh_path_renamed', $post, $old_filepath, $new_filepath );

		// Handle the WebP if it exists
		$this->engine->rename_alternative_image_formats( $old_filepath, $old_ext, $new_filepath, $old_ext );

		// Update the attachment meta
		$meta = wp_get_attachment_metadata( $id );

		if ( $meta ) {
			if ( isset( $meta['file'] ) && !empty( $meta['file'] ) )
				$meta['file'] = $this->str_replace( $old_directory, $new_directory, $meta['file'] );
			if ( isset( $meta['url'] ) && !empty( $meta['url'] ) && strlen( $meta['url'] ) > 4 )
				$meta['url'] = $this->str_replace( $old_directory, $new_directory, $meta['url'] );
			//wp_update_attachment_metadata( $id, $meta );
		}

		// Better to check like this rather than with wp_attachment_is_image
		// PDFs also have thumbnails now, since WP 4.7
		$has_thumbnails = isset( $meta['sizes'] );

		if ( $has_thumbnails ) {

			// Support for the original image if it was "-rescaled".
			$is_scaled_image = isset( $meta['original_image'] ) && !empty( $meta['original_image'] );
			if ( $is_scaled_image ) {
				$meta_old_filename = $meta['original_image'];
				$meta_old_filepath = trailingslashit( $upload_dir['basedir'] ) . trailingslashit( $old_directory ) . $meta_old_filename;
				$meta_new_filepath = trailingslashit( $upload_dir['basedir'] ) . trailingslashit( $new_directory ) . $meta_old_filename;
				if ( !$this->engine->rename_file( $meta_old_filepath, $meta_new_filepath ) ) {
					$this->log( "🚫 File $meta_old_filepath ➡️ $meta_new_filepath" );
				}
				else {
					$this->log( "✅ File $meta_old_filepath ➡️ $meta_new_filepath" );
					do_action( 'mfrh_path_renamed', $post, $meta_old_filepath, $meta_new_filepath );
				}
			}

			// Image Sizes (Thumbnails)
			$orig_image_urls = array();
			$orig_image_data = wp_get_attachment_image_src( $id, 'full' );
			$orig_image_urls['full'] = $orig_image_data[0];
			foreach ( $meta['sizes'] as $size => $meta_size ) {
				if ( !isset($meta['sizes'][$size]['file'] ) )
					continue;
				$meta_old_filename = $meta['sizes'][$size]['file'];
				$meta_old_filepath = trailingslashit( $upload_dir['basedir'] ) . trailingslashit( $old_directory ) . $meta_old_filename;
				$meta_new_filepath = trailingslashit( $upload_dir['basedir'] ) . trailingslashit( $new_directory ) . $meta_old_filename;
				$orig_image_data = wp_get_attachment_image_src( $id, $size );
				$orig_image_urls[$size] = $orig_image_data[0];

				// Double check files exist before trying to rename.
				if ( file_exists( $meta_old_filepath )
						&& ( ( !file_exists( $meta_new_filepath ) ) || is_writable( $meta_new_filepath ) ) ) {
					// WP Retina 2x is detected, let's rename those files as well
					if ( function_exists( 'wr2x_get_retina' ) ) {
						$wr2x_old_filepath = $this->str_replace( '.' . $old_ext, '@2x.' . $old_ext, $meta_old_filepath );
						$wr2x_new_filepath = $this->str_replace( '.' . $old_ext, '@2x.' . $old_ext, $meta_new_filepath );
						if ( file_exists( $wr2x_old_filepath )
							&& ( ( !file_exists( $wr2x_new_filepath ) ) || is_writable( $wr2x_new_filepath ) ) ) {

							// Rename retina file
							if ( !$this->engine->rename_file( $wr2x_old_filepath, $wr2x_new_filepath ) ) {
								$this->log( "🚫 Retina $wr2x_old_filepath ➡️ $wr2x_new_filepath" );
								return $post;
							}
							$this->log( "✅ Retina $wr2x_old_filepath ➡️ $wr2x_new_filepath" );
							do_action( 'mfrh_path_renamed', $post, $wr2x_old_filepath, $wr2x_new_filepath );
						}
					}

					// Handle the WebP if it exists
					$this->engine->rename_alternative_image_formats( $meta_old_filepath, $old_ext, $meta_new_filepath, $old_ext );

					// Rename meta file
					if ( !$this->engine->rename_file( $meta_old_filepath, $meta_new_filepath ) ) {
						$this->log( "🚫 File $meta_old_filepath ➡️ $meta_new_filepath" );
						return false;
					}

					// Success, call other plugins
					$this->log( "✅ File $meta_old_filepath ➡️ $meta_new_filepath" );
					do_action( 'mfrh_path_renamed', $post, $meta_old_filepath, $meta_new_filepath );

				}
			}
		}
		else {
			$orig_attachment_url = wp_get_attachment_url( $id );
		}

		// Update DB: Media and Metadata
		$new_filepath = str_replace( $upload_dir['basedir'] . '/', '', $new_filepath );

		update_attached_file( $id, $new_filepath );
		if ( $meta ) {
			wp_update_attachment_metadata( $id, $meta );
		}
		clean_post_cache( $id ); // TODO: Would be good to know what this WP function actually does (might be useless)

		// Post actions
		$this->call_post_actions( $id, $post, $meta, $has_thumbnails, $orig_image_urls, $orig_attachment_url );
		do_action( 'mfrh_media_renamed', $post, $old_filepath, $new_filepath, false );
		return true;
	}

	// Call the actions so that the plugin's plugins can update everything else (than the files)
	// Called by rename() and move()
	function call_post_actions( $id, $post, $meta, $has_thumbnails, $orig_image_urls, $orig_attachment_url ) {
		if ( $has_thumbnails ) {
			$orig_image_url = $orig_image_urls['full'];
			$new_image_data = wp_get_attachment_image_src( $id, 'full' );
			$new_image_url = $new_image_data[0];
			$this->call_hooks_rename_url( $post, $orig_image_url, $new_image_url, 'full' );
			if ( !empty( $meta['sizes'] ) ) {
				foreach ( $meta['sizes'] as $size => $meta_size ) {
					if ( isset( $orig_image_urls[$size] ) ) {
						$orig_image_url = $orig_image_urls[$size];
						$new_image_data = wp_get_attachment_image_src( $id, $size );
						$new_image_url = $new_image_data[0];
						$this->call_hooks_rename_url( $post, $orig_image_url, $new_image_url, $size );
					}
				}
			}
		}
		else {
			$new_attachment_url = wp_get_attachment_url( $id );
			$this->call_hooks_rename_url( $post, $orig_attachment_url, $new_attachment_url, 'full' );
		}
		// HTTP REFERER set to the new media link
		if ( isset( $_REQUEST['_wp_original_http_referer'] ) &&
			strpos( $_REQUEST['_wp_original_http_referer'], '/wp-admin/' ) === false ) {
			$_REQUEST['_wp_original_http_referer'] = get_permalink( $id );
		}
	}

	function undo( $mediaId ) {
		$original_filename = get_post_meta( $mediaId, '_original_filename', true );
		if ( empty( $original_filename ) ) {
			return true;
		}
		$res = $this->engine->rename( $mediaId, $original_filename, true );
		if ( !!$res ) {
			delete_post_meta( $mediaId, '_original_filename' );
		}
		return $res;
	}

	/**
	 * Linking with api.php call (l.20)
	 */
	function rename( $mediaId, $manual ){
		$res = $this->engine->rename( $mediaId, $manual );
		return $res;
	}

	/**
	 * Locks a post to be manual-rename only
	 * @param int|WP_Post $post The post to lock
	 * @return True on success, false on failure
	 */
	function lock( $post ) {
		//TODO: We should probably only take an ID as the argument
		$id = $post instanceof WP_Post ? $post->ID : $post;
		delete_post_meta( $id, '_require_file_renaming' );
		update_post_meta( $id, '_manual_file_renaming', true, true );
		return true;
	}

	/**
	 * Unlocks a locked post
	 * @param int|WP_Post $post The post to unlock
	 * @return True on success, false on failure
	 */
	function unlock( $post ) {
		delete_post_meta( $post instanceof WP_Post ? $post->ID : $post, '_manual_file_renaming' );
		return true;
	}

	/**
	 * Determines whether a post is locked
	 * @param int|WP_Post $post The post to check
	 * @return Boolean
	 */
	function is_locked( $post ) {
		return get_post_meta( $post instanceof WP_Post ? $post->ID : $post, '_manual_file_renaming', true ) === true;
	}

	/**
	 *
	 * Roles & Access Rights
	 *
	 */

	public function can_access_settings() {
		return apply_filters( 'mfrh_allow_setup', current_user_can( 'manage_options' ) );
	}

	public function can_access_features() {
		return apply_filters( 'mfrh_allow_usage', current_user_can( 'administrator' ) );
	}

	#region Options
	function reset_options() {
		delete_option( $this->option_name );
	}

	function get_option( $option, $default = null ) {
		$options = $this->get_all_options();
		return $options[$option] ?? $default;
	}

	function list_options() {
		$options = get_option( $this->option_name, null );
		foreach ( MFRH_OPTIONS as $key => $value ) {
			if ( !isset( $options[$key] ) ) {
				$options[$key] = $value;
			}
		}
		return $options;
	}

	function needs_registered_options() {
		return array(
			'convert_to_ascii',
			'numbered_files',
			'sync_alt',
			'sync_media_title',
			'force_rename',
			'logsql',
		);
	}

	function get_all_options() {
		$options = get_option( $this->option_name, null );
		$options = $this->check_options( $options );

		$needs_registered_options = $this->needs_registered_options();
		foreach ( $options as $key => $value ) {
			if ( in_array( $key, $needs_registered_options ) ) {
				//$options[ $key ] = $this->admin->is_registered() && $value;
				$options[$key] = isset( $this->admin ) && $this->admin->is_registered() && $value;
				continue;
			}
		}

		return $options;
	}

	function update_options( $options ) {
		if ( !update_option( $this->option_name, $options, false ) ) {
			return false;
		}
		list($options, $result, $message) = $this->sanitize_options();
		$validation_result = $this->createValidationResult( $result, $message );
		return [ $options, $validation_result['result'], $validation_result['message'] ];
	}

	// Upgrade from the old way of storing options to the new way.
	function check_options( $options = [] ) {
		$plugin_options = $this->list_options();
		$options = empty( $options ) ? [] : $options;
		$hasChanges = false;
		foreach ( $plugin_options as $option => $default ) {
			// The option already exists
			if ( isset( $options[$option] ) ) {
				continue;
			}
			// The option does not exist, so we need to add it.
			// Let's use the old value if any, or the default value.
			$options[$option] = get_option( 'mfrh_' . $option, $default );
			delete_option( 'mfrh_' . $option );
			$hasChanges = true;
		}
		if ( $hasChanges ) {
			update_option( $this->option_name , $options );
		}
		return $options;
	}

	// Validate and keep the options clean and logical.
	function sanitize_options() {
		$options = $this->get_all_options();
		$result = true;
		$message = null;

		$force_rename = $options['force_rename'];
		$numbered_files = $options['numbered_files'];

		if ( $force_rename && $numbered_files ) {
			$options['force_rename'] = false;
			$result = false;
			$message = __( 'Force Rename and Numbered Files cannot be used at the same time. Please use Force Rename only when you are trying to repair a broken install. For now, Force Rename has been disabled.', 'media-file-renamer' );
		}

		$sync_alt = $options['sync_alt'];
		$sync_media_title = $options['sync_media_title'];

		if ( $sync_alt && $this->method === 'alt_text' ) {
			$options['sync_alt'] = false;
			$result = false;
			$message = __( 'The option Sync ALT was turned off since it does not make sense to have it with this Auto-Rename mode.', 'media-file-renamer' );
		}

		if ( $sync_media_title && $this->core->method === 'media_title' ) {
			$options['sync_media_title'] = false;
			$message = __( 'The option Sync Media Title was turned off since it does not make sense to have it with this Auto-Rename mode.', 'media-file-renamer' );
		}

		$needs_update = false;
		if ( !$options['move'] && $options['mode'] === 'move' ) {
			$options['mode'] = 'rename';
			$needs_update = true;
		}

		if ( !$result || $needs_update ) {
			update_option( $this->option_name, $options, false );
		}

		return [ $options, $result, $message ];
	}

	function createValidationResult( $result = true, $message = null) {
		$message = $message ? $message : __( 'Option updated.', 'media-file-renamer' );
		return [ 'result' => $result, 'message' => $message ];
	}

	#endregion

	private function random_ascii_chars( $length = 8 ) {
		$characters = array_merge( range( 'A', 'Z' ), range( 'a', 'z' ), range( '0', '9' ) );
		$characters_length = count( $characters );
		$random_string = '';

		for ($i = 0; $i < $length; $i++) {
			$random_string .= $characters[rand(0, $characters_length - 1)];
		}

		return $random_string;
	}
}
