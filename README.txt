=== 404 Notifier ===
Contributors: crowdfavorite, alexkingorg
Tags: 404, error, log, notify
Requires at least: 2.8
Tested up to: 3.0
Stable tag: 1.3

Log 404 (file not found) errors on your site and get them delivered to you via e-mail or RSS.

== Description ==

If you've decided to move things around on your site, you might overlook a few redirects and end up with some broken URLs. This will help you catch those so you can take care of them.

== Installation == 

1. Download the plugin archive and expand it (you've likely already done this).
2. Upload the 404-notifier.php file to your wp-content/plugins directory (not in a sub-folder).
3. Go to the Plugins page in your WordPress Administration area and click 'Activate' for 404 Notifier. This will create the 404 log table for you.
4. Optional: Configure your 404 Notifier options and get the RSS feed URL by going to Options > 404 Notifier in the WP Admin area.
5. Optional: Subscribe to the 404 Notifier RSS feed (link on the options page).

Congratulations, you've just installed 404 Notifier.

== Frequently Asked Questions ==  

= Does this work with ugly (?p=123) permalinks? =

No, this only works if you use pretty permalinks (/2006/01/01/post-name or similar). From what I can tell, the WordPress code doesn't flag URLs as 404s with ugly permalinks.

= Anything else? =

That about does it - enjoy!

== Changelog ==

= 1.3 =
* Updated code to newest best practices.
* Addressed feature requests and updated the readme.

= 1.2a =
* Pushed request handler to later in the WordPress startup queue.
* Updated copyright info and readme.

= 1.2 =
* Removed some old code.
* Updated translation slugs.
* Added new initialization function and plugin-specific admin CSS.
* Changed some wording in the readme.

= 1.1 =
* Updated copyright info, the readme, and bumped version.

= 1.0 =
* The first version.