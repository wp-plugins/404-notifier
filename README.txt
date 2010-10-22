=== 404 Notifier ===
Contributors: crowdfavorite, alexkingorg
Donate link: http://crowdfavorite.com/donate/
Tags: 404, error, log, notify
Requires at least: 3.0
Tested up to: 3.0.1
Stable tag: 1.3

Logs 404 (file not found) errors on your site and get them delivered to you via e-mail or RSS.

== Description ==

Links:

- [Wordpress.org 404 Notifier forum topics](http://wordpress.org/tags/404-notifier?forum_id=10).
- [404 Notifier plugin page at Crowd Favorite](http://crowdfavorite.com/wordpress/plugins/404-notifier/).
- [404 Notifier Plugin forums at Crowd Favorite](http://crowdfavorite.com/forums/forum/404-notifier).
- [WordPress Help Center 404 Notifier support](http://wphelpcenter.com/plugins/404-notifier/).

If you've decided to move things around on your site, you might overlook a few redirects and end up with some broken URLs. This will help you catch those so you can take care of them.

404 Notifier creates a log of all the 404 errors on your site in the administration section. It also can notify you via email or RSS any time a 404 error occurs. 

Certain URLs can be suppressed from the notifications list with the `ak404_surpress_notification` filter. This filter expects that the returned value is an array of regular expressions.

== Installation == 

1. Upload the 404-notifier directory to your wp-content/plugins directory.
2. Go to the Plugins page in your WordPress Administration area and click 'Activate' for 404 Notifier. This will create the 404 log table for you and start logging for you.
3. Go to the Plugins page in your WordPress Administration area and click 'Activate' for 404 Notifier. This will create the 404 log table for you.
4. Optional: Configure your 404 Notifier options and get the RSS feed URL by going to Options > 404 Notifier in the WP Admin area.
5. Optional: Subscribe to the 404 Notifier RSS feed (link on the options page).

== Frequently Asked Questions ==  

= Does this work with ugly (?p=123) permalinks? =

No, this only works if you use pretty permalinks (/2006/01/01/post-name or similar). The WordPress code doesn't flag URLs as 404s with ugly permalinks.

= Will this work for a multisite installation? =

Yes, your 404 logs will be blog specific.

= Anything else? =

That about does it - enjoy!

== Screenshots ==

1. Admin panel for 404 log
2. RSS feed of 404 log

== Changelog ==

= 1.3 =
- New : Notification filter that suppresses notifications based on regexs.
- New : Tracks Server remote host and server remote address
- New : Added dashboard page that lists all 404 hits and a dashboard widget that lists recent 404 hits
- New : Multisite Support
- New : CF_Admin integration
- New : Screenshots
- Changed : Added additional sanitization and best practices
- Bugfix : Date information was not being properly recorded
- Bugfix : Some SQL calls were returning the wrong data

= 1.2a =
- Changed : Pushed request handler to later in the WordPress startup queue.

= 1.2 =
- New : Initialization function and plugin-specific admin CSS.
- Changed : Removed some old code.
- Changed : Translation slugs.

= 1.1 =
- Changed : Added and updated copyright info, the readme, and bumped version.

= 1.0 =
- New : The first version.
