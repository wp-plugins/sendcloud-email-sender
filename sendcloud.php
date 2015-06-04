<?php
/*
 * Plugin Name: SendCloud
 * Plugin Uri: http://sendcloud.sohu.com/
 * Description: The subscribers will receive email notifications when a new post is published. The users who are related with these comments will receive email notifications when a new comment is replied. you can monitor the sending email data from data center
 * Version: 1.0
 * Author: SendCloud
 * Author URI: http://sendcloud.sohu.com/
 */
?>

<?php

require_once ('sendcloud_client.php');
require_once ('sendcloud_tool.php');
if (! function_exists ( 'wp_get_current_user' )) {
	include (ABSPATH . "wp-includes/pluggable.php");
}
// i18n plugin domain
define ( 'sdPATH', trailingslashit ( dirname ( __FILE__ ) ) );
define ( 'SENDCLOUD_I18N_DOMAIN', 'sendcloud' );
define ( 'sdDIR', trailingslashit ( dirname ( plugin_basename ( __FILE__ ) ) ) );
define ( 'sdURL', plugin_dir_url ( dirname ( __FILE__ ) ) . sdDIR );
define ( 'SENDCLOUD_SUBSCRIBE_URL', 'http://172.16.0.107/src/js/subscribe.js' );
// version of plugin
define ( 'SENDCLOUD_CURRENT_VERSION', '1.0' );
$is_sendcloud_i18n_setup = false;
function sendcloud_init_i18n() {
	global $is_sendcloud_i18n_setup;
	
	if ($is_sendcloud_i18n_setup == false) {
		load_plugin_textdomain ( SENDCLOUD_I18N_DOMAIN, false, dirname ( plugin_basename ( __FILE__ ) ) . '/languages/' );
		$is_sendcloud_i18n_setup = true;
	}
}

if (isset ( $_GET ["page"] )) {
	$occurence = Array (
			"sendcloud" 
	);
	while ( list ( $element, $valeur ) = each ( $occurence ) ) {
		$pos = strpos ( $_GET ["page"], $valeur );
		if (is_int ( $pos ) != false) {
			add_action ( 'admin_head', 'sendcloud_js_css_admin_head' );
		}
	}
}

register_activation_hook ( __FILE__, 'sendcloud_install' );

if (! function_exists ( 'sendcloud_js_css_admin_head' )) {
	function sendcloud_js_css_admin_head($hook_suffix) {
		if (in_array ( $hook_suffix, array (
				'sendcloud/admin/data_center.php', // dashboard
				'sendcloud/admin/setting.php',
				'sendcloud/admin/subscribe.php',
				'sendcloud-email-sender/admin/data_center.php',
				'sendcloud-email-sender/admin/setting.php',
				'sendcloud-email-sender/admin/subscribe.php' 
		) )) {
			wp_register_style ( 'sendcloud_bootstrap.min.css', sdURL . 'css/bootstrap.min.css', array () );
			wp_register_style ( 'sendcloud_daterangepicker-bs3.css', sdURL . 'css/daterangepicker-bs3.css', array () );
			wp_enqueue_style ( 'sendcloud_bootstrap.min.css' );
			wp_enqueue_style ( 'sendcloud_daterangepicker-bs3.css' );
			wp_register_script ( 'sendcloud_bootstrap.js', sdURL . 'js/bootstrap.js', array (
					'jquery' 
			) );
			wp_enqueue_script ( 'sendcloud_bootstrap.js' );
			wp_register_script ( 'sendcloud_moment.min.js', sdURL . 'js/moment.min.js', array (
					'jquery' 
			) );
			wp_enqueue_script ( 'sendcloud_moment.min.js' );
			wp_register_script ( 'sendcloud_daterangepicker.js', sdURL . 'js/daterangepicker.js', array (
					'jquery' 
			) );
			wp_enqueue_script ( 'sendcloud_daterangepicker.js' );
		}
	}
}
function  sendcloud_scripts() {
	wp_enqueue_script ( 'sendcloud_subscribe_script',  sdURL . 'js/subscribe.js', array (),SENDCLOUD_CURRENT_VERSION,false );
}

