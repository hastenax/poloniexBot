<?php

class poloniexBot {
	// currencies and their proportions to be kept / autoexchanged
	// must be uppercase
	private $_pairs = ['ETH' => 0.5, 'BTC' => 0.5, 'LTC' => 0, 'XMR' => 0, 'ZEC' => 0];
	
	// poloniex API keys
	// you have to keep it safe
	// good idea is to set 600/700 permission on this file
	private $_api_key = '';
	private $_api_secret = '';
	
	// telegram bot token and chat_id
	// leave them empty if you don't want to receive notifications
	private $_telegram_bot_token = '';
	private $_telegram_channel = '';
	
	// minimal pct. change for start rebalance
	private $_min_percent = 0.005;
	
	// how many time to wait after each check/rebalance, in sec.
	private $_max_delay = 120;
	private $_min_delay = 60;
	private $_delay_next_round = 60;
	private $_dynamic_delay = true;
	
	private $_verbose = true;
	
	// if true make sure you create logs/ subdirectory
	private $_log = true;
	
	private $_bot;
	private $_rebalances = 0;
	private $_telegram_bot;
	
	public function __construct($pairs = [], $min_percent = 0, $delay = 0, $verbose = '') {
		include_once 'poloniex.php';
		if ($pairs)
			$this->_pairs = $pairs;
		if ($min_percent)
			$this->_min_percent = $min_percent;
		if ($delay && $delay > 0)
			$this->_delay_next_round = $delay;
		if (is_bool($verbose))
			$this->_verbose = $verbose;
		$this->_bot = new poloniex($this->_api_key, $this->_api_secret, $this->_verbose);
		if (trim($this->_telegram_bot_token)) {
			include_once 'telegram.php';
			$this->_telegram_bot = new TelegramBot($this->_telegram_bot_token);
			$this->_telegram_bot->sendMessage($this->_telegram_channel, $this->t().'<a href="https://poloniex.com">poloniex</a> balance bot started ('.($this->_min_percent*100).'%)', 'HTML');
		}
	}
	
	public function get_balances() {
		return $this->_bot->get_balances();
	}
	
	public function get_tickers() {
		return $this->_bot->get_ticker();
	}
	
	public function get_prices() {
		return $this->_bot->get_ticker();
	}
	
	public function get_available_balances() {
		return $this->_bot->get_available_balances();
	}
	
	public function get_volume() {
		return $this->_bot->get_volume();
	}

	public function get_trading_history($pair) {
		return $this->_bot->get_trade_history($pair);
	}
	
	public function withdraw($currency, $amount, $address) {
		return $this->_bot->withdraw($currency, $amount, $address);
	}
	
	public function get_btc_balances($prices = [])
	{
		if (!$prices)
			$prices = $this->get_prices();
		$available_balances = $this->get_available_balances();
		
		$balances = [];
		$total = 0;
		
		foreach ($this->_pairs as $coin => $proportion) {
			if ($coin != 'BTC') {
				$balances[$coin]['amount'] = isset($available_balances['exchange'][$coin]) ? $available_balances['exchange'][$coin] : 0;
				$balances[$coin]['BTC'] = number_format(round($balances[$coin]['amount'] * $prices['BTC_'.$coin]['last'], 8, PHP_ROUND_HALF_DOWN), 8);
				$total += $balances[$coin]['BTC'];
			}
			else {
				$balances['BTC']['amount'] = $available_balances['exchange']['BTC'];
				$balances['BTC']['BTC'] = $available_balances['exchange']['BTC'];
				$total += $balances['BTC']['BTC'];
			}
		}
		$balances['BTC']['total'] = $total;
		return $balances;
	}
	
	public function get_proportions($balances = []) {
		if (!$balances)
			$balances = $this->get_btc_balances();
		$proportions = [];		
		foreach ($balances as $coin => $info) {
			$proportions[$coin] = round($info['BTC'] / $balances['BTC']['total'], 6);
		}
		return $proportions;
	}
	
	public function get_rebalance_proposal($balances = [], $proportions = [], $prices = []) {
		if (!$prices)
			$prices = $this->get_prices();		
		if (!$balances)
			$balances = $this->get_btc_balances($prices);
		if (!$proportions)
			$proportions = $this->get_proportions($balances);

		$decisions = [];
		if ($balances['BTC']['total'] > 0.01) {
			foreach($this->_pairs as $coin => $target_proportion) {
				if ($coin != 'BTC') {
					$decisions[$coin]['change_pct'] = -1 * round(($target_proportion - $proportions[$coin]) * 100, 2);
					$decisions[$coin]['amount_BTC'] = number_format(round($balances['BTC']['total'] * $target_proportion - $balances[$coin]['BTC'], 6, PHP_ROUND_HALF_DOWN), 8);
					$decisions[$coin]['amount'] = number_format(round($decisions[$coin]['amount_BTC'] / $prices['BTC_'.$coin][($decisions[$coin]['amount_BTC'] > 0) ? 'lowestAsk' : 'highestBid'], 8, PHP_ROUND_HALF_DOWN), 8);
				}
			}
		}
		return $decisions;
	}
	
	private function t()
	{
		return date('[Y-m-d H:i:s] ');
	}
	
	protected function log($message, $two_newlines = false) {
		$log_string = $this->t().$message.($two_newlines ? "\n\n" : "\n");
		if ($this->_verbose)
			echo $log_string;
		$written = 0;
		if ($this->_log && ($h = fopen('logs/'.date('Y-m-d').'-poloniex-balance.log', 'a'))) {
			$written = fwrite($h, $log_string);
			fclose($h);
		}
		return $written;
	}
	
