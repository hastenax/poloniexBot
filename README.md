# poloniexBot

poloniexBot is an unofficial extension of the [poloniex PHP API wrapper by compcentral](http://pastebin.com/iuezwGRZ).
poloniexBot is designed to perform basic trade actions (cryptocurrencies exchange), automated assets rebalancing
and withdrawals on the [poloniex digital exchange](https://poloniex.com/exchange/). 

poloniexBot can use [Telegram API](https://core.telegram.org) to notify users about all completed trades and withdrawals. 
Set 
`$_telegram_bot_token` and `$_telegram_channel`
properties if you want to receive notifications to a specified [Telegram Messenger](https://telegram.org) channel.

In order to use poloniexBot for trading and withdrawals you have to enable corresponding options at [poloniex api keys management page](https://poloniex.com/apiKeys).

## Getting started

Clone this repository or install it via composer. Insert your poloniex API credentials into poloniexBot.php.

## Usage

### Obtain actual coin prices

```php
    include 'poloniexBot.php';

    $bot = new poloniexBot();

    $prices = $bot->get_prices();
    
    print_r($prices);
```

### Obtain account balance

```php
    include 'poloniexBot.php';

    $bot = new poloniexBot();

    $balances = $bot->get_available_balances();
    
    print_r($balances);
```

### Obtain specified coin balance

```php
    include 'poloniexBot.php';

    $bot = new poloniexBot();

    $balance = $bot->get_coin_balance('ETH');
    
    var_dump($balance);
```

### Place buy order (1 Ether)

```php
    $bot = new poloniexBot();

    $price = $bot->get_viable_price('ETH', 1);
    
    $result = $bot->buy('BTC_ETH', $price, 1);
```
### Place sell order (1 Ether)

```php
    $bot = new poloniexBot();

    $price = $bot->get_viable_price('ETH', -1);
    
    $result = $bot->sell('BTC_ETH', $price, 1);
```

### Obtain current exchange volume

```php
    include 'poloniexBot.php';

    $bot = new poloniexBot();

    $volume = $bot->get_volume();
    
    print_r($volume);
```

### Obtain USD balance (account wealth)

```php
    include 'poloniexBot.php';

    $bot = new poloniexBot();

    $wealth = $bot->get_wealth();
    
    var_dump($wealth);
```

### Exchange all cryptos to a preferred one and then immediate withdraw.

```php
    include 'poloniexBot.php';
	
    $bot = new poloniexBot(['ETH' => 1, 'BTC' => 0, 'LTC' => 0, 'XMR' => 0, 'ZEC' => 0]);
	
    $bot->auto_exchange();
    $bot->check_and_withdraw('ETH', '<your_address>');
```

### Rebalancing

```php
	include 'poloniexBot.php';
	
	$bot = new poloniexBot();
	
    $bot->check_and_rebalance();
```
## Tuning targets for rebalancing.

By default ETH, BTC, LTC, XMR and ZEC deposits will be autoexchanged (rebalanced) to 50% ETH and 50% BTC.
You can adjust coins and their target proportions via `$_pairs` property.

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

## Using from command line: auto exchange/rebalancing

`$ php rebalance.php`

## Using from command line: auto withdrawals

`$ php withdraw.php`

## Supported tickers

poloniexBot supports all BTC_* pairs and only reverse pair USDT_BTC.

## Feedback and donations

Join [@poloniexExchangeBot](https://t.me/poloniexExchangeBot) channel for feedback. I use it as test channel for poloniexBot notifications.

BTC: 12S1fAvZt1GjoRmogGiVgb3p3u4Z52knov

ETH: 0x1F575fFB648a01e248c3AA2c37269835e18C38Ff

ZEC: t1ZSEie2vRrNFjtR374XxsmDooh4GeYwF2w
