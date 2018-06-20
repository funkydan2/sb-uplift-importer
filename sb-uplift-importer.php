<?php
/*
Plugin Name: Import Sermon Browser 1 into Uplift Theme
Plugin URI: http://www.kenmore.org.au
Description: Add audio and video sermons, manage speakers, series, and more. (Based on plugin by Jack Lamb of www.wpforchurch.com)
Version: .1.2
Author: Daniel Saunders
Author URI: http://www.kenmore.org.au
License: GPL2
Text Domain: sb-uplift-importer
Domain Path: /languages/
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Plugin Folder Path
if ( ! defined( 'SBUP_PLUGIN_DIR' ) )
	define( 'SBUP_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . basename( dirname( __FILE__ ) ) . '/' );

// Plugin Folder URL
if ( ! defined( 'SBUP_PLUGIN_URL' ) )
	define( 'SBUP_PLUGIN_URL', plugin_dir_url( SBUP_PLUGIN_DIR ) . basename( dirname( __FILE__ ) ) . '/' );

// Plugin Root File
if ( ! defined( 'SBUP_PLUGIN_FILE' ) )
	define( 'SBUP_PLUGIN_FILE', __FILE__ );
	
if ( !defined( 'SB_FRONTEND_FUNCTIONS' ) )
	define( 'SB_FRONTEND_FUNCTIONS', WP_PLUGIN_DIR . '/sermon-browser/sb-includes/frontend.php' );

// Translations
function _ds_sermon_importer_translations() {
	load_plugin_textdomain( 'sermon-importer', false, basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', '_ds_sermon_importer_translations' );

/**
* Add sub-menu
*/
function _ds_sermon_importer_add_admin_menu() {
  add_submenu_page('edit.php?post_type=wp3s_sermons', 'Import Sermon Browser 1 to Uplift Theme custom posts', 'Import SB1', 'edit_plugins', __FILE__, '_ds_sermon_importer_admin_page');
}
add_action('admin_menu', '_ds_sermon_importer_add_admin_menu');

/**
* Gets a Sermon Browser 1 option (previous plugin version)
*
* @param string $option - the name of the SB1 option
* @return mixed - returns null if the option does not exist
*/
function _ds_sermon_importer_get_sb1_option ($option) {
    $sb1_options = unserialize( base64_decode( get_option('sermonbrowser_options') ) );
    if ( $sb1_options and isset($sb1_options[$option]) )
        return $sb1_options[$option];
    else
        return null;
}

/**
* Updates a SermonBrowser option
*
* @param string $option
* @param mixed $new_value
* @return boolean - true on success, false on failure
*/
function _ds_sermon_importer_update_option ($option, $new_value) {
    $all_options = get_option ('sermon_browser_2');
    $all_options [$option] = $new_value;
    return update_option ('sermon_browser_2', $all_options);
}

/**
* Function to use WP_Query to search for posts - no idea!!
*/
add_filter( 'posts_where', '_ds_query_post_title', 10, 2 );
function _ds_query_post_title( $where, &$wp_query )
{
    global $wpdb;
    if ( $_ds_query_post_title = $wp_query->get( '_ds_query_post_title' ) ) {
        $where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'' . esc_sql( like_escape( $_ds_query_post_title ) ) . '%\'';
    }
    return $where;
}