	public function check_proportions($proportions = [])
	{
		if ($this->_verbose) 
			$this->log("Calculating proportions");
		if (!$proportions)
			$proportions = $this->get_proportions();
		if ($this->_verbose)
			$this->log(print_r($proportions, true));
		$max_change = 0;
		foreach($this->_pairs as $coin => $target) {
			$change = abs($proportions[$coin] - $target);
			if ($change > 0) {
				$change_pct = $change / $target;
				if ($this->_dynamic_delay) {
					if ($max_change < $change_pct)
						$max_change = $change_pct;
					$dyn_delay = ((float) ($this->_max_delay - $this->_min_delay)) * ($this->_min_percent - min($this->_min_percent, $max_change)) / $this->_min_percent;
					$this->_delay_next_round = round($this->_min_delay + $dyn_delay);
				}
				if ($change_pct >= $this->_min_percent){
					if ($this->_verbose)
						$this->log("Rebalance needed");
					return true;
				}
			}
		}
		if ($this->_verbose)
			$this->log("Everything as expected. Going to sleep (".$this->_delay_next_round.")", true);
		return false;
	}
	
	function get_order_book($pair = 'all') {
		return $this->_bot->get_order_book($pair);
	}
	
	function get_viable_price($pair, $amount, $order_book = []) {
		if (!$order_book)
			$order_book = $this->get_order_book();
		$price = ($amount > 0) ? 0 : INF;
		$buy = ($amount > 0);
		$remains = abs($amount);
		foreach($order_book['BTC_'.$pair][$buy ? 'asks' : 'bids'] as $position) {
			$remains -= $position[1];
			$price = $position[0];
			if ($remains <= 0)
				break;
		}
		return $price;
	}
	
	public function rebalance($balances, $proportions, $prices, $order_book) {
		if ($this->_verbose)
			$this->log("Rebalancing in progress");
		
		// making decisions
		$decisions = $this->get_rebalance_proposal($balances, $proportions, $prices);
		
		if ($this->_verbose)
			$this->log(print_r($decisions, true));
		$results = [];
		
		// firstly sell overpluses 
		foreach($decisions as $coin => $data) {
				if ($data['change_pct']) {
					$price = $this->get_viable_price($coin, $data['amount'], $order_book);

					if ($data['amount_BTC'] < 0)
						$results[$coin] = $this->sell('BTC_'.$coin, $price, abs($data['amount']));
				}
		}

		// then buy deficits
		foreach($decisions as $coin => $data) {
				if ($data['change_pct']) {
					$price = $this->get_viable_price($coin, $data['amount'], $order_book);

					if ($data['amount_BTC'] > 0)
						$results[$coin] = $this->buy('BTC_'.$coin, $price, abs($data['amount']));
				}
		}
		
		return $results;
	}
	
	public function buy($pair, $rate, $amount) {
		if ($this->_verbose) {
			$message = 'Buying '.$amount.' of '.$pair.' @ '.$rate;
			$this->log($message);
			if (trim($this->_telegram_channel))
				$this->_telegram_bot->sendMessage($this->_telegram_channel, str_replace($pair, '<a href="https://poloniex.com/exchange#'.  strtolower($pair).'">'.$pair.'</a>', $message), 'HTML');
		}
		$result = $this->_bot->buy($pair, $rate, $amount);
		//var_dump($result);
		return $result;
	}

	public function sell($pair, $rate, $amount) {
		if ($this->_verbose) {
			$message = 'Selling '.$amount.' of '.$pair.' @ '.$rate;
			$this->log($message);
			if (trim($this->_telegram_channel))
				$this->_telegram_bot->sendMessage($this->_telegram_channel, str_replace($pair, '<a href="https://poloniex.com/exchange#'.  strtolower($pair).'">'.$pair.'</a>', $message), 'HTML');
		}
		$result = $this->_bot->sell($pair, $rate, $amount);
		//var_dump($result);
		return $result;
	}

	public function check_and_rebalance() {
		$prices = $this->get_prices();
		$balances = $this->get_btc_balances($prices);
		$proportions = $this->get_proportions($balances);
		$order_book = $this->get_order_book();
		if (!is_null($prices) && is_array($balances) && $balances['BTC']['total'] > 0.01 && $this->check_proportions($proportions)) {
			$result = $this->rebalance($balances, $proportions, $prices, $order_book);
			if ($this->_verbose) {
				$this->_rebalances++;
				if (trim($this->_telegram_channel)) {
					$this->_telegram_bot->sendMessage($this->_telegram_channel, "Rebalance completed");
					$this->_telegram_bot->sendMessage($this->_telegram_channel, "You have <b>".$balances['BTC']['total'].' BTC</b> in total', 'HTML');
				}
				$this->log("Rebalance completed");
				$this->log("You have ".$balances['BTC']['total'].' BTC in total', true);
			}
		}
		sleep($this->_delay_next_round);
	}
	
	public function auto_exchange() {
		$prices = $this->get_prices();
		$balances = $this->get_btc_balances($prices);
		$proportions = $this->get_proportions($balances);
		$order_book = $this->get_order_book();
		$result = $this->rebalance($balances, $proportions, $prices, $order_book);
		if ($this->_verbose) {
			if (trim($this->_telegram_channel)) {
				$this->_telegram_bot->sendMessage($this->_telegram_channel, "Exchange completed");
			}
			$this->log("Exchange completed");
		}
		return $result;
	}
	
	public function check_and_withdraw($currency, $address, $min_amount = 0) {
		$balances = $this->get_balances();
		$result = false;
		// withdraw if balance is positive and greater than $min_amount
		if (($balances[strtoupper($currency)] > 0) && ($balances[strtoupper($currency)] > $min_amount))
			$result = $this->withdraw(strtoupper($currency), $balances[strtoupper($currency)], $address);
		sleep($this->_delay_next_round);
	}
	
}
?>