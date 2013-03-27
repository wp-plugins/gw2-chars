<?php
/*
Plugin Name: Guild Wars 2 Character List
Description: Users can manage their own Guild Wars 2 characters. A list of all characters can be integrated in any post or page.
Version: 1.1.1
Author: Arne
License: GPL3
*/

global $gw2chars_database_version;
$gw2chars_database_version = 1;

add_action ( 'admin_menu', 'gw2chars_menu' );
register_activation_hook(__FILE__,'gw2chars_create_database');
if (get_site_option('gw2chars_database_version') != gw2chars_database_version) gw2chars_create_database();
add_shortcode( 'gw2chars', 'gw2chars_shortcode' );
add_action('wp_head', 'gw2chars_css');
register_uninstall_hook( __FILE__, 'gw2chars_uninstall' );

load_plugin_textdomain( 'gw2chars', false, basename( dirname( __FILE__ ) ) . '/languages' );


function gw2chars_css() {
	echo '<link rel="stylesheet" href="' . plugin_dir_url( __FILE__ ) . 'gw2-chars.css" />' . "\n";
}


function gw2chars_menu() {
	add_menu_page( __( 'Manage my Characters', 'gw2chars' ), __( 'My Chars', 'gw2chars' ), 'upload_files', 'gw2charsmainmenu', 'gw2chars_page', plugin_dir_url( __FILE__ ).'pics/gw2.png', '99.000020120924171044' );
}


/**
 *	Displaying Guild Member List on the Frontend
 */

