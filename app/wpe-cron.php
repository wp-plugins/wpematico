<?php
ignore_user_abort(true);

if ( !empty($_POST) || defined('DOING_AJAX') || defined('DOING_CRON') )
	die();
	
if ( !defined('ABSPATH') ) {
	/** Set up WordPress environment */
	//require_once( '/wp-load.php');
	if( !(include $_SERVER['DOCUMENT_ROOT'].'/wp-load.php') )
		if( !(include $_SERVER['DOCUMENT_ROOT'].'../wp-load.php') )
		if( !(include 'wp-load.php') )
		if( !(include '../../../wp-load.php') )
		if( !(include '../../../../wp-load.php') )
		if( !(include '../../../../../wp-load.php') )
			die('<H1>Can\'t include wp-load. Report to etruel@gmail.com</H1>');
}

Function linelog($handle, $msg){
	if($handle!==FALSE) {
		fwrite($handle , $msg.PHP_EOL);
	}
}

$cfg = WPeMatico::check_options( get_option( 'WPeMatico_Options' ) );

if($cfg['logexternalcron']) {
	$upload_dir = wp_upload_dir(); 
	//try open log file on uploads dir 
	if($upload_dir['error']==FALSE) {
		$filedir = $upload_dir['basedir'].'/';
	}else {  //if can't open in uploads dir try in this dir
		$filedir = '';	
	}
}

$args = array( 'post_type' => 'wpematico', 'orderby' => 'ID', 'order' => 'ASC', 'numberposts' => -1 );
$campaigns = get_posts( $args );
foreach( $campaigns as $post ) {
	$campaign = WPeMatico :: get_campaign( $post->ID );
	$activated = $campaign['activated'];
	$cronnextrun = $campaign['cronnextrun'];
	if ( !$activated )
		continue;
	if ( $cronnextrun >= current_time('timestamp') ) {
		if($cfg['logexternalcron']) {
			@$file_handle = fopen($filedir.sanitize_file_name($post->post_title.".txt.log"), "w+");
			$msg = 'Running WPeMatico external WP-Cron'."\n";
			linelog($file_handle , $msg.PHP_EOL); 
			echo $msg;
			$msg = $post->post_title.' '."\n";
			linelog($file_handle , $msg.PHP_EOL); 
			echo $msg;
		}
		$msg = WPeMatico :: wpematico_dojob( $post->ID );
		
		if($cfg['logexternalcron']) {
			$msg = strip_tags($msg);
			$msg .= "\n";
			linelog($file_handle , $msg.PHP_EOL); 
			echo '<pre>'.$msg.'</pre>';
		}	
	}
}

if($cfg['logexternalcron'] && $file_handle != false ) {
	$msg = ' Success !'."\n";
	linelog($file_handle , $msg.PHP_EOL); echo $msg;
	if($file_handle!==FALSE) {
		fclose($file_handle ); 
	}
}

die(1);

?>