<?php
$sendcloud_api = array (
		'sd_send_mail' => 'http://sendcloud.sohu.com/webapi/mail.send.json',
		'get_api_userInfo' => 'http://sendcloud.sohu.com/webapi/discuz.getApiUserInfo.json',
		'update_email' => 'http://sendcloud.sohu.com/webapi/discuz.updateEmail.json',
		'send_email_template' => 'http://sendcloud.sohu.com/webapi/mail.send_template.json',
		'add_list_member' => 'http://sendcloud.sohu.com/webapi/list_member.add.json',
		'delete_list_member' => 'http://sendcloud.sohu.com/webapi/list_member.delete.json',
		'get_quota' => 'http://sendcloud.sohu.com/webapi/discuz.getUserQuota.json',
		'stats' => 'http://sendcloud.sohu.com/webapi/stats.get.json',
		'get_label_list' => 'http://sendcloud.sohu.com/webapi/label.list.json',
		'create_label' => 'http://sendcloud.sohu.com/webapi/label.create.json',
		'get_subinfo_code' => 'http://sendcloud.sohu.com/webapi/discuz.getSubinfo.json',
		'get_maillist_member' => 'http://sendcloud.sohu.com/webapi/list_member.get.json',
		'update_subject_fromname' => 'http://sendcloud.sohu.com/webapi/discuz.updateSubinfo.json' 
);
function sendcloud_create_api_user() {
	global $wpdb;
	global $sendcloud_api;
	$table = $wpdb->prefix . 'sendcloud_account';
	$total = $wpdb->get_var ( "SELECT COUNT(api_user) FROM " . $table );
	if ($total == 0) {
		$sd = new sendcloud_client ();
		$sd->setGatewayUrl ( $sendcloud_api ['get_api_userInfo'] );
		$result = $sd->getAccount ( get_option ( 'siteurl' ), get_option ( 'sendcloud_email' ) );
		if ($result->message == 'success') {
			$data = array (
					'api_user' => $result->apiUserInfo->apiUser,
					'api_key' => $result->apiUserInfo->apiKey,
					'from' => $result->apiUserInfo->fromEmail,
					'fromname' => get_option ( 'blogname' ),
					'datetime' => date ( 'Y-m-d H:i:s', time () ) 
			);
			$wpdb->insert ( $table, $data );
			$sd->setGatewayUrl ( $sendcloud_api ['get_subinfo_code'] );
			$result = $sd->getSubinfocode ( $result->apiUserInfo->apiUser, $result->apiUserInfo->apiKey, 'wp_maillist' );
			if ($result->message == 'success') {
				add_option ( 'sendcloud_invitecode', $result->maillistSubInvitecode->invitecode );
			}
		}
	}
	load_plugin_textdomain ( 'sendcloud', false, dirname ( plugin_basename ( __FILE__ ) ) . '/languages/' );
}
function sendcloud_getLableIdArray($api_user, $api_key) {
	global $sendcloud_api;
	
	$lables = get_option ( 'sendcloud_lable' );
	
	if (! empty ( $lables )) {
		return $lables;
	}
	$sd = new sendcloud_client ();
	$sd->setGatewayUrl ( $sendcloud_api ['get_label_list'] );
	$result = $sd->getLabelList ( $api_user, $api_key );
	$lable_posts_publish_email_remind = __ ( 'Post Published Reminder', 'sendcloud' );
	$lable_posts_reply_email_remind = __ ( 'Post Commented Reminder', 'sendcloud' );
	$wordpress_label = array (
			'posts_publish_email',
			'posts_reply_email' 
	);
	$search_label4send = array ();
	if ($result->message == 'success' && $result->totalCount == 0) {
		$sd->setGatewayUrl ( $sendcloud_api ['create_label'] );
		$reponse1 = $sd->create_label ( $api_user, $api_key, 'posts_publish_email' );
		if ($reponse1->message == 'success') {
			$search_label4send [$reponse1->label->labelName] = $reponse1->label->labelId;
		}
		$reponse2 = $sd->create_label ( $api_user, $api_key, 'posts_reply_email' );
		if ($reponse2->message == 'success') {
			$search_label4send [$reponse2->label->labelName] = $reponse2->label->labelId;
		}
	} else {
		foreach ( $result->list as $lable ) {
			if (in_array ( $lable->labelName, $wordpress_label )) {
				$search_label4send [$lable->labelName] = $lable->labelId;
			}
		}
	}
	
	if (count ( $search_label4send ) != 2) {
		foreach ( $wordpress_label as $value ) {
			if (! array_key_exists ( $value, $search_label4send )) {
				$sd->setGatewayUrl ( $sendcloud_api ['create_label'] );
				
				$reponse = $sd->create_label ( $api_user, $api_key, $value );
				
				if ($reponse->message == 'success') {
					$search_label4send [$reponse->label->labelName] = $reponse->label->labelId;
				}
			}
		}
	}
	
	return $search_label4send;
}
function validate_email($email) {
	$is_ok = true;
	if (! empty ( $email )) {
		$is_mail = is_email ( $email );
		if (! $is_mail) {
			$is_ok = false;
		} else if (strlen ( $email ) > 64) {
			$is_ok = false;
		}
	}
	return $is_ok;
}
function validate_fromname($fromname) {
	$is_ok = true;
	if (! empty ( $fromname )) {
		if (strlen ( $fromname ) > 50) {
			$is_ok = false;
		}
	}
	return $is_ok;
}
function validate_date($date,$format='Y-m-d'){
	$t=date_parse_from_format($format,$date);
	if(empty($t['errors'])){
		return true;
	}else{
		return false;
	}
}