function gw2chars_shortcode( $attributes ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'gw2chars';
	$output = "";
	
	$chars = $wpdb->get_results( "
		SELECT id, user_id, name, picture_file, race, profession, crafting1, crafting2, fractals, dungeons, teaser
		FROM $table_name
		ORDER BY user_id ASC, name ASC
	" );

	$alternate = 1;
	$last_uid = 0;
	
	foreach ( $chars as $char ) {
		if ( $char->user_id != $last_uid) {
			$user = get_user_by('id', $char->user_id);
			$output .= '<div class="gw2user">' . esc_html( $user->display_name ) . '</div>' . "\n";
			$last_uid = $char->user_id;
		}
		$output .= '<div class="gw2row ' . ( $alternate ? 'gw2odd' : 'gw2even' ) . '">' . "\n";
		$output .= '<div class="gw2desc">';
		
		$output .= '<div class="gw2pic">';
		if ( file_exists( $char->picture_file ) ) {
	
			$upload_path = wp_upload_dir();
			$image_sized = image_resize( $char->picture_file, 150, 200, true, null, null, 100 );		
			$image_sized = is_wp_error($image_sized) ? $char->picture_file : $image_sized;
			$image_sized = str_replace( $upload_path['basedir'], $upload_path['baseurl'], $image_sized );	

			$output .= '<img src="' . $image_sized . '" alt="' . esc_html( $char->name ) . '" />';
		} else {
			$output .= '<img src="' . plugin_dir_url( __FILE__ ).'pics/nopic.png' . '" alt="' . esc_html( $char->name ) . '" />';
		}
		
		$output .= '</div>' . "\n";
		$output .= '<strong><span style="font-size:2em;">' . esc_html($char->name) . '</span></strong><br />';
		
		$output .= '<img src="' . plugin_dir_url( __FILE__ ) . 'pics/';
		
		switch ( $char->race ) {
			case 1:
				$output .= 'asura';
				break;
			case 2:
				$output .= 'charr';
				break;
			case 3:
				$output .= 'human';
				break;
			case 4:
				$output .= 'norn';
				break;
			case 5:
				$output .= 'sylvari';
				break;
		}
		
		$output .= '.png" alt="' . gw2chars_get_race( $char->race ) . '" /> ';

		$output .= gw2chars_get_race( $char->race );
		
		$output .= '<br />';
		
		$output .= '<img src="' . plugin_dir_url( __FILE__ ) . 'pics/';
		
		switch ( $char->profession ) {
			case 1:
				$output .= 'guardian';
				break;
			case 2:
				$output .= 'warrior';
				break;
			case 3:
				$output .= 'engineer';
				break;
			case 4:
				$output .= 'ranger';
				break;
			case 5:
				$output .= 'thief';
				break;
			case 6:
				$output .= 'elementalist';
				break;
			case 7:
				$output .= 'mesmer';
				break;
			case 8:
				$output .= 'necromancer';
				break;
		}
		
		$output .= '.png" alt="' . gw2chars_get_profession( $char->profession ) . '" /> ';

		$output .= gw2chars_get_profession( $char->profession );

		$output .= '<br />';
		
		if ( $char->crafting1 != 0 ) $output .= gw2chars_get_crafting_skill_pic( $char->crafting1 ) . ' ';
		if ( $char->crafting1 != 0 ) $output .= gw2chars_get_crafting_skill( $char->crafting1 );
		if ( ( $char->crafting1 != 0 ) and ( $char->crafting2 != 0 ) ) $output .= ' ' . __( 'and', 'gw2chars' ) . ' ';
		if ( $char->crafting2 != 0 ) $output .= gw2chars_get_crafting_skill_pic( $char->crafting2 ) . ' ';
		if ( $char->crafting2 != 0 ) $output .= gw2chars_get_crafting_skill( $char->crafting2 );
		if ( ( $char->crafting1 == 0 ) and ( $char->crafting2 == 0 ) ) $output .= __( 'no crafting skills', 'gw2chars' );
                if ( $char->fractals != 0 ) {
                    $output .= '<br />';
                    $output .= '<img src="' . plugin_dir_url( __FILE__ ) . 'pics/fractals.jpg" />';
                    $output .= ' ' . __( 'Fractals Level', 'gw2chars' ) . ': ' . $char->fractals;
                }
                if ( strlen($char->dungeons) > 0 ) {
                    $dungeons = unserialize( $char->dungeons );
                    if ( sizeof( $dungeons ) > 0 ) {
                        $output .= '<br />';
                        $output .= __( 'Story Mode Completed', 'gw2chars' ) . ':<br />';
                        if ($dungeons['ac'] == 1) $output .= '<img title="' . __( 'Ascalonian Catacombs', 'gw2chars' ) . '" src="' . plugin_dir_url( __FILE__ ) . 'pics/dungeons/ac.png" class="gw2chars_dungeonpic" /> ';
                        if ($dungeons['cm'] == 1) $output .= '<img title="' . __( 'Caudecus\'s Manor', 'gw2chars' ) . '" src="' . plugin_dir_url( __FILE__ ) . 'pics/dungeons/cm.png" class="gw2chars_dungeonpic" /> ';
                        if ($dungeons['ta'] == 1) $output .= '<img title="' . __( 'Twilight Arbor', 'gw2chars' ) . '" src="' . plugin_dir_url( __FILE__ ) . 'pics/dungeons/ta.png" class="gw2chars_dungeonpic" /> ';
                        if ($dungeons['se'] == 1) $output .= '<img title="' . __( 'Sorrow\'s Embrace', 'gw2chars' ) . '" src="' . plugin_dir_url( __FILE__ ) . 'pics/dungeons/se.png" class="gw2chars_dungeonpic" /> ';
                        if ($dungeons['cof'] == 1) $output .= '<img title="' . __( 'Citadel of Flame', 'gw2chars' ) . '" src="' . plugin_dir_url( __FILE__ ) . 'pics/dungeons/cof.png" class="gw2chars_dungeonpic" /> ';
                        if ($dungeons['hotw'] == 1) $output .= '<img title="' . __( 'Honor of the Waves', 'gw2chars' ) . '" src="' . plugin_dir_url( __FILE__ ) . 'pics/dungeons/hotw.png" class="gw2chars_dungeonpic" /> ';
                        if ($dungeons['coe'] == 1) $output .= '<img title="' . __( 'Crucible of Eternity', 'gw2chars' ) . '" src="' . plugin_dir_url( __FILE__ ) . 'pics/dungeons/coe.png" class="gw2chars_dungeonpic" /> ';
                        if ($dungeons['arah'] == 1) $output .= '<img title="' . __( 'Arah', 'gw2chars' ) . '" src="' . plugin_dir_url( __FILE__ ) . 'pics/dungeons/arah.png" class="gw2chars_dungeonpic" /> ';
                    }
                }
		if ( strlen( $char->teaser ) > 2 ) {
			$output .= '<br />'."\n".'<em>»' . esc_html( $char->teaser ) . '«</em>';
		}
		$output .= '</div>' . "\n";
                $output .= '<div style="clear:both;"></div>';
		$output .= '</div>' . "\n";
		$alternate = 1 - $alternate;
	}
	
	return $output;
}


/**
 *	DISPLAYS PAGE IN BACKEND AND DOES THE DB-EDITING
 */


function gw2chars_page() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'gw2chars';
	$cur_user = get_userdata( get_current_user_id() );
	$user_level = $cur_user->user_level;

	// Ensuring I'm either at least a editor or the owner of this char
	$where_two = "";
	if ( $user_level < 7 ) {
		$where_two = ' AND user_id = '.get_current_user_id();
	}
	

	/**
	 *	ADD NEW CHAR TO DATABASE
	 */

	if ( isset( $_POST['gw2chars_add'] ) ) {

		if ( ! isset( $_POST['_gw2chars_nonce'] ) || ! wp_verify_nonce( $_POST['_gw2chars_nonce'], 'gw2chars_nonce' ) )	return;
		
		$name       = stripslashes( $_POST['name'] );
		$teaser     = stripslashes( $_POST['teaser'] );
		$race       = intval      ( $_POST['race'] );
		$profession = intval      ( $_POST['profession'] );
		$crafting1  = intval      ( $_POST['crafting1'] );
		$crafting2  = intval      ( $_POST['crafting2'] );
		$fractals   = intval      ( $_POST['fractals'] );
                if (($fractals > 999) or ($fractals < 2)) $fractals = 0;
                
                $dungeons = array();
                if ( intval( $_POST['ac'] ) == 1)   $dungeons['ac'] = 1;
                if ( intval( $_POST['cm'] ) == 1)   $dungeons['cm'] = 1;
                if ( intval( $_POST['ta'] ) == 1)   $dungeons['ta'] = 1;
                if ( intval( $_POST['se'] ) == 1)   $dungeons['se'] = 1;
                if ( intval( $_POST['cof'] ) == 1)  $dungeons['cof'] = 1;
                if ( intval( $_POST['hotw'] ) == 1) $dungeons['hotw'] = 1;
                if ( intval( $_POST['coe'] ) == 1)  $dungeons['coe'] = 1;
                if ( intval( $_POST['arah'] ) == 1) $dungeons['arah'] = 1;
		
		if ($name == "") {
			echo '<div id="message" class="updated fade"><p><strong>' . __( 'Name can\'t be left blank.', 'gw2chars' ) . '</strong></p></div>';
		} else {
			
			// Only JPG and PNG. Not even annoying GIFs.
			
			if ( ! empty( $_FILES['picture']['name'] ) ) {
				$mimes = array(
					'jpg|jpeg' => 'image/jpeg',
					'png' => 'image/png'
				);
				$charpic = wp_handle_upload( $_FILES['picture'], array( 'mimes' => $mimes, 'test_form' => false ) );
				if ( ! empty ( $charpic['file'] ) ) {
					$picture_file = $charpic['file'];
				}
			} 
		
			$wpdb->query(
				$wpdb->prepare("
					INSERT INTO $table_name
					( name, race, profession, crafting1, crafting2, picture_file, teaser, fractals, dungeons, user_id )
					VALUES ( %s, %d, %d, %d, %d, %s, %s, %d, %s, %d )
				", $name, $race, $profession, $crafting1, $crafting2, $picture_file, $teaser, $fractals, serialize($dungeons), get_current_user_id() )
			);
		
			echo '<div id="message" class="updated fade"><p><strong>' . __( 'Character added.', 'gw2chars' ) . '</strong></p></div>';
		}
	}
	
	
	/**
	 *	MODIFY ENTRY
	 */
		
	if ( isset( $_POST['gw2chars_change'] ) ) {

		if ( ! isset( $_POST['_gw2chars_nonce'] ) || ! wp_verify_nonce( $_POST['_gw2chars_nonce'], 'gw2chars_nonce' ) )	return;
		
		$name       = stripslashes( $_POST['name'] );
		$teaser     = stripslashes( $_POST['teaser'] );
		$id         = intval      ( $_POST['gw2chars_id'] );
		$race       = intval      ( $_POST['race'] );
		$profession = intval      ( $_POST['profession'] );
		$crafting1  = intval      ( $_POST['crafting1'] );
		$crafting2  = intval      ( $_POST['crafting2'] );
		$fractals   = intval      ( $_POST['fractals'] );
                if (($fractals > 999) or ($fractals < 2)) $fractals = 0;
		$delete     = intval      ( $_POST['deleteoldpic'] or 0 );

                $dungeons = array();
                if ( intval( $_POST['ac'] ) == 1)   $dungeons['ac'] = 1;
                if ( intval( $_POST['cm'] ) == 1)   $dungeons['cm'] = 1;
                if ( intval( $_POST['ta'] ) == 1)   $dungeons['ta'] = 1;
                if ( intval( $_POST['se'] ) == 1)   $dungeons['se'] = 1;
                if ( intval( $_POST['cof'] ) == 1)  $dungeons['cof'] = 1;
                if ( intval( $_POST['hotw'] ) == 1) $dungeons['hotw'] = 1;
                if ( intval( $_POST['coe'] ) == 1)  $dungeons['coe'] = 1;
                if ( intval( $_POST['arah'] ) == 1) $dungeons['arah'] = 1;

                // Am I allowed to do that?

		if ( $id > 0 ) {
			$entry = $wpdb->get_row('SELECT * FROM ' . $table_name . ' WHERE id = '.$id . $where_two );
			if ($entry != null) {
				$picture_file = $entry->picture_file;
			} else {
				wp_die( 'No' );
			}
		} else {
			wp_die( 'No' );
		}
		
		// Then do it
		// unnice code reuse

		if ($name == "") {
			echo '<div id="message" class="updated fade"><p><strong>' . __( 'Name can\'t be left blank.', 'gw2chars' ) . '</strong></p></div>';
		} else {
		
			// Delete old images if a new one was uploaded
			// or the user wanted the old one to be deleted
			
			if ( ( ! empty( $_FILES['picture']['name'] ) ) or ( $delete ) ) {
					@unlink( $entry->picture_file );
					$otherpath = pathinfo( $entry->picture_file );
					@unlink( $otherpath['dirname'] . '/' . $otherpath['filename'] . '-150x200.' . $otherpath['extension'] );
					$picture_file = "";
			}
		
			if ( ! empty( $_FILES['picture']['name'] ) ) {
				$mimes = array(
					'jpg|jpeg' => 'image/jpeg',
					'png' => 'image/png'
				);
				$charpic = wp_handle_upload( $_FILES['picture'], array( 'mimes' => $mimes, 'test_form' => false ) );
				if ( ! empty ( $charpic['file'] ) ) {
					$picture_file = $charpic['file'];
				}
			} 
		
			$wpdb->query(
				$wpdb->prepare("
					UPDATE $table_name SET
					name = %s,
					race = %d,
					profession = %d,
					crafting1 = %d,
					crafting2 = %d,
					picture_file = %s,
					teaser = %s,
                                        fractals = %d,
                                        dungeons = %s
					WHERE id = %d
				", $name, $race, $profession, $crafting1, $crafting2, $picture_file, $teaser, $fractals, serialize($dungeons), $id)
			);
		
			echo '<div id="message" class="updated fade"><p><strong>' . __( 'Character edited.', 'gw2chars' ) . '</strong></p></div>';
		}
	}
	
	// Default Values
	
	$name       = "";
	$teaser     = "";
	$race       = 3;
	$profession = 6;
	$crafting1  = 0;
	$crafting2  = 0;
        $fractals   = 0;
        $dungeons = array();
	
	/**
	 *	Delete selected
	 */
	 
	if ( isset( $_POST['gw2chars_delete'] ) ) {
		$delete = $_POST["post"];
		
		if ( sizeof( $delete ) < 1 ) {
		
			echo '<div id="message" class="updated fade"><p><strong>' . __( 'Nothing to delete.', 'gw2chars' ) . '</strong></p></div>';
		
		} else {
		
			$delete_string = "";
			foreach ($delete as $todelete) {
				$delete_string = $delete_string . intval($todelete) . ', ';
			}
			$delete_string = substr($delete_string, 0, strlen($delete_string) - 2);
			
			// Deleting Images and resized Images

			$file_list = $wpdb->get_results( 'SELECT user_id, picture_file FROM ' . $table_name . ' WHERE id IN ( ' . $delete_string  . ' )' . $where_two );
					
			foreach ( $file_list as $image_file ) {
				if ( $image_file->picture_file ) {
					@unlink( $image_file->picture_file );
					$otherpath = pathinfo( $image_file->picture_file );
					@unlink( $otherpath['dirname'] . '/' . $otherpath['filename'] . '-150x200.' . $otherpath['extension'] );
				}
			}
			
			$wpdb->query( 'DELETE FROM ' . $table_name . ' WHERE id IN ( ' . $delete_string  . ' )' . $where_two );
			
			echo '<div id="message" class="updated fade"><p><strong>' . __( 'Deleted selected Characters.', 'gw2chars' ) . '</strong></p></div>';
			
		}
	}
	
	
	/**
	 *  DISPLAY LIST OF CHARS IN THE BACKEND
	 */
	
	?>
	<h1><?php _e( 'Manage my Characters', 'gw2chars' ); ?></h1>
	<p><?php _e( 'On this page you can enter all your characters for the list of all guild members.', 'gw2chars' ); ?></p>
<?php
	if ( $user_level >= 7 ) {
		//
		echo '<p>' . sprintf( __( 'To insert the list on a page or an article enter the shortcode %s.', 'gw2chars' ), '<code>[gw2chars]</code>' ) . '</p>' . "\n";
		if ( isset( $_POST['gw2chars_view_all'] ) ) $_SESSION["gw2chars-viewall"] = true;
		if ( isset( $_POST['gw2chars_view_mine'] ) ) $_SESSION["gw2chars-viewall"] = false;
		if ( $_SESSION["gw2chars-viewall"] == false) {
			echo '<form method="post" action=""><p><input type="submit" name="gw2chars_view_all" id="gw2chars_view_all" value="' . __( 'view all', 'gw2chars' ) . '" /></p></form>'."\n";
		} else {
			echo '<form method="post" action=""><p><input type="submit" name="gw2chars_view_mine" id="gw2chars_view_mine" value="' . __( 'view just mine', 'gw2chars' ) . '" /></p></form>'."\n";
		}
	}
?>
	<form method="post" action="">
	<table class="wp-list-table widefat" cellspacing="0">
		<thead>
			<tr>
				<th scope='col' id='cb' class='manage-column column-cb check-column'  style=""><input type="checkbox" /></th>
				<th><?php _e( 'Picture', 'gw2chars' ); ?></th>
				<th><?php _e( 'Details', 'gw2chars' ); ?></th>
			</tr>
		</thead>
		<tbody id="the-list">
	<?php
	
	$chars = $wpdb->get_results( "
		SELECT *
		FROM $table_name
		" . ((($user_level >= 7) and ($_SESSION["gw2chars-viewall"])) ? "" : "WHERE user_id = " . get_current_user_id() ) . "
		ORDER BY name
	" );
	
	$alternate = 1;
	
	foreach ( $chars as $char ) {
		echo '<tr' . ( $alternate ? ' class="alternate"' : '' ) . '>' . "\n";
		echo '<th scope="row" class="check-column"><input type="checkbox" name="post[]" value="' . $char->id . '" /></th>' . "\n";
		echo '<td>';
		
		if ( file_exists( $char->picture_file ) ) {
	
			$upload_path = wp_upload_dir();
			$image_sized = image_resize( $char->picture_file, 150, 200, true, null, null, 100 );		
			$image_sized = is_wp_error($image_sized) ? $char->picture_file : $image_sized;
			$image_sized = str_replace( $upload_path['basedir'], $upload_path['baseurl'], $image_sized );	

			echo '<img src="' . $image_sized . '" alt="' . esc_html( $char->name ) . '" />';
		} else {
			echo '<img src="' . plugin_dir_url( __FILE__ ).'pics/nopic.png' . '" alt="' . esc_html( $char->name ) . '" />';
		}
		
		echo '</td>' . "\n";
		echo '<td>';
		echo '<strong><span style="font-size:2em;">' . esc_html($char->name) . '</span></strong><br />';

		$user = (get_user_meta( $char->user_id, 'nickname', true ));
		if ( $user == "" ) {
			$user = '<strong><span style="color:#f00;">' . __( 'User doesn\'t exist!', 'gw2chars' ) . '</span></strong>';
		} else {
			$user = '<em>' . $user . '</em>';
		}
		echo $user . '<br />';
		
		echo gw2chars_get_race( $char->race );
		
		echo '<br />';
		
		echo gw2chars_get_profession( $char->profession );

		echo '<br />';
		
		if ( $char->crafting1 != 0 ) echo gw2chars_get_crafting_skill( $char->crafting1 );
		if ( ( $char->crafting1 != 0 ) and ( $char->crafting2 != 0 ) ) echo ' ' . __( 'and', 'gw2chars' ) . ' ';
		if ( $char->crafting2 != 0 ) echo gw2chars_get_crafting_skill( $char->crafting2 );
		if ( ( $char->crafting1 == 0 ) and ( $char->crafting2 == 0 ) ) _e( 'no crafting skills', 'gw2chars' );

		echo '<br />';
                
                if ( ( $char->fractals) == 0) {
                    _e( 'hasn\'t been doing fractals so far', 'gw2chars' );
                } else {
                    _e( 'Can enter fractals level', 'gw2chars' );
                    echo ': ' . $char->fractals;
                }

		if ( strlen( $char->teaser ) > 2 ) {
			echo '<br />'."\n".'<em>»' . esc_html( $char->teaser ) . '«</em>';
		}
		
		echo '<form method="POST" action="#edit"><p><input type="submit" name="gw2chars_edit" id="gw2chars_edit" value="' . __( 'edit', 'gw2chars' ) . '" /><input type="hidden" name="gw2chars_id" value="' . $char->id . '" /></p></form>' . "\n";
		echo '</td>' . "\n";
		echo '</tr>' . "\n";
		$alternate = 1 - $alternate;
	}
	
	?>
		</tbody>
	</table>
	<p><input type="submit" name="gw2chars_delete" id="gw2chars_delete" value="<?php _e( 'Delete selected', 'gw2chars' ); ?>" /></p>
	</form>
<?php


	/**
	 *	FORM FOR ADDING AND MODIFYING NEW CHARS
	 *	Man, that's ugly. I'm not used to do that on my own.
	 */
	 
?>
	<h2><?php
		if ( isset( $_POST['gw2chars_edit'] ) ) {
			_e( 'Edit Character', 'gw2chars' );
			$id = intval( $_POST['gw2chars_id'] );
			if ( $id > 0 ) {
				$entry = $wpdb->get_row('SELECT * FROM ' . $table_name . ' WHERE id = '.$id . $where_two );
				if ($entry != null) {
					$name       = $entry->name;
					$race       = $entry->race;
					$teaser     = $entry->teaser;
					$profession = $entry->profession;
					$crafting1  = $entry->crafting1;
					$crafting2  = $entry->crafting2;
                                        $fractals   = $entry->fractals;
                                        $dungeons   = unserialize( $entry->dungeons );
				}
			}
		} else {
			_e( 'Add New Character', 'gw2chars' ); 
		}
	?></h2>

	<form method="post" enctype="multipart/form-data" action="">
<?php wp_nonce_field( 'gw2chars_nonce', '_gw2chars_nonce', false ); ?>
	<table class="form-table">
	<tr valign="top">
	<th scope="row"><label for="name"><?php _e( 'Name', 'gw2chars' ); ?>:</label></th>
	<td><input name="name" type="text" id="name" class="regular-text" value="<?php echo $name; ?>" /></td>
	<td rowspan="8" width="160" valign="top">
	<?php
		if ( isset( $_POST['gw2chars_edit'] ) ) {
		
			// When editing a char display the image, too.

			if ( file_exists( $entry->picture_file ) ) {
		
				$upload_path = wp_upload_dir();
				$image_sized = image_resize( $entry->picture_file, 150, 200, true, null, null, 100 );		
				$image_sized = is_wp_error($image_sized) ? $entry->picture_file : $image_sized;
				$image_sized = str_replace( $upload_path['basedir'], $upload_path['baseurl'], $image_sized );	

				echo '<img src="' . $image_sized . '" alt="' . esc_html( $entry->name ) . '" />';
			} else {
				echo '<img src="' . plugin_dir_url( __FILE__ ).'pics/nopic.png' . '" alt="' . esc_html( $entry->name ) . '" />';
			}

		}
	?>
	</td>
	</tr>
	<tr valign="top">
	<th scope="row"><label for="race"><?php _e( 'Race', 'gw2chars' ); ?>:</label></th>
	<td><select name="race" id="race">
	<?php
		for ( $i=1;$i<=5;$i++ ) {
			echo '<option' . ( $i == $race ? ' selected="selected"' : '' ) . ' value="' . $i . '">' . gw2chars_get_race( $i ) . '</option>' . "\n";
		}
	?></select></td>
	</tr>
	<tr valign="top">
	<th scope="row"><label for="profession"><?php _e( 'Profession', 'gw2chars' ); ?>:</label></th>
	<td><select name="profession" id="profession">
	<?php
		for ( $i=1;$i<=8;$i++ ) {
			echo '<option' . ( $i == $profession ? ' selected="selected"' : '' ) . ' value="' . $i . '">' . gw2chars_get_profession( $i ) . '</option>' . "\n";
		}
	?></select></td>
	</tr>
	<tr valign="top">
	<th scope="row"><label for="crafting1"><?php _e( 'First Crafting Skill', 'gw2chars' ); ?>:</label></th>
	<td><select name="crafting1" id="crafting1">
	<?php
		for ( $i=0;$i<=8;$i++ ) {
			echo '<option' . ( $i == $crafting1 ? ' selected="selected"' : '' ) . ' value="' . $i . '">' . gw2chars_get_crafting_skill( $i ) . '</option>' . "\n";
		}
	?></select></td>
	</tr>
	<tr valign="top">
	<th scope="row"><label for="crafting2"><?php _e( 'Second Crafting Skill', 'gw2chars' ); ?>:</label></th>
	<td><select name="crafting2" id="crafting2">
	<?php
		for ( $i=0;$i<=8;$i++ ) {
			echo '<option' . ( $i == $crafting2 ? ' selected="selected"' : '' ) . ' value="' . $i . '">' . gw2chars_get_crafting_skill( $i ) . '</option>' . "\n";
		}
	?></select></td>
	</tr>
	<tr valign="top">
	<th scope="row"><label for="fractals"><?php _e( 'Ready to do fractals level', 'gw2chars' ); ?>:</label></th>
	<td><input name="fractals" type="text" id="fractals" class="regular-text" value="<?php echo $fractals; ?>" /></td>
	</tr>
	<tr valign="top">
	<th scope="row"><?php _e( 'Story Mode Completed', 'gw2chars' ); ?>:</th>
        <td>
        <input name="ac" type="checkbox" id="ac" class="checkbox" value="1" <?php if ($dungeons['ac'] == 1) echo 'checked="checked" '; ?>/><label for="ac"> <?php _e( 'Ascalonian Catacombs', 'gw2chars' ); ?></label><br />
        <input name="cm" type="checkbox" id="cm" class="checkbox" value="1" <?php if ($dungeons['cm'] == 1) echo 'checked="checked" '; ?>/><label for="cm"> <?php _e( 'Caudecus\'s Manor', 'gw2chars' ); ?></label><br />
        <input name="ta" type="checkbox" id="ta" class="checkbox" value="1" <?php if ($dungeons['ta'] == 1) echo 'checked="checked" '; ?>/><label for="ta"> <?php _e( 'Twilight Arbor', 'gw2chars' ); ?></label><br />
        <input name="se" type="checkbox" id="se" class="checkbox" value="1" <?php if ($dungeons['se'] == 1) echo 'checked="checked" '; ?>/><label for="se"> <?php _e( 'Sorrow\'s Embrace', 'gw2chars' ); ?></label><br />
        <input name="cof" type="checkbox" id="cof" class="checkbox" value="1" <?php if ($dungeons['cof'] == 1) echo 'checked="checked" '; ?>/><label for="cof"> <?php _e( 'Citadel of Flame', 'gw2chars' ); ?></label><br />
        <input name="hotw" type="checkbox" id="hotw class="checkbox" value="1" <?php if ($dungeons['hotw'] == 1) echo 'checked="checked" '; ?>/><label for="hotw"> <?php _e( 'Honor of the Waves', 'gw2chars' ); ?></label><br />
        <input name="coe" type="checkbox" id="coe" class="checkbox" value="1" <?php if ($dungeons['coe'] == 1) echo 'checked="checked" '; ?>/><label for="coe"> <?php _e( 'Crucible of Eternity', 'gw2chars' ); ?></label><br />
        <input name="arah" type="checkbox" id="arah" class="checkbox" value="1" <?php if ($dungeons['arah'] == 1) echo 'checked="checked" '; ?>/><label for="arah"> <?php _e( 'Arah', 'gw2chars' ); ?></label>
        </td>
	</tr>
	<tr valign="top">
	<th scope="row"><label for="picture"><?php _e( 'Upload Picture', 'gw2chars' ); ?>:</label></th>
	<td><input type="file" name="picture" id="picture" /></td>
	</tr>
<?php
	if ( isset( $_POST['gw2chars_edit'] ) ) {
		echo '<tr>' . "\n";
		echo '<td>' . "\n";
		echo '<input type="checkbox" name="deleteoldpic" id="deleteoldpic" value="1" /><label for="deleteoldpic"> ' . __( 'delete old picture', 'gw2chars' ) . '</label>' . "\n";
		echo '</td>' . "\n";
		echo '<td>' . "\n";
		echo '</td>' . "\n";
		echo '</tr>' . "\n";
	}
?>
	<tr valign="top">
	<th scope="row"><label for="teaser"><?php _e( 'Short Phrase', 'gw2chars' ); ?>:</label></th>
	<td><input name="teaser" type="text" id="teaser" class="regular-text" value="<?php echo $teaser; ?>" /></td>
	</tr>
	</table>
<?php
	if ( isset( $_POST['gw2chars_edit'] ) ) {
		echo '<input type="hidden" name="gw2chars_id" value="' . $id . '" />' . "\n";
		echo '<p><input type="submit" name="gw2chars_change" value="' . __( 'Change', 'gw2chars' ) . '" /></p>' . "\n";
	} else {
		echo '<p><input type="submit" name="gw2chars_add" value="' . __( 'Save', 'gw2chars' ) . '" /></p>' . "\n";
	}
?>
		<a name="edit"></a>
	</form>

	<?php
}


/**
 *	Creating our own database table.
 */

function gw2chars_create_database() {
	global $wpdb;
	global $gw2chars_database_version;
	
	$table_name = $wpdb->prefix . 'gw2chars';

	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		user_id bigint(20) unsigned NOT NULL DEFAULT '0',
		name VARCHAR(255) DEFAULT '' NOT NULL,
		teaser VARCHAR(255) DEFAULT '' NOT NULL,
		picture_file VARCHAR(255) DEFAULT '' NOT NULL,
		race int(11) NOT NULL DEFAULT '0',
		profession int(11) NOT NULL DEFAULT '0',
		crafting1 int(11) NOT NULL DEFAULT '0',
		crafting2 int(11) NOT NULL DEFAULT '0',
		fractals int(11) NOT NULL DEFAULT '0',
                dungeons TEXT NOT NULL DEFAULT '',
		UNIQUE KEY id (id)
	);";
	
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
	
	add_option("gw2chars_database_version", $gw2chars_database_version);
}


/**
 * Uninstall: delete files and database
 */

function gw2chars_uninstall() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'gw2chars';
	
	$file_list = $wpdb->get_results( '
		SELECT id, picture_file
		FROM $table_name
		WHERE NOT picture_file = ""
	' );

	foreach ( $file_list as $image_file ) {
		@unlink( $image_file->picture_file );
		$otherpath = pathinfo( $image_file->picture_file );
		@unlink( $otherpath['dirname'] . '/' . $otherpath['filename'] . '-150x200.' . $otherpath['extension'] );
	}
	
	$wpdb->query(  'DROP TABLE ' . $table_name );
}

/**
 *	HELPER FUNCTIONS
 */

function gw2chars_get_race( $id ) {
	switch ( $id ) {
		case 1:
			return( __( 'Asura', 'gw2chars' ) );
			break;
		case 2:
			return( __( 'Charr', 'gw2chars' ) );
			break;
		case 3:
			return( __( 'Human', 'gw2chars' ) );
			break;
		case 4:
			return( __( 'Norn', 'gw2chars' ) );
			break;
		case 5:
			return( __( 'Sylvari', 'gw2chars' ) );
			break;
	}
	return( __( 'unknown race', 'gw2chars' ) );
}


function gw2chars_get_profession( $id ) {
		switch ( $id ) {
			case 1:
				return( __( 'Guardian', 'gw2chars' ) );
				break;
			case 2:
				return( __( 'Warrior', 'gw2chars' ) );
				break;
			case 3:
				return( __( 'Engineer', 'gw2chars' ) );
				break;
			case 4:
				return( __( 'Ranger', 'gw2chars' ) );
				break;
			case 5:
				return( __( 'Thief', 'gw2chars' ) );
				break;
			case 6:
				return( __( 'Elementalist', 'gw2chars' ) );
				break;
			case 7:
				return( __( 'Mesmer', 'gw2chars' ) );
				break;
			case 8:
				return( __( 'Necromancer', 'gw2chars' ) );
				break;
		}
		return( __( 'unknown profession', 'gw2chars' ) );
}


function gw2chars_get_crafting_skill( $id ) {
	switch ( $id ) {
		case 0:
			return ( __( 'I don\'t craft', 'gw2chars' ) );
			break;
		case 1:
			return ( __( 'Armorsmith', 'gw2chars' ) );
			break;
		case 2:
			return ( __( 'Artificer', 'gw2chars' ) );
			break;
		case 3:
			return ( __( 'Chef', 'gw2chars' ) );
			break;
		case 4:
			return ( __( 'Huntsman', 'gw2chars' ) );
			break;
		case 5:
			return ( __( 'Jeweler', 'gw2chars' ) );
			break;
		case 6:
			return ( __( 'Leatherworker', 'gw2chars' ) );
			break;
		case 7:
			return ( __( 'Tailor', 'gw2chars' ) );
			break;
		case 8:
			return ( __( 'Weaponsmith', 'gw2chars' ) );
			break;
	}
	return ( __( 'unknown crafting skill', 'gw2chars' ) );
}


function gw2chars_get_crafting_skill_pic( $id ) {
	$pic = "";
	switch ( $id ) {
		case 1:
			$pic = 'armorsmith';
			break;
		case 2:
			$pic = 'artificer';
			break;
		case 3:
			$pic = 'chef';
			break;
		case 4:
			$pic = 'huntsman';
			break;
		case 5:
			$pic = 'jeweler';
			break;
		case 6:
			$pic = 'leatherworker';
			break;
		case 7:
			$pic = 'tailor';
			break;
		case 8:
			$pic = 'weaponsmith';
			break;
	}
	
	return ('<img src="' . plugin_dir_url( __FILE__ ) . 'pics/' . $pic . '.png" alt="' . gw2chars_get_crafting_skill( $id ) . '" />');
}

?>