add_action ( 'wp_enqueue_scripts', 'sendcloud_scripts' );
add_action ( 'admin_enqueue_scripts', 'sendcloud_js_css_admin_head' );
function sendcloud_get_installed_version() {
	return get_option ( 'sendcloud_version' );
}
function sendcloud_get_current_version() {
	return SENDCLOUD_CURRENT_VERSION;
}
function sendcloud_install() {
	sendcloud_init_i18n ();
	global $wpdb;
	$current_user = wp_get_current_user ();
	$installed_version = sendcloud_get_installed_version ();
	// if (empty ( $installed_version )) {
	$email = $current_user->user_email;
	if (empty ( $email )) {
		$email = "service@sendcloud.im";
	}
	add_option ( 'sendcloud_email', $email );
	add_option ( 'sendcloud_fromname', '' );
	add_option ( 'sendcloud_post_publish_notify', 0 );
	add_option ( 'sendcloud_posts_reply_notify', 0 );
	add_option ( 'sendcloud_version', sendcloud_get_current_version () );
	$table_account = $wpdb->prefix . 'sendcloud_account';
	$table_account_sql = "CREATE TABLE IF NOT EXISTS `$table_account` (
	`api_user` varchar(100) NOT NULL COMMENT 'api_user',
	`api_key` varchar(50) NOT NULL COMMENT 'api_key',
	`from` varchar(100) NOT NULL COMMENT 'from',
	`fromname` varchar(50) NOT NULL COMMENT 'fromname',
	`datetime` datetime NOT NULL,
	 PRIMARY KEY  (`api_user`),
	 UNIQUE KEY `api_user` (`api_user`)
	)ENGINE=MyISAM  DEFAULT CHARSET=utf8";
	$wpdb->query ( $table_account_sql );
	
	// }
}
function sendcloud_register_menu_page() {
	sendcloud_init_i18n ();
	add_menu_page ( 'SendCloud', 'SendCloud', 'administrator', sdPATH . 'admin/setting.php', NULL, plugins_url ( 'img/sendcloud.png', __FILE__ ) );
	add_submenu_page ( sdPATH . 'admin/setting.php', 'SendCloud', __ ( 'Setting', SENDCLOUD_I18N_DOMAIN ), 'manage_options', sdPATH . 'admin/setting.php', NULL );
	add_submenu_page ( sdPATH . 'admin/setting.php', 'SendCloud', __ ( 'Data Center', SENDCLOUD_I18N_DOMAIN ), 'manage_options', sdPATH . 'admin/data_center.php', NULL );
	add_submenu_page ( sdPATH . 'admin/setting.php', 'SendCloud', __ ( 'Subscriber', SENDCLOUD_I18N_DOMAIN ), 'manage_options', sdPATH . 'admin/subscribe.php', NULL );
}
function sendcloud_get_template_name() {
	$locale = get_locale ();
	$template_invoke_name = ($locale == 'zh_CN') ? 'wdpress_post_comment_template_zh' : 'wdpress_post_comment_template_en';
	
	return $template_invoke_name;
}
function sendcloud_notify_email_subscriber($postId, $post) {
	global $wpdb;
	global $sendcloud_api;
	$maillist_addr = 'wp_post_publish_maillist@sendcloud.org';
	if (get_option ( 'sendcloud_post_publish_notify' ) == 0) {
		return;
	}
	
	if (get_option ( 'sendcloud_quota_exceeded' ) && (strtotime ( get_option ( 'sendcloud_quota_exceeded' ) ) > strtotime ( date ( "Y-m-d H:i:s" ) ))) {
		return;
	}
	
	$table = $wpdb->prefix . 'sendcloud_account';
	$ret = $wpdb->get_row ( "SELECT * FROM " . $table );
	$api_user = $ret->api_user;
	$api_key = $ret->api_key;
	$from = get_option ( 'sendcloud_email' ) ? get_option ( 'sendcloud_email' ) : $ret->from;
	$fromname = get_option ( 'sendcloud_fromname' ) ? get_option ( 'sendcloud_fromname' ) : $ret->fromname;
	$subject = __ ( 'Dear,  your subscription $sitename is updated!', SENDCLOUD_I18N_DOMAIN );
	
	$content = __ ( 'Dear subscribers, $sitename has new interesting updates! Click to view the latest posts: <a href="$posturl">$postname</a>', SENDCLOUD_I18N_DOMAIN );
	$link = get_permalink ( $postId );
	$subject = str_replace ( '$sitename', get_option ( 'blogname' ), $subject );
	$content = str_replace ( '$sitename', get_option ( 'blogname' ), $content );
	$content = str_replace ( '$posturl', $link, $content );
	$html = str_replace ( '$postname', $post->post_title, $content );
	
	$sd = new sendcloud_client ();
	$sd->setGatewayUrl ( $sendcloud_api ['sd_send_mail'] );
	$labelIds = sendcloud_getLableIdArray ( $api_user, $api_key );
	$substitution_vars = array (
			'sitename' => get_option ( 'blogname' ),
			'postname' => $post->post_title,
			'posturl' => $link 
	);
	$substitution_vars = json_encode ( $substitution_vars );
	$result = $sd->sendMail ( $api_user, $api_key, $from, $fromname, $maillist_addr, $subject, $html, $labelIds ['posts_publish_email'], 'true' );
}
function sendcloud_replace_param($user, $postname, $link, $to) {
	$subject = __ ( 'Your post has a new comment on $sitename!', SENDCLOUD_I18N_DOMAIN );
	$subject = str_replace ( '$sitename', get_option ( 'blogname' ), $subject );
	$content = __ ( 'Dear $user, your focus on $sitename has a new comment! Open the link<a href="$posturl">$postname</a>, and see the latest comments!', SENDCLOUD_I18N_DOMAIN );
	$content = str_replace ( '$sitename', get_option ( 'blogname' ), $content );
	$content = str_replace ( '$posturl', $link, $content );
	$content = str_replace ( '$user', $user, $content );
	$html = str_replace ( '$postname', $postname, $content );
	return array (
			'subject' => $subject,
			'content' => $html,
			'to' => $to 
	);
}
function sendcloud_notify_post_author($comment_id) {
	global $wpdb;
	global $sendcloud_api;
	sendcloud_init_i18n ();
	if (get_option ( 'sendcloud_posts_reply_notify' ) == 0) {
		return;
	}
	if (get_option ( 'sendcloud_quota_exceeded' ) && (strtotime ( get_option ( 'sendcloud_quota_exceeded' ) ) > strtotime ( date ( "Y-m-d H:i:s" ) ))) {
		return;
	}
	
	try {
		$labelIds = sendcloud_getLableIdArray ( $api_user, $api_key );
	} catch ( Exception $e ) {
		// echo 'Caught exception: '. $e->getMessage()."\n";
	}
	$template_invoke_name = sendcloud_get_template_name ();
	$comment = get_comment ( $comment_id );
	$comment_parent_id = $comment->comment_parent;
	$comment_author_email = $comment->comment_author_email;
	$comment_post_ID = $comment->comment_post_ID;
	$comment_approved = $comment->comment_approved;
	
	// Automatic auditing
	if ($comment_approved == 1 || $comment_approved == "approve") {
		sendcloud_notify_all_comment_user ( $comment_id );
	}
}
function sendcloud_uninstall() {
	global $wpdb;
	$table_account = $wpdb->prefix . 'sendcloud_account';
	$table_account_sql = "DROP TABLE `$table_account`";
	$wpdb->query ( $table_account_sql );
	delete_option ( 'sendcloud_lable' );
	delete_option ( 'sendcloud_email' );
	delete_option ( 'sendcloud_fromname' );
	delete_option ( 'sendcloud_post_publish_notify' );
	delete_option ( 'sendcloud_posts_publish_notify_subject' );
	delete_option ( 'sendcloud_posts_publish_notify_content' );
	delete_option ( 'sendcloud_posts_reply_notify' );
	delete_option ( 'sendcloud_posts_reply_notify_subject' );
	delete_option ( 'sendcloud_posts_reply_notify_content' );
	delete_option ( 'sendcloud_invitecode' );
}
function sendcloud_notify_all_comment_user($comment_id) {
	global $wpdb;
	global $sendcloud_api;
	sendcloud_init_i18n ();
	$template_invoke_name = sendcloud_get_template_name ();
	$comment = get_comment ( $comment_id );
	$comment_post_ID = $comment->comment_post_ID;
	$comments_sql = "select comment_author,comment_author_email,comment_approved from $wpdb->comments where  comment_post_ID=" . $comment_post_ID . " and (comment_approved='approve' or comment_approved='1') and comment_id!=" . $comment_id;
	$approved_comments = $wpdb->get_results ( $comments_sql );
	$post = get_post ( $comment_post_ID );
	$link = get_permalink ( $comment_post_ID );
	$to = array ();
	foreach ( $approved_comments as $comment ) {
		if (! in_array ( $comment->comment_author_email, $to )) {
			$to [] = $comment->comment_author_email;
			$postname [] = $post->post_title;
			$posturl [] = $link;
			$sitenames [] = get_option ( 'blogname' );
			$user [] = $comment->comment_author;
		}
	}
	
	$sd = new sendcloud_client ();
	$sd->setGatewayUrl ( $sendcloud_api ['send_email_template'] );
	
	$table = $wpdb->prefix . 'sendcloud_account';
	$ret = $wpdb->get_row ( "SELECT * FROM " . $table );
	$api_user = $ret->api_user;
	$api_key = $ret->api_key;
	
	$from = get_option ( 'sendcloud_email' ) ? get_option ( 'sendcloud_email' ) : $ret->from;
	$fromname = get_option ( 'sendcloud_fromname' ) ? get_option ( 'sendcloud_fromname' ) : $ret->fromname;
	try {
		$labelIds = sendcloud_getLableIdArray ( $api_user, $api_key );
	} catch ( Exception $e ) {
		// echo 'Caught exception: '. $e->getMessage()."\n";
	}
	
	$subject = __ ( 'Your post has a new comment on $sitename!', SENDCLOUD_I18N_DOMAIN );
	$subject = str_replace ( '$sitename', get_option ( 'blogname' ), $subject );
	$count = count ( $to );
	for($i = 0; $i <= $count - 1; $i ++) {
		$sendto [] = $to [$i];
		$uservar [] = $user [$i];
		if (($i % 100 == 0 && $i > 0) || ($i == ($count - 1))) {
			$substitution_vars = array (
					'to' => $sendto,
					'sub' => array (
							'%sitename%' => $sitenames,
							'%postname%' => $postname,
							'%posturl%' => $posturl,
							'%user%' => $uservar 
					) 
			);
			
			$substitution = json_encode ( $substitution_vars );
			
			$result = $sd->sendEmailTemplate ( $api_user, $api_key, $template_invoke_name, $subject, $from, $fromname, "", $labelIds ['posts_reply_email'], $substitution, 'false' );
			unset ( $sendto );
			unset ( $uservar );
			
			if ($result->message == "error" && stristr ( $result->errors [0], "Request quota exceeded" )) {
				update_option ( "sendcloud_quota_exceeded", date ( "Y-m-d H:i:s", time () + 60 * 60 ) );
				break;
			}
		}
	}
}
function sendcloud_comment_status_change_notify($comment_id, $comment_status) {
	if ($comment_status != 'approve' && $comment_status != 1)
		return;
	sendcloud_notify_all_comment_user ( $comment_id );
}

register_deactivation_hook ( __FILE__, 'sendcloud_uninstall' );
add_action ( 'admin_head', 'sendcloud_create_api_user' );
add_action ( 'admin_menu', 'sendcloud_register_menu_page' );
add_action ( 'publish_post', 'sendcloud_notify_email_subscriber', 10, 2 );
add_action ( 'comment_post', 'sendcloud_notify_post_author' );
add_action ( 'wp_set_comment_status', 'sendcloud_comment_status_change_notify', 20, 2 );
require_once (dirname ( __FILE__ ) . "/sendcloud_widget.php");

?>
