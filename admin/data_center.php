<?php
require_once (sdPATH . "sendcloud_client.php");
require_once (sdPATH . "sendcloud_tool.php");
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
	} else {
		echo __ ( 'create api_user error!' );
	}
}

$search_label = array ();
$search_label4send = array ();
$lable_posts_publish_email_remind = __ ( 'Post Published Reminder', SENDCLOUD_I18N_DOMAIN );
$lable_posts_reply_email_remind = __ ( 'Post Commented Reminder', SENDCLOUD_I18N_DOMAIN );
$wordpress_label = array (
		'posts_publish_email',
		'posts_reply_email' 
);

$discz_labelname_sendcloud_labelname_map = array (
		'posts_publish_email' => $lable_posts_publish_email_remind,
		'posts_reply_email' => $lable_posts_reply_email_remind 
);

$search_label4send = get_option ( 'sendcloud_lable' );

if (empty ( $search_label4send )) {
	
	$search_label4send = sendcloud_getLableIdArray ( $account->api_user, $account->api_key );
	add_option ( 'sendcloud_lable', $search_label4send );
}

foreach ( $search_label4send as $key => $value ) {
	$search_label [$value] = $discz_labelname_sendcloud_labelname_map [$key];
}

$begin_date = (isset ( $_POST ['begin_date'] ) ? $_POST ['begin_date'] : null);
$end_date = (isset ( $_POST ['end_date'] ) ? $_POST ['end_date'] : null);
$time_type = (isset ( $_POST ['time_type'] ) ? $_POST ['time_type'] : 0);
$days = (isset ( $_POST ['days'] ) ? $_POST ['days'] : 10);
$dimension = (isset ( $_POST ['dimension'] ) ? $_POST ['dimension'] : 0);
$f_tab = (isset ( $_POST ['f_tab'] ) ? $_POST ['f_tab'] : 1);
$mail_label = (isset ( $_POST ['mail_label'] ) ? $_POST ['mail_label'] : "");
$stats_url = $sendcloud_api ['stats'];
$time_type = ($time_type == 0) ? 7 : $time_type;
if ($begin_date && ! validate_date ( $begin_date )) {
	$begin_date = null;
}
if ($end_date && ! validate_date ( $end_date )) {
	$end_date = null;
}

if(!is_numeric($time_type)){
	$time_type=0;
}
if(!is_numeric($dimension)){
	$dimension=0;
}

if(!is_numeric($f_tab)){
	$f_tab=1;
}
if(empty($mail_label)){
	$mail_label = sanitize_text_field ( $mail_label );
}
if(!is_numeric($days)){
	$days=10;
}

$str_begin_date = $begin_date;
$str_end_date = $end_date;

if ($time_type == 0) {
	$search_param ['time_type'] = 7;
}
if ($begin_date == null && $end_date == null) {
	$str_begin_date = date ( "Y-m-d", mktime ( 0, 0, 0, date ( "m" ), date ( "d" ) - $time_type + 1, date ( "Y" ) ) );
	$str_end_date = date ( "Y-m-d", mktime ( 23, 59, 59, date ( "m" ), date ( "d" ) - $time_type + 1, date ( "Y" ) ) );
	if ($time_type == 7 || $time_type == 30) {
		$str_end_date = date ( "Y-m-d", mktime ( 0, 0, 0, date ( "m" ), date ( "d" ), date ( "Y" ) ) );
	}
}
if ($time_type == 99) {
	$daterange = date ( 'm/d/Y', strtotime ( $str_begin_date ) ) . '-' . date ( 'm/d/Y', strtotime ( $str_end_date ) );
}
$sd->setGateWayUrl ( $stats_url );
$data_list = $sd->getStats ( $account->api_user, $account->api_key, $days, $str_begin_date, $str_end_date, '', $mail_label, '', 0 );
$message = $data_list->message;
$replort_list = $data_list->stats;
$responseData = array ();
$chart_data_send_summary = array ();
$chart_data_tracking_summary = array ();
$stats_total = array (
		'request' => 0,
		'deliveredNum' => 0,
		'clickNum' => 0,
		'spamReportedNum' => 0,
		'invalidEmailsNum' => 0,
		'bounceNum' => 0,
		'openNum' => 0,
		'uniqueOpensNum' => 0,
		'unsubscribeNum' => 0,
		'uniqueClicksNum' => 0 
);

