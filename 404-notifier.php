<?php
/*
Plugin Name: 404 Notifier 
Plugin URI: http://crowdfavorite.com/wordpress/plugins/404-notifier/ 
Description: This plugin will log 404 hits on your site and can notify you via e-mail or you can subscribe to the generated RSS feed of 404 events.
Version: 1.3
Author: Crowd Favorite
Author URI: http://crowdfavorite.com
*/

// Copyright (c) 2006-2010 
//   Crowd Favorite, Ltd. - http://crowdfavorite.com
//   Alex King - http://alexking.org
// All rights reserved.
//
// Released under the GPL license
// http://www.opensource.org/licenses/gpl-license.php
//
// This is an add-on for WordPress - http://wordpress.org
//
// **********************************************************************
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
// **********************************************************************

load_plugin_textdomain('404-notifier');



if (is_file(trailingslashit(WP_PLUGIN_DIR).'404-notifier.php')) {
	define('N404_FILE', trailingslashit(WP_PLUGIN_DIR).'404-notifier.php');
	define('N404_RELATIVE_FILE', '404-notifier.php');
}
else if (is_file(trailingslashit(WP_PLUGIN_DIR).'404-notifier/404-notifier.php')) {
	define('N404_FILE', trailingslashit(WP_PLUGIN_DIR).'404-notifier/404-notifier.php');
	define('N404_RELATIVE_FILE', '404-notifier/404-notifier.php');
}

$_SERVER['REQUEST_URI'] = ( isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $_SERVER['SCRIPT_NAME'] . (( isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '')));

class ak_404 {
	var $url_404;
	var $url_refer;
	var $remote_addr;
	var $remote_host;
	var $user_agent;
	var $mailto;
	var $mail_enabled;
	var $rss_limit;
	var $options;

	function ak_404() {
		global $wpdb;
		if (!isset($wpdb->ak_404_log)) {
			$wpdb->ak_404_log = $wpdb->prefix.'ak_404_log';
		}
		$this->url_404 = isset($_SERVER['REQUEST_URI']) ? 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'] : '';
		$this->url_refer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		$this->remote_addr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
		$this->remote_host = isset($_SERVER['REMOTE_HOST']) ? $_SERVER['REMOTE_HOST'] : '';
		$this->user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$this->mailto = '';
		$this->mail_enabled = 0;
		$this->rss_limit = 100;
		$this->options = array(
			'mailto' => 'email',
			'mail_enabled' => 'int',
			'rss_limit' => 'int'
		);
	}

