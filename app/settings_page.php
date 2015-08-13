<?php
// don't load directly 
if ( !defined('ABSPATH') ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

$cfg = $this->check_options($this->options);  
if ( $cfg['force_mysimplepie']){
	include_once( dirname( __FILE__) . '/lib/simplepie.inc.php' );
}else{
	if (!class_exists('SimplePie')) {
		if (is_file( ABSPATH . WPINC . '/class-simplepie.php'))
			include_once( ABSPATH. WPINC . '/class-simplepie.php' );
		else if (is_file( ABSPATH.'wp-admin/includes/class-simplepie.php'))
			include_once( ABSPATH.'wp-admin/includes/class-simplepie.php' );
		else
			include_once( dirname( __FILE__) . '/lib/simplepie.inc.php' );
	}		
}
$simplepie = new SimplePie();
$cfg['strip_htmltags']	= (!($cfg['simplepie_strip_htmltags'])) ? implode(',',$simplepie->strip_htmltags): $cfg['strip_htmltags'];
$cfg['strip_htmlattr']	= (!($cfg['simplepie_strip_attributes'])) ? implode(',', $simplepie->strip_attributes) : $cfg['strip_htmlattr'];
$cfg['mailsndemail']	= (!($cfg['mailsndemail']) || empty($cfg['mailsndemail']) ) ? 'noreply@'.str_ireplace('www.', '', parse_url(get_option('siteurl'), PHP_URL_HOST)) : $cfg['mailsndemail'];
$cfg['mailsndname']		= (!($cfg['mailsndname']) or empty($cfg['mailsndname']) ) ? 'WPeMatico Log' : $cfg['mailsndname'];
//$cfg['mailpass']		= (!($cfg['mailpass']) or empty($cfg['mailpass']) ) ? '' : base64_decode($cfg['mailpass']);

$helptip = array(
 'disable_credits' 	=> __('I really appreciate if you can left this option blank to show the plugin\'s credits.', self :: TEXTDOMAIN ),
 'enableseelog' 	=> __('Show `See Log` link on campaigns list.  This link show the last processed log of every campaign.', self :: TEXTDOMAIN ),
 'enabledelhash' 	=> __('Show `Del Hash` link on campaigns list.  This link delete all hash codes for check duplicates on every feed per campaign.', self :: TEXTDOMAIN ),
 'disablecheckfeeds'=> __('Check this if you don\'t want automatic check feed URLs before save every campaign.', self :: TEXTDOMAIN ),
 'disabledashboard'	=> __('Check this if you don\'t want to display the widget dashboard.  Anyway, only admins will see it.', self :: TEXTDOMAIN ) ,
 'imgcache' 	 	=> "<b>" . __('Image Caching', self :: TEXTDOMAIN ) . ":</b> " . __('When image caching is on, a copy of every image found in content of every feed (only in &lt;img&gt; tags) is downloaded to the Wordpress UPLOADS Dir.', self :: TEXTDOMAIN ) . "<br />" . __('If not enabled all images will linked to the image owner\'s server, but also make your website faster for your visitors.', self :: TEXTDOMAIN ) . "<br /><b>" . __('Caching all images', self :: TEXTDOMAIN ) . ":</b> " . __('This featured in the general Settings section, will be overridden for the campaign-specific options.', self :: TEXTDOMAIN ),
 'imgattach' 	 	=> "<b>" . __('Image Attaching', self :: TEXTDOMAIN ).":</b> " . __('By default when image caching is on (and everything is working fine), a copy of every image found is added to Wordpress Media.', self :: TEXTDOMAIN ). "<br />" . __('If enabled Image Attaching all images will be attached to the owner post in WP media library; but if you see that the job process is too slowly you can deactivate this here.', self :: TEXTDOMAIN ),
 'gralnolinkimg' 	=> "<b>" . __('Note',  self :: TEXTDOMAIN ). ":</b> " . __('If selected and image upload get error, then delete the \'src\' attribute of the &lt;img&gt;. Check this for don\'t link images from external sites.', self :: TEXTDOMAIN ),
 'enablefeatures' 	=> __('If you need these features in each campaign, you can activate them here. This is not recommended if you will not use the feature.', self :: TEXTDOMAIN ),
 'enablerewrite' 	=> __('Rewrite a word or phrase for another.', self :: TEXTDOMAIN ),
 'enableword2cats' 	=> __('Assign a category to the post if a word is found in the content.', self :: TEXTDOMAIN ),
 'PROfeatures'		=> __('Features only available when you buy the PRO version.', self :: TEXTDOMAIN ),
 'enablekwordf' 	=> __('This is for exclude or include posts according to the keywords <b>found</b> at content or title.', self :: TEXTDOMAIN ),
 'enablewcf' 	 	=> __('This is for cut, exclude or include posts according to the letters o words <b>counted</b> at content.', self :: TEXTDOMAIN ),
 'enablecustomtitle'=> __('If you want a custom title for posts of a campaign, you can activate here.', self :: TEXTDOMAIN ),
 'enabletags'		=> __('This feature generate tags automatically on every published post, on campaign edit you can disable auto feature and manually enter a list of tags or leave empty.', self :: TEXTDOMAIN ),
 'enablecfields'	=> __('Add custom fields with values as templates on every post.', self :: TEXTDOMAIN ),
 'fullcontent'		=> __('If you want to attempt to obtain full items content from source site instead of the campaign feed, you can activate here.', self :: TEXTDOMAIN ),
 'authorfeed'		=> __('This option allow you assign an author per feed when editing campaign. If no choice any author, the campaign author will be taken.', self :: TEXTDOMAIN ),
 'importfeeds'		=> __('On campaign edit you can import, copy & paste in a textarea field, a list of feed addresses with/out author names.', self :: TEXTDOMAIN ),
	
 'mysimplepie'		=> __('Check this if you want to ignore Wordpress Simplepie library.', self :: TEXTDOMAIN ) . " " . __('Almost never be necessary.  Just if you have problems with version of Simplepie installed in Wordpress.', self :: TEXTDOMAIN ),
 'stupidly_fast'	=> __('Forgoes a substantial amount of data sanitization in favor of speed. This turns SimplePie into a dumb parser of feeds.  This means all feed content is gotten without parsers, filters or filters.', self :: TEXTDOMAIN ),
 'strip_htmltags'	=> __('By Default Simplepie strip these html tags from feed content.  You can change or allow some tags, for example if you want to allow iframes or embed code like videos.', self :: TEXTDOMAIN ),
 'strip_htmlattr'	=> __('Simplepie also strip these attributes from html tags in content.  You can change it if you want to retain some of them or add more attributes to strip.', self :: TEXTDOMAIN ),

 'jumpduplicates'	=> __('Unless it is the first time, when finds a duplicate, it means that all following items were read before.  This option avoids and allows jump every duplicate and continues reading the feed searching more new items.  Not recommended.', self :: TEXTDOMAIN ),
 'disableccf'	=> __('This option nulls saving custom fields on every post that campaign publish.', self :: TEXTDOMAIN ) .'<br>'
 . __(' For default the plugin save three custom fields on every post with campaign and source item data, necessary for use permalink to source feature, identify which campaign fetch the post or to make any bulk action on post types related with original campaign.', self :: TEXTDOMAIN ) .'<br>'
 . __('Not recommended unless you want to loose this data and features in order to save DB.', self :: TEXTDOMAIN ) .'<br>'
 . __('(Enabling this feature don\'t deletes the previous saved data.)', self :: TEXTDOMAIN ),
	
 'dontruncron'		=> __('Check this to deactivate WPeMatico cron schedules. Affects all campaigns. To run campaigns you must do it manually or with external cron. (Recommended with External Cron).', self :: TEXTDOMAIN ),
 'disablewpcron'	=> __('Checking this, deactivates all Wordpress cron schedules. Affects to Wordpress itself and all other plugins.  Not recommended.', self :: TEXTDOMAIN ),
 'logexternalcron'	=> __('Try to save a file with simple steps taken at run wpe-cron.php. "wpemextcron.txt.log" will be saved on uploads folder or inside plugin, "app" folder.  Recommended on issues with cron.', self :: TEXTDOMAIN ),
// Other tools
 'emptytrashbutton'	=> __('Just an extra tool to display a button for empty trash folder on every custom post main screen. May be posts, pages or selects what you want.', self :: TEXTDOMAIN ),
);
foreach($helptip as $key => $value){
	$helptip[$key] = htmlentities($value);
}

?>
<div class="wrap">
	<h2><?php _e( 'WPeMatico settings', self :: TEXTDOMAIN );?></h2>
	<div id="poststuff" class="metabox-holder has-right-sidebar">
		<form method="post" action="" autocomplete="off" >
		<?php  wp_nonce_field('wpematico-settings'); ?>
		<div id="side-info-column" class="inner-sidebar">
			<div id="side-sortables" class="meta-box-sortables ui-sortable">
				<div class="postbox inside"><div class="handlediv" title="<?php _e('Click to toggle'); ?>"><br></div>
					<h3 class="handle"><?php _e( 'About', self :: TEXTDOMAIN );?></h3>
					<div class="inside">
						<p id="left1" onmouseover="this.style.background =  '#111';" onmouseout="this.style.background =  '#FFF';" style="text-align:center; background-color: rgb(255, 255, 255); background-position: initial initial; background-repeat: initial initial; "><a href="http://www.wpematico.com" target="_Blank" title="Go to new WPeMatico WebSite"><img style="background: transparent;border-radius: 15px;width: 258px;" src="http://www.netmdp.com/wpematicofiles/bannerWPematico.png" title=""></a><br />
						WPeMatico Free Version <?php echo self :: $version ; ?></p>
						<p><?php _e( 'Thanks for test, use and enjoy this plugin.', self :: TEXTDOMAIN );?></p>
						<p><?php _e( 'If you like it, I really appreciate a donation.', self :: TEXTDOMAIN );?></p>
						<p>
							<input type="button" class="button-primary" name="donate" value="<?php _e( 'Click for Donate', self :: TEXTDOMAIN );?>" onclick="javascript:window.open('https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=B8V39NWK3NFQU');return false;"/>
						</p>
						<p>
							<a href="#" onclick="javascript:window.open('https://www.paypal.com/ar/cgi-bin/webscr?cmd=xpt/Marketing/general/WIPaypal-outside','WIPaypal','toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=700, height=600');">
								<img  src="https://www.paypal.com/es_XC/Marketing/i/logo/bnr_airlines1_205x67.gif" border="0" alt="Paypal Help">
							</a>
						</p>
						<p></p>
						<p>
							<input type="button" class="button-primary" name="buypro" value="<?php _e( 'Buy PRO version online', self :: TEXTDOMAIN );?>" onclick="javascript:window.open('http://etruel.com/downloads/wpematico-pro/');return false;"/>
						</p>
						<p></p>
					</div>
				</div>
				<div class="postbox"><div class="handlediv" title="<?php _e('Click to toggle'); ?>"><br></div>
					<h3 class="handle"><?php _e( 'Sending e-Mails', self :: TEXTDOMAIN );?></h3>
					<div class="inside">
						<p><b><?php _e('Sender Email:', self :: TEXTDOMAIN ); ?></b><br /><input name="mailsndemail" id="mailsndemail" type="text" value="<?php echo $cfg['mailsndemail'];?>" class="large-text" /><span id="mailmsg"></span></p>
						<p><b><?php _e('Sender Name:', self :: TEXTDOMAIN ); ?></b><br /><input name="mailsndname" type="text" value="<?php echo $cfg['mailsndname'];?>" class="large-text" /></p>
						<p><b><?php _e('Send mail method:', self :: TEXTDOMAIN ); ?></b><br />
						<?php 
						echo '<select id="mailmethod" name="mailmethod">';
						echo '<option value="mail"'.selected('mail',$cfg['mailmethod'],false).'>'.__('PHP: mail()', self :: TEXTDOMAIN ).'</option>';
						//echo '<option value="Sendmail"'.selected('Sendmail',$cfg['mailmethod'],false).'>'.__('Sendmail', self :: TEXTDOMAIN ).'</option>';
						echo '<option value="SMTP"'.selected('SMTP',$cfg['mailmethod'],false).'>'.__('SMTP', self :: TEXTDOMAIN ).'</option>';
						echo '</select>';
						?></p>
						<label id="mailsendmail" <?php if ($cfg['mailmethod']!='Sendmail') echo 'style="display:none;"';?>><b><?php _e('Sendmail Path:', self :: TEXTDOMAIN ); ?></b><br /><input name="mailsendmail" type="text" value="<?php echo $cfg['mailsendmail'];?>" class="large-text" /><br /></label>
						<label id="mailsmtp" <?php if ($cfg['mailmethod']!='SMTP') echo 'style="display:none;"';?>>
						<b><?php _e('SMTP Hostname:', self :: TEXTDOMAIN ); ?></b><br /><input name="mailhost" type="text" value="<?php echo $cfg['mailhost'];?>" class="large-text" /><br />
						<b><?php _e('SMTP Port:', self :: TEXTDOMAIN ); ?></b><br /><input name="mailport" type="text" value="<?php echo $cfg['mailport'];?>" class="small-text" /><br />
						<b><?php _e('SMTP Secure Connection:', self :: TEXTDOMAIN ); ?></b><br />
						<select name="mailsecure">
						<option value=""<?php selected('',$cfg['mailsecure'],true); ?>><?php _e('none', self :: TEXTDOMAIN ); ?></option>
						<option value="ssl"<?php selected('ssl',$cfg['mailsecure'],true); ?>>SSL</option>
						<option value="tls"<?php selected('tls',$cfg['mailsecure'],true); ?>>TLS</option>
						</select><br />
						<b><?php _e('SMTP Username:', self :: TEXTDOMAIN ); ?></b><br /><input name="mailuser" type="text" value="<?php echo $cfg['mailuser'];?>" class="user large-text" autocomplete="off" /><br />
						<b><?php _e('SMTP Password:', self :: TEXTDOMAIN ); ?></b><br />
						<input name="mailpass" type="password" value="<?php echo base64_decode($cfg['mailpass']);?>" class="password large-text" autocomplete="off" /></label><span class="mya4_sprite searchIco pshow" style="position: absolute;margin: 8px 0 0 -20px;"></span>
						<br />
						<div class="mphlp" style="margin: 0px;">
							<br /> 
							<a rel="nofollow" target="_Blank" href="https://support.google.com/a/answer/176600"><?php _e('Configure Gmail Server for SMTP', self :: TEXTDOMAIN ); ?></a>
							<br /> 
						</div>
					</div>
				</div>
				
				<div class="postbox inside">
					<div class="inside">
						<p>
						<input type="submit" class="button-primary" name="submit" value="<?php _e( 'Save settings', self :: TEXTDOMAIN );?>" />
						</p>
					</div>
				</div>
				<div class="postbox inside"><div class="handlediv" title="<?php _e('Click to toggle'); ?>"><br></div>
					<h3 class="handle"><?php _e( 'Advanced', self :: TEXTDOMAIN );?></h3>
					<div class="inside">
						<p></p>
						<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['disablecheckfeeds'],true); ?> name="disablecheckfeeds" id="disablecheckfeeds" /> <?php _e('Disable <b><i>Check Feeds before Save</i></b>', self :: TEXTDOMAIN ); ?> <span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['disablecheckfeeds']; ?>"></span>
						<p></p>
						<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['enabledelhash'],true); ?> name="enabledelhash" id="enabledelhash" /><b>&nbsp;<?php _e('Enable <b><i>Del Hash</i></b>', self :: TEXTDOMAIN ); ?></b> <span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['enabledelhash']; ?>"></span>
						<p></p>
						<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['enableseelog'],true); ?> name="enableseelog" id="enableseelog" /><b>&nbsp;<?php _e('Enable <b><i>See last log</i></b>', self :: TEXTDOMAIN ); ?></b> <span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['enableseelog']; ?>"></span>
						<p></p>
						<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['disable_credits'],true); ?> name="disable_credits" id="disable_credits" /><b>&nbsp;<?php _e('Disable <i>WPeMatico Credits</i>', self :: TEXTDOMAIN ); ?></b> <span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['disable_credits']; ?>"></span>
						<p></p>
					</div>
				</div>
				
			</div>
			<?php include( self :: $dir . 'myplugins.php');	?>
			
		</div>
			
		<div id="post-body">
			<div id="post-body-content">
				<div id="normal-sortables" class="meta-box-sortables ui-sortable">

			<div id="imgs" class="postbox"><div class="handlediv" title="<?php _e('Click to toggle'); ?>"><br></div>
				<h3 class="hndle"><span><?php _e('Global Settings', self :: TEXTDOMAIN ); ?></span></h3>
				<div class="inside">
					<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['imgcache'],true); ?> name="imgcache" id="imgcache" />&nbsp;<b><label for="imgcache"><?php _e('Cache Images.', self :: TEXTDOMAIN ); ?></label></b><span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['imgcache']; ?>"></span>
					<div id="nolinkimg" style="padding-left:20px; <?php if (!$cfg['imgcache']) echo 'display:none;';?>">
						<input name="gralnolinkimg" id="gralnolinkimg" class="checkbox" value="1" type="checkbox" <?php checked($cfg['gralnolinkimg'],true); ?> /><label for="gralnolinkimg"><?php _e('No link to source images', self :: TEXTDOMAIN ); ?></label><span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['gralnolinkimg']; ?>"></span>
					</div>
					<p></p>
					<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['imgattach'],true); ?> name="imgattach" id="imgattach" /><b>&nbsp;<label for="imgattach"><?php _e('Attach Images to posts.', self :: TEXTDOMAIN ); ?></label></b><span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['imgattach']; ?>"></span>
					<div id="featimg" style="padding-left:20px; <?php if (!$cfg['imgattach']) echo 'display:none;';?>">
						<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['featuredimg'],true); ?> name="featuredimg" id="featuredimg" /><b>&nbsp;<label for="featuredimg"><?php _e('Enable first image found on content as Featured Image.', self :: TEXTDOMAIN ); ?></label></b> <small> Read about <a href="http://codex.wordpress.org/Post_Thumbnails" target="_Blank">Post_Thumbnails</a></small>
					</div>
				</div>
			</div>
		
			<div id="enablefeatures" class="postbox"><div class="handlediv" title="<?php _e('Click to toggle'); ?>"><br></div>
				<h3 class="hndle"><span><?php _e('Enable Features', self :: TEXTDOMAIN ); ?></span><span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['enablefeatures']; ?>"></span></h3>
				<div class="inside"> 
					<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['enablerewrite'],true); ?> name="enablerewrite" id="enablerewrite" /> <label for="enablerewrite"><?php _e('Enable <b><i>Rewrite</i></b> feature', self :: TEXTDOMAIN ); ?></label>
					<span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['enablerewrite']; ?>"></span>
					<p></p>
					<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['enableword2cats'],true); ?> name="enableword2cats" id="enableword2cats" /> <label for="enableword2cats"><?php _e('Enable <b><i>Words to Categories</i></b> feature', self :: TEXTDOMAIN ); ?></label>
					<span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['enableword2cats']; ?>"></span>
					<p></p>
					
					<?php if( ! $this->options['nonstatic'] ) : ?>
					
				</div>
			</div>
		
			<div id="PROfeatures" class="postbox"><div class="handlediv" title="<?php _e('Click to toggle'); ?>"><br></div>
				<h3 style="float:right; background-color: yellow;"><?php _e('ONLY AVAILABLE AT PRO VERSION.', self :: TEXTDOMAIN ); ?></h3>
				<h3 class="hndle" style="background-color: yellow;"><span><?php _e('PRO Features', self :: TEXTDOMAIN ); ?></span> <span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['PROfeatures']; ?>"></span></h3>
				<div class="inside"> 
						<!-- a href="http://etruel.com/downloads/wpematico-pro/" target="_Blank" title="Go to WPeMatico WebSite"><img style="background: transparent;height: 86%;position: absolute;margin-left: -10px;overflow: hidden;width: 100%;border: 1px solid #CCC;" src="<?php echo self :: $uri; ?>images/onlypro.png" title=""></a -->
					<p></p>
					<input class="checkbox" value="1" type="checkbox" disabled /> <?php _e('Enable <b><i>Keyword Filtering</i></b> feature', self :: TEXTDOMAIN ); ?> <span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['enablekwordf']; ?>"></span>
					<p></p>
					<input class="checkbox" value="1" type="checkbox" disabled /> <?php _e('Enable <b><i>Word count Filters</i></b> feature', self :: TEXTDOMAIN ); ?> <span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['enablewcf']; ?>"></span>
					<p></p>
					<input class="checkbox" value="1" type="checkbox" disabled /> <?php _e('Enable <b><i>Custom Title</i></b> feature', self :: TEXTDOMAIN ); ?> <span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['enablecustomtitle']; ?>"></span>
					<p></p>
					<input class="checkbox" value="1" type="checkbox" disabled /> <?php _e('Enable attempt to <b><i>Get Full Content</i></b> feature', self :: TEXTDOMAIN ); ?> <span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['fullcontent']; ?>"></span>
					<p></p>
					<input class="checkbox" value="1" type="checkbox" disabled /> <?php _e('Enable <b><i>Author per feed</i></b> feature', self :: TEXTDOMAIN ); ?> <span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['authorfeed']; ?>"></span>
					<p></p>
					<input class="checkbox" value="1" type="checkbox" disabled /> <?php _e('Enable <b><i>Import feed list</i></b> feature', self :: TEXTDOMAIN ); ?> <span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['importfeeds']; ?>"></span>
					<p></p>
					<input class="checkbox" value="1" type="checkbox" disabled /> <?php _e('Enable <b><i>Auto Tags</i></b> feature.', self :: TEXTDOMAIN ); ?> <span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['enabletags']; ?>"></span>
					<div id="badtags" style="margin-left:25px;">
					<b><label for="all_badtags"><?php _e('Bad Tags that will be not used on any post:', WPeMatico :: TEXTDOMAIN ); ?></label></b><br />
					<textarea style="width:500px;" disabled >some, tags, not, allowed</textarea><br />
					<?php echo __('Enter comma separated list of excluded Tags in all campaigns.', WPeMatico :: TEXTDOMAIN ); ?>
					</div><br />
					<input class="checkbox" value="1" type="checkbox" disabled /> <?php _e('Enable <b><i>Custom Fields</i></b> feature.', self :: TEXTDOMAIN ); ?> <span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['enablecfields']; ?>"></span>

					<?php endif; ?>
				</div>
			</div>
		
			<div id="advancedfetching" class="postbox"><div class="handlediv" title="<?php _e('Click to toggle'); ?>"><br></div>
				<h3 class="hndle"><span><?php _e('Advanced Fetching', self :: TEXTDOMAIN ); ?> <?php _e('(SimplePie Settings)', self :: TEXTDOMAIN ); ?></span></h3>
				<div class="inside">
					<p><b><?php _e('Test if SimplePie library works well on your server:', self :: TEXTDOMAIN ); ?></b>
						<a onclick="javascript:window.open(
							'<?php echo self :: $uri; ?>app/lib/sp_compatibility_test.php'
							,'SimplePie',
							'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=630, height=600'); return false;" 
							href="javascript:Void(0);">	<?php _e('Click here', self :: TEXTDOMAIN ); ?></a>. <small> <?php _e('(open in popup)', self :: TEXTDOMAIN ); ?></small>
					</p>
					<p></p>
					<label><input class="checkbox" value="1" type="checkbox" <?php checked($cfg['force_mysimplepie'],true); ?> name="force_mysimplepie" id="force_mysimplepie" /> <?php _e('Force <b><i>Custom Simplepie Library</i></b>', self :: TEXTDOMAIN ); ?></label>  <span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['mysimplepie']; ?>"></span>
					<p></p>
					<label><input class="checkbox" value="1" type="checkbox" <?php checked($cfg['set_stupidly_fast'],true); ?> name="set_stupidly_fast" id="set_stupidly_fast"  onclick="jQuery('#simpie').show();"  /> <?php _e('Set Simplepie <b><i>stupidly fast</i></b>', self :: TEXTDOMAIN ); ?></label>  <span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['stupidly_fast']; ?>"></span>
					<p></p>
					<div id="simpie" style="margin-left: 25px;<?php if ($cfg['set_stupidly_fast']) echo 'display:none;';?>">
						<input name="simplepie_strip_htmltags" id="simplepie_strip_htmltags" class="checkbox" value="1" type="checkbox" <?php checked($cfg['simplepie_strip_htmltags'],true); ?> />
						<label for="simplepie_strip_htmltags"><b><?php _e('Change SimplePie HTML tags to strip', self :: TEXTDOMAIN ); ?></b></label> <span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['strip_htmltags']; ?>"></span>
						<br />
						<textarea style="width:500px;" <?php disabled($cfg['simplepie_strip_htmltags'],false,true); ?> name="strip_htmltags" id="strip_htmltags" ><?php echo $cfg['strip_htmltags'] ; ?></textarea>
						<p></p>
						<input name="simplepie_strip_attributes" id="simplepie_strip_attributes" class="checkbox" value="1" type="checkbox" <?php checked($cfg['simplepie_strip_attributes'],true); ?> />
						<label for="simplepie_strip_attributes"><b><?php _e('Change SimplePie HTML attributes to strip', self :: TEXTDOMAIN ); ?></b></label>  <span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['strip_htmlattr']; ?>"></span>
						<br />
						<textarea style="width:500px;" <?php disabled($cfg['simplepie_strip_attributes'],false,true); ?> name="strip_htmlattr" id="strip_htmlattr" ><?php echo $cfg['strip_htmlattr']; ?></textarea>
					</div>
					<p></p>
					
					</div>
			</div>

			<div id="advancedfetching" class="postbox"><div class="handlediv" title="<?php _e('Click to toggle'); ?>"><br></div>
				<h3 class="hndle"><span><?php _e('Advanced Fetching', self :: TEXTDOMAIN ); ?></span></h3>
				<div class="inside">
					<p></p>
					<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['woutfilter'],true); ?> name="woutfilter" id="woutfilter" /> <?php _e('<b><i>Allow option on campaign for skip the content filters</i></b>', self :: TEXTDOMAIN ); ?><br />
					<div id="hlpspl" style="padding-left:20px;"><?php _e('NOTE: It is extremely dangerous to allow unfiltered content because there may be some vulnerability in the source code.', self :: TEXTDOMAIN ); ?> <?php _e('Use only with reliable sources.', self :: TEXTDOMAIN ); ?>
					<br /><?php _e('See How WordPress Processes Post Content: ', self :: TEXTDOMAIN ); ?><a href="http://codex.wordpress.org/How_WordPress_Processes_Post_Content" target="_blank">http://codex.wordpress.org/How_WordPress_Processes_Post_Content</a>
					<br />
					</div> 
					<p></p>
					<p><b><?php _e('Timeout running campaign:', self :: TEXTDOMAIN ); ?></b> <input name="campaign_timeout" type="text" value="<?php echo $cfg['campaign_timeout'];?>" class="small-text" /> Seconds.
					<span id="hlpspl" style="padding-left:20px;display: inline-block;"><?php _e('When a campaign running is interrupted, cannot be executed again until click "Clear Campaign".  This option clear campaign after this timeout then can run again on next scheduled cron. A value of "0" ignore this, means that remain until user make click.  Recommended 300 Seconds.', self :: TEXTDOMAIN ); ?>
					</span></p>
					<p></p>
					<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['allowduplicates'],true); ?> name="allowduplicates" id="allowduplicates" /><b>&nbsp;<?php echo '<label for="allowduplicates">' . __('Deactivate duplicate controls.', self :: TEXTDOMAIN ) . '</label>'; ?></b><br />
					<div id="hlpatt" style="padding-left:20px;">
						<b><?php _e('Allowing duplicated posts', self :: TEXTDOMAIN ); ?>:</b> <small><?php _e("There are two controls for duplicates, title of the post and a hash generated by last item's url obtained on campaign process.  When the running campaign found a duplicated post the process is interrupted because assume that all followed posts, are also duplicates.  You can disable these controls here.", self :: TEXTDOMAIN ); ?></small>
					</div>
					<p></p>
					<div id="enadup" style="padding-left:20px; <?php if (!$cfg['allowduplicates']) echo 'display:none;';?>">
						<small><?php _e('NOTE: If disable both controls, all items will be fetched again and again... and again, ad infinitum.  If you want allow duplicated titles, just activate "Allow duplicated titles".', self :: TEXTDOMAIN ); ?></small><br />
						<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['allowduptitle'],true); ?> name="allowduptitle" id="allowduptitle" /><b>&nbsp;<?php echo '<label for="allowduptitle">' . __('Allow duplicates titles.', self :: TEXTDOMAIN ) . '</label>'; ?></b><br />
						<input class="checkbox" value="1" type="checkbox" <?php checked($cfg['allowduphash'],true); ?> name="allowduphash" id="allowduphash" /><b>&nbsp;<?php echo '<label for="allowduphash">' . __('Allow duplicates hashes. (Not Recommended)', self :: TEXTDOMAIN ) . '</label>'; ?></b>
					</div>
					<p></p>
					<input name="jumpduplicates" id="jumpduplicates" class="checkbox" value="1" type="checkbox" <?php checked($cfg['jumpduplicates'],true); ?> />
					<label for="jumpduplicates"><b><?php _e('Jump Duplicates / Continue fetching.', self :: TEXTDOMAIN ); ?></b></label>  <span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['jumpduplicates']; ?>"></span>
					<p></p>
					<input name="disableccf" id="disableccf" class="checkbox" value="1" type="checkbox" <?php checked($cfg['disableccf'],true); ?> />
					<label for="disableccf"><b><?php _e('Disables Plugin Custom fields.', self :: TEXTDOMAIN ); ?></b></label>  <span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['disableccf']; ?>"></span>
					<br /> 

				</div>
			</div>
		
			<div id="disablewpcron" class="postbox"><div class="handlediv" title="<?php _e('Click to toggle'); ?>"><br></div>
				<h3 class="hndle"><span><?php _e('Cron and Scheduler Settings', self :: TEXTDOMAIN ); ?></span></h3>
				<div class="inside">
					<label><input class="checkbox" id="dontruncron" type="checkbox"<?php checked($cfg['dontruncron'],true);?> name="dontruncron" value="1"/> 
						<strong><?php _e('Disable WPeMatico schedulings', self :: TEXTDOMAIN ); ?></strong></label> <span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['dontruncron']; ?>"></span>
					<br />
					<div id="hlpcron" style="padding-left:20px;">
						<?php _e('You must set up a cron job that calls:', self :: TEXTDOMAIN ); ?><br />
						<span class="coderr b"><i> php -q <?php echo self :: $dir . "app/wpe-cron.php"; ?></i></span><br />
						<?php _e('or URL:', self :: TEXTDOMAIN ); ?> &nbsp;&nbsp;&nbsp;<span class="coderr b"><i><?php echo self :: $uri . "app/wpe-cron.php"; ?></i></span>
						<br /><br />
					</div>
					<label><input class="checkbox" id="disablewpcron" type="checkbox"<?php checked($cfg['disablewpcron'],true);?> name="disablewpcron" value="1"/> 
						<strong><?php _e('Disable all WP_Cron', self :: TEXTDOMAIN ); ?></strong></label>  <span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['disablewpcron']; ?>"></span>
					<div id="hlpcron2" style="padding-left:20px;">
						<?php _e('To run the wordpress cron with external cron you can set up a cron job that calls:', self :: TEXTDOMAIN ); ?><br />
						<span class="coderr b"><i> php -q <?php echo ABSPATH.'wp-cron.php'; ?></i></span><br /> 
						<?php _e('or URL:', self :: TEXTDOMAIN ); ?> &nbsp;&nbsp;&nbsp;<span class="coderr b"><i><?php echo trailingslashit(get_option('siteurl')).'wp-cron.php'; ?></i></span>
						<br /> 
						<div class="mphlp" style="margin-top: 10px;">
							<?php _e('This set <code>DISABLE_WP_CRON</code> to <code>true</code>, then the <a href="https://core.trac.wordpress.org/browser/tags/4.2.3/src/wp-includes/cron.php#L314" target="_blank">current cron process should be killed</a>.', self :: TEXTDOMAIN ); ?>
							<br /> 
							<?php _e('You can find more info about WP Cron and also few steps to configure external crons:', self :: TEXTDOMAIN ); ?>
							<a href="http://code.tutsplus.com/articles/insights-into-wp-cron-an-introduction-to-scheduling-tasks-in-wordpress--wp-23119" target="_blank"><?php _e('here', self :: TEXTDOMAIN ); ?></a>.
						</div>
					</div><br /> 
					<label><input class="checkbox" id="logexternalcron" type="checkbox"<?php checked($cfg['logexternalcron'],true);?> name="logexternalcron" value="1"/> 
						<strong><?php _e('Log file on external Cron', self :: TEXTDOMAIN ); ?></strong></label>  <span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['logexternalcron']; ?>"></span>
						<br /> 
				</div>
			</div>				

			<div id="emptytrashdiv" class="postbox"><div class="handlediv" title="<?php _e('Click to toggle'); ?>"><br></div>
				<h3 class="hndle"><span><?php _e('Other Tools', self :: TEXTDOMAIN ); ?></span></h3>
				<div class="inside">
				<div class="insidesec" style="border-right: 1px lightgrey solid; margin-right: 5px;padding-right: 7px; ">
					<label><input class="checkbox" id="emptytrashbutton" type="checkbox"<?php checked($cfg['emptytrashbutton'],true);?> name="emptytrashbutton" value="1"/> 
					<?php _e('Shows Button to empty trash on lists.', self :: TEXTDOMAIN ); ?></label>  <span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['emptytrashbutton']; ?>"></span>
					<br />
					<?php _e('Select (custom) post types you want.', self :: TEXTDOMAIN ); ?>
					<br />
					<div id="hlptrash" style="padding-left:20px; <?php if (!$cfg['emptytrashbutton']) echo 'display:none;';?>">
					<?php
						// publicos y privados para que pueda mostrar el boton en todos
						$args=array( 'public'   => false );
						$args=array( );
						$output = 'names'; // names or objects
						$output = 'objects'; // names or objects
						$cpostypes = $cfg['cpt_trashbutton'];
						//unset($cpostypes['attachment']);
						$post_types=get_post_types($args,$output);
						foreach ($post_types  as $post_type_obj ) {
							$post_type = $post_type_obj->name;
							$post_label = $post_type_obj->labels->name;
							if ($post_type=='revision') continue;  // ignore 'attachment'
							if ($post_type=='nav_menu_item') continue;  // ignore 'attachment'
							echo '<div><input type="checkbox" class="checkbox" name="cpt_trashbutton['.$post_type.']" value="1" '; 
							if(!isset($cpostypes[$post_type])) $cpostypes[$post_type] = false;
							checked( $cpostypes[$post_type],true);
							echo ' /> '. __( $post_label ) .' ('. __( $post_type ) .')</div>';
						}

						
					?>
					</div><br /> 
				</div>
				<div id="enabledashboard" class="insidesec">
					<?php /*<h3 class="hndle"><span><?php _e('Dashboard widget', self :: TEXTDOMAIN ); ?></span></h3>
					<div class="inside">
					*/ ?>
					<label><input class="checkbox" value="1" type="checkbox" <?php checked($cfg['disabledashboard'],true); ?> name="disabledashboard" id="disabledashboard" /> <?php _e('Disable <b><i>WP Dashboard Widget</i></b>', self :: TEXTDOMAIN ); ?></label><span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['disabledashboard']; ?>"></span>
						<div>
							<label id="roleslabel" <?php if ($cfg['disabledashboard']) echo 'style="display:none;"';?>><?php _e('User roles to show Dashboard widget:', self :: TEXTDOMAIN ); ?></label>
							<div id="roles" <?php if ($cfg['disabledashboard']) echo 'style="display:none;"';?>>
							<?php
								global $wp_roles;
								if(!isset($cfg['roles_widget'])) $cfg['roles_widget'] = array( "administrator" => "administrator" );
								$role_select = '<input type="hidden" name="role_name[]" value="administrator" />';
								foreach( $wp_roles->role_names as $role => $name ) {
									$name = _x($name, self :: TEXTDOMAIN );
									if ( $role != 'administrator' ) {
										if ( array_search($role, $cfg['roles_widget']) ) {
											$checked = 'checked="checked"';
										}else{
											$checked = '';
										}
									  $role_select .= '<label style="margin:0 5px;"><input style="margin:0 5px;" ' . $checked . ' type="checkbox" name="role_name[]" value="'.$role .'" />'. $name . '</label>';
									}
								}
								echo $role_select;
							?>
							</div>
						</div>
					<?php /*</div>
					*/ ?>
					<br /> 
				</div>
				</div>
			</div>				

				
			<div class="postbox inside">
				<div class="inside">
					<p>
					<input type="submit" class="button-primary" name="submit" value="<?php _e( 'Save settings', self :: TEXTDOMAIN );?>" />
					</p>
				</div>
			</div>
			</div>
			</div>
		</div>
		</form>
	</div>