foreach ( $replort_list as $data ) {
	$dimension_value = ($dimension == 1) ? $data->domain : $data->sendDate;
	if (! array_key_exists ( $dimension_value, $responseData )) {
		$line = array ();
		$line ['request'] = $data->request;
		$line ['deliveredNum'] = $data->deliveredNum;
		$line ['clickNum'] = $data->clickNum;
		$line ['uniqueClicksNum'] = $data->uniqueClicksNum;
		$line ['openNum'] = $data->openNum;
		$line ['uniqueOpensNum'] = $data->uniqueOpensNum;
		$line ['unsubscribeNum'] = $data->unsubscribeNum;
		$line ['spamReportedNum'] = $data->spamReportedNum;
		$line ['invalidEmailsNum'] = $data->invalidEmailsNum;
		$line ['sendDate'] = $data->sendDate;
		$line ['bounceNum'] = $data->bounceNum;
		
		$responseData [$dimension_value] = $line;
	} else {
		$responseData [$dimension_value] ['request'] += $data->request;
		$responseData [$dimension_value] ['deliveredNum'] += $data->deliveredNum;
		$responseData [$dimension_value] ['spamReportedNum'] += $data->spamReportedNum;
		$responseData [$dimension_value] ['invalidEmailsNum'] += $data->invalidEmailsNum;
		$responseData [$dimension_value] ['bounceNum'] += $data->bounceNum;
		$responseData [$dimension_value] ['clickNum'] += $data->clickNum;
		$responseData [$dimension_value] ['uniqueClicksNum'] += $data->uniqueClicksNum;
		$responseData [$dimension_value] ['unsubscribeNum'] += $data->unsubscribeNum;
		$responseData [$dimension_value] ['openNum'] += $data->openNum;
		$responseData [$dimension_value] ['uniqueOpensNum'] += $data->uniqueOpensNum;
	}
	$stats_total ['request'] += $data->request;
	$stats_total ['deliveredNum'] += $data->deliveredNum;
	$stats_total ['spamReportedNum'] += $data->spamReportedNum;
	$stats_total ['invalidEmailsNum'] += $data->invalidEmailsNum;
	$stats_total ['bounceNum'] += $data->bounceNum;
	$stats_total ['openNum'] += $data->openNum;
	$stats_total ['uniqueOpensNum'] += $data->uniqueOpensNum;
	$stats_total ['clickNum'] += $data->clickNum;
	$stats_total ['uniqueClicksNum'] += $data->uniqueClicksNum;
	$stats_total ['unsubscribeNum'] += $data->unsubscribeNum;
}

// chart_data
$chart_data = array ();
$chart_cates = array ();
$request = array ();
$deliveredNum = array ();
$spamReportedNum = array ();
$invalidEmailsNum = array ();
$bounceNum = array ();
$openNum = array ();
$uniqueOpen = array ();
$unsubscribe = array ();
foreach ( $responseData as $key => $value ) {
	array_push ( $chart_cates, $key );
	array_push ( $request, $value ['request'] );
	array_push ( $deliveredNum, $value ['deliveredNum'] );
	array_push ( $spamReportedNum, $value ['spamReportedNum'] );
	array_push ( $invalidEmailsNum, $value ['invalidEmailsNum'] );
	array_push ( $bounceNum, $value ['bounceNum'] );
	array_push ( $openNum, $value ['openNum'] );
	if (array_key_exists ( '$unsubscribe', $value ))
		array_push ( $unsubscribe, $value ['unsubscribeNum'] );
	if (array_key_exists ( '$uniqueOpen', $value ))
		array_push ( $uniqueOpen, $value ['uniqueOpensNum'] );
}

