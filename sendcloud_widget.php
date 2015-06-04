<?php
class Sendcloud_Widget extends WP_Widget {
	function __construct() {
		load_plugin_textdomain ( SENDCLOUD_I18N_DOMAIN, false, dirname ( plugin_basename ( __FILE__ ) ) . '/languages/' );
		parent::__construct ( 'sendcloud_widget', __ ( 'Site Update Subscriber', 'sendcloud' ), array (
				'description' => __ ( 'This widget can collect your site subscribers. SendCloud will send e-mail to inform them once the site updated。', 'sendcloud' ) 
		) );
	}
	function form($instance) {
	}
	function update($new_instance, $old_instance) {
		$instance ['title'] = strip_tags ( $new_instance ['title'] );
		return $instance;
	}
	function widget($args, $instance) {
		$msg = __ ( 'please login email,clicke confirm link', 'sendcloud' );
		$title = __ ( 'Don\'t want to miss any changes? The mail subscription us', 'sendcloud' );
		$code = get_option ( 'sendcloud_invitecode' );
		echo '<div id="sendcloud-embed-subscribe"></div>
              <script>
        var option = {
        type: "bottom",
        expires: "50",
        trigger: "load",
        invitecode: "' . $code . '",
        title:"' . $title . '",
        successMsg:"' . $msg . '"
        };
        sendcloud.subscribe(option);
       </script>';
	}
}
function sendcloud_register_widgets() {
	register_widget ( 'Sendcloud_Widget' );
}
add_action ( 'widgets_init', 'sendcloud_register_widgets' );