</div>
<script type="text/javascript" language="javascript">
	jQuery('#mailsndemail').blur(function() {
		var x = jQuery(this).val();
		var atpos = x.indexOf("@");
		var dotpos = x.lastIndexOf(".");
	  if (atpos< 1 || dotpos<atpos+2 || dotpos+2>=x.length) {
		jQuery('#mailmsg').text("<?php _e( 'Invalid email.', self :: TEXTDOMAIN );?>");
		return false;
	  }else{
		jQuery('#mailmsg').text("");
		return true;
	  }
	});

//jQuery(document).ready(function($){
	jQuery('#imgcache').click(function() {
		if ( true == jQuery('#imgcache').is(':checked')) {
			jQuery('#nolinkimg').fadeIn();
		} else {
			jQuery('#nolinkimg').fadeOut();
		}
	});
	jQuery('#imgattach').click(function() {
		if ( true == jQuery('#imgattach').is(':checked')) {
			jQuery('#featimg').fadeIn();
		} else {
			jQuery('#featimg').fadeOut();
		}
	});
	jQuery('#allowduplicates').click(function() {
		if ( true == jQuery('#allowduplicates').is(':checked')) {
			jQuery('#enadup').fadeIn();
		} else {
			jQuery('#allowduptitle').removeAttr("checked");
			jQuery('#allowduphash').removeAttr("checked");
			jQuery('#enadup').fadeOut();
		}
	});
	jQuery('#disabledashboard').click(function() {
		if ( true == jQuery('#disabledashboard').is(':checked')) {
			jQuery('#roles').fadeOut();
			jQuery('#roleslabel').fadeOut();
		} else {
			jQuery('#roles').fadeIn();
			jQuery('#roleslabel').fadeIn();
		}
	});
	jQuery('.pshow').click(function() {
		if(jQuery('.password').prop('type')=="text")
			jQuery('.password').prop('type','password');
		else
			jQuery('.password').prop('type','text');
	});
	jQuery('#mailmethod').change(function() {
		if ( true == jQuery('#mailmethod > option[value="SMTP"]').prop("selected") ) {
			jQuery('#mailsendmail').fadeOut();
			jQuery('#mailsmtp').fadeIn();
		} else if ( true == jQuery('#mailmethod > option[value="Sendmail"]').prop("selected") ) {
			jQuery('#mailsendmail').fadeIn();
			jQuery('#mailsmtp').fadeOut();
		} else {
			jQuery('#mailsendmail').fadeOut();
			jQuery('#mailsmtp').fadeOut();
		}
	});
	jQuery('#set_stupidly_fast').click(function() {
		if ( false == jQuery('#set_stupidly_fast').is(':checked')) {
			jQuery('#simpie').fadeIn();
		} else {
			jQuery('#simplepie_strip_attributes').removeAttr("checked");
			jQuery('#simplepie_strip_htmltags').removeAttr("checked");
			jQuery('#simpie').fadeOut();
		}
	});
	jQuery('#simplepie_strip_htmltags').click(function() {
		if ( false == jQuery('#simplepie_strip_htmltags').is(':checked')) {
			jQuery('#strip_htmltags').attr('disabled',true);
		} else {
			jQuery('#strip_htmltags').removeAttr("disabled");
		}
	});
	jQuery('#simplepie_strip_attributes').click(function() {
		if ( false == jQuery('#simplepie_strip_attributes').is(':checked')) {
			jQuery('#strip_htmlattr').attr('disabled',true);
		} else {
			jQuery('#strip_htmlattr').removeAttr("disabled");
		}
	});
	jQuery('#emptytrashbutton').click(function() {
		if ( true == jQuery('#emptytrashbutton').is(':checked')) {
			jQuery('#hlptrash').fadeIn();
		} else {
			jQuery('#hlptrash').fadeOut();
		}
	});
	jQuery(function(){
		jQuery(".help_tip").tipTip({maxWidth: "300px", edgeOffset: 5,fadeIn:50,fadeOut:50, keepAlive:true, defaultPosition: "right"});
	});
//}
</script>