$request_name = iconv ( "utf-8", "UTF-8//IGNORE", __ ( 'Request', SENDCLOUD_I18N_DOMAIN ) );
$invalid_name = iconv ( "utf-8", "UTF-8//IGNORE", __ ( 'Invalid Email', SENDCLOUD_I18N_DOMAIN ) );
$send_name = iconv ( "utf-8", "UTF-8//IGNORE", __ ( 'Send', SENDCLOUD_I18N_DOMAIN ) );
$spam_name = iconv ( "utf-8", "UTF-8//IGNORE", __ ( 'Spam Report', SENDCLOUD_I18N_DOMAIN ) );
$soft_name = iconv ( "utf-8", "UTF-8//IGNORE", __ ( 'Soft Bounce', SENDCLOUD_I18N_DOMAIN ) );
$open_name = iconv ( "utf-8", "UTF-8//IGNORE", __ ( 'Open', SENDCLOUD_I18N_DOMAIN ) );
$uniqopen_name = iconv ( "utf-8", "UTF-8//IGNORE", __ ( 'Independent Open', SENDCLOUD_I18N_DOMAIN ) );
$unsubscribe_name = iconv ( "utf-8", "UTF-8//IGNORE", __ ( 'Unsubscribe', SENDCLOUD_I18N_DOMAIN ) );
array_push ( $chart_data_tracking_summary, array (
		'name' => $open_name,
		'data' => $openNum 
) );
array_push ( $chart_data_tracking_summary, array (
		'name' => $uniqopen_name,
		'data' => $uniqueOpen 
) );
array_push ( $chart_data_tracking_summary, array (
		'name' => $unsubscribe_name,
		'data' => $unsubscribe 
) );

array_push ( $chart_data_send_summary, array (
		'name' => $request_name,
		'data' => $request 
) );
array_push ( $chart_data_send_summary, array (
		'name' => $send_name,
		'data' => $deliveredNum 
) );
array_push ( $chart_data_send_summary, array (
		'name' => $spam_name,
		'data' => $spamReportedNum 
) );
array_push ( $chart_data_send_summary, array (
		'name' => $invalid_name,
		'data' => $invalidEmailsNum 
) );
array_push ( $chart_data_send_summary, array (
		'name' => $soft_name,
		'data' => $bounceNum 
) );

$chart_cates = json_encode ( $chart_cates );
$chart_data_send_summary_json = json_encode ( $chart_data_send_summary );
$chart_data_tracking_summary_json = json_encode ( $chart_data_tracking_summary );
$myresult = json_encode ( $responseData );

?>

