<?php
require_once (sdPATH . "sendcloud_client.php");
require_once (sdPATH . "sendcloud_tool.php");
extract ( $_POST );

if (isset ( $action ) && $action == 'update') {
	
	if (! validate_email ( $sd_email )) {
		echo "<div class='alert alert-info'>Email address format error or The  Maximum length of email address is 64  characters.</div>";
	} else if (! validate_fromname ( $sd_fromname )) {
		echo "<div class='alert alert-info'>Display name format error or The  Maximum length of Display name is 50  characters.</div>";
	} else {
		$sd_email = sanitize_email ( $sd_email );
		$sd_fromname = sanitize_text_field ( $sd_fromname );
		update_option ( 'sendcloud_email', $sd_email );
		update_option ( 'sendcloud_fromname', $sd_fromname );
		update_option ( 'sendcloud_post_publish_notify', $sd_post_publish_notify );
		update_option ( 'sendcloud_posts_reply_notify', $sd_posts_reply_notify );
		$sd = new sendcloud_client ();
		$table = $wpdb->prefix . 'sendcloud_account';
		$account = $wpdb->get_row ( "SELECT * FROM " . $table );
		if (empty ( $account )) {
			global $wpdb;
			global $sendcloud_api;
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
				$apiUser = $result->apiUserInfo->apiUser;
				$apiKey = $result->apiUserInfo->apiKey;
			} else {
				echo __ ( 'create api_user error!' );
			}
		} else {
			$apiUser = $account->api_user;
			$apiKey = $account->api_key;
		}
		$sd->setGatewayUrl ( $sendcloud_api ['update_subject_fromname'] );
		$subject = sprintf ( __ ( 'Subscription confirmation email from "%s"', SENDCLOUD_I18N_DOMAIN ), get_option ( 'blogname' ) );
		$sd->updateSubinfo ( $apiUser, $apiKey, $subject, $sd_fromname );
		echo "<div class='alert alert-info'>Setting Updated Successfully</div>";
	}
}
?>

