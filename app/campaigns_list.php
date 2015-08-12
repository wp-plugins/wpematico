<?php 
// don't load directly 
if ( !defined('ABSPATH') ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

add_filter('manage_edit-wpematico_columns' , array( 'WPeMatico_Campaigns', 'set_edit_wpematico_columns'));
add_action('manage_wpematico_posts_custom_column',array('WPeMatico_Campaigns','custom_wpematico_column'),10,2);
add_filter('post_row_actions' , array( 'WPeMatico_Campaigns', 'wpematico_quick_actions'), 10, 2);
add_filter("manage_edit-wpematico_sortable_columns", array( 'WPeMatico_Campaigns', "sortable_columns") );
add_action('pre_get_posts', array( 'WPeMatico_Campaigns', 'column_orderby') );
add_filter('editable_slug', array('WPeMatico_Campaigns','inline_custom_fields'),999,1);
		//CUSTOM BULK & EDIT ACTIONS
//		add_action( 'save_post', array( 'WPeMatico_Campaigns', 'save_quick_edit_post') );
		//add_action( 'bulk_edit_custom_box', array( 'WPeMatico_Campaigns', 'wpematico_add_to_bulk_edit_custom_box'), 10, 2 );
		add_action( 'quick_edit_custom_box', array( 'WPeMatico_Campaigns', 'wpematico_add_to_quick_edit_custom_box'), 10, 2 );
		add_action( 'wp_ajax_manage_wpematico_save_bulk_edit', array( 'WPeMatico_Campaigns', 'manage_wpematico_save_bulk_edit') );
		add_action( 'wp_ajax_get_wpematico_categ_bulk_edit', array( 'WPeMatico_Campaigns', 'get_wpematico_categ_bulk_edit') );

if( strstr($_SERVER['REQUEST_URI'], 'wp-admin/edit.php?post_type=wpematico')  
  || strstr($_SERVER['REQUEST_URI'], 'wp-admin/admin.php?action=wpematico_') ) 
	add_action( 'init', array( 'WPeMatico_Campaigns', 'init' ) );
	else return;
 	
if ( class_exists( 'WPeMatico_Campaigns' ) ) return;
class WPeMatico_Campaigns {
	public static function init() {
		new self();
	}
	
	public function __construct( $hook_in = FALSE ) {
		$cfg = get_option( WPeMatico :: OPTION_KEY);
		$cfg = apply_filters('wpematico_check_options', $cfg);
		add_action('admin_print_styles-edit.php', array(&$this,'list_admin_styles'));
		add_action('admin_print_scripts-edit.php', array(&$this,'list_admin_scripts'));
		// Messages 
		add_filter( 'post_updated_messages', array( &$this , 'wpematico_updated_messages') );
		//LIST FILTER ACTIONS 
		add_filter('views_edit-wpematico', array( &$this, 'my_views_filter') );
		//QUICK ACTIONS
		add_action('admin_action_wpematico_copy_campaign', array( &$this, 'wpematico_copy_campaign'));
		add_action('admin_action_wpematico_toggle_campaign', array(&$this, 'wpematico_toggle_campaign'));
		if ( $cfg['enabledelhash'])    // Si está habilitado en settings, lo muestra 
			add_action('admin_action_wpematico_delhash_campaign', array(&$this, 'wpematico_delhash_campaign'));
		add_action('admin_action_wpematico_reset_campaign', array(&$this, 'wpematico_reset_campaign'));
		add_action('admin_action_wpematico_clear_campaign', array(&$this, 'wpematico_clear_campaign'));

	}

	public static function my_views_filter($links) {
		global $post_type;
		if($post_type != 'wpematico') return $links;		
		$links['wpematico'] = __('Visit', WPeMatico :: TEXTDOMAIN).' <a href="http://www.wpematico.com" target="_Blank" class="wpelinks">www.wpematico.com</a>';
		return $links;
	}
	
  	public static function list_admin_styles(){
		wp_enqueue_style('campaigns-list',WPeMatico :: $uri .'app/css/campaigns_list.css');
//		add_action('admin_head', array( &$this ,'campaigns_admin_head_style'));
	}
	public static function list_admin_scripts(){
		add_action('admin_head', array( __CLASS__ ,'campaigns_list_admin_head'));
//		wp_register_script('jquery-input-mask', 'js/jquery.maskedinput-1.2.2.js', array( 'jquery' ));
//		wp_enqueue_script('color-picker', 'js/colorpicker.js', array('jquery'));
		$slug = 'wpematico';
		// load only when editing a video
		if ( ( isset( $_GET['page'] ) && $_GET['page'] == $slug ) || ( isset( $_GET['post_type'] ) && $_GET['post_type'] == $slug ) ) {
			wp_enqueue_script( 'wpematico-bulk-quick-edit', WPeMatico :: $uri . 'app/js/bulk_quick_edit.js', array( 'jquery', 'inline-edit-post' ), '', true );
		}
	}

	public static function campaigns_list_admin_head() {
		global $post, $post_type;
		if($post_type != 'wpematico') return $post->ID;
			$runallbutton = '<div style="margin: 2px 5px 0 0;float:left;background-color: #EB9600;" id="run_all" onclick="javascript:run_all();" class="button-primary">'. __('Run Selected Campaigns', WPeMatico :: TEXTDOMAIN ) . '</div>';
		?>		
		<script type="text/javascript" language="javascript">
			jQuery(document).ready(function($){
				$('div.tablenav.top').prepend('<?php echo $runallbutton; ?>');
	            $('span:contains("<?php _e('Slug'); ?>")').each(function (i) {
					$(this).parent().hide();
				});
				$('span:contains("<?php _e('Password'); ?>")').each(function (i) {
					$(this).parent().parent().hide();
				});
				$('select[name="_status"]').each(function (i) {
					$(this).parent().parent().parent().parent().hide();
				});
				$('span:contains("<?php _e('Date'); ?>")').each(function (i) {
					$(this).parent().hide();
				});
				$('.inline-edit-date').each(function (i) {
					$(this).hide();
				});
				$('.inline-edit-col-left').append(	$('#optionscampaign').html() );
				$('#optionscampaign').remove();
			});
			
			function run_now(c_ID) {
				jQuery('html').css('cursor','wait');
				jQuery("div[id=fieldserror]").remove();
				msgdev="<p><img width='16' src='<?php echo get_bloginfo('wpurl'); ?>/wp-admin/images/wpspin_light.gif'> <span style='vertical-align: top;margin: 10px;'><?php _e('Running Campaign...', WPeMatico :: TEXTDOMAIN ); ?></span></p>";
				jQuery(".subsubsub").before('<div id="fieldserror" class="updated fade">'+msgdev+'</div>');
				var data = {
					campaign_ID: c_ID ,
					action: "wpematico_run"
				};
				jQuery.post(ajaxurl, data, function(msgdev) {  //si todo ok devuelve LOG sino 0
					jQuery('#fieldserror').remove();
					if( msgdev.substring(0, 5) == 'ERROR' ){
						jQuery(".subsubsub").before('<div id="fieldserror" class="error fade">'+msgdev+'</div>');
					}else{
						jQuery(".subsubsub").before('<div id="fieldserror" class="updated fade">'+msgdev+'</div>');
						var floor = Math.floor;
						var ret_posts = floor(jQuery("tr#post-"+c_ID+" > .count").html()) + floor(jQuery("#ret_lastposts").html());
						jQuery("tr#post-"+c_ID+" > .count").html( '<b style="background-color: #FBB;padding: 3px 5px;">'+ret_posts.toString()+'</b>' );
						jQuery("#lastruntime").html( "<b>"+jQuery("#ret_lastruntime").html()+"</b>");
					}
					jQuery('html').css('cursor','auto');
				});
			}
 			function run_all() {
				var selectedItems = new Array();
				jQuery("input[name='post[]']:checked").each(function() {selectedItems.push(jQuery(this).val());});
				if (selectedItems .length == 0) {alert("<?php _e('Please select campaign(s) to Run.', WPeMatico :: TEXTDOMAIN ); ?>"); return; }
				
				jQuery('html').css('cursor','wait');
				jQuery('#fieldserror').remove();
				msgdev="<p><img width='16' src='<?php echo get_bloginfo('wpurl'); ?>/wp-admin/images/wpspin_light.gif'> <span style='vertical-align: top;margin: 10px;'><?php _e('Running Campaign...', WPeMatico :: TEXTDOMAIN ); ?></span></p>";
				jQuery(".subsubsub").before('<div id="fieldserror" class="updated fade ajaxstop">'+msgdev+'</div>');
				jQuery("input[name='post[]']:checked").each(function() {
					c_id = jQuery(this).val();
					var data = {
						campaign_ID: c_id ,
						action: "wpematico_run"
					};
					jQuery.post(ajaxurl, data, function(msgdev) {  //si todo ok devuelve LOG sino 0
						if( msgdev.substring(0, 5) == 'ERROR' ){
							jQuery(".subsubsub").before('<div id="fieldserror" class="error fade">'+msgdev+'</div>');
						}else{
							jQuery(".subsubsub").before('<div id="fieldserror" class="updated fade">'+msgdev+'</div>');
						}
					});
				}).ajaxStop(function() {
					jQuery('html').css('cursor','auto');
					jQuery('.ajaxstop').remove().ajaxStop();
				});
			}
 		</script>
		<?php
	}

	/**
	 ************ACCION COPIAR 
	 */
	function copy_duplicate_campaign($post, $status = '', $parent_id = '') {
		if ($post->post_type != 'wpematico') return;
		$prefix = "";
		$suffix = __("(Copy)",  WPeMatico :: TEXTDOMAIN) ;
		if (!empty($prefix)) $prefix.= " ";
		if (!empty($suffix)) $suffix = " ".$suffix;
		$status = 'publish';

		$new_post = array(
		'menu_order' => $post->menu_order,
		'guid' => $post->guid,
		'comment_status' => $post->comment_status,
		'ping_status' => $post->ping_status,
		'pinged' => $post->pinged,
		'post_author' => @$post->author,
		'post_content' => $post->post_content,
		'post_excerpt' => $post->post_excerpt,
		'post_mime_type' => $post->post_mime_type,
		'post_parent' => $post->post_parent,
		'post_password' => $post->post_password,
		'post_status' => $status,
		'post_title' => $prefix.$post->post_title.$suffix,
		'post_type' => $post->post_type,
		'to_ping' => $post->to_ping, 
		'post_date' => $post->post_date,
		'post_date_gmt' => get_gmt_from_date($post->post_date)
		);	

		$new_post_id = wp_insert_post($new_post);

		$post_meta_keys = get_post_custom_keys($post->ID);
		if (!empty($post_meta_keys)) {
			foreach ($post_meta_keys as $meta_key) {
				$meta_values = get_post_custom_values($meta_key, $post->ID);
				foreach ($meta_values as $meta_value) {
					$meta_value = maybe_unserialize($meta_value);
					add_post_meta($new_post_id, $meta_key, $meta_value);
				}
			}
		}
		$campaign_data = WPeMatico :: get_campaign( $new_post_id );
		$campaign_data['activated'] = false;

		WPeMatico :: update_campaign( $new_post_id, $campaign_data );

		// If the copy is not a draft or a pending entry, we have to set a proper slug.
		/*if ($new_post_status != 'draft' || $new_post_status != 'auto-draft' || $new_post_status != 'pending' ){
			$post_name = wp_unique_post_slug($post->post_name, $new_post_id, $new_post_status, $post->post_type, $new_post_parent);

			$new_post = array();
			$new_post['ID'] = $new_post_id;
			$new_post['post_name'] = $post_name;

			// Update the post into the database
			wp_update_post( $new_post );
		} */

		return $new_post_id;
	}

	function wpematico_copy_campaign($status = ''){
		if (! ( isset( $_GET['post']) || isset( $_POST['post'])  || ( isset($_REQUEST['action']) && 'wpematico_copy_campaign' == $_REQUEST['action'] ) ) ) {
			wp_die(__('No campaign ID has been supplied!',  WPeMatico :: TEXTDOMAIN));
		}

		// Get the original post
		$id = (isset($_GET['post']) ? $_GET['post'] : $_POST['post']);
		$post = get_post($id);

		// Copy the post and insert it
		if (isset($post) && $post!=null) {
			$new_id = self :: copy_duplicate_campaign($post, $status);

			if ($status == ''){
				// Redirect to the post list screen
				wp_redirect( admin_url( 'edit.php?post_type='.$post->post_type) );
			} else {
				// Redirect to the edit screen for the new draft post
				wp_redirect( admin_url( 'post.php?action=edit&post=' . $new_id ) );
			}
			exit;

		} else {
			$post_type_obj = get_post_type_object( $post->post_type );
			wp_die(esc_attr(__('Copy campaign failed, could not find original:',  WPeMatico :: TEXTDOMAIN)) . ' ' . $id);
		}
	}

	/**
	************FIN ACCION COPIAR 
	*/

	/**
	************ACCION TOGGLE 
	*/
	function wpematico_toggle_campaign($status = ''){
		if (! ( isset( $_GET['post']) || isset( $_POST['post'])  || ( isset($_REQUEST['action']) && 'wpematico_toggle_campaign' == $_REQUEST['action'] ) ) ) {
			wp_die(__('No campaign ID has been supplied!',  WPeMatico :: TEXTDOMAIN));
		}
		// Get the original post
		$id = (isset($_GET['post']) ? $_GET['post'] : $_POST['post']);

		$campaign_data =   WPeMatico :: get_campaign( $id );
		$campaign_data['activated'] = !$campaign_data['activated'];
		WPeMatico :: update_campaign( $id, $campaign_data );
		
		$notice= ($campaign_data['activated']) ? __('Campaign activated',  WPeMatico :: TEXTDOMAIN) : __('Campaign Deactivated',  WPeMatico :: TEXTDOMAIN);
		WPeMatico::add_wp_notice( array('text' => $notice .' <b>'.  get_the_title($id).'</b>', 'below-h2'=>false ) );

		// Redirect to the post list screen
		wp_redirect( admin_url( 'edit.php?post_type=wpematico') );
	}

	/*********FIN ACCION TOGGLE 	*/
	
	/**	************ACCION RESET 	*/
	function wpematico_reset_campaign($status = ''){
		if (! ( isset( $_GET['post']) || isset( $_POST['post'])  || ( isset($_REQUEST['action']) && 'wpematico_reset_campaign' == $_REQUEST['action'] ) ) ) {
			wp_die(__('No campaign ID has been supplied!',  WPeMatico :: TEXTDOMAIN));
		}
		// Get the original post
		$id = (isset($_GET['post']) ? $_GET['post'] : $_POST['post']);
		$campaign_data =   WPeMatico :: get_campaign( $id );
		$campaign_data['postscount'] = 0;
		$campaign_data['lastpostscount'] = 0;
		WPeMatico :: update_campaign( $id, $campaign_data );
		delete_post_meta($id, 'last_campaign_log');

		WPeMatico::add_wp_notice( array('text' => __('Reset Campaign',  WPeMatico :: TEXTDOMAIN).' <b>'.  get_the_title($id).'</b>', 'below-h2'=>false ) );
		// Redirect to the post list screen
		wp_redirect( admin_url( 'edit.php?post_type=wpematico') );
	}

	/**************FIN ACCION RESET 	*/
	
	/**	************ACCION DELHASH	 	*/
	function wpematico_delhash_campaign(){
		if (! ( isset( $_GET['post']) || isset( $_POST['post'])  || ( isset($_REQUEST['action']) && 'wpematico_delhash_campaign' == $_REQUEST['action'] ) ) ) {
			wp_die(__('No campaign ID has been supplied!',  WPeMatico :: TEXTDOMAIN));
		}
		// Get the original post
		$id = (isset($_GET['post']) ? $_GET['post'] : $_POST['post']);
		$campaign_data =   WPeMatico :: get_campaign( $id );
		foreach($campaign_data['campaign_feeds'] as $feed) {    // Grabo el ultimo hash de cada feed con 0
			$campaign_data[$feed]['lasthash']="0"; 
		}
		WPeMatico :: update_campaign( $id, $campaign_data );
		WPeMatico::add_wp_notice( array('text' => __('Hash deleted on campaign',  WPeMatico :: TEXTDOMAIN).' <b>'.  get_the_title($id).'</b>', 'below-h2'=>false ) );

		// Redirect to the post list screen
		wp_redirect( admin_url( 'edit.php?post_type=wpematico') );
	}
	/**************FIN ACCION DELHASH	*/
	
	/**	************ACCION CLEAR: ABORT CAMPAIGN	 	*/
	function wpematico_clear_campaign(){
		if (! ( isset( $_GET['post']) || isset( $_POST['post'])  || ( isset($_REQUEST['action']) && 'wpematico_clear_campaign' == $_REQUEST['action'] ) ) ) {
			wp_die(__('No campaign ID has been supplied!',  WPeMatico :: TEXTDOMAIN));
		}

		// Get the original post
		$id = (isset($_GET['post']) ? $_GET['post'] : $_POST['post']);
		$campaign_data =   WPeMatico :: get_campaign( $id );

		$campaign_data['cronnextrun']= WPeMatico :: time_cron_next($campaign_data['cron']); //set next run
		$campaign_data['stoptime']   = current_time('timestamp');
		$campaign_data['lastrun']  	 = $campaign_data['starttime'];
		$campaign_data['lastruntime']= $campaign_data['stoptime']-$campaign_data['starttime'];
		$campaign_data['starttime']  = '';

		WPeMatico :: update_campaign( $id, $campaign_data );
		WPeMatico::add_wp_notice( array('text' => __('Campaign cleared',  WPeMatico :: TEXTDOMAIN).' <b>'.  get_the_title($id).'</b>', 'below-h2'=>false ) );

		// Redirect to the post list screen
		wp_redirect( admin_url( 'edit.php?post_type=wpematico') );
	}
	/**************FIN ACCION DELHASH	*/
	
	public static function wpematico_action_link( $id = 0, $context = 'display', $actionslug ) {
		global $post;
		if ( !$post == get_post( $id ) ) return;
		switch ($actionslug){ 
		case 'copy':
			$action_name = "wpematico_copy_campaign";
			break;
		case 'toggle':
			$action_name = "wpematico_toggle_campaign";
			break;
		case 'reset':
			$action_name = "wpematico_reset_campaign";
			break;
		case 'delhash':
			$action_name = "wpematico_delhash_campaign";
			break;
		case 'clear':
			$action_name = "wpematico_clear_campaign";
			break;			
		}
		if ( 'display' == $context ) 
			$action = '?action='.$action_name.'&amp;post='.$post->ID;
		else 
			$action = '?action='.$action_name.'&post='.$post->ID;
			
		$post_type_object = get_post_type_object( $post->post_type );
		if ( !$post_type_object )	return;
		
		return apply_filters( 'wpematico_action_link', admin_url( "admin.php". $action ), $post->ID, $context );
	}

	//change actions from custom post type list
	static function wpematico_quick_actions( $actions ) {
		global $post;
		if( $post->post_type == 'wpematico' ) {
			$can_edit_post = current_user_can( 'edit_post', $post->ID );
			$cfg = get_option( WPeMatico :: OPTION_KEY);
//	//		unset( $actions['edit'] );
//			unset( $actions['view'] );
//	//		unset( $actions['trash'] );
//	//		unset( $actions['inline hide-if-no-js'] );
//			unset( $actions['clone'] );
//			unset( $actions['edit_as_new_draft'] );
			$actions = array();
			if ( $can_edit_post && 'trash' != $post->post_status ) {
				$actions['edit'] = '<a href="' . get_edit_post_link( $post->ID, true ) . '" title="' . esc_attr( __( 'Edit this item' ) ) . '">' . __( 'Edit' ) . '</a>';
				$actions['inline hide-if-no-js'] = '<a href="#" class="editinline" title="' . esc_attr( __( 'Edit this item inline' ) ) . '">' . __( 'Quick&nbsp;Edit' ) . '</a>';
			}
			if ( current_user_can( 'delete_post', $post->ID ) ) {
				if ( 'trash' == $post->post_status )
					$actions['untrash'] = "<a title='" . esc_attr( __( 'Restore this item from the Trash' ) ) . "' href='" . wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $post->ID ) ), 'untrash-post_' . $post->ID ) . "'>" . __( 'Restore' ) . "</a>";
				elseif ( EMPTY_TRASH_DAYS )
					$actions['trash'] = "<a class='submitdelete' title='" . esc_attr( __( 'Move this item to the Trash' ) ) . "' href='" . get_delete_post_link( $post->ID ) . "'>" . __( 'Trash' ) . "</a>";
				if ( 'trash' == $post->post_status || !EMPTY_TRASH_DAYS )
					$actions['delete'] = "<a class='submitdelete' title='" . esc_attr( __( 'Delete this item permanently' ) ) . "' href='" . get_delete_post_link( $post->ID, '', true ) . "'>" . __( 'Delete Permanently' ) . "</a>";
			}
			if ( 'trash' != $post->post_status ) {
				//++++++Toggle
				$campaign_data = WPeMatico :: get_campaign( $post->ID );
				$starttime = @$campaign_data['starttime']; 
				if (empty($starttime)) {
					$acnow = (bool)$campaign_data['activated'];
					$atitle = ( $acnow ) ? esc_attr(__("Deactivate this campaign", WPeMatico :: TEXTDOMAIN)) : esc_attr(__("Activate schedule", WPeMatico :: TEXTDOMAIN));
					$alink = ($acnow) ? __("Deactivate", WPeMatico :: TEXTDOMAIN): __("Activate",WPeMatico :: TEXTDOMAIN);
					$actions['toggle'] = '<a href="'.self :: wpematico_action_link( $post->ID , 'display','toggle').'" title="' . $atitle . '">' .  $alink . '</a>';
					//++++++Copy
					$actions['copy'] = '<a href="'.self :: wpematico_action_link( $post->ID , 'display','copy').'" title="' . esc_attr(__("Clone this item", WPeMatico :: TEXTDOMAIN)) . '">' .  __('Copy', WPeMatico :: TEXTDOMAIN) . '</a>';
					//++++++Reset
					$actions['reset'] = '<a href="'.self :: wpematico_action_link( $post->ID , 'display','reset').'" title="' . esc_attr(__("Reset post count", WPeMatico :: TEXTDOMAIN)) . '">' .  __('Reset', WPeMatico :: TEXTDOMAIN) . '</a>';
					//++++++runnow
					$actions['runnow'] = '<a href="JavaScript:run_now(' . $post->ID . ');" title="' . esc_attr(__("Run Now this campaign", WPeMatico :: TEXTDOMAIN)) . '">' .  __('Run Now', WPeMatico :: TEXTDOMAIN) . '</a>';
					//++++++delhash
					if ( @$cfg['enabledelhash'])    // Si está habilitado en settings, lo muestra 
						$actions['delhash'] = '<a href="'.self :: wpematico_action_link( $post->ID , 'display','delhash').'" title="' . esc_attr(__("Delete hash code for duplicates", WPeMatico :: TEXTDOMAIN)) . '">' .  __('Del Hash', WPeMatico :: TEXTDOMAIN) . '</a>';
					//++++++seelog
					if ( @$cfg['enableseelog']) {   // Si está habilitado en settings, lo muestra 
						$nonce= wp_create_nonce  ('clog-nonce');
						$nombre = get_the_title($post->ID);
						$actionurl = WPeMatico :: $uri . 'app/campaign_log.php?p='.$post->ID.'&_wpnonce=' . $nonce;
						$actionjs = "javascript:window.open('$actionurl','$nombre','toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=700, height=600');";

						$actions['seelog'] = '<a href="#" onclick="'.$actionjs.' return false;" title="' . esc_attr(__("See last log of campaign. (Open a PopUp window)", WPeMatico :: TEXTDOMAIN)) . '">' . __('See Log', WPeMatico :: TEXTDOMAIN) . '</a>';
					}
				} else {  // Está en ejecución o quedó a la mitad
					unset( $actions['edit'] );
					$actions['clear'] = '<a href="'.self :: wpematico_action_link( $post->ID , 'display','clear').'" title="' . esc_attr(__("Clear fetching and restore campaign", WPeMatico :: TEXTDOMAIN)) . '">' .  __('Clear campaign', WPeMatico :: TEXTDOMAIN) . '</a>';
				}
			}
		}
		return $actions;
	}


	static function wpematico_updated_messages( $messages ) {
	  global $post, $post_ID;
	  $messages['wpematico'] = array(
		0 => '', // Unused. Messages start at index 1.
		1 => sprintf( __('Campaign updated.', WPeMatico :: TEXTDOMAIN)),
		2 => __('Custom field updated.', WPeMatico :: TEXTDOMAIN) ,
		3 => __('Custom field deleted.', WPeMatico :: TEXTDOMAIN),
		4 => __('Campaign updated.', WPeMatico :: TEXTDOMAIN),
		/* translators: %s: date and time of the revision */
		5 => isset($_GET['revision']) ? sprintf( __('Campaign restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => sprintf( __('Campaign published.', WPeMatico :: TEXTDOMAIN)),
		7 => __('Campaign saved.'),
		8 => sprintf( __('Campaign submitted.', WPeMatico :: TEXTDOMAIN)),
		9 => sprintf( __('Campaign scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview campaign</a>'),
		  // translators: Publish box date format, see http://php.net/date
		  date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
		10 => sprintf( __('Campaign draft updated. <a target="_blank" href="%s">Preview campaign</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
	  );

	  return $messages;
	}


	static function set_edit_wpematico_columns($columns) { //this function display the columns headings
		return array(
			'cb' => '<input type="checkbox" />',
			'title' => __('Campaign Name', WPeMatico :: TEXTDOMAIN),
			'status' => __('Publish as', WPeMatico :: TEXTDOMAIN),
			'count' => __('Posts', WPeMatico :: TEXTDOMAIN),
			'next' => __('Next Run', WPeMatico :: TEXTDOMAIN),
			'last' =>__('Last Run', WPeMatico :: TEXTDOMAIN)
		);
	}
	
	static function inline_custom_fields( $text ) {
		global $post, $pagenow;
		if(	   ($pagenow=='edit.php' && isset($_GET['post_type']) && $_GET['post_type']=='wpematico' )
			|| ($pagenow=='admin-ajax.php' && isset($post) && $post->post_type=='wpematico' )) {
			$campaign_data = WPeMatico :: get_campaign ( $post->ID );
			/* Custom inline data for wpematico */
			$campaign_max = $campaign_data['campaign_max'];
			$campaign_feeddate = $campaign_data['campaign_feeddate'];
			$campaign_author = $campaign_data['campaign_author'];
			$campaign_linktosource = $campaign_data['campaign_linktosource'];
			$campaign_commentstatus = $campaign_data['campaign_commentstatus'];
			$campaign_allowpings = $campaign_data['campaign_allowpings'];
			$campaign_woutfilter = $campaign_data['campaign_woutfilter'];
			$campaign_strip_links = $campaign_data['campaign_strip_links'];
			$campaign_customposttype = $campaign_data['campaign_customposttype'];
			$campaign_posttype = $campaign_data['campaign_posttype'];
			$campaign_post_format = (isset($campaign_data['campaign_post_format']) && !empty($campaign_data['campaign_post_format']) ) ? $campaign_data['campaign_post_format'] : '';
			$campaign_categories = $campaign_data['campaign_categories'];
			$campaign_tags = @$campaign_data['campaign_tags'];

			$text .= '</div>
					<div class="post_id">' . $post->ID . '</div>
					<div class="campaign_max">' . $campaign_max . '</div>
					<div class="campaign_feeddate">' . $campaign_feeddate . '</div>
					<div class="campaign_author">' . $campaign_author . '</div>
					<div class="campaign_linktosource">' . $campaign_linktosource . '</div>
					<div class="campaign_commentstatus">' . $campaign_commentstatus . '</div>
					<div class="campaign_allowpings">' . $campaign_allowpings . '</div>
					<div class="campaign_woutfilter">' . $campaign_woutfilter . '</div>
					<div class="campaign_strip_links">' . $campaign_strip_links . '</div>
					<div class="campaign_customposttype">' . $campaign_customposttype . '</div>
					<div class="campaign_posttype">' . $campaign_posttype . '</div>
					<div class="campaign_post_format">' . $campaign_post_format . '</div>
					<div class="campaign_categories">' . implode(',',$campaign_categories) . '</div>
					<div class="campaign_tags">' .  stripslashes($campaign_tags);
		}
		return $text;
	}
	
	static function custom_wpematico_column( $column, $post_id ) {
		global $post;
		$cfg = get_option( WPeMatico :: OPTION_KEY);
		$campaign_data = WPeMatico :: get_campaign ( $post_id );
		switch ( $column ) {
		  case 'aaaaaaaaaa_name':
			
//			$taxonomy_names = get_object_taxonomies( $campaign_customposttype );
//			foreach ( $taxonomy_names as $taxonomy_name) {
//				$taxonomy = get_taxonomy( $taxonomy_name );
//
//				if ( $taxonomy->hierarchical && $taxonomy->show_ui ) {
//
//					$terms = get_object_term_cache( $post->ID, $taxonomy_name );
//					if ( false === $terms ) {
//						$terms = wp_get_object_terms( $post->ID, $taxonomy_name );
//						wp_cache_add( $post->ID, $terms, $taxonomy_name . '_relationships' );
//					}
//					$term_ids = empty( $terms ) ? array() : wp_list_pluck( $terms, 'term_id' );
//
//					echo '<div class="post_category" id="' . $taxonomy_name . '_' . $post->ID . '">' . implode( ',', $campaign_categories ) . '</div>';
//
//				} elseif ( $taxonomy->show_ui ) {
//
//					echo '<div class="tags_input" id="'.$taxonomy_name.'_'.$post->ID.'">'
//						. esc_html( str_replace( ',', ', ', get_terms_to_edit( $post->ID, $taxonomy_name ) ) ) . '</div>';
//
//				}
//			}
			
			break;
		  case 'status':
			echo '<div id="campaign_posttype-' . $post_id . '" value="' . $campaign_data['campaign_posttype'] . '">' . $campaign_data['campaign_posttype'] . '</div>'; 
			break;
		  case 'count':
			$postscount = get_post_meta($post_id, 'postscount', true);
			echo (isset($postscount) && !empty($postscount) ) ? $postscount : $campaign_data['postscount']; 
			break;
		  case 'next':
			$starttime = (isset($campaign_data['starttime']) && !empty($campaign_data['starttime']) ) ? $campaign_data['starttime'] : 0 ; 
			//print_r($campaign_data);
			$activated = $campaign_data['activated']; 
			if ($starttime>0) {
				$runtime=current_time('timestamp')-$starttime;
				// Aca agregar control de tiempo y sacarla de en ejecucion ***********************************************************************
				if(($cfg['campaign_timeout'] <= $runtime) && ($cfg['campaign_timeout']>0)) {
					$campaign_data['lastrun'] = $starttime;
					$campaign_data['lastruntime'] = ' <span style="color:red;">Timeout: '.$cfg['campaign_timeout'].'</span>';
					$campaign_data['starttime']   = '';
					$campaign_data['lastpostscount'] = 0; //  posts procesados esta vez
					WPeMatico :: update_campaign($post_id, $campaign_data);  //Save Campaign new data
				}
				echo __('Running since:', WPeMatico :: TEXTDOMAIN ).' '.$runtime.' '.__('sec.', WPeMatico :: TEXTDOMAIN );
			} elseif ($activated) {
				//$campaign_data['cronnextrun']= WPeMatico :: time_cron_next($campaign_data['cron']); //set next run, ver por que no actualizae el cron
				$cronnextrun = get_post_meta($post_id, 'cronnextrun', true);
				$cronnextrun = (isset($cronnextrun) && !empty($cronnextrun) && ($cronnextrun > 0 ) ) ? $cronnextrun : $campaign_data['cronnextrun']; 
				echo date_i18n( get_option('date_format').' '. get_option('time_format'), $cronnextrun );
			} else {
				echo __('Inactive', WPeMatico :: TEXTDOMAIN );
			}
			break;
		  case 'last':
			$lastrun = get_post_meta($post_id, 'lastrun', true);
			$lastrun = (isset($lastrun) && !empty($lastrun) ) ? $lastrun :  $campaign_data['lastrun']; 
			$lastruntime = (isset($campaign_data['lastruntime']) && !empty($campaign_data['lastruntime']) ) ? $campaign_data['lastruntime'] : ''; 
			if ($lastrun) {
				echo date_i18n( get_option('date_format').' '. get_option('time_format'), $lastrun );
				if(isset($lastruntime) && !empty($lastruntime) ) {
					echo '<br />'.__('Runtime:', WPeMatico :: TEXTDOMAIN ).' <span id="lastruntime">'.$lastruntime.'</span> '.__('sec.', WPeMatico :: TEXTDOMAIN );
				}
			} else {
				echo __('None', WPeMatico :: TEXTDOMAIN );
			}
			break;
		}
	}

	// Make these columns sortable
	static function sortable_columns() {
	  return array(
		'title'      => 'title',
		'status' => 'Status',
		'count'     => 'count',
		'next'     => 'next',
		'last'     => 'last'
	  );
	}
	
	public static function column_orderby($query ) {
		global $pagenow, $post_type;
		$orderby = $query->get( 'orderby');
		if( 'edit.php' != $pagenow || empty( $orderby ) || $post_type != 'wpematico' ) 	return;
		switch($orderby) {
			case 'count':
				$meta_group = array('key' => 'postscount','type' => 'numeric');
				$query->set( 'meta_query', array( 'sort_column'=>'count', $meta_group ) );
				$query->set( 'meta_key','postscount' );
				$query->set( 'orderby','meta_value_num' );

				break;
			case 'next':
				$meta_group = array('key' => 'cronnextrun','type' => 'numeric');
				$query->set( 'meta_query', array( 'sort_column'=>'next', $meta_group ) );
				$query->set( 'meta_key','cronnextrun' );
				$query->set( 'orderby','meta_value_num' );

				break;
			case 'last':
				$meta_group = array('key' => 'lastrun','type' => 'numeric');
				$query->set( 'meta_query', array( 'sort_column'=>'last', $meta_group ) );
				$query->set( 'meta_key','lastrun' );
				$query->set( 'orderby','meta_value_num' );

				break;

			default:
				break;
		}
	} 
	
	static function get_wpematico_categ_bulk_edit( $post_id, $post_type ) {
		$post_id = ( isset( $_POST[ 'post_id' ] ) && !empty( $_POST[ 'post_id' ] ) ) ? $_POST[ 'post_id' ] : $post_id;
		$post_type = ( isset( $_POST[ 'campaign_posttype' ] ) && !empty( $_POST[ 'campaign_posttype' ] ) ) ? $_POST[ 'campaign_posttype' ] : $post_type;
	}
	
	
	public static function wpematico_add_to_bulk_edit_custom_box( $column_name, $post_type ) {
	}
	
	public static function wpematico_add_to_quick_edit_custom_box( $column_name, $post_type ) {
		
		$post = get_default_post_to_edit( $post_type );
		$post_type_object = get_post_type_object( 'post' );

		$taxonomy_names = get_object_taxonomies( 'post' );
		$hierarchical_taxonomies = array();
		$flat_taxonomies = array();
		foreach ( $taxonomy_names as $taxonomy_name ) {
			$taxonomy = get_taxonomy( $taxonomy_name );
			if ( !$taxonomy->show_ui )
				continue;

			if ( $taxonomy->hierarchical )
				$hierarchical_taxonomies[] = $taxonomy;
			else
				$flat_taxonomies[] = $taxonomy;
		}

		switch ( $post_type ) {
		case 'wpematico':
			switch( $column_name ) {
            case 'status':			
				    static $printNonce = TRUE;
					if ( $printNonce ) {
						$printNonce = FALSE;
						wp_nonce_field( plugin_basename( __FILE__ ), 'wpematico_edit_nonce' );
					}

					?>
				<fieldset class="" id="optionscampaign" style="display:none;">
					<div class="inline-edit-col">
					<h4><?php _e('Campaign Options', WPeMatico :: TEXTDOMAIN ); ?></h4>
						<div class="inline-edit-group">
						<label class="alignleft">
							<span class="field-title"><?php _e('Max items to create on each fetch:', WPeMatico :: TEXTDOMAIN ); ?></span>
							<span class="input-text">
								<input type="number" min="0" size="3" name="campaign_max" class="campaign_max small-text" value="">
							</span>
						</label>
						<label class="alignleft">
							<input type="checkbox" name="campaign_feeddate" value="1">
							<span class="checkbox-title"><?php _e('Use feed date', WPeMatico :: TEXTDOMAIN ); ?></span>
						</label> 
						</div>
						<div class="inline-edit-group">						
						<label class="alignleft inline-edit-col">
							<span class="authortitle"><?php _e( 'Author:', WPeMatico :: TEXTDOMAIN ); ?></span>
							<span class="input-text">
								<?php wp_dropdown_users(array('name' => 'campaign_author' ) ); ?>
							</span>
						</label>
						<label class="alignleft inline-edit-col">
							<span class="commenttitle"><?php _e( 'Discussion options:', WPeMatico :: TEXTDOMAIN ); ?></span>
							<span class="input-text">
							<select class="campaign_commentstatus" name="campaign_commentstatus">
							<?php
								$options = array(
									'open' => __('Open', WPeMatico :: TEXTDOMAIN),
									'closed' => __('Closed', WPeMatico :: TEXTDOMAIN),
									'registered_only' => __('Registered only', WPeMatico :: TEXTDOMAIN)
								);
								foreach($options as $key => $value) {
									echo '<option value="' . esc_attr($key) . '">' . $value . '</option>';
								}
							?>
							</select>
							</span>
						</label>
							
						</div>
						<div class="inline-edit-group">
						<label class="alignleft">
							<input type="checkbox" name="campaign_allowpings" value="1">
							<span class="checkbox-title"><?php _e( 'Allow pings?', WPeMatico :: TEXTDOMAIN ); ?>&nbsp;</span>
						</label>
						<label class="alignleft">
							<input type="checkbox" name="campaign_linktosource" value="1">
							<span class="checkbox-title"><?php _e( 'Post title links to source?', WPeMatico :: TEXTDOMAIN ); ?>&nbsp;&nbsp;</span>
						</label>
						<label class="alignleft">
							<input type="checkbox" name="campaign_strip_links" value="1">
							<span class="checkbox-title"><?php _e( 'Strip links from content', WPeMatico :: TEXTDOMAIN ); ?></span>
						</label>
						<br class="clear" />
						</div>
					</div>
				</fieldset>	
				<?php if ( count( $hierarchical_taxonomies ) ) : ?>
					
				<fieldset class="inline-edit-col-center inline-edit-categories"><div class="inline-edit-col">
					<?php foreach ( $hierarchical_taxonomies as $taxonomy ) : ?>

					<span class="title inline-edit-categories-label"><?php echo esc_html( $taxonomy->labels->name ) ?></span>
					<input type="hidden" name="<?php echo ( $taxonomy->name == 'category' ) ? 'post_category[]' : 'tax_input[' . esc_attr( $taxonomy->name ) . '][]'; ?>" value="0" />
					<ul class="cat-checklist <?php echo esc_attr( $taxonomy->name )?>-checklist">
						<?php wp_terms_checklist( null, array( 'taxonomy' => $taxonomy->name ) ) ?>
					</ul>

					<?php endforeach; //$hierarchical_taxonomies as $taxonomy ?>
					</div>
					
				</fieldset>
				<?php endif; // count( $hierarchical_taxonomies ) && !$bulk ?>
				
				<?php
					if ( count( $flat_taxonomies ) ) : ?>
					<fieldset class="inline-edit-col-right">
						<div class="inline-edit-col">
					<?php foreach ( $flat_taxonomies as $taxonomy ) : ?>
						<?php if ( current_user_can( $taxonomy->cap->assign_terms ) ) : ?>
							<label class="inline-edit-tags">
								<span class="title"><?php echo esc_html( $taxonomy->labels->name ) ?></span>
								<textarea cols="22" rows="1" name="campaign_tags" class="tax_input_<?php echo esc_attr( $taxonomy->name )?>"></textarea>
							</label>
						<?php endif; ?>
					<?php endforeach; //$flat_taxonomies as $taxonomy ?>
					
					<?php endif; // count( $flat_taxonomies ) && !$bulk  ?>

					
						<div class="inline-edit-radiosbox">
							<label>
								<span class="title"><?php _e('Post type',  WPeMatico :: TEXTDOMAIN ); ?></span>
								<br/>
								<span class="input-text"> <?php
									$args=array(
									  'public'   => true
									); 
									$output = 'names'; // names or objects, note names is the default
									$operator = 'and'; // 'and' or 'or'
									$post_types=get_post_types($args,$output,$operator); 
									foreach ($post_types  as $posttype ) {
										if ($posttype == 'wpematico') continue;
										echo '<label><input type="radio" name="campaign_customposttype" value="'. $posttype. '" id="customtype_'. $posttype. '" /> '. $posttype. '</label>';
									} ?>
								</span>
							</label>
						</div>
						<div class="inline-edit-radiosbox">
							<label>
								<span class="title"><?php _e('Status',  WPeMatico :: TEXTDOMAIN ); ?></span>
								<br/>
								<span class="input-text">
									<label><input type="radio" name="campaign_posttype" value="publish" /> <?php _e('Published'); ?></label>
									<label><input type="radio" name="campaign_posttype" value="private" /> <?php _e('Private'); ?></label>
									<label><input type="radio" name="campaign_posttype" value="pending" /> <?php _e('Pending'); ?></label>
									<label><input type="radio" name="campaign_posttype" value="draft" /> <?php _e('Draft'); ?></label>
								</span>
							</label>
						</div>
					<?php if ( current_theme_supports( 'post-formats' ) ) :
							$post_formats = get_theme_support( 'post-formats' );
							?>
						<div class="inline-edit-radiosbox qedscroll">
							<label>
								<span class="title" style="width: 100%;"><?php _e('Post Format',  WPeMatico :: TEXTDOMAIN ); ?></span>
								<br/>
								<span class="input-text"> <?php
									if ( is_array( $post_formats[0] ) ) :
										global $post, $campaign_data;
										$campaign_post_format = ( @!$campaign_post_format )? '0' : $campaign_data['campaign_post_format'];
									?>
									<div id="post-formats-select">
										<label><input type="radio" name="campaign_post_format" class="post-format" id="post-format-0" value="0" /> <?php echo get_post_format_string( 'standard' ); ?></label>
										<?php foreach ( $post_formats[0] as $format ) : ?>
											<label><input type="radio" name="campaign_post_format" class="post-format" id="post-format-<?php echo esc_attr( $format ); ?>" value="<?php echo esc_attr( $format ); ?>" /> <?php echo esc_html( get_post_format_string( $format ) ); ?></label>
										<?php endforeach; ?>
									</div>
									<?php endif; ?>
								</span>
							</label>
						</div>
					<?php endif; ?>
					</div>
					</fieldset><?php				
				break;

			case 'title': // No entra en title		
				break;
            case 'others':
/*               ?><fieldset class="inline-edit-col-right">
                  <div class="inline-edit-col">
                     <label>
                        <span class="title">Release Date</span>
                        <input type="text" name="next" value="" />
                     </label>
                  </div>
               </fieldset><?php
*/               break;
			}
			break;  //		case 'wpematico'

		}
	}

	
	static function save_quick_edit_post($post_id) {
		//wp_die('save_quick_edit_post'.print_r($_POST,1));
	    $slug = 'wpematico';
		if ( !isset($_POST['post_type']) || ( $slug !== $_POST['post_type'] ) ) return $post_id; 
		if ( !current_user_can( 'edit_post', $post_id ) ) 	return $post_id;
		$_POST += array("{$slug}_edit_nonce" => '');
		if ( !wp_verify_nonce( $_POST["{$slug}_edit_nonce"],  plugin_basename( __FILE__ ) ) ) {	wp_die('No verify nonce' /* .print_r($_POST,1) */ ); return;	}

		$nivelerror = error_reporting(E_ERROR | E_WARNING | E_PARSE);

		$campaign = WPeMatico :: get_campaign ($post_id);
		$posdata  = $_POST; //apply_filters('wpematico_check_campaigndata', $_POST );
		
		$campaign = array_merge($campaign, $posdata);
		
		$campaign = apply_filters('wpematico_check_campaigndata', $campaign );

		error_reporting($nivelerror);
		
		WPeMatico :: update_campaign($post_id, $campaign);
		
		return $post_id ;	
	}
	
	
	
	/**
	 * Saving the 'Bulk Edit' data is a little trickier because we have
	 * to get JavaScript involved. WordPress saves their bulk edit data
	 * via AJAX so, guess what, so do we.
	 *
	 * Your javascript will run an AJAX function to save your data.
	 * This is the WordPress AJAX function that will handle and save your data.
	 */
	function manage_wpematico_save_bulk_edit() {
		wp_die('manage_wpematico_save_bulk_edit');
		//if (wp_verify_nonce($_POST['_inline_edit'], 'inlineeditnonce')) return;
		// we need the post IDs
		$post_ids = ( isset( $_POST[ 'post_ids' ] ) && !empty( $_POST[ 'post_ids' ] ) ) ? $_POST[ 'post_ids' ] : NULL;		
		// if we have post IDs
		if ( ! empty( $post_ids ) && is_array( $post_ids ) ) {	
			// get the custom fields
			$custom_fields = array( 'campaign_max', 'campaign_customposttype', 'campaign_post_format', 'campaign_tags' );		
			foreach( $custom_fields as $field ) {			
				// if it has a value, doesn't update if empty on bulk
				if ( isset( $_POST[ $field ] ) && !empty( $_POST[ $field ] ) ) {			
					// update for each post ID
					foreach( $post_ids as $post_id ) {
						update_post_meta( $post_id, $field, $_POST[ $field ] );
					}				
				}			
			}		
		}
	}
	
}  // class
?>