<div style="padding: 15px">
	<ul class="nav nav-tabs" role="tablist">
		<li class="active" role="presentation"><a href="#tab-send"
			aria-controls="tab-send" role="tab" data-toggle="tab"><?php _e('Sending Summary',SENDCLOUD_I18N_DOMAIN)?></a></li>
		<li role="presentation"><a href="#tab-track" aria-controls="tab-track"
			role="tab" data-toggle="tab"><?php _e('Tracking Summary',SENDCLOUD_I18N_DOMAIN)?></a></li>
	</ul>
	<div class="tab-content" style="overflow: hidden">
		<div class="tab-pane active" id="tab-send" role="tabpanel">
			<br>
			<div class="tool">
				<form action="admin.php?page=sendcloud-email-sender/admin/data_center.php"
					method="post" class="form row">
					<div class="col-md-2">
						<div class="form-group">
							<label><?php _e('Email Type',SENDCLOUD_I18N_DOMAIN)?></label> 
							<?php
							echo "<select name=\"mail_label\" id=\"mail_label\" class=\"form-control\">";
							?>
							<option value=""><?php _e('All',SENDCLOUD_I18N_DOMAIN)?></option>;
							<?php
							foreach ( $search_label as $key => $value ) {
								if ($mail_label == $key)
									echo "<option value=\"" . $key . "\" selected>" . $value . "</option>";
								else
									echo "<option value=\"" . $key . "\">" . $value . "</option>";
							}
							echo "</select>";
							?>
						</div>
					</div>
					<div class="col-md-2">
						<div class="form-group">
							<label>
							<?php _e('Statistical Dimension',SENDCLOUD_I18N_DOMAIN)?>
							</label> <select name="dimension" id="dimension"
								class="form-control">
								<option value="0" <?php if($dimension==0){echo "selected";}?>>
								<?php _e('Count per day',SENDCLOUD_I18N_DOMAIN)?>
								</option>
								<option value="1" <?php if($dimension==1){echo "selected";}?>>
								<?php _e('Count per domain',SENDCLOUD_I18N_DOMAIN)?>
								</option>
							</select>
						</div>
					</div>
					<div class="col-md-2" id="search_date">
						<div class="form-group">
							<label><?php _e('Statistical Time',SENDCLOUD_I18N_DOMAIN)?></label>
							<select name="time_type" id="time_type"
								class="form-control send-time-type">
								<option value="1" <?php if($time_type==1){echo "selected";}?>><?php _e('Today',SENDCLOUD_I18N_DOMAIN)?></option>
								<option value="2" <?php if($time_type==2){echo "selected";}?>><?php _e('Yesterday',SENDCLOUD_I18N_DOMAIN)?></option>
								<option value="7" <?php if($time_type==7){echo "selected";}?>><?php _e('The last 7 days',SENDCLOUD_I18N_DOMAIN)?></option>
								<option value="30" <?php if($time_type==30){echo "selected";}?>><?php _e('The last 30 days',SENDCLOUD_I18N_DOMAIN)?></option>
								<option value="99" <?php if($time_type==99){echo "selected";}?>><?php _e('Customize',SENDCLOUD_I18N_DOMAIN)?></option>
							</select>
						</div>
					</div>
					<div class="col-md-2 custom-send-date" style="display: none">
						<label><?php _e('Customize',SENDCLOUD_I18N_DOMAIN)?></label> <input
							name="daterange" type="text" class="form-control send-daterange"
							value="<?php echo $daterange?>">
					</div>
					<div class="col-md-2">
						<div class="form-group">
							<label>&nbsp;</label></br> <input class="btn btn-success"
								type="submit" value="<?php _e('Search',SENDCLOUD_I18N_DOMAIN)?>" />
						</div>
					</div>
					<input type="hidden" name="begin_date" id="begin_date" /> <input
						type="hidden" name="end_date" id="end_date" /> <input
						type="hidden" name="f_tab" id="f_tab" value="1" />
				</form>
			</div>
			<div class="clearfix"></div>
			<div class="card-list row">
				<div class="col-md-2">
					<div class="panel panel-default text-center">
						<div class="panel-heading">
						<?php _e('Request',SENDCLOUD_I18N_DOMAIN)?></div>
						<div class="panel-body">
						<?php echo $stats_total['request'];?>
							<br> &nbsp;
						</div>
					</div>
				</div>
				<div class="col-md-2">
					<div class="panel panel-default text-center">
						<div class="panel-heading">
						<?php _e('Send',SENDCLOUD_I18N_DOMAIN)?>
						</div>
						<div class="panel-body">
							<?php echo $stats_total['deliveredNum'];?> <br /> <small
								class="text-muted"><?php
								if ($stats_total ['request'] == 0) {
									echo '0%';
								} else
									echo round ( ($stats_total ['deliveredNum'] / $stats_total ['request'] * 1.0) * 100, 2 ) . '%';
								?></small>
						</div>
					</div>
				</div>
				<div class="col-md-2">
					<div class="panel panel-default text-center">
						<div class="panel-heading"><?php _e('Invalid Email',SENDCLOUD_I18N_DOMAIN)?></div>
						<div class="panel-body">
							<?php echo $stats_total['invalidEmailsNum'];?> <br /> <small
								class="text-muted"><?php
								if ($stats_total ['request'] == 0) {
									echo '0%';
								} else {
									echo round ( ($stats_total ['invalidEmailsNum'] / $stats_total ['request'] * 1.0) * 100, 2 ) . '%';
								}
								
								?></small>
						</div>
					</div>
				</div>
				<div class="col-md-2">
					<div class="panel panel-default text-center">
						<div class="panel-heading"><?php _e('Soft Bounce',SENDCLOUD_I18N_DOMAIN)?></div>
						<div class="panel-body">
							<?php echo $stats_total['bounceNum'];?> <br /> <small
								class="text-muted"><?php
								if ($stats_total ['request'] == 0) {
									echo '0%';
								} else {
									echo round ( ($stats_total ['bounceNum'] / $stats_total ['request'] * 1.0) * 100, 2 ) . '%';
								}
								
								?></small>
						</div>
					</div>
				</div>
				<div class="col-md-2">
					<div class="panel panel-default text-center">
						<div class="panel-heading"><?php _e('Spam Report',SENDCLOUD_I18N_DOMAIN)?></div>
						<div class="panel-body">
							<?php echo $stats_total['spamReportedNum'];?><br> <small
								class="text-muted"><?php
								if ($stats_total ['request'] == 0) {
									echo '0%';
								} else {
									echo round ( ($stats_total ['spamReportedNum'] / $stats_total ['request'] * 1.0) * 100, 2 ) . '%';
								}
								?></small>
						</div>
					</div>
				</div>
			</div>
			<div class="clearfix"></div>
			<div class="sendchart-container"></div>
			<table class="table table-bordered table-striped">
				<thead>
					<tr>
						<th><?php _e('Date',SENDCLOUD_I18N_DOMAIN)?></th>
						<th><?php _e('Request',SENDCLOUD_I18N_DOMAIN)?></th>
						<th><?php _e('Send',SENDCLOUD_I18N_DOMAIN)?></th>
						<th><?php _e('Invalid Email',SENDCLOUD_I18N_DOMAIN)?></th>
						<th><?php _e('Soft Bounce',SENDCLOUD_I18N_DOMAIN)?></th>
						<th><?php _e('Spam Report',SENDCLOUD_I18N_DOMAIN)?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					
					foreach ( $responseData as $key => $value ) {
						?>
						<tr>
						<td><?php echo $key?></td>
						<td><?php echo $value['request'] ?></td>
						<td><?php echo $value['deliveredNum']?></td>
						<td><?php echo $value['invalidEmailsNum']?></td>
						<td><?php echo $value['bounceNum']?></td>
						<td><?php echo $value['spamReportedNum']?></td>
					</tr>
						<?php }?>
					</tbody>
			</table>
		</div>
		<div class="tab-pane" id="tab-track" role="tabpanel">
			<br>
			<div class="tool">
				<form action="admin.php?page=sendcloud/admin/data_center.php"
					class="form row" method="post">
					<div class="col-md-2">
						<div class="form-group">
							<label><?php _e('Email Type',SENDCLOUD_I18N_DOMAIN)?></label> 
							<?php
							echo "<select name=\"mail_label\" id=\"mail_label\" class=\"form-control\">";
							?>
							<option value=""><?php _e('All',SENDCLOUD_I18N_DOMAIN)?></option>;
							<?php
							foreach ( $search_label as $key => $value ) {
								if ($mail_label == $key)
									echo "<option value=\"" . $key . "\" selected>" . $value . "</option>";
								else
									echo "<option value=\"" . $key . "\">" . $value . "</option>";
							}
							echo "</select>";
							?>
						</div>
					</div>
					<div class="col-md-2">
						<div class="form-group">
							<label>
							<?php _e('Statistical Dimension',SENDCLOUD_I18N_DOMAIN)?>
							</label> <select name="dimension" id="dimension"
								class="form-control">
								<option value="0" <?php if($dimension==0){echo "selected";}?>>
								<?php _e('Count per day',SENDCLOUD_I18N_DOMAIN)?>
								</option>
								<option value="1" <?php if($dimension==1){echo "selected";}?>>
								<?php _e('Count per domain',SENDCLOUD_I18N_DOMAIN)?>
								</option>
							</select>
						</div>
					</div>
					<div class="col-md-2">
						<div class="form-group">
							<label><?php _e('Statistical Time',SENDCLOUD_I18N_DOMAIN)?></label>
							<select name="time_type" id="time_type"
								class="form-control track-time-type">
								<option value="1" <?php if($time_type==1){echo "selected";}?>><?php _e('Today',SENDCLOUD_I18N_DOMAIN)?></option>
								<option value="2" <?php if($time_type==2){echo "selected";}?>><?php _e('Yesterday',SENDCLOUD_I18N_DOMAIN)?></option>
								<option value="7" <?php if($time_type==7){echo "selected";}?>><?php _e('The last 7 days',SENDCLOUD_I18N_DOMAIN)?></option>
								<option value="30" <?php if($time_type==30){echo "selected";}?>><?php _e('The last 30 days',SENDCLOUD_I18N_DOMAIN)?></option>
								<option value="99" <?php if($time_type==99){echo "selected";}?>><?php _e('Customize',SENDCLOUD_I18N_DOMAIN)?></option>
							</select>
						</div>
					</div>
					<div class="col-md-2 custom-track-date" id="search_date"
						style="display: none">
						<label><?php _e('Customize',SENDCLOUD_I18N_DOMAIN)?></label> <input
							name="daterange" type="text" class="form-control track-daterange"
							value="<?php echo $daterange;?>">
					</div>
					<div class="col-md-2">
						<div class="form-group">
							<label>&nbsp;</label></br> <input class="btn btn-success"
								type="submit" value="<?php _e('Search',SENDCLOUD_I18N_DOMAIN)?>" />
						</div>
					</div>
					<input type="hidden" name="begin_date" id="begin_date1" /> <input
						type="hidden" name="end_date" id="end_date1" /> <input
						type="hidden" name="f_tab" id="f_tab" value="2" />
				</form>
			</div>
			<div class="clearfix"></div>
			<div class="card-list row">
				<div class="col-md-2">
					<div class="panel panel-default text-center">
						<div class="panel-heading"><?php _e('Open',SENDCLOUD_I18N_DOMAIN)?></div>
						<div class="panel-body">
							<?php echo $stats_total['openNum'];?> <br> 
							<?php
							if ($stats_total ['request'] == 0) {
								echo '0%';
							} else {
								echo round ( ($stats_total ['openNum'] / $stats_total ['request'] * 1.0) * 100, 2 ) . '%';
							}
							?>
						</div>
					</div>
				</div>
				<div class="col-md-2">
					<div class="panel panel-default text-center">
						<div class="panel-heading"><?php _e('Independent Open',SENDCLOUD_I18N_DOMAIN)?></div>
						<div class="panel-body">
							<?php echo $stats_total['uniqueOpensNum'];?> <br /> <small
								class="text-muted"><?php
								if ($stats_total ['request'] == 0) {
									echo '0%';
								} else
									echo round ( ($stats_total ['uniqueOpensNum'] / $stats_total ['request'] * 1.0) * 100, 2 ) . '%';
								?></small>
						</div>
					</div>
				</div>
				<div class="col-md-2">
					<div class="panel panel-default text-center">
						<div class="panel-heading"><?php _e('Unsubscribe',SENDCLOUD_I18N_DOMAIN)?></div>
						<div class="panel-body">
							<?php echo $stats_total['unsubscribeNum'];?> <br /> <small
								class="text-muted">
							<?php
							if ($stats_total ['request'] == 0) {
								echo '0%';
							} else {
								echo round ( ($stats_total ['unsubscribeNum'] / $stats_total ['request'] * 1.0) * 100, 2 ) . '%';
							}
							
							?>
							</small>
						</div>
					</div>
				</div>
			</div>
			<div class="clearfix"></div>
			<div class="trackchart-container"></div>
			<div class="table-responsive">
				<table class="table table-bordered table-striped">
					<thead>
						<tr>
							<th><?php _e('Date',SENDCLOUD_I18N_DOMAIN); ?></th>
							<th><?php _e('Open',SENDCLOUD_I18N_DOMAIN);?></th>
							<th><?php _e('Independent Open',SENDCLOUD_I18N_DOMAIN)?></th>
							<th><?php _e('Unsubscribe',SENDCLOUD_I18N_DOMAIN)?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						
						foreach ( $responseData as $key => $value ) {
							?>
						<tr>
							<td><?php echo $key?></td>
							<td><?php echo $value['openNum'] ?></td>
							<td><?php echo $value['uniqueOpensNum']?></td>
							<td><?php echo $value['unsubscribeNum']?></td>
						</tr>
						<?php }?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
