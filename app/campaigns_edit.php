<?php 
// don't load directly 
if ( !defined('ABSPATH') ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

add_action( 'init', array( 'WPeMatico_Campaign_edit', 'init' ) );

if ( class_exists( 'WPeMatico_Campaign_edit' ) ) return;

class WPeMatico_Campaign_edit extends WPeMatico_Campaign_edit_functions {
	
	public static function init() {
		new self();
	}
	
	public function __construct( $hook_in = FALSE ) {
		add_action('save_post', array( __CLASS__ , 'save_campaigndata'));
		add_action('wp_ajax_wpematico_run', array( &$this, 'RunNowX'));
		add_action('wp_ajax_wpematico_checkfields', array( __CLASS__, 'CheckFields'));
		add_action('wp_ajax_wpematico_test_feed', array( 'WPeMatico', 'Test_feed'));
		add_action('admin_print_styles-post.php', array( __CLASS__ ,'admin_styles'));
		add_action('admin_print_styles-post-new.php', array( __CLASS__ ,'admin_styles'));
		add_action('admin_print_scripts-post.php', array( __CLASS__ ,'admin_scripts'));
		add_action('admin_print_scripts-post-new.php', array( __CLASS__ ,'admin_scripts'));  
	}
	public static function disable_autosave() {
	//	global $post_type, $post, $typenow;
		if(get_post_type() != 'wpematico') return ;
		wp_deregister_script( 'autosave' );
	}

  	public static function admin_styles(){
		global $post;
		if($post->post_type != 'wpematico') return $post->ID;
		wp_enqueue_style('campaigns-edit',WPeMatico :: $uri .'app/css/campaigns_edit.css');	
		wp_enqueue_style( 'WPematStylesheet' );
//		add_action('admin_head', array( &$this ,'campaigns_admin_head_style'));
	}

	public static function admin_scripts(){
		global $post;
		if($post->post_type != 'wpematico') return $post->ID;
		wp_enqueue_script( 'WPemattiptip' );
		wp_dequeue_script( 'autosave' );
		add_action('admin_head', array( __CLASS__ ,'campaigns_admin_head'));
	}

	function RunNowX() {
		if(!isset($_POST['campaign_ID'])) die('ERROR: ID no encontrado.'); 
		$campaign_ID=$_POST['campaign_ID'];
		echo substr( WPeMatico :: wpematico_dojob( $campaign_ID ) , 0, -1); // borro el ultimo caracter que es un 0
		return ''; 
	}
	
	public static function campaigns_admin_head() {
		global $post;
		if($post->post_type != 'wpematico') return $post_id;
		$post->post_password = '';
		$visibility = 'public';
		$visibility_trans = __('Public');
		$description = __('Campaign Description', WPeMatico :: TEXTDOMAIN );
		$description_help = __('Here you can write some observations.',  WPeMatico :: TEXTDOMAIN);
		//$runnowbutton_OLD = '<div class="right m7 " style="margin-left: 47px;"><div style="background-color: #EB9600;" id="run_now" class="button-primary">'. __('Run Now', WPeMatico :: TEXTDOMAIN ) . '</div></div>';
		$runnowbutton = '<button style="background-color: #EB9600;" id="run_now" class="button button-large" type="button">'. __('Run Now', WPeMatico :: TEXTDOMAIN ) . '';
		$cfg = get_option(WPeMatico :: OPTION_KEY);
		
		?>
		<script type="text/javascript" language="javascript">
		jQuery(document).ready(function($){
			//try {
			$('#post-visibility-display').text('<?php echo $visibility_trans; ?>');
			$('#hidden-post-visibility').val('<?php echo $visibility; ?>');
			$('#visibility-radio-<?php echo $visibility; ?>').attr('checked', true);
			$('#postexcerpt .hndle span').text('<?php echo $description; ?>');
			$('#postexcerpt .inside .screen-reader-text').text('<?php echo $description; ?>');
			$('#postexcerpt .inside p').text('<?php echo $description_help; ?>');
			//jQuery('#delete-action').append('<?php echo $runnowbutton; ?>');
			$('#publishing-action').prepend('<?php echo $runnowbutton; ?>');

			$('#psearchtext').keyup(function(tecla){
				if(tecla.keyCode==27) {
					$(this).attr('value','');
					$('.feedinput').parent().parent().show();
				}else{
					buscafeed = $(this).val();
					$('.feedinput').each(function (el,item) {
						feed = $(item).attr('value');
						if (feed.toLowerCase().indexOf(buscafeed) >= 0) {
							$(item).parent().parent().show();
						}else{
							$(item).parent().parent().hide();
						}
					});
				}
			});
			
			$('#campaign_imgcache').click(function() {
				if ( true == $('#campaign_imgcache').is(':checked')) {
					$('#nolinkimg').fadeIn();
				} else {
					$('#nolinkimg').fadeOut();
				}
			});
			
			$('.tag').click(function(){
				$('#campaign_template').attr('value',$('#campaign_template').attr('value')+$(this).html());
			});
			
			$('.w2cregex').click(function() {
				var cases = $(this).parent().children('#campaign_wrd2cat_cases');
				if ( true == $(this).is(':checked')) {
					cases.attr('checked','checked');
					cases.attr('disabled','disabled');
				}else{
					cases.removeAttr('checked');
					cases.removeAttr('disabled');
				}
			});
			
			$('#addmorerew').click(function() {
				$('#rew_max').val( parseInt($('#rew_max').val(),10) + 1 );
				newval = $('#rew_max').val();					
				nuevo= $('#nuevorew').clone();
				$('input', nuevo).eq(0).attr('name','campaign_word_option_title['+ newval +']');
				$('input', nuevo).eq(1).attr('name','campaign_word_option_regex['+ newval +']');
				$('textarea', nuevo).eq(0).attr('name','campaign_word_origin['+ newval +']');
				$('textarea', nuevo).eq(1).attr('name','campaign_word_rewrite['+ newval +']');
				$('textarea', nuevo).eq(2).attr('name','campaign_word_relink['+ newval +']');
				$('input', nuevo).eq(0).removeAttr('checked');
				$('input', nuevo).eq(1).removeAttr('checked');
				$('#rw3', nuevo).show();
				$('textarea', nuevo).eq(0).text('');
				$('textarea', nuevo).eq(1).text('');
				$('textarea', nuevo).eq(2).text('');
				nuevo.show();
				$('#rewrites_edit').append(nuevo);
			});
			
			$('#addmorew2c').click(function() {
				$('#wrd2cat_max').val( parseInt($('#wrd2cat_max').val(),10) + 1 );
				newval = $('#wrd2cat_max').val();					
				nuevo= $('#nuevow2c').clone();
				$('input', nuevo).eq(0).attr('name','campaign_wrd2cat[word]['+ newval +']');
				$('input', nuevo).eq(1).attr('name','campaign_wrd2cat[regex]['+ newval +']');
				$('input', nuevo).eq(2).attr('name','campaign_wrd2cat[cases]['+ newval +']');
				$('select', nuevo).eq(0).attr('name','campaign_wrd2cat[w2ccateg]['+ newval +']');
				$('input', nuevo).eq(0).attr('value','');
				$('input', nuevo).eq(1).removeAttr('checked');
				$('input', nuevo).eq(2).attr('value','');
				nuevo.show();
				$('#wrd2cat_edit').append(nuevo);
			});
			
			$('#run_now').click(function() {
				$(this).attr('style','Background:#CCC;');
				$('html').css('cursor','wait');
//				$.ajaxSetup({async:false});
				$('#fieldserror').remove();
				msgdev="<img width='12' src='<?php echo get_bloginfo('wpurl'); ?>/wp-admin/images/wpspin_light.gif' class='mt2'> <?php _e('Running Campaign...', WPeMatico :: TEXTDOMAIN ); ?>";
				$("#poststuff").prepend('<div id="fieldserror" class="updated fade he20">'+msgdev+'</div>');
				c_ID = $('#post_ID').val();
				var data = {
					campaign_ID: c_ID ,
					action: "wpematico_run"
				};
				$.post(ajaxurl, data, function(msgdev) {  //si todo ok devuelve LOG sino 0
					$('#fieldserror').remove();
					if( msgdev.substring(0, 5) == 'ERROR' ){
						$("#poststuff").prepend('<div id="fieldserror" class="error fade">'+msgdev+'</div>');
					}else{
						$("#poststuff").prepend('<div id="fieldserror" class="updated fade">'+msgdev+'</div>');
					}
					$('html').css('cursor','auto');
					$(this).attr('style','Background:#FFF52F;');
				});
			});
			
			$('#post').submit( function() {		//checkfields
				$('#wpcontent .ajax-loading').attr('style',' visibility: visible;');
				$.ajaxSetup({async:false});
				error=false;
				var msg="Guardando...";
				var wrd2cat= $("input[name='campaign_wrd2cat[]']").serialize();
				var wrd2cat_regex  = new Array();
				$("input[name='campaign_wrd2cat_regex[]']").each(function() {
					if ( true == $(this).is(':checked')) {
						wrd2cat_regex.push('1');
					}else{
						wrd2cat_regex.push('0');
					}
				});

				reword = $("textarea[name='campaign_word_origin[]']").serialize();
				var reword_regex  = new Array();
				$("input[name='campaign_word_option_regex[]']").each(function() {
					if ( true == $(this).is(':checked')) {
						reword_regex.push('1');
					}else{
						reword_regex.push('0');
					}
				});
				var reword_title  = new Array();
				$("input[name='campaign_word_option_title[]']").each(function() {
					if ( true == $(this).is(':checked')) {
						reword_title.push('1');
					}else{
						reword_title.push('0');
					}
				});

				feeds= $("input[name='campaign_feeds[]']").serialize();
				
				var data = {
					campaign_feeds: feeds,
					campaign_word_origin: reword,
					campaign_word_option_regex: reword_regex,
					campaign_word_option_title: reword_title,
					campaign_wrd2cat: wrd2cat,
					campaign_wrd2cat_regex: wrd2cat_regex,
					action: "wpematico_checkfields"
				};
				$.post(ajaxurl, data, function(todok){  //si todo ok devuelve 1 sino el error
					if( todok != 1 ){
						error=true;
						msg=todok;
					}else{
						error=false;  //then submit campaign
					}
				});
				if( error == true ) {
					$('#fieldserror').remove();
					$("#poststuff").prepend('<div id="fieldserror" class="error fade">ERROR: '+msg+'</div>');
					$('#wpcontent .ajax-loading').attr('style',' visibility: hidden;');

					return false;
				}else {
					$('.w2ccases').removeAttr('disabled'); //si todo bien habilito los check para que los tome el php
					return true;
				}
			});
			

			//$('#checkfeeds').click(function() {
			$(document).on("click", '.notice-dismiss', function(event) {
				$(this).parent().remove();
			});
			//$('#checkfeeds').click(function() {
			$(document).on("click", '#checkfeeds', function(event) {
				//$.ajaxSetup({async:false});
				var feederr = 0;
				var feedcnt = 0;
				errmsg ="Feed ERROR";
				$('.feederror').remove();
				$('.feedinput').each(function (el,item) {
					feederr += 1;
					feed = $(item).attr('value');
					working = $(item).parent().find('#ruedita');
					$(working).removeClass("ui-icon yellowalert_small");
					$(working).removeClass("ui-icon redalert_small");
					$(working).removeClass("ui-icon checkmark_small");
					if (feed !== "") {
						$(working).addClass("spinner");  
						$(item).attr('style','Background:#CCC;');
						var data = {
							action: "wpematico_test_feed",
							url: feed, 
							'cookie': encodeURIComponent(document.cookie)
						};
						$.post(ajaxurl, data, function(response){
							var dismiss = '<button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php _e( 'Dismiss this notice.', WPeMatico :: TEXTDOMAIN ); ?></span></button>';
							working = $(item).parent().find('#ruedita');
							if( response.success ){
								$(item).attr('style','Background:#75EC77;');
								$("#poststuff").prepend('<div id="message" class="feederror notice notice-success is-dismissible"><p>'+response.message+'</p>' +dismiss +'</div>');
								$(working).addClass("ui-icon checkmark_small");
							}else{
								$(item).attr('style','Background:Red;');
								$("#poststuff").prepend('<div id="message" class="feederror notice notice-error is-dismissible"><p>ERROR: '+response.message+'</p>' +dismiss +'</div>');
								$(working).addClass("ui-icon redalert_small");
							}
							$(working).removeClass("spinner");
						});
					}else{
						if(feedcnt>1) alert("<?php _e('Type some new Feed URL/s.', WPeMatico :: TEXTDOMAIN ); ?>");
					}
				}); 
				if(feederr == 1){
					alert(errmsg);
				}else{ }
			});
			
			//$('.check1feed').click(function() {
			$(document).on("click", '.check1feed', function(event) {
				item = $(this).parents('div').children('input');
				feed = item.val();
				working = $(this).find('#ruedita');
				$(working).removeClass("ui-icon yellowalert_small");
				$(working).removeClass("ui-icon redalert_small");
				$(working).removeClass("ui-icon checkmark_small");
				//$.ajaxSetup({async:false});
				if (feed !== "") {
					$(working).addClass("spinner");  
					$(item).attr('style','Background:#CCC;');
					var data = {
						action: "wpematico_test_feed",
						url: feed, 
						'cookie': encodeURIComponent(document.cookie)
					};
					$.post(ajaxurl, data, function(response){
						working = $(item).parent().find('#ruedita');
						$('.feederror').remove();
						if( response.success ){
							$(item).attr('style','Background:#75EC77;');
							$("#poststuff").prepend('<div id="message" class="feederror notice notice-success">'+response.message +'</div>');
							$(working).addClass("ui-icon checkmark_small");
						}else{
							$(item).attr('style','Background:Red;');
							$("#poststuff").prepend('<div id="message" class="feederror notice notice-error is-dismissible">ERROR: '+response.message +'</div>');
							$(working).addClass("ui-icon redalert_small");
						}
						$(working).removeClass("spinner");
					});
 				}else{			
					alert("<?php _e('Type some feed URL.', WPeMatico :: TEXTDOMAIN ); ?>");
					$(working).addClass("ui-icon yellowalert_small");
				}
			});
			
			
			$('.feedinput').focus(function() {
				$(this).attr('style','Background:#FFFFFF;');
			});

			$(document).on("change", '#post', function(event) {
				disable_run_now();
			});
			
			disable_run_now = function() {
				$('#run_now').attr('disabled','disabled');
				$('#run_now').attr('title','<?php _e('Save before Run Campaign', WPeMatico :: TEXTDOMAIN ); ?>');
			}

			jQuery(".help_tip").tipTip({maxWidth: "400px", edgeOffset: 5,fadeIn:50,fadeOut:50, keepAlive:true, defaultPosition: "right"});

			//} catch(err)}
		});
		</script>
		<?php
	}
	/********** CHEQUEO CAMPOS ANTES DE GRABAR ****************************************************/
	public static function CheckFields() {  // check required fields values before save post
		$cfg = get_option(WPeMatico :: OPTION_KEY);
		$err_message = "";
		if(isset($_POST['campaign_wrd2cat'])) {
			$wrd2cat = array();
			parse_str($_POST['campaign_wrd2cat'], $wrd2cat);
			$campaign_wrd2cat = @$wrd2cat['campaign_wrd2cat'];
			for ($id = 0; $id < count($campaign_wrd2cat); $id++) {
				$word = $campaign_wrd2cat[$id];
				$regex = ($_POST['campaign_wrd2cat_regex'][$id]==1) ? true : false ;
				if(!empty($word))  {
					if($regex) 
						if(false === @preg_match($word, '')) {
							$err_message = ($err_message != "") ? $err_message."<br />" : "" ;
							$err_message .= sprintf(__('There\'s an error with the supplied RegEx expression in word: %s', WPeMatico :: TEXTDOMAIN ),'<span class="coderr">'.$word.'</span>');
						}
				}
			}
		}
		
		if(isset($_POST['campaign_word_origin'])) {
			$rewrites = array();
			parse_str($_POST['campaign_word_origin'], $rewrites);
			$campaign_word_origin = @$rewrites['campaign_word_origin'];
			for ($id = 0; $id < count($campaign_word_origin); $id++) {
				$origin = $campaign_word_origin[$id];
				$regex = $_POST['campaign_word_option_regex'][$id]==1 ? true : false ;
				if(!empty($origin))  {
					if($regex) 
						if(false === @preg_match($origin, '')) {
							$err_message = ($err_message != "") ? $err_message."<br />" : "" ;
							$err_message .= sprintf(__('There\'s an error with the supplied RegEx expression in ReWrite: %s', WPeMatico :: TEXTDOMAIN ),'<span class="coderr">'.$origin.'</span>');
						}
				}
			}
		}
		
		if(!isset($cfg['disablecheckfeeds']) || !$cfg['disablecheckfeeds'] ){  // Si no esta desactivado en settings
			// Si no hay ningun feed devuelve mensaje de error
			// Proceso los feeds sacando los que estan en blanco
			if(isset($_POST['campaign_feeds'])) {
				$feeds = array();
				parse_str($_POST['campaign_feeds'], $feeds);
				$all_feeds = $feeds['campaign_feeds'];
				for ($id = 0; $id < count($all_feeds); $id++) {
					$feedname = $all_feeds[$id];
					if(!empty($feedname))  {
						if(!isset($campaign_feeds)) 
							$campaign_feeds = array();					
						$campaign_feeds[]=$feedname ;
					}
				}
			}

			if(empty($campaign_feeds) || !isset($campaign_feeds)) {
				$err_message = ($err_message != "") ? $err_message."<br />" : "" ;
				$err_message .= __('At least one feed URL must be filled.',  WPeMatico :: TEXTDOMAIN );
			} else {  
				foreach($campaign_feeds as $feed) {
					$pos = strpos($feed, ' '); // el feed no puede tener espacios en el medio
					if ($pos === false) {
						$simplepie = WPeMatico :: fetchFeed($feed, true);
						if($simplepie->error()) {
							$err_message = ($err_message != "") ? $err_message."<br />" : "" ;
							$err_message .= sprintf(__('Feed %s could not be parsed. (SimplePie said: %s)',  WPeMatico :: TEXTDOMAIN ),'<strong class="coderr">'. $feed. '</strong>', $simplepie->error());
						}
					}else{
						$err_message = ($err_message != "") ? $err_message."<br />" : "" ;
						$err_message .= sprintf(__('Feed %s could not be parsed because has an space in url.',  WPeMatico :: TEXTDOMAIN ),'<strong class="coderr">'. $feed. '</strong>');
					}
				}
			}
		}
		if($cfg['nonstatic']) {$err_message = NoNStatic::Checkp($_POST, $err_message);}
		
		if($err_message =="" ) $err_message="1";  //NO ERROR
		die($err_message);  // Return 1 si OK, else -> error string
	}
	
	
	//************************* GRABA CAMPAÑA *******************************************************
	public static function save_campaigndata( $post_id ) {
		global $post,$cfg;
		//wp_die('save_campaigndata<br>DOING_AUTOSAVE:'.DOING_AUTOSAVE.'<br>DOING_AJAX:'.DOING_AJAX.'<br>$_REQUEST[bulk_edit]:'.$_REQUEST['bulk_edit']);
		if((defined('DOING_AJAX') && DOING_AJAX) || isset($_REQUEST['bulk_edit']) || (isset($_REQUEST['action']) && $_REQUEST['action']=='inline-save') ) {
			WPeMatico_Campaigns::save_quick_edit_post($post_id);
			//wp_die('save_campaigndata<br>DOING_AUTOSAVE:'.DOING_AUTOSAVE.'<br>DOING_AJAX:'.DOING_AJAX.'<br>$_REQUEST[bulk_edit]:'.$_REQUEST['bulk_edit']);
			return $post_id;
		}//http://news.google.com.pe/news?pz=1&cf=all&ned=es_pe&hl=es&output=rss
		if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || (defined('DOING_AJAX') && DOING_AJAX) || isset($_REQUEST['bulk_edit']))
			return $post_id;
		if ( !wp_verify_nonce( @$_POST['wpematico_nonce'], 'edit-campaign' ) )
			return $post_id;

		if($post->post_type != 'wpematico') return $post_id;

		$nivelerror = error_reporting(E_ERROR | E_WARNING | E_PARSE);
		//$cfg = get_option(WPeMatico :: OPTION_KEY);
		
		$campaign = array();
//		$campaign['cron'] = WPeMatico :: cron_string($_POST);
//		$campaign = WPeMatico :: get_campaign ($post_id);
		$campaign = apply_filters('wpematico_check_campaigndata', $_POST);

		//***** Call nonstatic
//		if( $cfg['nonstatic'] ) { $campaign = NoNStatic :: save_data($campaign, $_POST); }
		 
		error_reporting($nivelerror);

		if(has_filter('wpematico_presave_campaign')) $campaign = apply_filters('wpematico_presave_campaign', $campaign);
		
		// Grabo la campaña
		WPeMatico :: update_campaign($post_id, $campaign);

		return $post_id ;
	}
	
}