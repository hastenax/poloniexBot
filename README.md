# poloniexBot

poloniexBot is an unofficial extension of the [poloniex PHP API wrapper by compcentral](http://pastebin.com/iuezwGRZ).
poloniexBot is designed to perform automated poloniex assets rebalancing, cryptocurrencies exchange 
and withdrawals. 

poloniexBot can use [Telegram API](https://core.telegram.org) to notify user about all completed rebalances, exchanges and withdrawals. 
Set 
`$_telegram_bot_token` and `$_telegram_channel`
properties if you want to receive notifications to your Telegram channel.

In order to use poloniexBot for withdrawals you need to enable withdrawals at [poloniex api keys management page](https://poloniex.com/apiKeys).

## Getting started

Insert your poloniex API credentials into poloniexBot.php and adjust ratio of currencies to be kept.
By default all ETH, BTC, LTC, XMR and ZEC deposits will be autoexchanged (rebalanced) to 50% ETH and 50% BTC using current best exchange rate.

`$_min_percent = 0.005`
means that check_and_rebalance() method will initiate rebalance() or auto_exchange() if any of your asset coin rose or decline by at least 0.5%.

`$_delay_next_round`
is default delay between two consequent checks in seconds.

```php
	// currencies and their proportions to be kept / autoexchanged
	// must be uppercase
	private $_pairs = ['ETH' => 0.5, 'BTC' => 0.5, 'LTC' => 0, 'XMR' => 0, 'ZEC' => 0];

	// poloniex API keys
	// you have to keep them safe
	// good idea is to set 600/700 permission on this file
	private $_api_key = '<MY_API_KEY>';
	private $_api_secret = '<LONG_LONG_LONG_API_SECRET>';

	// minimal pct. change for start rebalance
	private $_min_percent = 0.005;
	
	// how many time to wait after each check, in sec.
	private $_delay_next_round = 60;
```

## Typical usage: auto exchange/rebalancing

`$ php rebalance.php`

## Typical usage: autowithdrawal

`$ php withdraw.php`

You can craft your own cases, for example autoexchange all cryptos to preffered one and then immediate withdraw.

```php
	include 'poloniexBot.php';
	
	$bot = new poloniexBot(['ETH' => 1, 'BTC' => 0, 'LTC' => 0, 'XMR' => 0, 'ZEC' => 0]);
	
	$bot->auto_exchange();
	$bot->check_and_withdraw('ETH', '<your_address>');

	//security delay
	sleep(1);
```
## Supported tickers

poloniexBot supports all BTC_* pairs and only reverse pair USDT_BTC.

## Feedback and donations

Join [@poloniexExchangeBot](https://t.me/poloniexExchangeBot) channel for feedback. I use it as test channel for poloniexBot notifications.

BTC: 12S1fAvZt1GjoRmogGiVgb3p3u4Z52knov

ETH: 0x1F575fFB648a01e248c3AA2c37269835e18C38Ff

ZEC: t1ZSEie2vRrNFjtR374XxsmDooh4GeYwF2w
