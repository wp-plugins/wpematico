<?php
/*
 Plugin Name: WPeMatico
 Plugin URI: http://www.wpematico.com
 Description: Enables administrators to create posts automatically from RSS/Atom feeds with multiples filters.  If you like it, please rate it 5 stars.
 Version: 1.2
 Author: etruel <esteban@netmdp.com>
 Author URI: http://www.netmdp.com
 */
# @charset utf-8
if ( ! function_exists( 'add_filter' ) )
	exit;

if (is_admin()) {
if(file_exists('app/nonstatic.php'))
	include_once('app/nonstatic.php');
	include_once('app/campaigns_list.php');
	include_once("app/campaign_edit_functions.php");
	include_once('app/campaigns_edit.php');
	include_once( ABSPATH . basename(admin_url()) . '/includes/plugin.php' );
}
include_once('app/wpematico_functions.php');
add_action( 'init', array( 'WPeMatico', 'init' ) );

$cfg = get_option( 'WPeMatico_Options' );
$cfg = apply_filters('wpematico_check_options',$cfg );
//Disable WP_Cron
if( isset($cfg['dontruncron']) && $cfg['dontruncron'] ) {
	define('DISABLE_WP_CRON',true);
	if( $time=wp_next_scheduled('wpematico_cron') ) {
		//wp_unschedule_event($time,'wpematico_cron',array('ID'=>$post->ID));
		wp_clear_scheduled_hook('wpematico_cron');
	}
}else{
	add_filter('cron_schedules', array( 'WPeMatico', 'wpematico_intervals' ) ); //add cron intervals
	add_action('wpematico_cron', array( 'WPeMatico', 'wpem_cron_callback' ) );  //Actions for Cron job
}

//add_action('admin_init', array( 'WPeMatico_Campaign_edit' ,'disable_autosave' ));
load_plugin_textdomain( 'WPeMatico', FALSE, dirname( plugin_basename( __FILE__ ) ) . '/lang' );

register_activation_hook( plugin_basename( __FILE__ ), array( 'WPeMatico', 'activate' ) );
register_deactivation_hook( plugin_basename( __FILE__ ), array( 'WPeMatico', 'deactivate' ) );
register_uninstall_hook( plugin_basename( __FILE__ ), array( 'WPeMatico', 'uninstall' ) );