/**
* Display Import page
*/
function _ds_sermon_importer_admin_page() {
    if (isset($_POST['import']))
        _ds_sermon_importer_from_SB1();
    global $wpdb;
    
    
?>
    <div class="wrap">
        <div id="icon-tools" class="icon32 icon32-edit"><br /></div>
        <h2><?php _e('Sermon Browser Import', 'sermon-importer'); ?></h2>
        <p>
        <?php 
	        if ( !is_plugin_active( 'sermon-browser/sermon.php' ) ) {
	        ?>
   			 <div class="wrap">
        		<div id="icon-tools" class="icon32 icon32-edit"><br /></div>
	        	<h2><?php _e('Sermon Browser Import', 'sermon-importer'); ?></h2>
	    	    <p>
    	    	<?php _e('Sermon Browser importer requires Sermon Browser 1 to be active. Please activate and run the importer again.'); ?>
        		</p>
        <?php
    	}
    	else {
    	?>
    	    <?php _e('Uplift Theme can import sermons, series, preachers, and services from Sermon Browser 1.
  	 	     When you import data from Sermon Browser 1, your Sermon Browser 1 data will remain untouched in the database, in case you would like to run Sermon Browser 1 in the future.
   		     To remove Sermon Browser 1 data after you import, activate Sermon Browser 1 and choose Uninstall from the SB1 menu.', 'sermon-importer'); ?>
   	    	 </p>
	        <p>
    	    <?php _e('There is no undo for this import function.', 'sermon-importer'); ?>
        	</p>
	        <p>
    	    <?php _e('We recommend that you back up your database before using this import function.', 'sermon-importer'); ?>
        	</p>
	        <hr />
		<?php
    		$import_count = array();
		    $tables = array('sb_sermons', 'sb_series', 'sb_preachers', 'sb_services');
    		foreach ($tables as $table) {
        		$table_name = $wpdb->prefix.$table;
	        	if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name) {
	    	        $import_count[$table] = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
    	    	}
	    	    else
    	    	    $import_count[$table] = 0;
		    }
    		if ( array_values($import_count) === array(0,0,0,0) ) {
		?>
    	    <p>
        	<?php _e('There is not any SB1 data found in your database.', 'sermon-importer'); ?>
        	</p>
		<?php
    		}
	    	else {
		?>
    		    <p>
        		<?php _e('The following SB1 data has been found in your database:', 'sermon-importer'); ?>
		        </p>
    		    <ul>
        		    <li><?php echo $import_count['sb_sermons'].' '.__('Sermons', 'sermon-importer'); ?></li>
            		<li><?php echo $import_count['sb_series'].' '.__('Series', 'sermon-importer'); ?></li>
	            	<li><?php echo $import_count['sb_preachers'].' '.__('Preachers', 'sermon-importer'); ?></li>
	    	        <li><?php echo $import_count['sb_services'].' '.__('Services', 'sermon-importer'); ?></li>
    	    	</ul>
		<?php
// Testing zone 
/*
$sb1_upload_folder = _ds_sermon_importer_get_sb1_option('upload_dir');
if ($sb1_upload_folder != null)
	_ds_sermon_importer_update_option( 'legacy_upload_folder', trailingslashit(ltrim($sb1_upload_folder, '/')) );
echo '<pre>$sb1_upload_folder : '; print_r($sb1_upload_folder); echo '</pre>';
$uploads_url = trailingslashit( site_url() ). $sb1_upload_folder;
echo trailingslashit( site_url() ). $sb1_upload_folder;

    $sermons_sb1_db = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sb_sermons", OBJECT_K);
    if ($wpdb->num_rows > 0) {
        foreach ($sermons_sb1_db as $sermon_sb1) {
			echo '<pre>Sermon: '; print_r($sermon_sb1); echo '</pre>';
			$sb1_series = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sb_series WHERE id={$sermon_sb1->series_id}" );
			if ( $wpdb->num_rows > 0) {
				foreach ($sb1_series as $sb1_serie) {
							echo '<pre>Series: '; print_r($sb1_serie->name); echo '</pre>';
				}
			}
			$sb1_preachers = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sb_preachers WHERE id={$sermon_sb1->preacher_id}" );
			if ( $wpdb->num_rows > 0) {
				foreach ($sb1_preachers as $sb1_preacher) {
							echo '<pre>Preacher: '; print_r($sb1_preacher->name); echo '</pre>';
				}
			}
			$sb1_services = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sb_services WHERE id={$sermon_sb1->service_id}" );
			if ( $wpdb->num_rows > 0) {
				foreach ($sb1_services as $sb1_service) {
							echo '<pre>Service: '; print_r($sb1_service->name); echo '</pre>';
				}
			}
			echo '<pre>Date: '; print_r($sermon_sb1->datetime); echo '</pre>';
			$ugly_date = $sermon_sb1->datetime;
			$pretty_date = mysql2date('Y-m-d H:i:s', $ugly_date);
			echo '<pre>$pretty_date: '; print_r($pretty_date); echo '</pre>';
		}
	}
	return; */
 //end testing zone
 		?>
    		    <form method="post">
        		<p class="submit">
            		<input type="submit" name="import" class="button button-primary" value="<?php esc_attr_e('Import data from SB1', 'sermon-importer'); ?>" onclick="return confirm('<?php esc_attr_e('Do you REALLY want to import data from SB1?', 'sermon-importer'); ?>')" />
		        </p>
    		    </form>
		<?php
    		}
	    }
		?>
	    </div><!-- /.wrap -->
<?php
}

