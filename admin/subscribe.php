<?php
require_once (sdPATH . "sendcloud_client.php");
require_once (sdPATH . "sendcloud_tool.php");
$sd = new sendcloud_client();
$table = $wpdb->prefix . 'sendcloud_account';
$account = $wpdb->get_row ( "SELECT * FROM " . $table );
if (empty ( $account )) {
	global $wpdb;
	global $sendcloud_api;
	$sd->setGatewayUrl ( $sendcloud_api ['get_api_userInfo'] );
	$current_user = wp_get_current_user ();
	$result = $sd->getAccount ( get_option ( 'siteurl' ),  get_option('sendcloud_email') );
	if ($result->message == 'success') {
		$data = array (
				'api_user' => $result->apiUserInfo->apiUser,
				'api_key' =>$result->apiUserInfo->apiKey,
				'from' => $result->apiUserInfo->fromEmail,
				'fromname' => get_option ( 'blogname' ),
				'datetime' => date ( 'Y-m-d H:i:s', time () )
		);
		$wpdb->insert ( $table, $data );
		$apiUser=$result->apiUserInfo->apiUser;
		$apiKey=$result->apiUserInfo->apiKey;
	} else {
		echo __ ( 'create api_user error!' );
	}
}else{
	$apiUser=$account->api_user;
    $apiKey=$account->api_key;
	
}
$sd->setGatewayUrl ( $sendcloud_api ['get_maillist_member'] );
$result=$sd->getMaillistMember($apiUser, $apiKey,'wp_post_publish_maillist@sendcloud.org');

if(empty($result)){
	die('server connection timeout');
}
if($result->message=='success'){
    $members=$result->members;
}
?>
 <div style="padding:15px">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th><?php _e('Email Address',SENDCLOUD_I18N_DOMAIN);?></th>
                    </tr>
                </thead>
                <tbody>
                   	<?php
					
					foreach ( $members as $data ) {
						?>
						<tr>
						<td><?php 
						echo $data->address;
						?></td>
						
					</tr>
						<?php }?>
                </tbody>
            </table>
        </div>
    </div>
    <?php ?>