if ( !class_exists( 'WPeMatico' ) ) {
	class WPeMatico extends WPeMatico_functions {
		const TEXTDOMAIN = 'wpematico';
		const PROREQUIRED = '1.2';
//		const RELEASE = '1';
		const OPTION_KEY = 'WPeMatico_Options';
		public static $name = '';
		public static $version = '';
		public static $basen;		/** Plugin basename * @var string	 */
		public static $uri = '';
		public static $dir = '';		/** filesystem path to the plugin with trailing slash */

		public $options = array();
		public static function init() {
			if(is_admin()) $plugin_data = get_plugin_data( __FILE__ );
			@self :: $name = $plugin_data['Name'];
			@self :: $version = $plugin_data['Version'];
			self :: $uri = plugin_dir_url( __FILE__ );
			self :: $dir = plugin_dir_path( __FILE__ );
			self :: $basen = plugin_basename(__FILE__);
			
			new self( TRUE );
		}
		
		/**
		 * constructor
		 *
		 * @access public
		 * @param bool $hook_in
		 * @return void
		 */
		public function __construct( $hook_in = FALSE ) {
			//Admin message
			//add_action('admin_notices', array( &$this, 'wpematico_admin_notice' ) ); 
			if ( ! $this->wpematico_env_checks() )
				return;
			$this->load_options();

			if($this->options['nonstatic'] && !class_exists( 'NoNStatic' )){
				$this->options['nonstatic'] = false; 
				$this->options['mailpass'] = base64_decode($this->options['mailpass']); // encode again when save
				$this->update_options();
			}
			
			$this->Create_campaigns_page();
			if ( $hook_in ) {
				add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
				add_action( 'the_permalink', array( &$this, 'wpematico_permalink' ) );

				wp_register_style( 'WPematStylesheet', self :: $uri .'app/css/wpemat_styles.css' );
				wp_register_script( 'WPemattiptip', self :: $uri .'app/js/jquery.tipTip.minified.js','jQuery' );
				wp_register_style( 'oplugincss',  self :: $uri .'app/css/oplugins.css');
				wp_register_script( 'opluginjs',  self :: $uri .'app/js/oplugins.js');

				//Additional links on the plugin page
				add_filter(	'plugin_row_meta',	array(	__CLASS__, 'init_row_meta'),10,2);
				add_filter(	'plugin_action_links_' . self :: $basen, array( __CLASS__,'init_action_links'));
				
				add_filter(	'wpematico_check_campaigndata', array( __CLASS__,'check_campaigndata'),10,1);
				add_filter(	'wpematico_check_options', array( __CLASS__,'check_options'),10,1);
								
				//add Dashboard widget
				if (!$this->options['disabledashboard']){
					global $current_user;      
					get_currentuserinfo();	
					$user_object = new WP_User($current_user->ID);
					$roles = $user_object->roles;
					$display = false;
					if (!is_array($this->options['roles_widget'])) $this->options['roles_widget']= array( "administrator" => "administrator" );
					foreach( $roles as $cur_role ) {
						if ( array_search($cur_role, $this->options['roles_widget']) ) {
							$display = true;
						}
					}	
					if ( $current_user->ID && ( $display == true ) ) {	
						add_action('wp_dashboard_setup', array( &$this, 'wpematico_add_dashboard'));
					}
				}
			}
			//test if cron active
			if( wp_next_scheduled('wpematico_cron') === false ) {
				wp_schedule_event(0, 'wpematico_int', 'wpematico_cron');
			}
			//add Empty Trash folder buttons
			if ($this->options['emptytrashbutton']){
				// Add button to list table for all post types
				add_action( 'restrict_manage_posts', array( &$this, 'add_button' ), 90 );
			}
			//Check timeout of running campaigns
			if ($this->options['campaign_timeout'] > 0 ) {
				$args = array( 'post_type' => 'wpematico', 'orderby' => 'ID', 'order' => 'ASC', 'numberposts' => -1 );
				$campaigns = get_posts( $args );
				foreach( $campaigns as $post ) {
					$campaign = $this->get_campaign( $post->ID );
					$starttime = @$campaign['starttime']; 
					if ($starttime>0) {
						$runtime=current_time('timestamp')-$starttime;
						if(($this->options['campaign_timeout'] <= $runtime)) {
							$campaign['lastrun'] = $starttime;
							$campaign['lastruntime'] = ' <span style="color:red;">Timeout: '.$this->options['campaign_timeout'].'</span>';
							$campaign['starttime']   = '';
							$campaign['lastpostscount'] = 0; 
							$this->update_campaign($post->ID, $campaign);  //Save Campaign new data
						}

					}
				}
			}
		}

		/**
		 * Display empty trash button on list tables
		 * @return void
		 */
		public function add_button() {
			global $typenow, $pagenow;
			// Don't show on comments list table
			if( 'edit-comments.php' == $pagenow ) return;
			// Don't show on trash page
			if( isset( $_REQUEST['post_status'] ) && $_REQUEST['post_status'] == 'trash' ) return;
			// Don't show if current user is not allowed to edit other's posts for this post type
			if ( ! current_user_can( get_post_type_object( $typenow )->cap->edit_others_posts ) ) return;
			// Don't show if there are no items in the trash for this post type
			if( 0 == intval( wp_count_posts( $typenow, 'readable' )->trash ) ) return;
			
			$display = false;
			$args=array();
			$output = 'names'; // names or objects
			$post_types=get_post_types($args,$output); 
			foreach ($post_types  as $post_type ) {
				if($post_type != $typenow) continue;
				if( isset($this->options['cpt_trashbutton'][$post_type]) && $this->options['cpt_trashbutton'][$post_type] ) {
					$display = true;
				}
			}
			if ( !$display ) return;
			?>
			<div class="alignright empty_trash">
				<input type="hidden" name="post_status" value="trash" />
				<?php submit_button( __( 'Empty Trash' ), 'apply', 'delete_all', false ); ?>
			</div>
			<?php
		}
		
		//add dashboard widget
		function wpematico_add_dashboard() {
			wp_add_dashboard_widget( 'wpematico_widget', 'WPeMatico' , array( &$this, 'wpematico_dashboard_widget') );
		}

		 //Dashboard widget
		function wpematico_dashboard_widget() {
			$campaigns= $this->get_campaigns();
			echo '<div style="background-color: #E1DC9C;border: 1px solid #DDDDDD; height: 20px; margin: -10px -10px 2px; padding: 5px 10px 0px;';
			echo "background: -moz-linear-gradient(center bottom,#FCF6BC 0,#E1DC9C 98%,#FFFEA8 0);
				background: -webkit-gradient(linear,left top,left bottom,from(#FCF6BC),to(#E1DC9C));
				-ms-filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#FCF6BC',endColorstr='#E1DC9C');
				filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#FCF6BC',endColorstr='#E1DC9C');\">";
			echo '<strong>'.__('Last Processed Campaigns:', self :: TEXTDOMAIN ).'</strong></div>';
			@$campaigns2 = $this->filter_by_value($campaigns, 'lastrun', '');  
			$this->array_sort($campaigns2,'!lastrun');
			if (is_array($campaigns2)) {
				$count=0;
				foreach ($campaigns2 as $key => $campaign_data) {
					echo '<a href="'.wp_nonce_url('post.php?post='.$campaign_data['ID'].'&action=edit', 'edit').'" title="'.__('Edit Campaign', self :: TEXTDOMAIN ).'">';
						if ($campaign_data['lastrun']) {
							echo " <i><strong>".$campaign_data['campaign_title']."</i></strong>, ";
							echo  date_i18n( (get_option('date_format').' '.get_option('time_format') ), $campaign_data['lastrun'] ).', <i>'; 
							if ($campaign_data['lastpostscount']>0)
								echo ' <span style="color:green;">'. sprintf(__('Processed Posts: %1s', self :: TEXTDOMAIN ),$campaign_data['lastpostscount']).'</span>, ';
							else
								echo ' <span style="color:red;">'. sprintf(__('Processed Posts: %1s', self :: TEXTDOMAIN ), '0').'</span>, ';
								
							if ($campaign_data['lastruntime']<10)
								echo ' <span style="color:green;">'. sprintf(__('Fetch done in %1s sec.', self :: TEXTDOMAIN ),$campaign_data['lastruntime']) .'</span>';
							else
								echo ' <span style="color:red;">'. sprintf(__('Fetch done in %1s sec.', self :: TEXTDOMAIN ),$campaign_data['lastruntime']) .'</span>';
						} 
					echo '</i></a><br />';
					$count++;
					if ($count>=5)
						break;
				}		
			}
			unset($campaigns2);
			echo '<br />';
			echo '<div style="background-color: #E1DC9C;border: 1px solid #DDDDDD; height: 20px; margin: -10px -10px 2px; padding: 5px 10px 0px;';
			echo "background: -moz-linear-gradient(center bottom,#FCF6BC 0,#E1DC9C 98%,#FFFEA8 0);
				background: -webkit-gradient(linear,left top,left bottom,from(#FCF6BC),to(#E1DC9C));
				-ms-filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#FCF6BC',endColorstr='#E1DC9C');
				filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#FCF6BC',endColorstr='#E1DC9C');\">";
			echo '<strong>'.__('Next Scheduled Campaigns:', self :: TEXTDOMAIN ).'</strong>';
			echo '</div>';
			echo '<ul style="list-style: circle inside none; margin-top: 2px; margin-left: 9px;">';
			$this->array_sort($campaigns,'cronnextrun');
			foreach ($campaigns as $key => $campaign_data) {
				if ($campaign_data['activated']) {
					echo '<li><a href="'.wp_nonce_url('post.php?post='.$campaign_data['ID'].'&action=edit', 'edit').'" title="'.__('Edit Campaign', self :: TEXTDOMAIN ).'">';
					echo '<strong>'.$campaign_data['campaign_title'].'</strong>, ';
					if ($campaign_data['starttime']>0 and empty($campaign_data['stoptime'])) {
						$runtime=current_time('timestamp')-$campaign_data['starttime'];
						echo __('Running since:', self :: TEXTDOMAIN ).' '.$runtime.' '.__('sec.', self :: TEXTDOMAIN );
					} elseif ($campaign_data['activated']) {
						//echo date(get_option('date_format'),$campaign_data['cronnextrun']).' '.date(get_option('time_format'),$campaign_data['cronnextrun']);
						echo date_i18n( (get_option('date_format').' '.get_option('time_format') ), $campaign_data['cronnextrun'] );
					}
					echo '</a></li>';
				}
			}
			$campaigns=$this->filter_by_value($campaigns, 'activated', '');
			if (empty($campaigns)) 
				echo '<i>'.__('None', self :: TEXTDOMAIN ).'</i><br />';
			echo '</ul>';

		}
		
		/**
		* Actions-Links del Plugin
		*
		* @param   array   $data  Original Links
		* @return  array   $data  modified Links
		*/
		public static function init_action_links($data)	{
			if ( !current_user_can('manage_options') ) {
				return $data;
			}
			return array_merge(
				$data,
				array(
					'<a href="edit.php?post_type=wpematico&page=wpematico_settings" title="' . __('Load WPeMatico Settings Page', self :: TEXTDOMAIN ) . '">' . __('Settings', self :: TEXTDOMAIN ) . '</a>',
					'<a href="http://etruel.com/downloads/wpematico-pro/" target="_Blank" title="' . __('View PRO version features', self :: TEXTDOMAIN ) . '">' . __('Go PRO', self :: TEXTDOMAIN ) . '</a>'
				)
			);
		}


		/**
		* Meta-Links del Plugin
		*
		* @param   array   $data  Original Links
		* @param   string  $page  plugin actual
		* @return  array   $data  modified Links
		*/

		public static function init_row_meta($data, $page)	{
			if ( $page != self::$basen ) {
				return $data;
			}
			return array_merge(
				$data,
				array(
				'<a href="http://etruel.com.ar/?do=index&project=1&status=0" target="_blank">' . __('Bugtracker', self :: TEXTDOMAIN ) . '</a>',
				'<a href="http://www.wpematico.com/wpematico/" target="_blank">' . __('Support') . '</a>',
				'<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=B8V39NWK3NFQU" target="_blank">' . __('Donate', self :: TEXTDOMAIN ) . '</a>'
				)
			);
		}		
		
		/**
		 * admin menu custom post type
		 *
		 * @access public
		 * @return void
		 */
		 public static function Create_campaigns_page() {
		  $labels = array(
			'name' => __('Campaigns',  self :: TEXTDOMAIN ),
			'singular_name' => __('Campaign',  self :: TEXTDOMAIN ),
			'add_new' => __('Add New', self :: TEXTDOMAIN ),
			'add_new_item' => __('Add New Campaign', self :: TEXTDOMAIN ),
			'edit_item' => __('Edit Campaign', self :: TEXTDOMAIN ),
			'new_item' => __('New Campaign', self :: TEXTDOMAIN ),
			'all_items' => __('All Campaigns', self :: TEXTDOMAIN ),
			'view_item' => __('View Campaign', self :: TEXTDOMAIN ),
			'search_items' => __('Search Campaign', self :: TEXTDOMAIN ),
			'not_found' =>  __('No campaign found', self :: TEXTDOMAIN ),
			'not_found_in_trash' => __('No Campaign found in Trash', self :: TEXTDOMAIN ), 
			'parent_item_colon' => '',
			'menu_name' => 'WpeMatico');
		  $args = array(
			'labels' => $labels,
			//'public' => true,
			'public' => false,
			'exclude_from_search' => true,
			'publicly_queryable' => false,
			'show_ui' => true, 
			'show_in_menu' => true, 
			'query_var' => true,
			'rewrite' => true,
			'capability_type' => 'post',
			'has_archive' => true, 
			'hierarchical' => false,
			'menu_position' => 7,
			'menu_icon' => self :: $uri.'/images/wpe_ico.png',
			'register_meta_box_cb' => array( 'WPeMatico_Campaign_edit', 'create_meta_boxes'),
			'map_meta_cap' => true,
			'supports' => array( 'title', 'excerpt' ) ); 
		  register_post_type('wpematico',$args);
		}  //
		

		/**
		 * admin menu
		 *
		 * @access public
		 * @return void
		 */
		public function admin_menu() {
			$page = add_submenu_page(
				'edit.php?post_type=wpematico',
				__( 'Settings', self :: TEXTDOMAIN ),
				__( 'Settings', self :: TEXTDOMAIN ),
				'manage_options',
				'wpematico_settings',
				array( &$this, 'add_admin_submenu_page' )
			);
			add_action( 'admin_print_styles-' . $page, array(&$this, 'WPemat_admin_styles') );
		}

		function WPemat_admin_styles() {
			wp_enqueue_style( 'WPematStylesheet' );
			wp_enqueue_style( 'oplugincss' );			
			wp_enqueue_script( 'WPemattiptip' );
			wp_enqueue_script( 'opluginjs' );
			add_action('admin_head', array( __CLASS__ ,'settings_admin_head'));
		}

	public static function settings_admin_head() {
		?>		
		<style type="text/css">
			.insidesec {display: inline-block; vertical-align: top;}
		</style>
		<script type="text/javascript" language="javascript">
			jQuery(document).ready(function($){
				$('.handlediv').click(function() { 
					$(this).parent().toggleClass('closed');
				});
			});	
		</script>
		<?php
	}
		/**
		 * an admin submenu page
		 *
		 * @access public
		 * @return void
		 */
		public function add_admin_submenu_page () {
			global $pagenow;			
			$currenttab = (isset($_GET['tab']) ) ? $_GET['tab'] : 'homepage' ;
			$tabs = array( 'homepage' => 'Settings' );  // Agregar pestañas aca
			if ( 'POST' === $_SERVER[ 'REQUEST_METHOD' ] ) {
				if ( get_magic_quotes_gpc() ) {
					$_POST = array_map( 'stripslashes_deep', $_POST );
				}
				# evaluation goes here
				check_admin_referer('wpematico-settings');
				$errlev = error_reporting();
				error_reporting(E_ALL & ~E_NOTICE);  // desactivo los notice que aparecen con los _POST
				$cfg = $this->options;
				if($pagenow=='edit.php' && $_GET['post_type']=='wpematico' && $_GET['page']=='wpematico_settings' ){
					switch ( $currenttab ){
					case 'homepage' :
						$cfg = apply_filters('wpematico_check_options',$_POST);
						if(!isset($cfg['nonstatic']) || !is_bool($cfg['nonstatic'])) $cfg['nonstatic'] = false;

						break;
					case 'extensions' :
						break;
					}
				}
				// Roles 
				global $wp_roles, $current_user;    
				get_currentuserinfo();
				$role_conf = array();
				foreach ( $_POST['role_name'] as $role_id => $role_val ) {
					$role_conf["$role_val"]= $role_val;
				}
				$cfg['roles_widget'] = $role_conf; 
				
				error_reporting($errlev);
				
				$this->options = $cfg;
				# saving
				if ( $this->update_options() ) {
					?><div class="notice notice-success is-dismissible"><p> <?php _e( 'Settings saved.', self :: TEXTDOMAIN );?></p></div><?php
				}else{
				/*	?><div class="error"><p> <?php _e( 'Settings NOT saved.', self :: TEXTDOMAIN );?></p></div><?php  */
				}
			}
			include_once( self :: $dir . "app/settings_page.php");
		}
		
		
		/**
		 * load_options in class options attribute
		 * 
		 * @access public 
		 * load array with options in class options attribute 
		 * @return void
		 */
		public function load_options() {
			$cfg= get_option( self :: OPTION_KEY );
			if ( !$cfg ) {
				$this->options = $this->check_options( array() );
				add_option( self :: OPTION_KEY, $this->options , '', 'yes' );
			}else {
				$this->options = $this->check_options( $cfg );
			}
			return;
		}

		public static function check_options($options) {
			$cfg['mailmethod']	= (!isset($options['mailmethod'])) ?'mail':$options['mailmethod'];
			$cfg['mailsndemail']	= (!isset($options['mailsndemail'])) ? '':sanitize_email($options['mailsndemail']);
			$cfg['mailsndname']	= (!isset($options['mailsndname'])) ? '':$options['mailsndname'];
			$cfg['mailsendmail']	= (!isset($options['mailsendmail'])) ? '': untrailingslashit(str_replace('//','/',str_replace('\\','/',stripslashes($options['mailsendmail']))));
			$cfg['mailsecure']	= (!isset($options['mailsecure'])) ? '': $options['mailsecure'];
			$cfg['mailhost']	= (!isset($options['mailhost'])) ? '': $options['mailhost'];
			$cfg['mailport']	= (!isset($options['mailport'])) ? '': $options['mailport'];
			$cfg['mailuser']	= (!isset($options['mailuser'])) ? '': $options['mailuser'];			
			$cfg['mailpass']	= (!isset($options['mailpass'])) ? '': $options['mailpass'];
			$cfg['disabledashboard']= (!isset($options['disabledashboard']) || empty($options['disabledashboard'])) ? false : ($options['disabledashboard']==1) ? true : false;
			$cfg['roles_widget']	= (!isset($options['roles_widget']) || !is_array($options['roles_widget'])) ? array( "administrator" => "administrator" ): $options['roles_widget'];
			$cfg['dontruncron']	= (!isset($options['dontruncron']) || empty($options['dontruncron'])) ? false: ($options['dontruncron']==1) ? true : false;
			$cfg['disablewpcron']	= (!isset($options['disablewpcron']) || empty($options['disablewpcron'])) ? false: ($options['disablewpcron']==1) ? true : false;
			$cfg['logexternalcron']	= (!isset($options['logexternalcron']) || empty($options['logexternalcron'])) ? false: ($options['logexternalcron']==1) ? true : false;
			$cfg['disable_credits']	= (!isset($options['disable_credits']) || empty($options['disable_credits'])) ? false: ($options['disable_credits']==1) ? true : false;
			$cfg['disablecheckfeeds']=(!isset($options['disablecheckfeeds']) || empty($options['disablecheckfeeds'])) ? false: ($options['disablecheckfeeds']==1) ? true : false;
			$cfg['enabledelhash']	= (!isset($options['enabledelhash']) || empty($options['enabledelhash'])) ? false: ($options['enabledelhash']==1) ? true : false;
			$cfg['enableseelog']	= (!isset($options['enableseelog']) || empty($options['enableseelog'])) ? false: ($options['enableseelog']==1) ? true : false;
			$cfg['enablerewrite']	= (!isset($options['enablerewrite']) || empty($options['enablerewrite'])) ? false: ($options['enablerewrite']==1) ? true : false;
			$cfg['enableword2cats']	= (!isset($options['enableword2cats']) || empty($options['enableword2cats'])) ? false: ($options['enableword2cats']==1) ? true : false;
			$cfg['imgattach']	= (!isset($options['imgattach']) || empty($options['imgattach'])) ? false: ($options['imgattach']==1) ? true : false;
			$cfg['imgcache']	= (!isset($options['imgcache']) || empty($options['imgcache'])) ? false: ($options['imgcache']==1) ? true : false;
			$cfg['gralnolinkimg']	= (!isset($options['gralnolinkimg']) || empty($options['gralnolinkimg'])) ? false: ($options['gralnolinkimg']==1) ? true : false;
			$cfg['featuredimg']	= (!isset($options['featuredimg']) || empty($options['featuredimg'])) ? false: ($options['featuredimg']==1) ? true : false;
			$cfg['force_mysimplepie']	= (!isset($options['force_mysimplepie']) || empty($options['force_mysimplepie'])) ? false: ($options['force_mysimplepie']==1) ? true : false;
			$cfg['set_stupidly_fast']	= (!isset($options['set_stupidly_fast']) || empty($options['set_stupidly_fast'])) ? false: ($options['set_stupidly_fast']==1) ? true : false;
			$cfg['simplepie_strip_htmltags'] = (!isset($options['simplepie_strip_htmltags']) || empty($options['simplepie_strip_htmltags'])) ? false: ($options['simplepie_strip_htmltags']==1) ? true : false;
			$cfg['simplepie_strip_attributes'] = (!isset($options['simplepie_strip_attributes']) || empty($options['simplepie_strip_attributes'])) ? false: ($options['simplepie_strip_attributes']==1) ? true : false;
			$cfg['strip_htmltags']	= (!isset($options['strip_htmltags'])) ? '': $options['strip_htmltags'];			
			$cfg['strip_htmlattr']	= (!isset($options['strip_htmlattr'])) ? '': $options['strip_htmlattr'];			
			$cfg['woutfilter']	= (!isset($options['woutfilter']) || empty($options['woutfilter'])) ? false: ($options['woutfilter']==1) ? true : false;
			$cfg['campaign_timeout'] = (!isset($options['campaign_timeout']) ) ? 300: (int)$options['campaign_timeout'];
			$cfg['allowduplicates']	= (!isset($options['allowduplicates']) || empty($options['allowduplicates'])) ? false: ($options['allowduplicates']==1) ? true : false;
			$cfg['allowduptitle']	= (!isset($options['allowduptitle']) || empty($options['allowduptitle'])) ? false: ($options['allowduptitle']==1) ? true : false;
			$cfg['allowduphash']	= (!isset($options['allowduphash']) || empty($options['allowduphash'])) ? false: ($options['allowduphash']==1) ? true : false;
			$cfg['jumpduplicates']	= (!isset($options['jumpduplicates']) || empty($options['jumpduplicates'])) ? false: ($options['jumpduplicates']==1) ? true : false;
			$cfg['disableccf']	= (!isset($options['disableccf']) || empty($options['disableccf'])) ? false: ($options['disableccf']==1) ? true : false;
			$cfg['nonstatic']	= (!isset($options['nonstatic']) || empty($options['nonstatic'])) ? false: ($options['nonstatic']==1) ? true : false;
			$cfg['emptytrashbutton']	= (!isset($options['emptytrashbutton']) || empty($options['emptytrashbutton'])) ? false: ($options['emptytrashbutton']==1) ? true : false;
			$cfg['cpt_trashbutton']	= (!isset($options['cpt_trashbutton']) || !is_array($options['cpt_trashbutton'])) ? array( 'post' => 1,	'page' => 1 ): $options['cpt_trashbutton'];

			return $cfg;
		}
		
		/**
		 * update_options
		 *
		 * @access protected
		 * @return bool True, if option was changed
		 */
		public function update_options() {
			$this->options['mailpass'] = base64_encode($this->options['mailpass']);
			return update_option( self :: OPTION_KEY, $this->options );
		}

		/**
		 * activation
		 *
		 * @access public
		 * @static
		 * @return void
		 */
		public static function activate() {
		    self :: Create_campaigns_page(); 
			// ATTENTION: This is *only* done during plugin activation hook // You should *NEVER EVER* do this on every page load!!
			flush_rewrite_rules();			
			//tweaks old campaigns data, now saves meta for columns
			$campaigns_data = array();
			$args = array(
				'orderby'         => 'ID',
				'order'           => 'ASC',
				'post_type'       => 'wpematico', 
				'numberposts' => -1
			);
			$campaigns = get_posts( $args );
			foreach( $campaigns as $post ):
				$campaigndata = self::get_campaign( $post->ID );	
				$campaigndata = apply_filters('wpematico_check_campaigndata', $campaigndata);
				self::update_campaign($post->ID, $campaigndata);
			endforeach; 

			wp_clear_scheduled_hook('wpematico_cron');
			//make schedule
			wp_schedule_event(0, 'wpematico_int', 'wpematico_cron'); 
		}

		/**
		 * deactivation
		 *
		 * @access public
		 * @static
		 * @return void
		 */
		public static function deactivate() {
			//remove old cron jobs
			$args = array( 'post_type' => 'wpematico', 'orderby' => 'ID', 'order' => 'ASC' );
			$campaigns = get_posts( $args );
			foreach( $campaigns as $post ) {
				$campaign = self :: get_campaign( $post->ID );	
				$activated = $campaign['activated'];
				if ($time=wp_next_scheduled('wpematico_cron',array('ID'=>$post->ID)))
					wp_unschedule_event($time,'wpematico_cron',array('ID'=>$post->ID));
			}
			wp_clear_scheduled_hook('wpematico_cron');
			// NO borro opciones ni campañas
		}

		/**
		 * uninstallation
		 *
		 * @access public
		 * @static
		 * @global $wpdb, $blog_id
		 * @return void
		 */
		public static function uninstall() {
			global $wpdb, $blog_id;
			if ( is_network_admin() ) {
				if ( isset ( $wpdb->blogs ) ) {
					$blogs = $wpdb->get_results(
						$wpdb->prepare(
							'SELECT blog_id ' .
							'FROM ' . $wpdb->blogs . ' ' .
							"WHERE blog_id <> '%s'",
							$blog_id
						)
					);
					foreach ( $blogs as $blog ) {
						delete_blog_option( $blog->blog_id, self :: OPTION_KEY );
					}
				}
			}
			delete_option( self :: OPTION_KEY );
			// Tambien borrar campañas ?
			//self :: delete_campaigns();  *** TODO
			//
			//This is not a good wordpress practice.  Recommended select all campaigns on campaigns list and delete them before uninstall plugin.
			//$wpdb->query( "DELETE FROM {$wpdb->prefix}posts WHERE post_type = 'wpematico'" );
		}
		
		
		/**
		* Add cron interval
		*
		* @access protected
		* @param array $schedules
		* @return array
		*/
		static function wpematico_intervals($schedules) {
			$intervals['wpematico_int'] = array('interval' => '300', 'display' => __('WPeMatico'));
			$schedules = array_merge( $intervals, $schedules);
			return $schedules;
		}

		static function wpem_cron_callback() {
			$args = array( 'post_type' => 'wpematico', 'orderby' => 'ID', 'order' => 'ASC', 'numberposts' => -1 );
			$campaigns = get_posts( $args );
			foreach( $campaigns as $post ) {
				$campaign = WPeMatico :: get_campaign( $post->ID );
				$activated = $campaign['activated'];
				$cronnextrun = $campaign['cronnextrun'];
				if ( !$activated )
					continue;
				if ( $cronnextrun <= current_time('timestamp') ) {
					WPeMatico :: wpematico_dojob( $post->ID );
				}
			}
		}
	}
}