<div style="padding: 15px">
	<form action="admin.php?page=sendcloud-email-sender/admin/setting.php" method="post">
		<input type="hidden" name="action" value="update" />
		<div class="panel panel-default">
			<div class="panel-heading"><?php _e('Basic Setting',SENDCLOUD_I18N_DOMAIN)?></div>
			<div class="panel-body">
				<div class="form-group">
					<label><?php _e('Email Address',SENDCLOUD_I18N_DOMAIN)?></label> <input
						type="text" class="form-control" name="sd_email"
						value="<?php echo get_option('sendcloud_email');?>" maxlength="64"><?php _e('Please fill in the real common email address to receive the official update information!',SENDCLOUD_I18N_DOMAIN)?>
				</div>
				<div class="form-group">
					<label><?php _e('Sender Display',SENDCLOUD_I18N_DOMAIN)?></label> <input
						type="text" class="form-control" name="sd_fromname"
						value="<?php echo get_option('sendcloud_fromname');?>">
				</div>
			</div>
		</div>
		<div class="panel panel-default">
			<div class="panel-heading"><?php _e('Email Setting',SENDCLOUD_I18N_DOMAIN)?></div>
			<div class="panel-body">
				<div role="tabpanel">
					<ul class="nav nav-tabs" role="tablist">
						<li class="active" role="presentation"><a href="#tab-article"
							aria-controls="tab-acticle" role="tab" data-toggle="tab"><?php _e('Post Published Reminder',SENDCLOUD_I18N_DOMAIN)?></a>
						</li>
						<li role="presentation"><a href="#tab-comment"
							aria-controls="tab-comment" role="tab" data-toggle="tab"><?php _e('Post Commented Reminder',SENDCLOUD_I18N_DOMAIN)?></a>
						</li>
					</ul>
					<div class="tab-content">
						<div class="tab-pane active" id="tab-article" role="tabpanel">
							<p></p>
							<div class="form-group">
								<label><?php _e('Open Post Published Reminder',SENDCLOUD_I18N_DOMAIN)?><small
									class="text-muted">(<?php _e('After the opening, when a new post published, subscriber will receive this welcome email.',SENDCLOUD_I18N_DOMAIN)?>)</small></label>
								<div class="radio">
									<label class="radio-inline"> <input type="radio"
										name="sd_post_publish_notify" value="1"
										<?php if(get_option('sendcloud_post_publish_notify')==1) echo "checked";?>><?php _e('Yes',SENDCLOUD_I18N_DOMAIN)?>
                                        </label> <label
										class="radio-inline"> <input type="radio"
										name="sd_post_publish_notify" value="0"
										<?php if(get_option('sendcloud_post_publish_notify')==0) echo "checked";?>><?php _e('No',SENDCLOUD_I18N_DOMAIN)?>
                                        </label>
								</div>
							</div>
							<div class="form-group">
								<label><?php _e('Email Title',SENDCLOUD_I18N_DOMAIN)?><small
									class="text-muted">(<?php _e('The email title and email content cannot be modified!',SENDCLOUD_I18N_DOMAIN)?>)</small></label>
								<input type="text" class="form-control"
									value="<?php
									$subject = get_option ( 'sendcloud_posts_publish_notify_subject' );
									if ($subject == '') {
										_e ( 'Dear,  your subscription $sitename is updated!', SENDCLOUD_I18N_DOMAIN );
									} else {
										echo get_option ( 'sendcloud_posts_publish_notify_subject' );
									}
									?>"
									disabled="disabled">
							</div>
							<div class="form-group">
								<label><?php _e('Email Content',SENDCLOUD_I18N_DOMAIN)?><small
									class="text-muted">(<?php _e('The email title and email content cannot be modified!',SENDCLOUD_I18N_DOMAIN)?>)</small></label>
								<textarea disabled="disabled" name="email-content" id=""
									cols="30" rows="3" class="form-control"><?php
									$subject_content = get_option ( 'sendcloud_posts_publish_notify_content' );
									if ($subject_content == '') {
										_e ( 'Dear subscribers, $sitename has new interesting updates! Click to view the latest posts: <a href="$posturl">$postname</a>', SENDCLOUD_I18N_DOMAIN );
									} else {
										echo get_option ( 'sendcloud_posts_publish_notify_content' );
									}
									?>
                                    </textarea>
							</div>
							<div class="form-group">
								<button class="btn btn-success btn-lg btn-block"><?php _e('Save',SENDCLOUD_I18N_DOMAIN)?></button>
							</div>
						</div>
						<div class="tab-pane" id="tab-comment" role="tabpanel">
							<p></p>
							<div class="form-group">
								<label><?php _e('Open Post Commented Reminder',SENDCLOUD_I18N_DOMAIN)?><small
									class="text-muted">(<?php _e('After the opening, the comments related users including the author of the post, the author of the comment will receive this email reminder.',SENDCLOUD_I18N_DOMAIN)?>)</small></label>
								<div class="radio">
									<label class="radio-inline"> <input type="radio"
										name="sd_posts_reply_notify" value="1"
										<?php if (get_option('sendcloud_posts_reply_notify')==1)echo "checked"; ?>><?php _e('Yes',SENDCLOUD_I18N_DOMAIN)?>
                                        </label> <label
										class="radio-inline"> <input type="radio"
										name="sd_posts_reply_notify" value="0"
										<?php if (get_option('sendcloud_posts_reply_notify')==0)echo "checked"; ?>><?php _e('No',SENDCLOUD_I18N_DOMAIN)?>
                                        </label>
								</div>
							</div>
							<div class="form-group">
								<label><?php  _e('Email Title',SENDCLOUD_I18N_DOMAIN)?><small
									class="text-muted">(<?php _e('The email title and email content cannot be modified!',SENDCLOUD_I18N_DOMAIN)?>)</small></label>
								<input type="text" class="form-control"
									value="<?php
									$subject = get_option ( 'sendcloud_posts_reply_notify_subject' );
									if ($subject == '') {
										_e ( 'Your post has a new comment on $sitename!', SENDCLOUD_I18N_DOMAIN );
									} else {
										echo get_option ( 'sendcloud_posts_reply_notify_subject' );
									}
									?>"
									disabled="disabled">
							</div>
							<div class="form-group">
								<label><?php _e('Email Content',SENDCLOUD_I18N_DOMAIN)?><small
									class="text-muted">(<?php _e('The email title and email content cannot be modified!',SENDCLOUD_I18N_DOMAIN)?>)</small></label>
								<textarea disabled="disabled" name="email-content"
									id="email-content" cols="30" rows="3" class="form-control"><?php
									$subject_content = get_option ( 'sendcloud_posts_reply_notify_content' );
									if ($subject_content == '') {
										_e ( 'Dear $user, your focus on $sitename has a new comment! Open the link<a href="$posturl">$postname</a>, and see the latest comments!', SENDCLOUD_I18N_DOMAIN );
									} else {
										echo get_option ( 'sendcloud_posts_reply_notify_content' );
									}
									?>
                                    </textarea>
							</div>
							<div class="form-group">
								<button class="btn btn-success btn-lg btn-block"><?php _e('Save',SENDCLOUD_I18N_DOMAIN)?></button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</form>
</div>