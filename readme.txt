=== WPeMatico ===
Contributors: etruel
Donate link: http://www.netmdp.com/tag/wpematico/
Tags: RSS, Post, Posts, Feed, Feeds, RSS to Post, Feed to Post, admin, aggregation, atom, autoblogging, bot, content, syndication, writing
Requires at least: 3.0
Tested up to: 3.1
Stable tag: 0.3Beta

WPeMatico is for autoblogging, automatically creating posts from the RSS/Atom feeds you choose, which are organized into campaigns. 

== Description ==

WPeMatico is for autoblogging, automatically creating posts from the RSS/Atom feeds you choose, which are organized into campaigns. 
For RSS fetching it's using the simplepie library included in Wordpress.
Also for image processing it's using the core functions of wordpress.
Translations ready. .pot english file included for localized. 
I take code from many many other plugins, but for this plugin I read a lot of code of the old WP-o-Matic and BackWPUp. Thanks to the developers;)

Supported features:

* Campaigs Feeds and options are organized into campaigns.
* Comfortable interface like Worpress posts editing for every campaign.
* Multiple feeds / categories: it’s possible to add as many feeds as you want, and add them to some categories as you want.
* Integrated with the Simplepie library that come with Wordpress.  This includes RSS 0.91 and RSS 1.0 formats, the popular RSS 2.0 format, Atom...
* Feed autodiscovery, which lets you add feeds without even knowing the exact URL. (Thanks Simplepie!)
* Unix cron and WordPress cron jobs For maximum performance, you can make the RSS fetching process be called by a Unix cron job, or simply let WordPress handle it.
* Images caching are integrated with Wordpress Media Library and posts attach. upload remote images or link to source. Fully configurable.
* Words Rewriting. Regular expressions supported.
* Words Relinking. Define custom links for words you specify.
* Detailed Log sending to custom e-mail. Always on every executed cron or only on errors with campaign.
* Multilanguage.

Upcoming features:

* Post templating. 
* Campaigns import/export.
* Some requested easy cool features...
* May be a PRO versión.

PHP5 is required!

Copyright 2010.
This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version. 

[If you want support or more detail in spanish you can search WPeMatico here:](http://www.netmdp.com). 
Traducción al español de Argentina de la Licencia GNU: http://www.spanish-translator-services.com/espanol/t/gnu/gpl-ar.html

== Installation ==

You can either install it automatically from the WordPress admin, or do it manually:

1. Unzip "wpematico" archive and put the folder into your plugins folder (/wp-content/plugins/).
2. Activate the plugin from the Plugins menu.

== Frequently Asked Questions ==

= Where can I ask a question? =

* [Search the page WPeMatico here](http://www.netmdp.com).

== Screenshots ==

1. The table list of campaigns and some info of everyone.

2. The detailed log after executing "Run Now" in campaign.

3. Checking feeds on campaign editing.

== Changelog ==

= 0.3Beta =
* Fix issue in 1st feed for checking.
* Fix bug Warning & Error messages on running campaign.
* Added Go Back button on error saving and get the old values.
* Added 2 more Screenshots on Wordpress repository.
* Readme.txt updated.

= 0.2Beta =
* Fixed version number.
* Fix wrong message when activating.
* Deleted .mo & .po files, replacing with new wordpress generated .pot

= 0.1Beta =
* initial release
* [more info in spanish, en español](http://www.netmdp.com/tag/wpematico/)

== Upgrade Notice ==

= 0.3Beta =
Must upgrade.