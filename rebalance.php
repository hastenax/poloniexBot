<?php 
	include 'poloniexBot.php';
	
	$bot = new poloniexBot();
	
	while(true) {
		$bot->check_and_rebalance();
		
		//security delay
		sleep(1);
	}