	function install() {
		global $wpdb;
		$result = $wpdb->query("
			CREATE TABLE `$wpdb->ak_404_log` (
			`id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`url_404` TEXT NOT NULL ,
			`url_refer` TEXT NULL ,
			`remote_addr` VARCHAR(255) NULL ,
			`remote_host` TEXT NULL ,
			`user_agent` TEXT NULL ,
			`date_gmt` DATETIME NOT NULL
			)
		");
		add_option('ak404_mailto', $this->mailto);
		add_option('ak404_mail_enabled', $this->mail_enabled);
		add_option('ak404_rss_limit', $this->rss_limit);
	}

	function upgrade() {
		global $wpdb;
		$col_data = $wpdb->get_results("
			SHOW COLUMNS FROM $wpdb->ak_404_log
		");
		$cols = array();
		foreach ($col_data as $col) {
			$cols[] = $col->Field;
		}
		// 1.3 schema upgrade
		if (!in_array('remote_addr', $cols)) {
			$wpdb->query("
				ALTER TABLE `$wpdb->ak_404_log`
				ADD `remote_addr` VARCHAR(255) DEFAULT NULL
				AFTER `url_refer`
			");
		}
		if (!in_array('remote_host', $cols)) {
			$wpdb->query("
				ALTER TABLE `$wpdb->ak_404_log`
				ADD `remote_host` TEXT DEFAULT NULL
				AFTER `remote_addr`
			");
		}
	}

	function update_settings() {
		if (!current_user_can('manage_options')) {
			return;
		}
		foreach ($this->options as $option => $type) {
			if (isset($_POST[$option])) {
				switch ($type) {
					case 'email':
						$value = stripslashes($_POST[$option]);
						if (!ak_check_email_address($value)) {
							$value = '';
						}
						break;
					case 'int':
						$value = intval($_POST[$option]);
						break;
					default:
						$value = stripslashes($_POST[$option]);
				}
				update_option('ak404_'.$option, $value);
			}
			else {
				update_option('ak404_'.$option, $this->$option);
			}
		}
		$this->upgrade();
	}

	function get_settings() {
		foreach ($this->options as $option => $type) {
			$this->$option = get_option('ak404_'.$option);
			switch ($type) {
				case 'email':
					$this->$option = $this->$option;
					break;
				case 'int':
					$this->$option = intval($this->$option);
					break;
			}
		}
	}
	
	function log_404() {
		global $wpdb;
		if (empty($this->url_404)) {
			return;
		}
		$wpdb->query("
			INSERT INTO $wpdb->ak_404_log
			( url_404
			, url_refer
			, remote_addr
			, remote_host
			, user_agent
			, date_gmt
			)
			VALUES
			( '".mysql_real_escape_string($this->url_404)."'
			, '".mysql_real_escape_string($this->url_refer)."'
			, '".mysql_real_escape_string($this->remote_addr)."'
			, '".mysql_real_escape_string($this->remote_host)."'
			, '".mysql_real_escape_string($this->user_agent)."'
			, '".current_time('mysql',1)."'
			)
		");
		$this->mail_404();
	}
	
	function mail_404() {
		if (!empty($this->mailto) && $this->mail_enabled) {
			$to      = $this->mailto;
			$subject = __('404: ', '404-notifier').$this->url_404;
			$message = __('404 Report - a file not found error was registered on your site.', '404-notifier')."\n\n"
				.__('404 URL:     ', '404-notifier').$this->url_404."\n\n"
				.__('Referred by: ', '404-notifier').$this->url_refer."\n\n"
				.__('Remote Address: ', '404-notifier').$this->remote_addr."\n\n"
				.__('Remote Host: ', '404-notifier').$this->remote_host."\n\n"
				.__('User Agent: ', '404-notifier').$this->user_agent."\n\n";
			$headers = 'From: '.$this->mailto . "\r\n"
				.'Reply-To: '.$this->mailto . "\r\n"
				.'X-Mailer: PHP/' . phpversion();
			
			wp_mail($to, $subject, $message, $headers);
		}
	}

	function dashboard_page() {
		global $wpdb;
		print('
			<div class="wrap">
				'.screen_icon().'
				<h2>'.__('404 Notifier Logs', '404-notifier').'</h2>
		');

		$per_page = 20;
		$pagenum = isset($_GET['paged']) ? absint($_GET['paged']) : 0;
		if (empty($pagenum)) $pagenum = 1;
		$offset = ($pagenum - 1) * $per_page;
		$events = $wpdb->get_results("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM $wpdb->ak_404_log
			ORDER BY date_gmt DESC
			LIMIT $offset, $per_page
		");
		$overall_count = $wpdb->get_var("SELECT FOUND_ROWS()");
		if ($overall_count > 0) {
			$num_pages = ceil($overall_count / $per_page);
			$page_links = paginate_links(array(
				'base' => add_query_arg('paged', '%#%'),
				'format' => '',
				'prev_text' => __('&laquo;'),
				'next_text' => __('&raquo;'),
				'total' => $num_pages,
				'current' => $pagenum
			));
			if ($page_links) {
				echo '<div class="tablenav"><div class="tablenav-pages">';
				$page_links_text = sprintf('<span class="displaying-num">'.__('Displaying %s&#8211;%s of %s', '404-notifier').'</span>%s',
					number_format_i18n(($pagenum-1) * $per_page+1),
					number_format_i18n(min($pagenum * $per_page, $overall_count)),
					number_format_i18n($overall_count),
					$page_links
				);
				echo $page_links_text.'</div><div class="clear"></div></div>';
			}

			echo '<table class="widefat post fixed" cellspacing="0">
			<thead>
				<tr>
					<th scope="col" id="url" class="manage-column column-url">'.__('404 URL', '404-notifier').'</th>
					<th scope="col" id="refer" class="manage-column column-refer">'.__('Referring URL', '404-notifier').'</th>
					<th scope="col" id="address" class="manage-column column-address">'.__('Remote Address', '404-notifier').'</th>
					<th scope="col" id="host" class="manage-column column-host">'.__('Remote Host', '404-notifier').'</th>
					<th scope="col" id="agent" class="manage-column column-agent">'.__('User Agent', '404-notifier').'</th>
					<th scope="col" id="date" class="manage-column column-date">'.__('Date', '404-notifier').'</th>
				</tr>
			</thead>

			<tfoot>
				<tr>
					<th scope="col" class="manage-column column-url">'.__('404 URL', '404-notifier').'</th>
					<th scope="col" class="manage-column column-refer">'.__('Referring URL', '404-notifier').'</th>
					<th scope="col" class="manage-column column-address">'.__('Remote Address', '404-notifier').'</th>
					<th scope="col" class="manage-column column-host">'.__('Remote Host', '404-notifier').'</th>
					<th scope="col" class="manage-column column-agent">'.__('User Agent', '404-notifier').'</th>
					<th scope="col" class="manage-column column-date">'.__('Date', '404-notifier').'</th>
				</tr>
			</tfoot>

			<tbody>';
			$rowclass = ' class="alternate"';
			foreach ($events as $event) {
				$rowclass = ' class="alternate"' == $rowclass ? '' : ' class="alternate"';
				print('<tr id="log-'.absint($event->id).'"'.$rowclass.' valign="top">
				<td class="url column-url"><strong><a href="'.esc_url($event->url_404).'">'.esc_html($event->url_404).'</a></strong></td>
				<td class="refer column-refer">'.(isset($event->url_refer) && !empty($event->url_refer) ? '<a href="'.esc_url($event->url_refer).'">'.esc_html($event->url_refer).'</a>' : '<span class="nonessential">'.__('N/A', '404-notifier').'</span>').'</td>
				<td class="address column-address">'.(isset($event->remote_addr) && !empty($event->remote_addr) ? esc_html($event->remote_addr) : '<span class="nonessential">'.__('N/A', '404-notifier').'</span>').'</td>
				<td class="host column-host">'.(isset($event->remote_host) && !empty($event->remote_host) ? esc_html($event->remote_host) : '<span class="nonessential">'.__('N/A', '404-notifier').'</span>').'</td>
				<td class="agent column-agent">'.(isset($event->user_agent) && !empty($event->user_agent) ? esc_html($event->user_agent) : '<span class="nonessential">'.__('N/A', '404-notifier').'</span>').'</td>
				<td class="date column-date">'.mysql2date('D, d M Y H:i:s +0000', $event->date_gmt, false).'</td>
				</tr>');
			}
			echo '</tbody>
			</table>';

			if ($page_links) {
				echo '<div class="tablenav"><div class="tablenav-pages">'.$page_links_text.'</div><div class="clear"></div></div>';
			}
		} else {
			print('<p><em>'.__('No logs to display&hellip;', '404-notifier').'</em></p>');
		}

		print('
			</div>
		');
	}

	function options_form() {
		print('
			<div class="wrap">
				<h2>'.__('404 Notifier Options', '404-notifier').'</h2>
				<form name="ak_404" action="'.esc_url(admin_url('options-general.php')).'" method="post">
					<fieldset class="options">
						<p>
							<input type="checkbox" name="mail_enabled" id="ak404_mail_enabled" value="1" '.checked($this->mail_enabled, '1', false).'/>
							<label for="ak404_mail_enabled">'.__('Enable mail notifications on 404 hits.', '404-notifier').'</label>
						</p>
						<p>
							<label for="mailto">'.__('E-mail address to notify:', '404-notifier').'</label>
							<input type="text" size="35" name="mailto" id="mailto" value="'.htmlspecialchars($this->mailto).'" />
						</p>
						<p>
							<label for="rss_limit">'.__('Limit the RSS Feed to how many items?', '404-notifier').'</label>
							<input type="text" size="5" name="rss_limit" id="rss_limit" value="'.intval($this->rss_limit).'" />
						</p>
						<p><a href="'.esc_url(admin_url('options-general.php?ak_action=404_feed')).'">'.__('RSS Feed of 404 Events', '404-notifier').'</a></p>
						<input type="hidden" name="ak_action" value="update_404_settings" />
					</fieldset>
					<p class="submit">
						<input type="submit" name="submit" value="'.__('Update 404 Notifier Settings', '404-notifier').'" />
						'.wp_nonce_field('404-notifier', '_wpnonce', true, false).'
						'.wp_referer_field(false).'
					</p>
				</form>
			</div>
		');
	}
	
	function rss_feed() {
		global $wpdb;
		$events = $wpdb->get_results("
			SELECT *
			FROM $wpdb->ak_404_log
			ORDER BY date_gmt DESC
			LIMIT $this->rss_limit
		");
		header('Content-type: text/xml; charset=' . get_option('blog_charset'), true);
		echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>';
?>
<rss version="2.0" 
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
>

<channel>
	<title><?php print(__('404 Report for: ', '404-notifier')); bloginfo_rss('name'); ?></title>
	<link><?php bloginfo_rss('url') ?></link>
	<description><?php bloginfo_rss("description") ?></description>
	<pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_lastpostmodified('GMT'), false); ?></pubDate>
	<generator>http://wordpress.org/?v=<?php bloginfo_rss('version'); ?></generator>
	<language><?php echo get_option('rss_language'); ?></language>
<?php
		if (count($events) > 0) {
			foreach ($events as $event) {
				$content = '
					<p>'.__('404 URL: ', '404-notifier').'<a href="'.$event->url_404.'">'.$event->url_404.'</a></p>
					<p>'.__('Referring URL: ', '404-notifier').'<a href="'.$event->url_refer.'">'.$event->url_refer.'</a></p>
					<p>'.__('User Agent: ', '404-notifier').$event->user_agent.'</p>
				';
?>
	<item>
		<title><![CDATA[<?php print('404: '.htmlspecialchars($event->url_404)); ?>]]></title>
		<link><![CDATA[<?php print($event->url_404); ?>]]></link>
		<pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', $event->date_gmt, false); ?></pubDate>
		<guid isPermaLink="false"><?php print($event->id); ?></guid>
		<description><![CDATA[<?php print($content); ?>]]></description>
		<content:encoded><![CDATA[<?php print($content); ?>]]></content:encoded>
	</item>
<?php $items_count++; if (($items_count == get_option('posts_per_rss')) && !is_date()) { break; } } } ?>
</channel>
</rss>
<?php
		die();
	}
}

if (!function_exists('ak_check_email_address')) {
	function ak_check_email_address($email) {
// From: http://www.ilovejackdaniels.com/php/email-address-validation/
// First, we check that there's one @ symbol, and that the lengths are right
		if (!ereg("^[^@]{1,64}@[^@]{1,255}$", $email)) {
			// Email invalid because wrong number of characters in one section, or wrong number of @ symbols.
			return false;
		}
// Split it into sections to make life easier
		$email_array = explode("@", $email);
		$local_array = explode(".", $email_array[0]);
		for ($i = 0; $i < sizeof($local_array); $i++) {
			 if (!ereg("^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$", $local_array[$i])) {
				return false;
			}
		}	
		if (!ereg("^\[?[0-9\.]+\]?$", $email_array[1])) { // Check if domain is IP. If not, it should be valid domain name
			$domain_array = explode(".", $email_array[1]);
			if (sizeof($domain_array) < 2) {
					return false; // Not enough parts to domain
			}
			for ($i = 0; $i < sizeof($domain_array); $i++) {
				if (!ereg("^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$", $domain_array[$i])) {
					return false;
				}
			}
		}
		return true;
	}
}

function ak404_activate() {	
	if (function_exists('is_multisite') && is_multisite()) {
		if (isset($_GET['networkwide']) && ($_GET['networkwide'] == 1)) {
			$blogs = cfmobi_get_site_blogs();
			foreach ($blogs as $blog_id){
				switch_to_blog($blog_id);
				ak404_activate_single();
				restore_current_blog();
			}
		}
	}
	ak404_activate_single();
}

function ak404_activate_single() {
	global $ak404, $wpdb;
	$ak404 = new ak_404;
	$tables = $wpdb->get_col("
		SHOW TABLES LIKE '$wpdb->ak_404_log'
	");
	
	error_log($wpdb->ak_404_log);
	error_log(get_bloginfo('name'));
	error_log($wpdb->prefix);
	error_log(implode(', ', $tables));
	
	if (!in_array($wpdb->ak_404_log, $tables)) {
		$ak404->install();
	}
	
}


register_activation_hook(N404_FILE, 'ak404_activate');

function ak404_init() {
	global $ak404;
	// Place here instead of activation for sites added after activation
	// Based on the proper-network-activation plugin by scribu
	/*if (ak404_is_network_wide(N404_RELATIVE_FILE)) {
		ak404_activate_all_sites();
	}*/
		
	global $ak404;
	$ak404 = new ak_404;
	$ak404->get_settings();
}
add_action('init', 'ak404_init');

function ak404_log() {
	if (is_404()) {
		global $ak404;
		$ak404->log_404();
	}
}
add_action('shutdown', 'ak404_log');

function ak404_admin_menu() {
		add_submenu_page(
			'index.php'
			, __('404 Notifier Logs', '404-notifier')
			, __('404 Logs', '404-notifier')
			, 'manage_options'
			, basename(N404_FILE)
			, 'ak404_dashboard_page'
		);

		add_options_page(
			__('404 Notifier Options', '404-notifier')
			, __('404 Notifier', '404-notifier')
			, 'manage_options'
			, basename(N404_FILE)
			, 'ak404_options_form'
		);

}
add_action('admin_menu', 'ak404_admin_menu');

function ak404_dashboard_page() {
	global $ak404;
	$ak404->dashboard_page();
}

function ak404_options_form() {
	global $ak404;
	$ak404->options_form();
}

function ak404_request_handler() {
	global $ak404;
	if (!empty($_POST['ak_action'])) {
		switch($_POST['ak_action']) {
			case 'update_404_settings': 
				check_admin_referer('404-notifier');
				$ak404->update_settings();
				header('Location: '.admin_url('options-general.php?page=404-notifier.php&updated=true'));
				die();
				break;
		}
	}
	if (!empty($_GET['ak_action'])) {
		switch($_GET['ak_action']) {
			case '404_feed':
				$ak404->rss_feed();
				break;
		}
	}
}
add_action('admin_init', 'ak404_request_handler', 99);

function ak404_plugin_action_links($links, $file) {
	if ($file == plugin_basename(N404_FILE)) {
		$links[] = '<a href="'.esc_url(admin_url('options-general.php?page=404-notifier.php')).'">'.__('Settings').'</a>';
	}
	return $links;
}
add_filter('plugin_action_links', 'ak404_plugin_action_links', 10, 2);

function ak404_main_dashboard_widget() {
	global $wpdb;
	$events = $wpdb->get_results("
		SELECT *
		FROM $wpdb->ak_404_log
		ORDER BY date_gmt DESC
		LIMIT 5
	");
	if (count($events) > 0) {
		echo '<ul>';
		foreach ($events as $event) {
			print('<li>
			<strong>'.__('404 URL:', '404-notifier').'</strong> <a href="'.esc_url($event->url_404).'">'.esc_html($event->url_404).'</a><br/>
			<strong>'.__('Referring URL:', '404-notifier').'</strong> '.(isset($event->url_refer) && !empty($event->url_refer) ? '<a href="'.esc_url($event->url_refer).'">'.esc_html($event->url_refer).'</a>' : '<span class="nonessential">'.__('N/A', '404-notifier').'</span>').'<br/>
			<strong>'.__('Remote Address:', '404-notifier').'</strong> '.(isset($event->remote_addr) && !empty($event->remote_addr) ? esc_html($event->remote_addr) : '<span class="nonessential">'.__('N/A', '404-notifier').'</span>').'<br/>
			<strong>'.__('Remote Host:', '404-notifier').'</strong> '.(isset($event->remote_host) && !empty($event->remote_host) ? esc_html($event->remote_host) : '<span class="nonessential">'.__('N/A', '404-notifier').'</span>').'<br/>
			<strong>'.__('User Agent:', '404-notifier').'</strong> '.(isset($event->user_agent) && !empty($event->user_agent) ? esc_html($event->user_agent) : '<span class="nonessential">'.__('N/A', '404-notifier').'</span>').'<br/>
			<strong>'.__('Date:', '404-notifier').'</strong> '.mysql2date('D, d M Y H:i:s +0000', $event->date_gmt, false).'
			</li>');
		}
		echo '</ul>
		<p class="textright"><a href="'.esc_url(admin_url('index.php?page=404-notifier.php')).'" class="button">'.__('View all').'</a></p>';
	} else {
		print('<p><em>'.__('No logs to display&hellip;', '404-notifier').'</em></p>');
	}
}
function ak404_add_dashboard_widgets() {
	wp_add_dashboard_widget('ak404_dashboard_widget', __('Recent 404 Logs', '404-notifier'), 'ak404_main_dashboard_widget');
}
add_action('wp_dashboard_setup', 'ak404_add_dashboard_widgets');

//Multisite utility functions 
function ak404_get_site_blogs() {
	global $wpdb;
	//Based the proper-networt-activation plugin by scribu
	return $wpdb->get_col( "
		SELECT blog_id
		FROM $wpdb->blogs
		WHERE site_id = '{$wpdb->siteid}'
		AND deleted = 0
	");
	
}
function ak404_activate_all_sites(){
	global $switched;
	$blogs = ak404_get_site_blogs();
	foreach ($blogs as $blog_id){
		switch_to_blog($blog_id);
		/*if ( in_array(N404_RELATIVE_FILE, (array) get_option( 'active_plugins' ))) {
			ak404_activate();
		}*/
		error_log( 'cursite: ' . get_bloginfo('name'));
		ak404_activate();
		error_log($ms_blog_id);
		restore_current_blog();
	}
}

function ak404_is_network_wide($plugin){
	global $wp_version;
	if( (int)$wp_version[0] >= 3){
		if (!is_multisite()){ 
		 return false;
		}
		$plugins = get_site_option( 'active_sitewide_plugins'); 
		if (isset($plugins[$plugin])) {  
			return true;
		}
	}
	else {
		return false;
	}
}

function myplugin_activate_for_network(){
	global $wpdb;
 	$blogs = $wpdb->get_col( "
		SELECT blog_id
		FROM $wpdb->blogs
		WHERE site_id = '{$wpdb->siteid}'
		AND deleted = 0
	");
	foreach ($blogs as $blog_id) {
		switch_to_blog($blog_id);
		myplugin_activate();
		unset($wpdb);
		restore_current_blog();
	}
	
	
}

function myplugin_activate() {	
	global $myplugin, $wpdb;
	$myplugin = new myplugin;
	$tables = $wpdb->get_col("
		SHOW TABLES LIKE '$wpdb->my_plugin_table'
	");

	error_log(get_bloginfo('name'));
	error_log($wpdb->prefix);
	error_log(implode(', ', $tables));


	if (!in_array($wpdb->my_plugin_table, $tables)) {
		$myplugin->install();
	}

}
/*
Hello, I have a plugin that is working on a wordpress single site installation and am trying to get to to work for multi-site. The obstacle has been upon network activation creating my plugin tables for each of the sites.

When I activate the plugin on any of the 'sub-sites' it will create the table with the proper prefix, however my check that attempts to setup tables for all the other sites simply does not work. Instead it will create a table that is named as if it were a single site installation. For example wp_myplugin_table vs wp_2_myplugin_table. 

I call myplugin_activate_for_network within my init function after checking to make sure it is a multisite:

function myplugin_activate_for_network(){
	global $wpdb;
 	$blogs = $wpdb->get_col( "
		SELECT blog_id
		FROM $wpdb->blogs
		WHERE site_id = '{$wpdb->siteid}'
		AND deleted = 0
	");
	foreach ($blogs as $blog_id) {
		switch_to_blog($blog_id);
		myplugin_activate();
		restore_current_blog();
	}
	
	
}


function myplugin_activate() {	
	global $myplugin, $wpdb;
	$myplugin = new myplugin;
	$tables = $wpdb->get_col("
		SHOW TABLES LIKE '$wpdb->my_plugin_table'
	");

	error_log(get_bloginfo('name'));
	error_log($wpdb->prefix);
	error_log(implode(', ', $tables));


	if (!in_array($wpdb->my_plugin_table, $tables)) {
		$myplugin->install();
	}

}

myplugin_activate_for_network() seems to work just fine as it runs myplugin_activate() as many times as there are sites. Additionally the get_bloginf( 'name' ) and $wpdb->prefix are producing the correct values for their respective site. However $wpdb->myplugin_table gives me wp_myplugin_table everytime instead of the correct prefix followed by _myplugin_table. I appreciate any insight on this! 
*/
?>