<?php 
// don't load directly 
if ( !defined('ABSPATH') ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( class_exists( 'WPeMatico_Campaign_edit_functions' ) ) return;

class WPeMatico_Campaign_edit_functions {
	public static function create_meta_boxes() {
		global $post,$campaign_data, $cfg, $helptip; 
		$campaign_data = WPeMatico :: get_campaign ($post->ID);
		$campaign_data = apply_filters('wpematico_check_campaigndata', $campaign_data);
		$cfg = get_option(WPeMatico :: OPTION_KEY);
		$cfg = apply_filters('wpematico_check_options', $cfg);

   		$helptip = array( 
			'rewrites' => __('The rewrite feature allow you to replace words or phrases of the content with the text you specify.', WPeMatico :: TEXTDOMAIN ).' '.
				__('Also can use this feature to make simple links from some words with origin and re-link fields.', WPeMatico :: TEXTDOMAIN ).'<br>'.
				__('For examples click on [?] below.', WPeMatico :: TEXTDOMAIN ),
			'feeds'	=> __('You must type at least one feed url.', WPeMatico :: TEXTDOMAIN ).'  '.
				__('(Less feeds equal less used resources when fetching).', WPeMatico :: TEXTDOMAIN ).' '.
				__('Type the domain name to try to autodetect the feed url.', WPeMatico :: TEXTDOMAIN ),
			'itemfetch'	=> __('Items to fetch PER every feed above.', WPeMatico :: TEXTDOMAIN ).'  '.
				__('Recommended values are between 3 and 5 fetching more times to not lose items.', WPeMatico :: TEXTDOMAIN ).'  '.
				__('Set it to 0 for unlimited.', WPeMatico :: TEXTDOMAIN ),
			'itemdate'	=> __('Use the original date from the post instead of the time the post is created by WPeMatico.', WPeMatico :: TEXTDOMAIN ).'  '.
				__('To avoid incoherent dates due to lousy setup feeds, WPeMatico will use the feed date only if these conditions are met:', WPeMatico :: TEXTDOMAIN ).'  '.
				'<ul style=\'list-style-type: square;margin:0 0 5px 20px;font:0.92em "Lucida Grande","Verdana";\'>
				<li>'. __('The feed item date is not too far in the past (specifically, as much time as the campaign frequency).', WPeMatico :: TEXTDOMAIN ).' </li>
				<li>'. __('The fetched feed item date is not in the future.', WPeMatico :: TEXTDOMAIN ).' </li></ul>',
			'itemautor' => __('The created posts will be assigned to this author.', WPeMatico :: TEXTDOMAIN ),
			'linktosource' => __('This option make the title permalink to original URL.', WPeMatico :: TEXTDOMAIN ).'<br />'
				. __('This feature will be ignored if you deactivate Campaign Custom Fields on settings.', WPeMatico :: TEXTDOMAIN ),
			'striplinks' => __('This option take out clickable links from content, leaving just the text.', WPeMatico :: TEXTDOMAIN ),
			'postsauthor' => __('The posts created by this campaign will be assigned to this author.', WPeMatico :: TEXTDOMAIN ),
			'allowpings' => __('Allows pinbacks and trackbacks in the posts created by this campaign.', WPeMatico :: TEXTDOMAIN ),
			'commentstatus' => __('Comments options to these posts.', WPeMatico :: TEXTDOMAIN ),
			'woutfilter' => '<b><i>'.__('Skip the Wordpress post content filters.', WPeMatico :: TEXTDOMAIN ).'</i></b>'.
				'<br>'.__('Save the content exactly how to Simplepie gets it.', WPeMatico :: TEXTDOMAIN ).
				'<br>'.__('Not recommended.', WPeMatico :: TEXTDOMAIN ),
			
			'schedule' => __('Activate Automatic Mode.', WPeMatico :: TEXTDOMAIN ).
				'<br>'.__('You can define here on what times you wants to fetch this feeds.  This has 5 min. of margin on WP-cron schedules.  If you set up an external cron en WPeMatico Settings, you\'ll get better preciseness.', WPeMatico :: TEXTDOMAIN ),
			
			//images
			'imgoptions' => __('This features will be overridden only for this campaign the general Settings options for images.', WPeMatico :: TEXTDOMAIN ),
			'cancel_imgcache' => __('Checked do not upload the images to your server just for the posts of this campaign.', WPeMatico :: TEXTDOMAIN ),
			'imgcache' => __('All images found in &lt;img&gt; tags will be updated to your current WP Upload Folder, added to Wordpress Media and replaced urls in content. Otherwise remains links to source host server.', WPeMatico :: TEXTDOMAIN ),
			'imgattach'	=> __('All images will be attached to the owner post in WP media library; necessary for Featured image, but if you see that the job process is too slowly you can deactivate this here.', WPeMatico :: TEXTDOMAIN ),
			'gralnolinkimg' => __('If selected and image upload get error, then delete the \'src\' attribute of the &lt;img&gt;. Check this for don\'t link images from external sites.', WPeMatico :: TEXTDOMAIN ),
			//post template
			'postemplate' => __('Campaign post template allow to modify the content fetched by adding extra information, such as text, images, campaign data, etc. before save it as post content.', WPeMatico :: TEXTDOMAIN ).
				'<br>'.__('You can use some tags that will be replaced for current value. Click on [?] below to see description and examples on how to use this feature.', WPeMatico :: TEXTDOMAIN ),
			//word to category
			'wordcateg' => __('Allow to assign a singular category to the post if a word is found in the content.', WPeMatico :: TEXTDOMAIN ),
			
			'category' => __('Add categories from the source post and/or assign already existing categories.', WPeMatico :: TEXTDOMAIN ),
			'autocats' => __('If categories are found on source item, these categories will be added to the post; If category does not exist, then will be created.', WPeMatico :: TEXTDOMAIN ),
			'tags' => __('You can insert here the tags for every post of this campaign.', WPeMatico :: TEXTDOMAIN ),
			'sendlog' => __('An email will be sent with the events of campaign fetching. You can also filter the emails only if an error occurred or left blank to not send emails of this campaign.', WPeMatico :: TEXTDOMAIN ),
			'postformat' => __('If your theme supports post formats you can select one for the posts of this campaign, otherwise left on Standard.', WPeMatico :: TEXTDOMAIN ),
		);

		foreach($helptip as $key => $value){
			$helptip[$key] = htmlentities($value);
		}

	//	add_meta_box( $id, $title, $callback, $post_type, $context, $priority, $callback_args );
		add_meta_box( 'format-box',__('Campaign Posts Format',WPeMatico::TEXTDOMAIN). '<span class="mya4_sprite infoIco help_tip" title="'. $helptip['postformat'].'"></span>', array( 'WPeMatico_Campaign_edit' ,'format_box'),'wpematico','side', 'default' );
		add_meta_box( 'cat-box',__('Campaign Categories',WPeMatico::TEXTDOMAIN). '<span class="mya4_sprite infoIco help_tip" title="'. $helptip['category'].'"></span>', array( 'WPeMatico_Campaign_edit' ,'cat_box'),'wpematico','side', 'default' );
		add_meta_box( 'tags-box', __('Tags generation', WPeMatico :: TEXTDOMAIN ). '<span class="mya4_sprite infoIco help_tip" title="'. $helptip['tags'].'"></span>', array(  'WPeMatico_Campaign_edit'  ,'tags_box' ),'wpematico','side', 'default' );
		add_meta_box( 'log-box', __('Send log', WPeMatico :: TEXTDOMAIN ). '<span class="mya4_sprite infoIco help_tip" title="'. $helptip['sendlog'].'"></span>', array(  'WPeMatico_Campaign_edit'  ,'log_box' ),'wpematico','side', 'default' );
		add_meta_box( 'feeds-box', __('Feeds for this Campaign', WPeMatico :: TEXTDOMAIN ). '<span class="mya4_sprite infoIco help_tip" title="'. $helptip['feeds'].'"></span>', array(  'WPeMatico_Campaign_edit'  ,'feeds_box' ),'wpematico','normal', 'default' );
		add_meta_box( 'options-box', __('Options for this campaign', WPeMatico :: TEXTDOMAIN ), array(  'WPeMatico_Campaign_edit'  ,'options_box' ),'wpematico','normal', 'default' );
		add_meta_box( 'images-box', __('Options for images', WPeMatico :: TEXTDOMAIN ). '<span class="mya4_sprite infoIco help_tip" title="'. $helptip['imgoptions'].'"></span>', array(  'WPeMatico_Campaign_edit'  ,'images_box' ),'wpematico','normal', 'default' );
		add_meta_box( 'template-box', __('Post Template', WPeMatico :: TEXTDOMAIN ). '<span class="mya4_sprite infoIco help_tip" title="'. $helptip['postemplate'].'"></span>', array(  'WPeMatico_Campaign_edit'  ,'template_box' ),'wpematico','normal', 'default' );
		if ($cfg['enableword2cats'])   // Si está habilitado en settings, lo muestra 
			add_meta_box( 'word2cats-box', __('Word to Category options', WPeMatico :: TEXTDOMAIN ). '<span class="mya4_sprite infoIco help_tip" title="'. $helptip['wordcateg'].'"></span>', array(  'WPeMatico_Campaign_edit'  ,'word2cats_box' ),'wpematico','normal', 'default' );
		if ($cfg['enablerewrite'])   // Si está habilitado en settings, lo muestra 
			add_meta_box( 'rewrite-box', __('Rewrite options', WPeMatico :: TEXTDOMAIN ). '<span class="mya4_sprite infoIco help_tip" title="'. $helptip['rewrites'].'"></span>', array(  'WPeMatico_Campaign_edit'  ,'rewrite_box' ),'wpematico','normal', 'default' );
		//***** Call nonstatic
		if( $cfg['nonstatic'] ) { NoNStatic :: meta_boxes($campaign_data, $cfg); }
		// Publish Meta_box edited
		add_action('post_submitbox_start', array( __CLASS__ ,'post_submitbox_start')); 
	
	}
	
	
		//*************************************************************************************
	public static function format_box( $post, $box ) {
		global $post, $campaign_data, $helptip;
		if ( current_theme_supports( 'post-formats' ) ) :
		$post_formats = get_theme_support( 'post-formats' );
		$campaign_post_format = $campaign_data['campaign_post_format'];

		if ( is_array( $post_formats[0] ) ) :
			$campaign_post_format = ( @!$campaign_post_format )? '0' : $campaign_data['campaign_post_format'];
		?>
		<div id="post-formats-select">
			<input type="radio" name="campaign_post_format" class="post-format" id="post-format-0" value="0" <?php checked( $campaign_post_format, '0' ); ?> /> <label for="post-format-0" class="post-format-icon post-format-standard"><?php echo get_post_format_string( 'standard' ); ?></label>
			<?php foreach ( $post_formats[0] as $format ) : ?>
				<br /><input type="radio" name="campaign_post_format" class="post-format" id="post-format-<?php echo esc_attr( $format ); ?>" value="<?php echo esc_attr( $format ); ?>" <?php checked( $campaign_post_format, $format ); ?> /> <label for="post-format-<?php echo esc_attr( $format ); ?>" class="post-format-icon post-format-<?php echo esc_attr( $format ); ?>"><?php echo esc_html( get_post_format_string( $format ) ); ?></label>
			<?php endforeach; ?><br />
		</div>
		<?php endif; endif;
	}
	
		//************************************************************************************* 
	public static function rewrite_box( $post ) { 
		global $post, $campaign_data, $helptip;
		$campaign_rewrites = $campaign_data['campaign_rewrites'];
		?>
		<div class="LightPink inmetabox">
		<p class="he20">
		<span class="left"><?php _e('Replaces words or phrases by other that you want or turns into link.', WPeMatico :: TEXTDOMAIN ) ?></span>
		<label class="right" title="<?php _e('A little Help', WPeMatico :: TEXTDOMAIN ); ?>" onclick="jQuery('#hlprwg').fadeToggle();"><?php _e('Help', WPeMatico :: TEXTDOMAIN ) ?><span class="m4 ui-icon QIco right"></span></label>
		</p>
		<p class="mphlp"><span class="srchbdr0 hide" id="hlprwg">
			<b><?php _e('Basics:', WPeMatico :: TEXTDOMAIN ); ?></b> <?php _e('The rewriting settings allow you to replace parts of the content with the text you specify.', WPeMatico :: TEXTDOMAIN ); ?><br />
			<b><?php _e('Basic rewriting:', WPeMatico :: TEXTDOMAIN ); ?></b><br />
			<?php _e('To replace all occurrences the word ass with butt, simply type ass in the "origin field", and butt in "rewrite to".', WPeMatico :: TEXTDOMAIN ); ?><br />
			<b><?php _e('Title:', WPeMatico :: TEXTDOMAIN ); ?></b><br />
			<?php _e('If you check "Title" checkbox only replace on title. If you un-check "Title" only replace on content. you must insert twice if you want to replace on both fields.', WPeMatico :: TEXTDOMAIN ); ?><br />
			<b><?php _e('Relinking:', WPeMatico :: TEXTDOMAIN ); ?></b><br />
			<?php _e('If you want to find all occurrences of google and make them link to Google, just type google in the "origin field" and http://google.com in the "relink to" field.', WPeMatico :: TEXTDOMAIN ); ?><br />
			<b><?php _e('Regular expressions', WPeMatico :: TEXTDOMAIN ); ?></b><br />
			<?php _e('For advanced users, regular expressions are supported. Using this will allow you to make more powerful replacements. Take multiple word replacements for example. Instead of using many rewriting boxes to replace ass and arse with butt, you can use the | operator: (ass|arse).', WPeMatico :: TEXTDOMAIN ); ?>
		</span></p>
		<div id="rewrites_edit" class="inlinetext">		
			<?php for ($i = 0; $i < count($campaign_rewrites['origin']); $i++) : ?>			
			<div class="<?php if(($i % 2) == 0) echo 'bw'; else echo 'lightblue'; ?> <?php if($i==count($campaign_rewrites['origin'])) echo 'hide'; ?>">
				<div class="pDiv jobtype-select p7" id="nuevorew">
					<div id="rw1" class="wi30 left p4">
						<?php _e('Origin:','wpematico') ?>&nbsp;&nbsp;&nbsp;&nbsp;
						<input name="campaign_word_option_title[<?php echo $i; ?>]" id="campaign_word_option_title" class="checkbox" value="1" type="checkbox"<?php checked($campaign_rewrites['title'][$i],true) ?> onclick="relink=jQuery(this).parent().parent().children('#rw3');if(true==jQuery(this).is(':checked')) relink.fadeOut(); else relink.fadeIn();"/> <?php _e('Title','wpematico') ?>
						<input name="campaign_word_option_regex[<?php echo $i; ?>]" id="campaign_word_option_regex" class="checkbox" value="1" type="checkbox"<?php checked($campaign_rewrites['regex'][$i],true) ?> /> <?php _e('RegEx','wpematico') ?>
						<textarea class="large-text he35" id="campaign_word_origin" name="campaign_word_origin[<?php echo $i; ?>]" /><?php echo stripslashes($campaign_rewrites['origin'][$i]) ?></textarea>
					</div>
					<div class="wi30 left p4">
						 <?php _e('Rewrite to:','wpematico') ?>
						<textarea class="large-text he35" id="campaign_word_rewrite" name="campaign_word_rewrite[<?php echo $i; ?>]" /><?php echo stripslashes($campaign_rewrites['rewrite'][$i]) ?></textarea>
					</div>
					<div id="rw3" class="wi30 left p4" <?php if(checked($campaign_rewrites['title'][$i],true,false)) echo 'style="display:none"'; ?>>
						 <?php _e('ReLink to:','wpematico') ?>
						<textarea class="large-text he35" id="campaign_word_relink" name="campaign_word_relink[<?php echo $i; ?>]" /><?php echo stripslashes($campaign_rewrites['relink'][$i]) ?></textarea>
					</div>
					<div class="m7">
						<span class="" id="w2cactions">
							<label title="<?php _e('Delete this item', WPeMatico :: TEXTDOMAIN ); ?>" onclick=" jQuery(this).parent().parent().parent().children('#rw1').children('#campaign_word_origin').text(''); jQuery(this).parent().parent().parent().fadeOut();disable_run_now();" class="right ui-icon redx_circle"></label>
						</span>
					</div>
				</div>
			</div>
			<?php endfor ?>
			<input id="rew_max" value="<?php echo $i; ?>" type="hidden" name="rew_max">
			
		  </div>
		  <div id="paging-box">		  
				<a href="JavaScript:void(0);" class="button-primary left m4" id="addmorerew" style="font-weight: bold; text-decoration: none;"><?php _e('Add more', WPeMatico :: TEXTDOMAIN ); ?>.</a>
		  </div>
		</div>

		<?php 
	}
	
	//**************************************************************************
	public static function word2cats_box( $post ) { 
		global $post, $campaign_data, $helptip;
		$campaign_wrd2cat = $campaign_data['campaign_wrd2cat'];
		?> 
		<div class="Papaya inmetabox">
		<p class="he20">
			<span class="left"><?php _e('Assigning categories based on content words.', WPeMatico :: TEXTDOMAIN ) ?></span> 
			<label class="right" title="<?php _e('A little Help', WPeMatico :: TEXTDOMAIN ); ?>" onclick="jQuery('#hlpwtoc').fadeToggle();"><?php _e('Help', WPeMatico :: TEXTDOMAIN ) ?><span class="m4 ui-icon QIco right"></span></label>
		</p>	
		<p class="mphlp"><span class="srchbdr0 hide" id="hlpwtoc">
			<b><?php _e('Basics:', WPeMatico :: TEXTDOMAIN ); ?></b> <?php _e('The Word to Category option allow you to assign singular category to the post.', WPeMatico :: TEXTDOMAIN ); ?><br />
			<b><?php _e('Example:', WPeMatico :: TEXTDOMAIN ); ?></b><br />
			<?php _e('If the post content contain the word "motor" and then you want assign the post to category "Engines", simply type "motor" in the "Word" field, and select "Engine" in Categories combo.', WPeMatico :: TEXTDOMAIN ); ?><br />
			<b><?php _e('Regular Expressions', WPeMatico :: TEXTDOMAIN ); ?></b><br />
			<?php _e('For advanced users, regular expressions are supported. Using this will allow you to make more powerful replacements. Take multiple word replacements for example. Instead of using many Word2Cat boxes to assign motor and car to Engines, you can use the | operator: (motor|car). If you want Case insensitive on RegEx, add "/i" at the end of RegEx.', WPeMatico :: TEXTDOMAIN ); ?>
		<br /></span></p>
		<div id="wrd2cat_edit" class="inlinetext">		
			<?php for ($i = 0; $i <= count($campaign_wrd2cat['word']); $i++) : ?>			
			<div class="<?php if(($i % 2) == 0) echo 'bw'; else echo 'lightblue'; ?> <?php if($i==count($campaign_wrd2cat['word'])) echo 'hide'; ?>">
				<div class="pDiv jobtype-select p7" id="nuevow2c">
					<div id="w1" style="float:left;">
						<?php _e('Word:', WPeMatico :: TEXTDOMAIN ) ?> <input type="text" size="25" class="regular-text" id="campaign_wrd2cat" name="campaign_wrd2cat[word][<?php echo $i; ?>]" value="<?php echo stripslashes(@$campaign_wrd2cat['word'][$i]); ?>" /><br />
						<input name="campaign_wrd2cat[regex][<?php echo $i; ?>]" id="campaign_wrd2cat_regex" class="checkbox w2cregex" value="1" type="checkbox"<?php checked($campaign_wrd2cat['regex'][$i],true) ?> /> <?php _e('RegEx', WPeMatico :: TEXTDOMAIN ) ?>
						<input <?php echo ($campaign_wrd2cat['regex'][$i]) ? 'disabled' : '';?> name="campaign_wrd2cat[cases][<?php echo $i; ?>]" id="campaign_wrd2cat_cases" class="checkbox w2ccases" value="1" type="checkbox"<?php checked($campaign_wrd2cat['cases'][$i],true) ?> /> <?php _e('Case sensitive', WPeMatico :: TEXTDOMAIN ) ?>
					</div>
					<div id="c1">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						<?php _e('To Category:', WPeMatico :: TEXTDOMAIN ) ?>
						<?php 
						$catselected='selected='.$campaign_wrd2cat['w2ccateg'][$i];
						$catname="name=campaign_wrd2cat[w2ccateg][".$i."]";
						$catid="id=campaign_wrd2cat_category_".$i;
						wp_dropdown_categories('hide_empty=0&hierarchical=1&show_option_none='.__('Select category', WPeMatico :: TEXTDOMAIN ).'&'.$catselected.'&'.$catname.'&'.$catid);
						?>
						<span class="wi10" id="w2cactions">
							<label title="<?php _e('Delete this item', WPeMatico :: TEXTDOMAIN ); ?>" onclick=" jQuery(this).parent().parent().parent().children('#w1').children('#campaign_wrd2cat').attr('value',''); jQuery(this).parent().parent().parent().fadeOut();disable_run_now();" class="right ui-icon redx_circle"></label>
					</span>
					</div>
				</div>
			</div>
			<?php endfor ?>
			<input id="wrd2cat_max" value="<?php echo $i; ?>" type="hidden" name="wrd2cat_max">
			
		  </div>
		  <div id="paging-box">
				<a href="JavaScript:void(0);" class="button-primary left m4" id="addmorew2c" style="font-weight: bold; text-decoration: none;"><?php _e('Add more', WPeMatico :: TEXTDOMAIN ); ?>.</a>
		  </div>
		</div>

		<?php 
	}
	
	
	//*************************************************************************************
	public static function template_box( $post ) { 
		global $post, $campaign_data, $cfg, $helptip;
		$campaign_enable_template = $campaign_data['campaign_enable_template'];
		$campaign_template = $campaign_data['campaign_template'];
		//$cfg = get_option(WPeMatico :: OPTION_KEY);
		?>
		<div class="lightblue inmetabox">
			<p class="he20">
				<?php _e('Modify, manage or add extra content to every post fetched.', WPeMatico :: TEXTDOMAIN ) ?>
			</p>
		
		<div id="wpe_post_template_edit" class="inlinetext">
			<input name="campaign_enable_template" id="campaign_enable_template" class="checkbox" value="1" type="checkbox"<?php checked($campaign_enable_template,true) ?> />
			<label for="campaign_enable_template"> <?php _e('Enable Post Template', WPeMatico :: TEXTDOMAIN ) ?></label>
			<textarea class="large-text" id="campaign_template" name="campaign_template" /><?php echo stripslashes($campaign_template) ?></textarea>
			<p class="he20"><span id="tags_note" class="note left"><?php _e('Allowed tags', WPeMatico :: TEXTDOMAIN ); ?>: </span>
			<label title="<?php _e('A little Help', WPeMatico :: TEXTDOMAIN ); ?>" onclick="jQuery('#tags_list').fadeToggle(); jQuery('#tags_list_det').fadeToggle();" class="m4 ui-icon QIco left"></label></p>
			<p id="tags_list" style="border-left: 3px solid #EEEEEE; color: #999999; font-size: 11px; padding-left: 6px;margin-top: 0;">
				<span class="tag">{content}</span>, <span class="tag">{title}</span>, <span class="tag">{image}</span>, <span class="tag">{author}</span>, <span class="tag">{authorlink}</span>, <span class="tag">{permalink}</span>, <span class="tag">{feedurl}</span>, <span class="tag">{feedtitle}</span>, <span class="tag">{feeddescription}</span>, <span class="tag">{feedlogo}</span>, <span class="tag">{campaigntitle}</span>, <span class="tag">{campaignid}</span>
			</p>
			<div id="tags_list_det" style="display: none;">
				<b><?php _e('Supported tags', WPeMatico :: TEXTDOMAIN ); ?></b>
				<p><?php _e('A tag is a piece of text that gets replaced dynamically when the post is created. Currently, these tags are supported:', WPeMatico :: TEXTDOMAIN ); ?></p>
				<ul style='list-style-type: square;margin:0 0 5px 20px;font:0.92em "Lucida Grande","Verdana";'>
				  <li><strong class="tag">{content}</strong> <?php _e('The feed item content.', WPeMatico :: TEXTDOMAIN ); ?> </li>
				  <li><strong class="tag">{title}</strong> <?php _e('The feed item title.', WPeMatico :: TEXTDOMAIN ); ?> </li>
				  <li><strong class="tag">{image}</strong> <?php _e('Put the featured image on content.', WPeMatico :: TEXTDOMAIN ); ?> </li>
				  <li><strong class="tag">{author}</strong> <?php _e('The feed item author.', WPeMatico :: TEXTDOMAIN ); ?> </li>
				  <li><strong class="tag">{authorlink}</strong> <?php _e('The feed item author link (If exist).', WPeMatico :: TEXTDOMAIN ); ?> </li>
				  <li><strong class="tag">{permalink}</strong> <?php _e('The feed item permalink.', WPeMatico :: TEXTDOMAIN ); ?> </li>
				  <li><strong class="tag">{feedurl}</strong> <?php _e('The feed URL.', WPeMatico :: TEXTDOMAIN ); ?> </li>
				  <li><strong class="tag">{feedtitle}</strong> <?php _e('The feed title.', WPeMatico :: TEXTDOMAIN ); ?> </li>
				  <li><strong class="tag">{feeddescription}</strong> <?php _e('The description of the feed.', WPeMatico :: TEXTDOMAIN ); ?> </li>
				  <li><strong class="tag">{feedlogo}</strong> <?php _e('The feed\'s logo image URL.', WPeMatico :: TEXTDOMAIN ); ?> </li>
				  <li><strong class="tag">{campaigntitle}</strong> <?php _e('This campaign title', WPeMatico :: TEXTDOMAIN ); ?> </li>
				  <li><strong class="tag">{campaignid}</strong> <?php _e('This campaign ID.', WPeMatico :: TEXTDOMAIN ); ?> </li>
				</ul>
				<p><a name="examples" style="cursor: hand;cursor: pointer;" title="<?php _e('Some examples to help you to create custom post template.', WPeMatico :: TEXTDOMAIN ); ?>" onclick="jQuery('#tags_list_examples').fadeToggle();"><b><?php _e('Examples:', WPeMatico :: TEXTDOMAIN ); ?></b></a></p>
				<div id="tags_list_examples" style="display: none;">
					<span><?php _e('If you want to add a link to the source at the bottom of every post and the author, the post template would look like this:', WPeMatico :: TEXTDOMAIN ); ?></span>
					<div class="code">{content}<br>&lt;a href="{permalink}"&gt;<?php _e('Go to Source', WPeMatico :: TEXTDOMAIN ); ?>&lt;/a&gt;&lt;br /&gt;<br>Author: {author}</div>
					<p><em>{content}</em> <?php _e('will be replaced with the feed item content', WPeMatico :: TEXTDOMAIN ); ?>, <em>{permalink}</em> <?php _e('by the source feed item URL, which makes it a working link and', WPeMatico :: TEXTDOMAIN ); ?> <em>{author}</em> <?php _e('with the original author of the feed item.', WPeMatico :: TEXTDOMAIN ); ?></p>
					<span><?php _e('Also you can add a gallery with three columns with all thumbnails images clickables at the bottom of every content, but before source link and author name, the post template would look like this:', WPeMatico :: TEXTDOMAIN ); ?></span>
					<div class="code">{content}<br>[gallery link="file" columns="3"]<br>&lt;a href="{permalink}"&gt;<?php _e('Go to Source', WPeMatico :: TEXTDOMAIN ); ?>&lt;/a&gt;&lt;br /&gt;<br>Author: {author}</div>
					<p><em>[gallery link="file" columns="3"]</em> <?php _e('it\'s a WP shortcode for insert a gallery into the post.  You can use any shortcode here; will be processed by Wordpress.', WPeMatico :: TEXTDOMAIN ); ?></p>
				</div>
			</div>

		</div>
		<?php if( $cfg['nonstatic'] ) { NoNStatic :: last_html_tag($post, $cfg); } ?>
		</div>

		<?php
	}
	//*************************************************************************************
	public static function images_box( $post ) { 
		global $post, $campaign_data, $cfg, $helptip;
		$campaign_imgcache = $campaign_data['campaign_imgcache'];
		$campaign_cancel_imgcache = $campaign_data['campaign_cancel_imgcache'];
		$campaign_nolinkimg = $campaign_data['campaign_nolinkimg'];
		if (!$cfg['imgcache']) : ?>
			<p>
				<input name="campaign_imgcache" id="campaign_imgcache" class="checkbox left" value="1" type="checkbox" <?php checked($campaign_imgcache,true); ?> style="width: 19px;" />
				<label for="campaign_imgcache"><?php echo __('Enable Cache Images for this campaign.', WPeMatico :: TEXTDOMAIN ); ?></label> <span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['imgcache']; ?>"></span>
			</p>
			<div id="nolinkimg" <?php if (!$campaign_imgcache) echo 'style="display:none;"';?>>
				<p>
					<input name="campaign_nolinkimg" id="campaign_nolinkimg" class="checkbox" value="1" type="checkbox" <?php checked($campaign_nolinkimg,true); ?> />
					<b><?php echo '<label for="campaign_nolinkimg">' . __('No link to source images', WPeMatico :: TEXTDOMAIN ) . '</label>'; ?></b> <span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['gralnolinkimg']; ?>"></span>
				</p>
			</div>
		<?php else : ?>
			<p>
				<input name="campaign_cancel_imgcache" id="campaign_cancel_imgcache" class="checkbox" value="1" type="checkbox" <?php checked($campaign_cancel_imgcache,true); ?> />
				<label for="campaign_cancel_imgcache"><?php echo __('Cancel Cache Images for this campaign', WPeMatico :: TEXTDOMAIN ); ?></label> <span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['cancel_imgcache']; ?>"></span>
			</p>
		<?php endif ?>
	<?php	/*	<p><input name="campaign_solo1ra" id="campaign_solo1ra" class="checkbox" value="1" type="checkbox" <?php checked($campaign_solo1ra,true); ?> />
		<b><?php echo '<label for="campaign_solo1ra">' . __('Left just first image on every post.', WPeMatico :: TEXTDOMAIN ) . '</label>'; ?></b></p>   */  ?>
	<?php
	}
	//*************************************************************************************
	public static function options_box( $post ) { 
		global $post, $campaign_data, $cfg, $helptip ;
		$campaign_max = $campaign_data['campaign_max'];
		$campaign_feeddate = $campaign_data['campaign_feeddate'];
		$campaign_author = $campaign_data['campaign_author'];
		$campaign_linktosource = $campaign_data['campaign_linktosource'];
		$campaign_commentstatus = $campaign_data['campaign_commentstatus'];
		$campaign_allowpings = $campaign_data['campaign_allowpings'];
		$campaign_woutfilter = $campaign_data['campaign_woutfilter'];
		$campaign_strip_links = $campaign_data['campaign_strip_links'];
		?>
	<div id="optionslayer" class="ibfix vtop">
		<p>
			<input name="campaign_max" type="number" min="0" size="3" value="<?php echo $campaign_max;?>" class="small-text" id="campaign_max"/> 
			<label for="campaign_max"><?php echo __('Max items to create on each fetch.', WPeMatico :: TEXTDOMAIN ); ?></label> <span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['itemfetch']; ?>"></span>
		</p>
		<p>
			<input class="checkbox" type="checkbox"<?php checked($campaign_feeddate ,true);?> name="campaign_feeddate" value="1" id="campaign_feeddate"/>
			<label for="campaign_feeddate"><?php echo __('Use feed item date.', WPeMatico :: TEXTDOMAIN ); ?></label> <span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['itemdate']; ?>"></span>
		</p>				
		<p>
			<input class="checkbox" type="checkbox"<?php checked($campaign_linktosource ,true);?> name="campaign_linktosource" value="1" id="campaign_linktosource"/> 
			<label for="campaign_linktosource"><?php echo __('Post title links to source.', WPeMatico :: TEXTDOMAIN ); ?></label> <span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['linktosource']; ?>"></span>
			<?php if($cfg['disableccf']) echo '<br /><small>'. __('Feature deactivated on Settings.', WPeMatico :: TEXTDOMAIN ).'</small>'; ?>
		</p>
		<p>
			<input class="checkbox" type="checkbox"<?php checked($campaign_strip_links ,true);?> name="campaign_strip_links" value="1" id="campaign_strip_links"/> 
			<label for="campaign_strip_links"><?php echo __('Strip links from content.', WPeMatico :: TEXTDOMAIN ); ?></label> <span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['striplinks']; ?>"></span>
		</p>
		<p>
			<input class="checkbox" type="checkbox"<?php checked($campaign_allowpings ,true);?> name="campaign_allowpings" value="1" id="campaign_allowpings"/> 
			<label for="campaign_allowpings"><?php echo __('Pingbacks y trackbacks.', WPeMatico :: TEXTDOMAIN ); ?></label> <span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['allowpings']; ?>"></span>
		</p>
		<p>
			<label for="campaign_commentstatus"><?php echo __('Discussion options:', WPeMatico :: TEXTDOMAIN ); ?></label>
			<select id="campaign_commentstatus" name="campaign_commentstatus">
			<option value="open"<?php echo ($campaign_commentstatus =="open" || $campaign_commentstatus =="") ? 'SELECTED' : ''; ?> >Open</option>
			<option value="closed" <?php echo ($campaign_commentstatus =="closed") ? 'SELECTED' : ''; ?> >Closed</option>
			<option value="registered_only" <?php echo ($campaign_commentstatus =="registered_only") ? 'SELECTED' : ''; ?> >Registered only</option>
			</select>  <span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['commentstatus']; ?>"></span>
		</p>
		<p>
			<label for="campaign_author"><?php echo __('Author:', WPeMatico :: TEXTDOMAIN ); ?></label> 
			<?php wp_dropdown_users(array('name' => 'campaign_author','selected' => $campaign_author )); ?> <span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['postsauthor']; ?>"></span>
		</p>
		
		<?php if ($cfg['woutfilter']) : ?>
		<p>
			<input class="checkbox" value="1" type="checkbox" <?php checked($campaign_woutfilter,true); ?> name="campaign_woutfilter" id="campaign_woutfilter" /> 
			<label for="campaign_woutfilter"><?php echo __('Post Content Unfiltered.', WPeMatico :: TEXTDOMAIN ); ?></label><span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['woutfilter']; ?>"></span>
		</p>
		<?php endif; ?>
	</div>
		<?php
			$activated = $campaign_data['activated'];
			$cron = $campaign_data['cron'];
		?>
	<div id="schedulelayer" class="ibfix vtop">
		<p>
		<input class="checkbox" value="1" type="checkbox" <?php checked($activated,true); ?> name="activated" id="activated" /> <label for="activated"><?php _e('Activate scheduling', WPeMatico :: TEXTDOMAIN ); ?></label><span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['schedule']; ?>"></span>
		</p>
		<?php 
		_e('Working as <a href="http://wikipedia.org/wiki/Cron" target="_blank">Cron</a> job schedule:', WPeMatico :: TEXTDOMAIN ); echo ' <i>'.$cron.'</i><br />'; 
		_e('Next runtime:', WPeMatico :: TEXTDOMAIN ); echo ' '.date_i18n( (get_option('date_format').' '.get_option('time_format') ),WPeMatico :: time_cron_next($cron) );
		//_e('Next runtime:', WPeMatico :: TEXTDOMAIN ); echo ' '.date('D, M j Y H:i',WPeMatico :: time_cron_next($cron));
		?>

		<div id="cronboxes">
			<?php @list($cronstr['minutes'],$cronstr['hours'],$cronstr['mday'],$cronstr['mon'],$cronstr['wday']) = explode(' ',$cron,5);    ?>
			<div>
				<b><?php _e('Minutes: ','wpematico'); ?></b><br />
				<?php 
				if (strstr($cronstr['minutes'],'*/'))
					$minutes=explode('/',$cronstr['minutes']);
				else
					$minutes=explode(',',$cronstr['minutes']);
				?>
				<select name="cronminutes[]" id="cronminutes" multiple="multiple">
				<option value="*"<?php selected(in_array('*',$minutes,true),true,true); ?>><?php _e('Any (*)','wpematico'); ?></option>
				<?php
				for ($i=0;$i<60;$i=$i+5) {
					echo "<option value=\"".$i."\"".selected(in_array("$i",$minutes,true),true,false).">".$i."</option>";
				}
				?>
				</select>
			</div>
			<div>
				<b><?php _e('Hours:','wpematico'); ?></b><br />
				<?php 
				if (strstr($cronstr['hours'],'*/'))
					$hours=explode('/',$cronstr['hours']);
				else
					$hours=explode(',',$cronstr['hours']);
				?>
				<select name="cronhours[]" id="cronhours" multiple="multiple">
				<option value="*"<?php selected(in_array('*',$hours,true),true,true); ?>><?php _e('Any (*)','wpematico'); ?></option>
				<?php
				for ($i=0;$i<24;$i++) {
					echo "<option value=\"".$i."\"".selected(in_array("$i",$hours,true),true,false).">".$i."</option>";
				}
				?>
				</select>
			</div>
			<div>
				<b><?php _e('Days:','wpematico'); ?></b><br />
				<?php 
				if (strstr($cronstr['mday'],'*/'))
					$mday=explode('/',$cronstr['mday']);
				else
					$mday=explode(',',$cronstr['mday']);
				?>
				<select name="cronmday[]" id="cronmday" multiple="multiple">
				<option value="*"<?php selected(in_array('*',$mday,true),true,true); ?>><?php _e('Any (*)','wpematico'); ?></option>
				<?php
				for ($i=1;$i<=31;$i++) {
					echo "<option value=\"".$i."\"".selected(in_array("$i",$mday,true),true,false).">".$i."</option>";
				}
				?>
				</select>
			</div>
			<div>
				<b><?php _e('Months:','wpematico'); ?></b><br />
				<?php 
				if (strstr($cronstr['mon'],'*/'))
					$mon=explode('/',$cronstr['mon']);
				else
					$mon=explode(',',$cronstr['mon']);
				?>
				<select name="cronmon[]" id="cronmon" multiple="multiple">
				<option value="*"<?php selected(in_array('*',$mon,true),true,true); ?>><?php _e('Any (*)','wpematico'); ?></option>
				<option value="1"<?php selected(in_array('1',$mon,true),true,true); ?>><?php _e('January'); ?></option>
				<option value="2"<?php selected(in_array('2',$mon,true),true,true); ?>><?php _e('February'); ?></option>
				<option value="3"<?php selected(in_array('3',$mon,true),true,true); ?>><?php _e('March'); ?></option>
				<option value="4"<?php selected(in_array('4',$mon,true),true,true); ?>><?php _e('April'); ?></option>
				<option value="5"<?php selected(in_array('5',$mon,true),true,true); ?>><?php _e('May'); ?></option>
				<option value="6"<?php selected(in_array('6',$mon,true),true,true); ?>><?php _e('June'); ?></option>
				<option value="7"<?php selected(in_array('7',$mon,true),true,true); ?>><?php _e('July'); ?></option>
				<option value="8"<?php selected(in_array('8',$mon,true),true,true); ?>><?php _e('Augest'); ?></option>
				<option value="9"<?php selected(in_array('9',$mon,true),true,true); ?>><?php _e('September'); ?></option>
				<option value="10"<?php selected(in_array('10',$mon,true),true,true); ?>><?php _e('October'); ?></option>
				<option value="11"<?php selected(in_array('11',$mon,true),true,true); ?>><?php _e('November'); ?></option>
				<option value="12"<?php selected(in_array('12',$mon,true),true,true); ?>><?php _e('December'); ?></option>
				</select>
			</div>
			<div>
				<b><?php _e('Weekday:','wpematico'); ?></b><br />
				<select name="cronwday[]" id="cronwday" multiple="multiple">
				<?php 
				if (strstr($cronstr['wday'],'*/'))
					$wday=explode('/',$cronstr['wday']);
				else
					$wday=explode(',',$cronstr['wday']);
				?>
				<option value="*"<?php selected(in_array('*',$wday,true),true,true); ?>><?php _e('Any (*)','wpematico'); ?></option>
				<option value="0"<?php selected(in_array('0',$wday,true),true,true); ?>><?php _e('Sunday'); ?></option>
				<option value="1"<?php selected(in_array('1',$wday,true),true,true); ?>><?php _e('Monday'); ?></option>
				<option value="2"<?php selected(in_array('2',$wday,true),true,true); ?>><?php _e('Tuesday'); ?></option>
				<option value="3"<?php selected(in_array('3',$wday,true),true,true); ?>><?php _e('Wednesday'); ?></option>
				<option value="4"<?php selected(in_array('4',$wday,true),true,true); ?>><?php _e('Thursday'); ?></option>
				<option value="5"<?php selected(in_array('5',$wday,true),true,true); ?>><?php _e('Friday'); ?></option>
				<option value="6"<?php selected(in_array('6',$wday,true),true,true); ?>><?php _e('Saturday'); ?></option>
				</select>
			</div>
			<br class="clear" />
		</div>
	</div>	<?php
	}

	//*************************************************************************************
	public static function feeds_box( $post ) {  
		global $post, $campaign_data, $cfg, $helptip;
		$campaign_feeds = $campaign_data['campaign_feeds'];
		?>  
	  <div class="submenu_dropdown">
 		<div id="domainsPlaceHolder">
		  <div class="filter_bar">
			  <span class="srchbdr0">
			  </span>
			<div class="right srchFilterOuter">
				<div style="float:left;margin-left:2px;">
					<input id="psearchtext" name="psearchtext" class="srchbdr0" type="text" value=''>
				</div>
			  <div class="srchSpacer"></div>
			  <div id="productsearch" class="left mya4_sprite searchIco" style="margin-top:4px;"></div>
			</div>
		  </div>
		  <div id="domainsBlock">
			<div id="feeds_edit" class="maxhe290">      
			
			  <?php $id=0;
				foreach($campaign_feeds as $id => $feed): ?>
				<div class="<?php if(($id % 2) == 0) echo 'bw'; else echo 'lightblue'; ?>">
					<div class="pDiv jobtype-select">
					<?php echo '<span class="left mp04 b">' . __('Feed URL:', WPeMatico :: TEXTDOMAIN ) . '</span>
					<input class="feedinput" type="text" value="' . $feed . '" id="feed_' . $id . '" name="campaign_feeds[]">';  ?>
					<?php if( $cfg['nonstatic'] ) { NoNStatic :: feedat($feed, $cfg); }  ?>
					<span class="wi10" id="feedactions">
						<label title="<?php _e('Delete this item', WPeMatico :: TEXTDOMAIN ); ?>" onclick="if(confirm('<?php _e('Are you sure ?', WPeMatico :: TEXTDOMAIN ); ?>')){ jQuery(this).parents('div').children('input').attr('value',''); jQuery(this).parent().parent().fadeOut();disable_run_now();}" class="m4 right ui-icon redx_circle"></label>
						<label title="<?php _e('Check if this item work', WPeMatico :: TEXTDOMAIN ); ?>" id="checkfeed_<?php echo $id; ?>"  class="check1feed m4 right">
							<span id="ruedita" class="ui-icon yellowalert_small"></span>
						</label>
					</span>
					</div>
				</div>
			  <?php endforeach ?>
				<span id="newfeed">
				<div class="pDiv jobtype-select">
					<?php echo '<span class="left mp04 b">' . __('New Feed:', WPeMatico :: TEXTDOMAIN ) . '</span>
					<input class="feedinput" type="text" value="" id="feed_new" name="campaign_feeds[]">'; ?>
					<?php if( $cfg['nonstatic'] ) { NoNStatic :: feedat('', $cfg); }  ?>
					<span class="wi10" id="feedactions">
						<label title="<?php _e('Delete this item', WPeMatico :: TEXTDOMAIN ); ?>" onclick="if(confirm('<?php _e('Are you sure ?', WPeMatico :: TEXTDOMAIN ); ?>')){ jQuery(this).parents('div').children('input').attr('value',''); jQuery(this).parent().parent().fadeOut();disable_run_now();}" class="m4 right ui-icon redx_circle"></label>
						<label title="<?php _e('Check if this item work', WPeMatico :: TEXTDOMAIN ); ?>" id="checkfeed"  class="check1feed m4 right">
							<span id="ruedita" class="ui-icon yellowalert_small"></span>
						</label>
					</span>
				</div>
				</span>

			</div>
			<?php if($cfg['nonstatic']){NoNStatic::feedlist();} ?>
		  </div>
		  
		  <div class="left he20">
			<p class="m7">
				<a href="JavaScript:void(0);" class="button-primary" id="addmore" onclick="s=jQuery('#newfeed');s.children('div').show();jQuery('#feeds_edit').append( s.html() ); jQuery('#feeds_edit input:last').focus()" style="font-weight: bold; text-decoration: none;" ><?php _e('Add more', WPeMatico :: TEXTDOMAIN ); ?>.</a>
			<span class="button-primary" id="checkfeeds" style="font-weight: bold; text-decoration: none;" ><?php _e('Check all feeds', WPeMatico :: TEXTDOMAIN ); ?>.</span>
			<?php if($cfg['nonstatic']){NoNStatic::bimport();} ?>
			</p>
		  </div>
		  
		  <div id="paging-box">
			<div class="p7">
				<div class="leftText"><?php _e('Displaying', WPeMatico :: TEXTDOMAIN ); ?> <span id="pb-totalrecords" class="b"><?php echo $id+1; ?></span>&nbsp;<span id="pb-ptext">feeds </span></div>
				<div class="right"><label class="right ui-icon select_down" onclick="jQuery('#feeds_edit').toggleClass('maxhe290');jQuery(this).toggleClass('select_up');" title="<?php _e('Display all feeds', WPeMatico :: TEXTDOMAIN ); ?>"></label></div>
				
			</div>
		  <div id="paging-params" class="hide" data-totalrecords="<?php echo $id+1; ?>" data-totalpages="3" data-currentpage="1" data-pagesize="5"></div>
		
		  </div>
		</div>
	  </div>
	<?php
	}
		
	
	//********************************
	public static function log_box( $post ) {
		global $post, $campaign_data, $helptip;
		$mailaddresslog = $campaign_data['mailaddresslog'];
		$mailerroronly = $campaign_data['mailerroronly'];
		?>
		<?php _e('E-Mail-Adress:', WPeMatico :: TEXTDOMAIN ); ?>
		<input name="mailaddresslog" id="mailaddresslog" type="text" value="<?php echo $mailaddresslog; ?>" class="large-text" /><br />
		<input class="checkbox" value="1" type="checkbox" <?php checked($mailerroronly,true); ?> name="mailerroronly" /> <?php _e('Send only E-Mail on errors.', WPeMatico :: TEXTDOMAIN ); ?>
		<?php
	}

	//********************************
	public static function tags_box( $post ) {
		global $post, $campaign_data, $cfg, $helptip;
		$campaign_tags = $campaign_data['campaign_tags'];
		?>			
		<?php if( $cfg['nonstatic'] ) { NoNStatic :: protags($post); }  ?>
		<p><b><?php echo '<label for="campaign_tags">' . __('Tags:', WPeMatico :: TEXTDOMAIN ) . '</label>'; ?></b>
		<textarea style="" class="large-text" id="campaign_tags" name="campaign_tags"><?php echo stripslashes($campaign_tags); ?></textarea><br />
		<?php echo __('Enter comma separated list of Tags.', WPeMatico :: TEXTDOMAIN ); ?></p>
		<?php if( $cfg['nonstatic'] ) { NoNStatic :: protags1($post); }  ?>
		<?php
	}

	//********************************
	public static function cat_box( $post ) {
		global $post, $campaign_data, $helptip;
		$campaign_categories = $campaign_data['campaign_categories'];
		$campaign_autocats = $campaign_data['campaign_autocats'];
		//get_categories()
		$args = array(
			'descendants_and_self' => 0,
			'selected_cats' => $campaign_categories,
			'popular_cats' => false,
			'walker' => null,
			'taxonomy' => 'category',
			'checked_ontop' => true
		);

		//$aa = wp_terms_checklist( 0, $args );
		?>
		<input class="checkbox" type="checkbox"<?php checked($campaign_autocats ,true);?> name="campaign_autocats" value="1" id="campaign_autocats"/> <b><?php echo '<label for="campaign_autocats">' . __('Add auto Categories', WPeMatico :: TEXTDOMAIN ) . '</label>'; ?></b>
		<span class="mya4_sprite infoIco help_tip" title="<?php echo $helptip['autocats']; ?>"></span>
		<div class="inside" style="overflow-y: scroll; overflow-x: hidden; max-height: 250px;">
			<b><?php _e('Current Categories', WPeMatico :: TEXTDOMAIN ); ?></b>
			<ul id="categories" style="font-size: 11px;">
				<?php 
				wp_terms_checklist( 0, $args );
				//self :: Categories_box($campaign_categories) ?>
			</ul> 
		</div>
		<div id="major-publishing-actions">
			<a href="JavaScript:void(0);" id="quick_add" onclick="arand=Math.floor(Math.random()*101);jQuery('#categories').append('&lt;li&gt;&lt;input type=&quot;checkbox&quot; name=&quot;campaign_newcat[]&quot; checked=&quot;checked&quot;&gt; &lt;input type=&quot;text&quot; id=&quot;campaign_newcatname'+arand+'&quot; class=&quot;input_text&quot; name=&quot;campaign_newcatname[]&quot;&gt;&lt;/li&gt;');jQuery('#campaign_newcatname'+arand).focus();" style="font-weight: bold; text-decoration: none;" ><?php _e('Quick add',  WPeMatico :: TEXTDOMAIN ); ?>.</a>
		</div>
	<?php
	}

	// ** Muestro Categorías seleccionables 
	private static function _wpe_edit_cat_row($category, $level, &$data) {  
		$category = get_category( $category );
		$name = $category->cat_name;
		echo '
		<li style="margin-left:'.$level.'5px" class="jobtype-select checkbox">
		<input type="checkbox" value="' . $category->cat_ID . '" id="category_' . $category->cat_ID . '" name="campaign_categories[]" ';
		echo (in_array($category->cat_ID, $data )) ? 'checked="checked"' : '' ;
		echo '>
		<label for="category_' . $category->cat_ID . '">' . $name . '</label></li>';
	}

	private static function Categories_box(&$data, $parent = 0, $level = 0, $categories = 0)  {    
		if ( !$categories )
			$categories = get_categories(array('hide_empty' => 0));

		if(function_exists('_get_category_hierarchy'))
		  $children = _get_category_hierarchy();
		elseif(function_exists('_get_term_hierarchy'))
		  $children = _get_term_hierarchy('category');
		else
		  $children = array();

		if ( $categories ) {
			ob_start();
			foreach ( $categories as $category ) {
				if ( $category->parent == $parent) {
					echo "\t" . self :: _wpe_edit_cat_row($category, $level, $data);
					if ( isset($children[$category->term_id]) )
						self :: Categories_box($data, $category->term_id, $level + 1, $categories );
				}
			}
			$output = ob_get_contents();
			ob_end_clean();

			echo $output;
		} else {
			return false;
		}
	}

	// Action handler - The 'Save' button is about to be drawn on the advanced edit screen.
	public static function post_submitbox_start()	{
		global $post, $campaign_data, $helptip;
		if($post->post_type != 'wpematico') return $post->ID;
		
		$campaign_posttype = $campaign_data['campaign_posttype'];
		$campaign_customposttype = $campaign_data['campaign_customposttype'];
		wp_nonce_field( 'edit-campaign', 'wpematico_nonce' ); 
		?><div class="clear" style="margin: 0 0 15px 0;">
		<div class="postbox inside" style="min-width:30%;float:left; padding: 0pt 10px 16px 10px;">
			<p><b><?php _e('Status',  WPeMatico :: TEXTDOMAIN ); ?></b></p>
			<label><input type="radio" name="campaign_posttype" <?php echo checked('publish',$campaign_posttype,false); ?> value="publish" /> <?php _e('Published'); ?></label><br />
			<label><input type="radio" name="campaign_posttype" <?php echo checked('private',$campaign_posttype,false); ?> value="private" /> <?php _e('Private'); ?></label><br />
			<label><input type="radio" name="campaign_posttype" <?php echo checked('pending',$campaign_posttype,false); ?> value="pending" /> <?php _e('Pending'); ?></label><br />
			<label><input type="radio" name="campaign_posttype" <?php echo checked('draft',$campaign_posttype,false); ?> value="draft" /> <?php _e('Draft'); ?></label>
		</div>
		<div class="postbox inside" style="float: right; min-width: 45%; padding: 0pt 5px 16px 10px;">
		<p><b><?php _e('Post type',  WPeMatico :: TEXTDOMAIN ); ?></b></p>
		<?php
			$args=array(
			  'public'   => true
			); 
			$output = 'names'; // names or objects, note names is the default
			$output = 'objects'; // names or objects, note names is the default
			$operator = 'and'; // 'and' or 'or'
			$post_types=get_post_types($args,$output,$operator); 
			foreach ($post_types  as $post_type_obj ) {
				$post_type = $post_type_obj->name;
				$post_label = $post_type_obj->labels->name;
				if ($post_type == 'wpematico') continue;
/*				echo '<input class="radio" type="radio" '.checked($post_type,$campaign_customposttype,false).' name="campaign_customposttype" value="'. $post_type. '" id="customtype_'. $post_type. '" /> <label for="customtype_'. $post_type. '">'.
						__( $post_label ) .' ('. __( $post_type ) .')</label><br />';
*/
				echo '<input class="radio" type="radio" '.checked($post_type,$campaign_customposttype,false).' name="campaign_customposttype" value="'. $post_type. '" id="customtype_'. $post_type. '" /> <label for="customtype_'. $post_type. '">'.
						__( $post_label ) .'</label><br />';
			}
		?>
		</div>
		</div><div class="clear"></div>	<?php 
	}

}
?>