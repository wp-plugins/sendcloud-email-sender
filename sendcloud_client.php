<?php
class sendcloud_client {
	private $gatewayUrl;
	private $timeout;
	private $connectTimeout;
	public function sendcloundSort($requestParam) {
		ksort ( $requestParam );
		reset ( $requestParam );
		return $requestParam;
	}
	public function sendcloundCreatSign($sysParamsArray, $methodParamsArray) {
		$paramsArray = array_merge ( $sysParamsArray, $methodParamsArray );
		$paramsSortedArray = $this->sendcloundSort ( $paramsArray );
		$signStr = '';
		foreach ( $paramsSortedArray as $key => $val ) {
			$signStr .= $key . $val;
		}
		$sign = md5 ( $signStr );
		$paramsSortedArray ["sign"] = $sign;
		$inputsArray = $this->sendcloundSort ( $paramsSortedArray );
		return $inputsArray;
	}
	public function sendcloundSendByPostCurl($url, $postdata) {
		$urlRs = curl_init ();
		curl_setopt ( $urlRs, CURLOPT_URL, $url );
		curl_setopt ( $urlRs, CURLOPT_FAILONERROR, false );
		curl_setopt ( $urlRs, CURLOPT_RETURNTRANSFER, true );
		if ($this->timeout) {
			curl_setopt ( $urlRs, CURLOPT_TIMEOUT, $this->timeout );
		}
		if ($this->connectTimeout) {
			curl_setopt ( $urlRs, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout );
		}
		
		if (strlen ( $url ) > 5 && strtolower ( substr ( $url, 0, 5 ) ) == "https") {
			curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );
			curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, false );
		}
		curl_setopt ( $urlRs, CURLOPT_POST, true );
		curl_setopt ( $urlRs, CURLOPT_POSTFIELDS, $postdata );
		$reponse = curl_exec ( $urlRs );
		if (curl_errno ( $urlRs )) {
			throw new Exception ( curl_error ( $urlRs ), 0 );
		} else {
			$httpStatusCode = curl_getinfo ( $urlRs, CURLINFO_HTTP_CODE );
			if (200 !== $httpStatusCode) {
				throw new Exception ( $reponse, $httpStatusCode );
			}
		}
		curl_close ( $urlRs );
		return $reponse;
	}
	public function sendcloundSendByPost($url, $paramsArray) {
		$options = array (
				'http' => array (
						'method' => 'POST',
						'header' => "Content-type: application/x-www-form-urlencoded",
						'content' => http_build_query ( $paramsArray ), 
						'timeout'=>180
				) 
		);
		$context = stream_context_create ( $options );
		$result = file_get_contents ( $url, false, $context );
		return $result;
	}
	public function execute($sysParamsArray, $methodParamsArray, $fileArray) {
		$url = $this->gatewayUrl;
		date_default_timezone_set ( 'PRC' );
		$timestamp = date ( 'Y-m-d H:i:s', time () );
		$sysParamsArray ["timestamp"] = $timestamp;
		$paramsArray = $this->sendcloundCreatSign ( $sysParamsArray, $methodParamsArray );
		$fileUploadFlag = false;
		if (is_array ( $fileArray ) && count ( $fileArray ) > 0) {
			foreach ( $fileArray as $filekey => $filevalue ) {
				if ("@" != substr ( $filevalue, 0, 1 )) {
					trigger_error ( "upload file URL error. for example, '@C:\\Documents and Settings\\{1E2AFB12-25A1-4D44-8431-010C040188E4}.jpg'" );
				}
			}
			$fileUploadFlag = true;
		}
		$postdata = '';
		if (is_array ( $paramsArray ) && count ( $paramsArray ) > 0 && $fileUploadFlag == true) {
			$postdata = array_merge ( $paramsArray, $fileArray );
		} else {
			$postdata = $paramsArray;
		}
		try {
			$response = $this->sendcloundSendByPost ( $url, $postdata );
		} catch ( Exception $e ) {
			trigger_error ( "send msg exception: " + $e->getCode () + "  " + $e->getMessage () );
			return null;
		}
		if($response==FALSE){
			trigger_error ( "request url :".$url."Exception");
			return null;
			
		}
		$isRightFormed = false;
		$respObject = json_decode ( $response );
		if (null !== $respObject) {
			$isRightFormed = true;
		}
		if (false === $isRightFormed) {
			trigger_error ( "format error. it should be json or xml." );
			return null;
		}
		return $respObject;
	}
	public function sendMail($api_user, $api_key, $from, $fromname, $to, $subject, $html, $label = "", $user_maillist) {
		$paramsArray = array (
				'api_user' => $api_user,
				'api_key' => $api_key,
				'from' => $from,
				'fromname' => $this->str_iconv ( $fromname ),
				'to' => $this->str_iconv ( $to ),
				'subject' => $this->str_iconv ( $subject ),
				'html' => $this->str_iconv ( $html ) 
		);
		if (! empty ( $label )) {
			$paramsArray ['label'] = $label;
		}
		if (! empty ( $user_maillist )) {
			$paramsArray ['use_maillist'] = $user_maillist;
		}
		
		return $this->execute ( Array (), $paramsArray, Array () );
	}
	public function addListMember($api_user, $api_key, $mail_list_addr, $member_addr, $name, $vars) {
		$paramsArray = array (
				'api_user' => $api_user,
				'api_key' => $api_key,
				'mail_list_addr' => $mail_list_addr,
				'member_addr' => $member_addr,
				'name' => $name,
				'vars' => $vars,
				'subscribed' => "true",
				'upsert' => "true" 
		);
		return $this->execute ( Array (), $paramsArray, Array () );
	}
	public function deleteListMember($api_user, $api_key, $mail_list_addr, $name) {
		$paramsArray = array (
				'api_user' => $api_user,
				'api_key' => $api_key,
				'mail_list_addr' => $mail_list_addr,
				'member_addr' => '',
				'name' => $name 
		);
		return $this->execute ( Array (), $paramsArray, Array () );
	}
	public function sendEmailTemplate($api_user, $api_key, $template_invoke_name, $subject, $from, $fromname, $to, $label = "", $substitution_vars, $use_maillist) {
		$paramsArray = array (
				'api_user' => $api_user,
				'api_key' => $api_key,
				'template_invoke_name' => $template_invoke_name,
				'subject' => $this->str_iconv ( $subject ),
				'from' => $from,
				'fromname' => $this->str_iconv ( $fromname )
		)
		;
		if (! empty ( $label )) {
			$paramsArray ['label'] = $label;
		}
		
		if (! empty ( $substitution_vars )) {
			$paramsArray ['substitution_vars'] = $substitution_vars;
		}
		if (! empty ( $use_maillist )) {
			$paramsArray ['use_maillist'] = $use_maillist;
		}
		if (! empty ( $to )) {
			$paramsArray ['to'] = $to;
		}
		return $this->execute ( Array (), $paramsArray, Array () );
	}
	public function getQuota($api_user, $api_key) {
		$paramsArray = array (
				'api_user' => $api_user,
				'api_key' => $api_key 
		);
		return $this->execute ( Array (), $paramsArray, Array () );
	}
	public function str_iconv($str) {
		$encode = mb_detect_encoding ( $str, array (
				"ASCII",
				"UTF-8",
				"GB2312",
				"GBK",
				"BIG5" 
		) );
		if ($encode != "UTF-8") {
			if ($encode == "CP936") {
				return iconv ( "BIG-5", "UTF-8", $str );
			}
			return iconv ( $encode, "UTF-8", $str );
		}
		return $str;
	}
	public function getAccount($webSite, $email) {
		$paramsArray = array (
				'webSite' => $webSite,
				'email' => $email,
				'type' => 'wp',
				'timestamp' => time () * 1000 
		);
		return $this->execute ( Array (), $paramsArray, Array () );
	}
	public function updateEmail($webSite, $email) {
		$paramsArray = array (
				'webSite' => $webSite,
				'email' => $email,
				'timestamp' => time () * 1000 
		);
		return $this->execute ( Array (), $paramsArray, Array () );
	}
	public function getStats($api_user, $api_key, $days, $start_date, $end_date, $api_user_list, $label_id_list, $domain_list, $aggregate = 0) {
		$paramsArray = array (
				'api_user' => $api_user,
				'api_key' => $api_key 
		);
		if (! empty ( $start_date ) && ! empty ( $end_date )) {
			$paramsArray ['start_date'] = $start_date;
			$paramsArray ['end_date'] = $end_date;
		}
		if (! empty ( $days ) && empty ( $start_date ) && empty ( $end_date )) {
			$paramsArray ['days'] = $days;
		}
		if (! empty ( $api_user_list )) {
			$paramsArray ['api_user_list'] = $api_user_list;
		}
		if (! empty ( $label_id_list )) {
			$paramsArray ['label_id_list'] = $label_id_list;
		}
		if (! empty ( $domain_list )) {
			$paramsArray ['domain_list'] = $domain_list;
		}
		if (! empty ( $aggregate )) {
			$paramsArray ['aggregate'] = $aggregate;
		}
		
		return $this->execute ( Array (), $paramsArray, Array () );
	}
	public function getLabelList($api_user, $api_key) {
		$paramsArray = array (
				'api_user' => $api_user,
				'api_key' => $api_key 
		);
		return $this->execute ( Array (), $paramsArray, Array () );
	}
	public function create_label($api_user, $api_key, $labelname) {
		$paramsArray = array (
				'api_user' => $api_user,
				'api_key' => $api_key,
				'labelName' => $this->str_iconv ( $labelname ) 
		);
		return $this->execute ( Array (), $paramsArray, Array () );
	}
	public function getSubinfocode($api_user, $api_key, $name) {
		$paramsArray = array (
				'api_user' => $api_user,
				'api_key' => $api_key,
				'name' => $name 
		);
		return $this->execute ( Array (), $paramsArray, Array () );
	}
	public function getMaillistMember($api_user, $api_key, $mail_list_addr) {
		$paramsArray = array (
				'api_user' => $api_user,
				'api_key' => $api_key,
				'mail_list_addr' => $mail_list_addr 
		);
		return $this->execute ( Array (), $paramsArray, Array () );
	}
	
	public function updateSubinfo($api_user, $api_key,$subject,$fromname){
		$paramsArray = array (
				'api_user' => $api_user,
				'api_key' => $api_key,
				'subject' => $subject,
				'fromname'=>$fromname
		);
	   return $this->execute ( Array (), $paramsArray, Array () );;
	}
	
	public function setGatewayUrl($gatewayUrl) {
		$this->gatewayUrl = $gatewayUrl;
	}
	public function getGatewayUrl() {
		return $this->gatewayUrl;
	}
	public function setTimeout($timeout) {
		$this->timeout = $timeout;
	}
	public function getTimeout() {
		return $this->timeout;
	}
	public function setConnectTimeout($connectTimeout) {
		$this->connectTimeout = $connectTimeout;
	}
	public function getConnectTimeout() {
		return $this->connectTimeout;
	}
}
