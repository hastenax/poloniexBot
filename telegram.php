<?php 

class TelegramBot {
	private $_baseUrl = 'https://api.telegram.org/bot';
	private $_sendMessageUrl = '/sendMessage';
	
	public function __construct($botToken) {
		$this->_baseUrl .= $botToken;
	}
	
	public function sendMessage($channel, $message, $parse_mode = '', $disable_web_page_preview = true) {
		$postdata = http_build_query(
			array(
				'chat_id' => $channel,
				'text' => $message,
				'parse_mode' => $parse_mode,
				'disable_web_page_preview' => $disable_web_page_preview,
			)
		);
		
		$opts = array('http' =>
			array(
				'method'  => 'POST',
				'header'  => 'Content-type: application/x-www-form-urlencoded',
				'content' => $postdata,
			)
		);
		$context  = stream_context_create($opts);

		return file_get_contents($this->_baseUrl.$this->_sendMessageUrl, false, $context);
	}
}
