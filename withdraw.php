<?php 
	include 'poloniexBot.php';
	
	$bot = new poloniexBot();
	
	while(true) {
		$result = $bot->check_and_withdraw('ETH', '<your_address>');
		
		//security delay
		sleep(1);
	}
?>