/**
* Import data from SB1
*/
function _ds_sermon_importer_from_SB1() {
    global $wpdb;
    // Get currently logged in user ID, used as the author of the imported posts
    $current_user_id = wp_get_current_user()->ID;
    
    // Pull in some Sermon Browser functions that we need
	require_once( SB_FRONTEND_FUNCTIONS );
	
    // Import Series as a Category
    $count_series_imported = 0;
    $count_series_duplicate = 0;
    $count_series_restored = 0;
    $count_series_error = 0;
    $series_xref = array();
    $series_sb1_db = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sb_series", OBJECT_K);
    if ($wpdb->num_rows > 0) {
        foreach ($series_sb1_db as $series_sb1) {
			wp_insert_term( $series_sb1->name, 'wp3s_sermon_category');				
			        $count_series_imported++;
        }
    }

    // Import Services as a Category
    $count_services_imported = 0;
    $count_services_duplicate = 0;
    $count_services_restored = 0;
    $count_services_error = 0;
    $services_xref = array();
    $services_sb1_db = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sb_services", OBJECT_K);
    if ($wpdb->num_rows > 0) {
        foreach ($services_sb1_db as $service_sb1) {
			wp_insert_term( $service_sb1->name, 'wp3s_sermon_category');				
                    $count_services_imported++;
            }
    }
    

    // Import Preacher
    $count_preachers_imported = 0;
    $count_preachers_duplicate = 0;
    $count_preachers_restored = 0;
    $count_preachers_error = 0;
    $preacher_image_skipped = false;
    $preachers_xref = array();
    $preachers_sb1_db = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sb_preachers", OBJECT_K);
    if ($wpdb->num_rows > 0) {
        foreach ($preachers_sb1_db as $preacher_sb1) {
			wp_insert_term( $preacher_sb1->name, 'wp3s_sermon_speaker', array('description'=> $preacher_sb1->description));
                $count_preachers_imported++;
        }
    }
    
    // Import Sermons
    $count_sermons_imported = 0;
    $count_sermons_duplicate = 0;
    $count_sermons_restored = 0;
    $count_sermons_error = 0;
    $count_tags = 0;
    $count_attachments = 0;
    $sermons_xref = array();
    $sermons_sb1_db = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sb_sermons", OBJECT_K);
    if ($wpdb->num_rows > 0) {
        foreach ($sermons_sb1_db as $sermon_sb1) {
			// check if post exists by search for one with the same title
			$search_args = array(
//				'post_title_like' => $sermon_sb1->title
				'_ds_query_post_title' => $sermon_sb1->title
			);
			$title_search_result = new WP_Query( $search_args );

			// If there are no posts with the title of the sermon then make the sermon
			if ($title_search_result->post_count == 0) {
				// Get series data
				if ( $sermon_sb1->series_id ) {
					$sb1_series = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sb_series WHERE id={$sermon_sb1->series_id}" );
					if ( $wpdb->num_rows > 0) {
						foreach ($sb1_series as $sb1_serie) {
							$series_name = $sb1_serie->name;
						}
					}
				}
				//Get the taxomony for the series
				$series_term = term_exists( $series_name );
				
				
				              
				// Get service data
				if ( $sermon_sb1->service_id ){
					$sb1_services = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sb_services WHERE id={$sermon_sb1->service_id}" );
					if ( $wpdb->num_rows > 0) {
						foreach ($sb1_services as $sb1_service) {
							$service_name = $sb1_service->name;
						}
					}
				}  
				//Get the taxomony for the service
				$service_term = term_exists( $service_name );
				
				                  
				// Get preacher data
				if ( $sermon_sb1->preacher_id ){
					$sb1_preachers = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sb_preachers WHERE id={$sermon_sb1->preacher_id}" );
					if ( $wpdb->num_rows > 0) {
						foreach ($sb1_preachers as $sb1_preacher) {
							$preacher_name = $sb1_preacher->name;
						}
					}
				}   
				//Get the taxomony for the preacher (which Uplift calls a speaker)
				$speaker_term = term_exists( $preacher_name );
				
				                 
				$return_date = mysql2date('Y-m-d H:i:s', $sermon_sb1->datetime);
				// add new sermon to Uplift Sermons
                $new_sermon = array(
                    'post_title'   => $sermon_sb1->title,
                    'post_author'  => $current_user_id,
                    'post_status'  => 'publish',
                    'post_type'    => 'wp3s_sermons',
                    'post_content' => $sermon_sb1->description,
                    'post_date'    => $sermon_sb1->datetime,
					'tax_input'   => array (
							'wp3s_sermon_speaker'	=> isset($speaker_term) ? $speaker_term : '',
							'wp3s_sermon_category' 	=> array (
																isset($series_term) ? $series_term : '',
																isset($service_term) ? $service_term : ''
															)
						)
                );

                $wp3s_sermon_id = wp_insert_post($new_sermon);

                if ( $wp3s_sermon_id ) {
                    $count_sermons_imported++;
					
                    // Add Sermon Tags
                    $sb1_tag_db = $wpdb->get_results( "SELECT sermons_tags.*, tags.name FROM {$wpdb->prefix}sb_sermons_tags as sermons_tags LEFT JOIN {$wpdb->prefix}sb_tags as tags ON sermons_tags.tag_id=tags.id WHERE sermons_tags.sermon_id={$sermon_sb1->id}" );
                    if ( $wpdb->num_rows > 0 ) {
                        foreach ($sb1_tag_db as $tag) {
                            if ($tag->name)
                                wp_set_post_terms( $wp3s_sermon_id, $tag->name, 'wp3s_sermon_tag' );
                        }
                    }
 

                    // Add Bible Passages
                    $start = unserialize($sermon_sb1->start);
                    $end = unserialize($sermon_sb1->end);
                    $bible_passage_count = count($start);
                    
                    for ($i = 0; $i < $bible_passage_count; $i++) {
                        if ( $start[$i] and $end[$i] ){
                            $reference = sb_tidy_reference( $start[$i], $end[$i] );                      
                            $bible_text = sb_add_bible_text( $start[$i], $end[$i], 'esv' );
                            $new_verse = array(
								'post_title' 		=> $reference . ' ESV',
								'post_content' 		=> $bible_text,
			                    'post_author'  		=> $current_user_id,
								'post_date'    		=> $sermon_sb1->datetime,
								'post_status'    	=> 'publish',
								'post_type'		 	=> 'wp3s_verses' );
							$wp3s_verse_id = wp_insert_post( $new_verse );
							add_post_meta( $wp3s_sermon_id, '_wp3s_sermon_verses', $wp3s_verse_id );
							add_post_meta( $wp3s_verse_id, '_wp3s_verse', $reference );
						}
                    }
					
				
                    // Add Media Attachments
                    $sb1_attachments = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sb_stuff WHERE sermon_id={$sermon_sb1->id}" );
                    if ( $wpdb->num_rows > 0) {
                        // Define uploads url
						$sb1_upload_folder = _ds_sermon_importer_get_sb1_option('upload_dir');
                        if ($sb1_upload_folder != null)
                            _ds_sermon_importer_update_option( 'legacy_upload_folder', trailingslashit(ltrim($sb1_upload_folder, '/')) );
						$uploads_url = trailingslashit( site_url() ). $sb1_upload_folder;

                        foreach ($sb1_attachments as $sb1_attachment) {
						///////////////////start add attachment
						
                            if ($sb1_attachment->type == 'url') {
								// Get the url
								$file_name = rawurlencode( $sb1_attachment->name );
								$url_for_attachment = $file_name;
								$count_attachments++;
                            }
                            elseif ($sb1_attachment->type == 'file') {
                                // Get the filename
								$file_name = rawurlencode( $sb1_attachment->name );
								$url_for_attachment = trailingslashit( $uploads_url ). $file_name;
                                $count_attachments++;
                            }
                            elseif ($sb1_attachment->type == 'code') {
                                $embed_code = base64_decode($sb1_attachment->name);
								add_post_meta( $wp3s_sermon_id, 'sermon_video', $embed_code, $unique = false );
                                $count_attachments++;
								return;
                            }
						// move the file to the right month/date directory in wordpress
						//whilst we're at it - remove any spaces in the filename!
						$wp_file_info = wp_upload_bits( $file_name, null, file_get_contents( $url_for_attachment ), $sermon_sb1->datetime );

						// add the file to the sermon.
						// first step - create an 'audio' post
						$wp_filetype = wp_check_filetype( $wp_file_info['file'], null );
						
						$audio_post = array(
							'post_title' 	=> $sermon_sb1->title,
							'post_status' 	=> 'publish',
							'post_date'    	=> $sermon_sb1->datetime,
							'post_type'		=> 'wp3s_audio',
//							'guid'			=> $wp_file_info['file']
						);
						$wp3s_audio_id = wp_insert_post( $audio_post );
						
						//then attach the audio to the audio post
						$attachment = array(
							'post_mime_type'	=> $wp_filetype['type'],
							'post_title' 		=> $sermon_sb1->title .' audio '.$preacher_name,
							'post_content' 		=> $sermon_sb1->title.' by '.$preacher_name,
							'post_date'    		=> $sermon_sb1->datetime,
							'post_status'    	=> 'inherit',
//							'guid'           	=> $wp_file_info['url'],
							'post_parent'    	=> $wp3s_audio_id,
//							'post_type'		 	=> 'wp3s_audio'
						);
						$attach_id = wp_insert_attachment( $attachment, $wp_file_info['file'], $wp3s_audio_id );

						$attachment_data = wp_generate_attachment_metadata( $attach_id, $wp_file_info['file'] );
						wp_update_attachment_metadata( $attach_id, $attachment_data );

						add_post_meta( $wp3s_audio_id, '_wp3s_audio_file', $wp_file_info['url'] );

						//finally link the audio post and the sermon post through the metadata table
						//and also create an 'enclosure' for the podcast
						
						add_post_meta( $wp3s_sermon_id, '_wp3s_sermon_audio', $wp3s_audio_id );

						$file_size = filesize( get_attached_file($attach_id) );
						
						$podcast_enclosure = $wp_file_info['file']."\n".$file_size."\n".$wp_filetype['type'];
						
						add_post_meta( $wp3s_sermon_id, 'enclosue', $podcast_enclosure );


						// if moved correctly and file is still there delete the original
						if ( file_exists( $url_for_attachment ) && empty( $wp_file_info['error'] ) ) {
							unlink( $file_path );
						}
/*
I've skipped this since I don't think we've got any attachments.
						// This is for embeded images and attached files
						// you must first include the image.php file
						// for the function wp_generate_attachment_metadata() to work
						require_once ABSPATH . 'wp-admin/includes/image.php';
						$attach_data = wp_generate_attachment_metadata( $attach_id, $wp_file_info['file'] );
						wp_update_attachment_metadata( $attach_id, $attach_data );
						$clean_file_url = $wp_file_info['url'];
						
							switch($wp_filetype['ext']) {
								case "doc":
									add_post_meta( $wp3s_sermon_id, 'sermon_notes', $clean_file_url, $unique = false );
								break;

								case "docx":
									add_post_meta( $wp3s_sermon_id, 'sermon_notes', $clean_file_url, $unique = false );
								break;

								case "pdf":
									add_post_meta( $wp3s_sermon_id, 'sermon_notes', $clean_file_url, $unique = false );
								break;
								
								case "mp3":
									add_post_meta( $wp3s_sermon_id, 'sermon_audio', $clean_file_url, $unique = false );
								break;

								case "": // Handle file extension for files ending in '.'
								break;
								case NULL: // Handle no file extension
								break;
							}
						//add_post_meta( $wp3s_sermon_id, 'sermon_notes', $wp_file_info['url'], $unique = false );
						//add_post_meta( $wp3s_sermon_id, 'sermon_audio', $wp_file_info['url'], $unique = false );

						///////////////////end add attachment
*/
                        }
                    }
                }
			
            } else {
                // sermon already exists
                // skip import, use existing sermon
                $count_sermons_duplicate++;
            }
        }
    }
    // Output results
?>
    <div id="message" class="updated fade">
        <h3>Import Results</h3>
        <p><ul>
            <li><?php echo $count_sermons_imported, ' ', __('sermons imported.', 'sermon-importer'); ?></li>
            <li><?php echo $count_sermons_duplicate, ' ', __('duplicate sermons skipped.', 'sermon-importer'); ?></li>
            <li><?php echo $count_sermons_error, ' ', __('sermons not imported due to error.', 'sermon-importer'); ?></li>
        </ul></p>
        <p><ul>
            <li><?php echo $count_attachments, ' ', __('attachments imported.', 'sermon-importer'); ?></li>
        </ul></p>
        <p><ul>
            <li><?php echo $count_series_imported, ' ', __('series imported.', 'sermon-importer'); ?></li>
            <li><?php echo $count_series_duplicate, ' ', __('duplicate series skipped.', 'sermon-importer'); ?></li>
            <li><?php echo $count_series_restored, ' ', __('series restored from the trash.', 'sermon-importer'); ?></li>
            <li><?php echo $count_series_error, ' ', __('series not imported due to error.', 'sermon-importer'); ?></li>
        </ul></p>
        <p><ul>
            <li><?php echo $count_services_imported, ' ', __('services imported.', 'sermon-importer'); ?></li>
            <li><?php echo $count_services_duplicate, ' ', __('duplicate services skipped.', 'sermon-importer'); ?></li>
            <li><?php echo $count_services_restored, ' ', __('services restored from the trash.', 'sermon-importer'); ?></li>
            <li><?php echo $count_services_error, ' ', __('services not imported due to error.', 'sermon-importer'); ?></li>
        </ul></p>
        <p><ul>
            <li><?php echo $count_preachers_imported, ' ', __('preachers imported.', 'sermon-importer'); ?>
                <?php if ($preacher_image_skipped) echo ' ', __('Note: Images attached to preachers in SB1 have not been imported into SB2.', 'sermon-importer'); ?></li>
            <li><?php echo $count_preachers_duplicate, ' ', __('duplicate preachers skipped.', 'sermon-importer'); ?></li>
            <li><?php echo $count_preachers_restored, ' ', __('preachers restored from the trash.', 'sermon-importer'); ?></li>
            <li><?php echo $count_preachers_error, ' ', __('preachers not imported due to error.', 'sermon-importer'); ?></li>
        </ul></p>
    </div>
<?php
}

?>