<?php
$chart_title = __ ( 'Send Chart', SENDCLOUD_I18N_DOMAIN );
$chart_y_title = __ ( 'Num', SENDCLOUD_I18N_DOMAIN );
echo "function get_data(data,chart_title){var chartdata={
    title: {
        text: " . "chart_title" . "
     },
    subtitle: {
       text: '',
       x: -20
    },
    xAxis: {
        categories:" . $chart_cates . "
    },
    yAxis: {
    	min:0,
        title: {
            text: '" . $chart_y_title . "'
        },
        plotLines: [{
            value: 0,
            width: 1,
            color: '#808080'
        }]
    },
   
    legend: {
        align: 'center',
        verticalAlign: 'bottom',
        borderWidth: 0
    },
    credits:{
    enabled:false
    },
    series:" . "data" . "
};return chartdata};"?>
								
$jsendcloud=jQuery;
								
$jsendcloud(function() {

	var _tab='<?php echo $f_tab;?>';
	var _timetype='<?php echo $time_type;?>';
    // init daterangepicker
    $jsendcloud('input[name="daterange"]').daterangepicker();
    // init highchart, show send data
    if(_tab==1){
    	$jsendcloud('a[aria-controls="tab-send"]').tab('show');
    }else{
    	$jsendcloud('a[aria-controls="tab-track"]').tab('show');
    	
    }

    if(_timetype=='99' && _tab==1){
    	$jsendcloud('.custom-send-date').show();
    }else if(_timetype=='99' && _tab==2){
    	$jsendcloud('.custom-track-date').show();
    }

    // add eventListener on tab change, update the highchart view
    $jsendcloud('a[aria-controls="tab-track"]').on('shown.bs.tab', function(e) {
       
        
    })
    $jsendcloud('a[aria-controls="tab-send"]').on('shown.bs.tab', function(e) {
        
        })

    // add eventListener on datepicker apply
    $jsendcloud('.send-daterange').on('apply.daterangepicker', function(e, picker) {
        $jsendcloud('#begin_date').val(picker.startDate.format('YYYY-MM-DD'));
        $jsendcloud('#end_date').val(picker.endDate.format('YYYY-MM-DD'));
        
    })
    
     $jsendcloud('.send-time-type').on('change', function(e){
        $jsendcloud(this).val() == '99' ? $jsendcloud('.custom-send-date').show() : $jsendcloud('.custom-send-date').hide()
    })
    
    $jsendcloud('.track-time-type').on('change',function(e){
        $jsendcloud(this).val() == '99' ? $jsendcloud('.custom-track-date').show() : $jsendcloud('.custom-track-date').hide()
    })

    $jsendcloud('.track-daterange').on('apply.daterangepicker', function(e, picker) {
    	 $jsendcloud('#begin_date1').val(picker.startDate.format('YYYY-MM-DD'));
         $jsendcloud('#end_date1').val(picker.endDate.format('YYYY-MM-DD'));
    })
})


function changeTime(){
	var timeType_obj=document.getElementById('time_type');
	var search_date=document.getElementById('search_date');
	var time_type=timeType_obj.options[timeType_obj.selectedIndex].value;
	if(time_type=="99"){
		search_date.style.display="";
	}else{
		search_date.style.display="none";
	}
}
</script>
<?php ?>


