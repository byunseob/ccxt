<?php

namespace ccxtpro;

// PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
// https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

use Exception; // a common import
use \ccxt\ExchangeError;
use \ccxt\AuthenticationError;
use \ccxt\ArgumentsRequired;
use \ccxt\BadRequest;
use \ccxt\NotSupported;

class gateio extends \ccxt\async\gateio {

    use ClientTrait;

    public function describe() {
        return $this->deep_extend(parent::describe (), array(
            'has' => array(
                'ws' => true,
                'watchOrderBook' => true,
                'watchTicker' => true,
                'watchTickers' => false, // for now
                'watchTrades' => true,
                'watchMyTrades' => true,
                'watchOHLCV' => true,
                'watchBalance' => true,
                'watchOrders' => true,
            ),
            'urls' => array(
                'api' => array(
                    'ws' => 'wss://ws.gate.io/v4',
                    'spot' => 'wss://api.gateio.ws/ws/v4/',
                    'swap' => array(
                        'usdt' => 'wss://fx-ws.gateio.ws/v4/ws/usdt',
                        'btc' => 'wss://fx-ws.gateio.ws/v4/ws/btc',
                    ),
                    'future' => array(
                        'usdt' => 'wss://fx-ws.gateio.ws/v4/ws/delivery/usdt',
                        'btc' => 'wss://fx-ws.gateio.ws/v4/ws/delivery/btc',
                    ),
                    'option' => 'wss://op-ws.gateio.live/v4/ws',
                ),
                'test' => array(
                    'swap' => array(
                        'usdt' => 'wss://fx-ws-testnet.gateio.ws/v4/ws/usdt',
                        'btc' => 'wss://fx-ws-testnet.gateio.ws/v4/ws/btc',
                    ),
                    'future' => array(
                        'usdt' => 'wss://fx-ws-testnet.gateio.ws/v4/ws/usdt',
                        'btc' => 'wss://fx-ws-testnet.gateio.ws/v4/ws/btc',
                    ),
                    'option' => 'wss://op-ws-testnet.gateio.live/v4/ws',
                ),
            ),
            'options' => array(
                'tradesLimit' => 1000,
                'OHLCVLimit' => 1000,
                'watchTradesSubscriptions' => array(),
                'watchTickerSubscriptions' => array(),
                'watchOrderBookSubscriptions' => array(),
            ),
            'exceptions' => array(
                'ws' => array(
                    'exact' => array(
                        '2' => '\\ccxt\\BadRequest',
                        '4' => '\\ccxt\\AuthenticationError',
                        '6' => '\\ccxt\\AuthenticationError',
                        '11' => '\\ccxt\\AuthenticationError',
                    ),
                ),
            ),
        ));
    }

    public function watch_order_book($symbol, $limit = null, $params = array ()) {
        yield $this->load_markets();
        $market = $this->market($symbol);
        $marketId = $market['id'];
        $uppercaseId = strtoupper($marketId);
        $requestId = $this->nonce();
        $url = $this->urls['api']['ws'];
        $options = $this->safe_value($this->options, 'watchOrderBook', array());
        $defaultLimit = $this->safe_integer($options, 'limit', 30);
        if (!$limit) {
            $limit = $defaultLimit;
        } else if ($limit !== 1 && $limit !== 5 && $limit !== 10 && $limit !== 20 && $limit !== 30) {
            throw new ExchangeError($this->id . ' watchOrderBook $limit argument must be null, 1, 5, 10, 20, or 30');
        }
        $interval = $this->safe_string($params, 'interval', '100ms');
        $parameters = array( $uppercaseId, $limit, $interval );
        $subscriptions = $this->safe_value($options, 'subscriptions', array());
        $subscriptions[$symbol] = $parameters;
        $options['subscriptions'] = $subscriptions;
        $this->options['watchOrderBook'] = $options;
        $toSend = is_array($subscriptions) ? array_values($subscriptions) : array();
        $messageHash = 'depth.update' . ':' . $marketId;
        $subscribeMessage = array(
            'id' => $requestId,
            'method' => 'depth.subscribe',
            'params' => $toSend,
        );
        $subscription = array(
            'id' => $requestId,
        );
        $orderbook = yield $this->watch($url, $messageHash, $subscribeMessage, $messageHash, $subscription);
        return $orderbook->limit ($limit);
    }

    public function handle_delta($bookside, $delta) {
        $price = $this->safe_float($delta, 0);
        $amount = $this->safe_float($delta, 1);
        $bookside->store ($price, $amount);
    }

    public function handle_deltas($bookside, $deltas) {
        for ($i = 0; $i < count($deltas); $i++) {
            $this->handle_delta($bookside, $deltas[$i]);
        }
    }

    public function handle_order_book($client, $message) {
        //
        //     {
        //         "method":"depth.update",
        //         "params":[
        //             true, // snapshot or not
        //             array(
        //                 "asks":[
        //                     ["7449.62","0.3933"],
        //                     ["7450","3.58662932"],
        //                     ["7450.44","0.15"],
        //                 "bids":[
        //                     ["7448.31","0.69984534"],
        //                     ["7447.08","0.7506"],
        //                     ["7445.74","0.4433"],
        //                 ]
        //             ),
        //             "BTC_USDT"
        //         ],
        //         "id":null
        //     }
        //
        $params = $this->safe_value($message, 'params', array());
        $clean = $this->safe_value($params, 0);
        $book = $this->safe_value($params, 1);
        $marketId = $this->safe_string($params, 2);
        $symbol = $this->safe_symbol($marketId);
        $method = $this->safe_string($message, 'method');
        $messageHash = $method . ':' . $marketId;
        $orderBook = null;
        $options = $this->safe_value($this->options, 'watchOrderBook', array());
        $subscriptions = $this->safe_value($options, 'subscriptions', array());
        $subscription = $this->safe_value($subscriptions, $symbol, array());
        $defaultLimit = $this->safe_integer($options, 'limit', 30);
        $limit = $this->safe_value($subscription, 1, $defaultLimit);
        if ($clean) {
            $orderBook = $this->order_book(array(), $limit);
            $this->orderbooks[$symbol] = $orderBook;
        } else {
            $orderBook = $this->orderbooks[$symbol];
        }
        $this->handle_deltas($orderBook['asks'], $this->safe_value($book, 'asks', array()));
        $this->handle_deltas($orderBook['bids'], $this->safe_value($book, 'bids', array()));
        $client->resolve ($orderBook, $messageHash);
    }

    public function watch_ticker($symbol, $params = array ()) {
        yield $this->load_markets();
        $market = $this->market($symbol);
        $marketId = $market['id'];
        $type = $market['type'];
        $messageType = $this->get_uniform_type($type);
        $channel = $messageType . '.' . 'tickers';
        $messageHash = $channel . '.' . $market['symbol'];
        $payload = array( $marketId );
        $url = $this->get_url_by_market_type($type, $market['inverse']);
        return yield $this->subscribe_public($url, $channel, $messageHash, $payload);
    }

    public function handle_ticker($client, $message) {
        //
        //    {
        //        time => 1649326221,
        //        $channel => 'spot.tickers',
        //        event => 'update',
        //        $result => {
        //          currency_pair => 'BTC_USDT',
        //          last => '43444.82',
        //          lowest_ask => '43444.82',
        //          highest_bid => '43444.81',
        //          change_percentage => '-4.0036',
        //          base_volume => '5182.5412425462',
        //          quote_volume => '227267634.93123952',
        //          high_24h => '47698',
        //          low_24h => '42721.03'
        //        }
        //    }
        //
        $channel = $this->safe_string($message, 'channel');
        $result = $this->safe_value($message, 'result');
        if (gettype($result) === 'array' && count(array_filter(array_keys($result), 'is_string')) != 0) {
            $result = array( $result );
        }
        for ($i = 0; $i < count($result); $i++) {
            $ticker = $result[$i];
            $parsed = $this->parse_ticker($ticker);
            $symbol = $parsed['symbol'];
            $this->tickers[$symbol] = $parsed;
            $messageHash = $channel . '.' . $symbol;
            $client->resolve ($this->tickers[$symbol], $messageHash);
        }
    }

    public function watch_trades($symbol, $since = null, $limit = null, $params = array ()) {
        yield $this->load_markets();
        $market = $this->market($symbol);
        $marketId = $market['id'];
        $uppercaseId = strtoupper($marketId);
        $requestId = $this->nonce();
        $url = $this->urls['api']['ws'];
        $options = $this->safe_value($this->options, 'watchTrades', array());
        $subscriptions = $this->safe_value($options, 'subscriptions', array());
        $subscriptions[$uppercaseId] = true;
        $options['subscriptions'] = $subscriptions;
        $this->options['watchTrades'] = $options;
        $subscribeMessage = array(
            'id' => $requestId,
            'method' => 'trades.subscribe',
            'params' => is_array($subscriptions) ? array_keys($subscriptions) : array(),
        );
        $subscription = array(
            'id' => $requestId,
        );
        $messageHash = 'trades.update' . ':' . $marketId;
        $trades = yield $this->watch($url, $messageHash, $subscribeMessage, $messageHash, $subscription);
        if ($this->newUpdates) {
            $limit = $trades->getLimit ($symbol, $limit);
        }
        return $this->filter_by_since_limit($trades, $since, $limit, 'timestamp', true);
    }

    public function handle_trades($client, $message) {
        //
        //     array(
        //         'BTC_USDT',
        //         array(
        //             array(
        //                 id => 221994511,
        //                 time => 1580311438.618647,
        //                 price => '9309',
        //                 amount => '0.0019',
        //                 type => 'sell'
        //             ),
        //             array(
        //                 id => 221994501,
        //                 time => 1580311433.842509,
        //                 price => '9311.31',
        //                 amount => '0.01',
        //                 type => 'buy'
        //             ),
        //         )
        //     )
        //
        $params = $this->safe_value($message, 'params', array());
        $marketId = $this->safe_string($params, 0);
        $market = $this->safe_market($marketId, null, '_');
        $symbol = $market['symbol'];
        $stored = $this->safe_value($this->trades, $symbol);
        if ($stored === null) {
            $limit = $this->safe_integer($this->options, 'tradesLimit', 1000);
            $stored = new ArrayCache ($limit);
            $this->trades[$symbol] = $stored;
        }
        $trades = $this->safe_value($params, 1, array());
        $parsed = $this->parse_trades($trades, $market);
        for ($i = 0; $i < count($parsed); $i++) {
            $stored->append ($parsed[$i]);
        }
        $methodType = $message['method'];
        $messageHash = $methodType . ':' . $marketId;
        $client->resolve ($stored, $messageHash);
    }

    public function watch_ohlcv($symbol, $timeframe = '1m', $since = null, $limit = null, $params = array ()) {
        yield $this->load_markets();
        $market = $this->market($symbol);
        $marketId = $market['id'];
        $type = $market['type'];
        $interval = $this->timeframes[$timeframe];
        $messageType = $this->get_uniform_type($type);
        $method = $messageType . '.candlesticks';
        $messageHash = $method . ':' . $interval . ':' . $market['symbol'];
        $url = $this->get_url_by_market_type($type, $market['inverse']);
        $payload = [$interval, $marketId];
        $ohlcv = yield $this->subscribe_public($url, $method, $messageHash, $payload);
        if ($this->newUpdates) {
            $limit = $ohlcv->getLimit ($symbol, $limit);
        }
        return $this->filter_by_since_limit($ohlcv, $since, $limit, 0, true);
    }

    public function handle_ohlcv($client, $message) {
        //
        // {
        //     "time" => 1606292600,
        //     "channel" => "spot.candlesticks",
        //     "event" => "update",
        //     "result" => {
        //       "t" => "1606292580", // total volume
        //       "v" => "2362.32035", // volume
        //       "c" => "19128.1", // close
        //       "h" => "19128.1", // high
        //       "l" => "19128.1", // low
        //       "o" => "19128.1", // open
        //       "n" => "1m_BTC_USDT" // sub
        //     }
        //   }
        //
        $channel = $this->safe_string($message, 'channel');
        $result = $this->safe_value($message, 'result');
        $isArray = gettype($result) === 'array' && count(array_filter(array_keys($result), 'is_string')) == 0;
        if (!$isArray) {
            $result = [$result];
        }
        $marketIds = array();
        for ($i = 0; $i < count($result); $i++) {
            $ohlcv = $result[$i];
            $subscription = $this->safe_string($ohlcv, 'n', '');
            $parts = explode('_', $subscription);
            $timeframe = $this->safe_string($parts, 0);
            $prefix = $timeframe . '_';
            $marketId = str_replace($prefix, '', $subscription);
            $symbol = $this->safe_symbol($marketId, null, '_');
            $parsed = $this->parse_ohlcv($ohlcv);
            $stored = $this->safe_value($this->ohlcvs, $symbol);
            if ($stored === null) {
                $limit = $this->safe_integer($this->options, 'OHLCVLimit', 1000);
                $stored = new ArrayCacheByTimestamp ($limit);
                $this->ohlcvs[$symbol] = $stored;
            }
            $stored->append ($parsed);
            $marketIds[$symbol] = $timeframe;
        }
        $keys = is_array($marketIds) ? array_keys($marketIds) : array();
        for ($i = 0; $i < count($keys); $i++) {
            $symbol = $keys[$i];
            $timeframe = $marketIds[$symbol];
            $interval = $this->timeframes[$timeframe];
            $hash = $channel . ':' . $interval . ':' . $symbol;
            $stored = $this->safe_value($this->ohlcvs, $symbol);
            $client->resolve ($stored, $hash);
        }
    }

    public function authenticate($params = array ()) {
        $url = $this->urls['api']['ws'];
        $client = $this->client($url);
        $future = $client->future ('authenticated');
        $method = 'server.sign';
        $authenticate = $this->safe_value($client->subscriptions, $method);
        if ($authenticate === null) {
            $requestId = $this->milliseconds();
            $requestIdString = (string) $requestId;
            $signature = $this->hmac($this->encode($requestIdString), $this->encode($this->secret), 'sha512', 'hex');
            $authenticateMessage = array(
                'id' => $requestId,
                'method' => $method,
                'params' => array( $this->apiKey, $signature, $requestId ),
            );
            $subscribe = array(
                'id' => $requestId,
                'method' => array($this, 'handle_authentication_message'),
            );
            $this->spawn(array($this, 'watch'), $url, $requestId, $authenticateMessage, $method, $subscribe);
        }
        return yield $future;
    }

    public function watch_my_trades($symbol = null, $since = null, $limit = null, $params = array ()) {
        yield $this->load_markets();
        $this->check_required_credentials();
        $type = 'spot';
        $marketId = null;
        $marketSymbol = null;
        if ($symbol !== null) {
            $market = $this->market($symbol);
            $type = $market['type'];
            $marketId = $market['id'];
            $marketSymbol = $market['symbol'];
        }
        if ($type !== 'spot') {
            throw new BadRequest($this->id . ' watchMyTrades $symbol supports spot markets only');
        }
        $url = $this->get_url_by_market_type($type);
        $channel = 'spot.usertrades';
        $messageHash = $channel;
        $payload = array();
        if ($marketId !== null) {
            $payload = [$marketId];
            $messageHash .= ':' . $marketSymbol;
        }
        $trades = yield $this->subscribe_private($url, $channel, $messageHash, $payload, null);
        if ($this->newUpdates) {
            $limit = $trades->getLimit ($symbol, $limit);
        }
        return $this->filter_by_symbol_since_limit($trades, $symbol, $since, $limit, true);
    }

    public function handle_my_trades($client, $message) {
        //
        // {
        //     "time" => 1605176741,
        //     "channel" => "spot.usertrades",
        //     "event" => "update",
        //     "result" => array(
        //       {
        //         "id" => 5736713,
        //         "user_id" => 1000001,
        //         "order_id" => "30784428",
        //         "currency_pair" => "BTC_USDT",
        //         "create_time" => 1605176741,
        //         "create_time_ms" => "1605176741123.456",
        //         "side" => "sell",
        //         "amount" => "1.00000000",
        //         "role" => "taker",
        //         "price" => "10000.00000000",
        //         "fee" => "0.00200000000000",
        //         "point_fee" => "0",
        //         "gt_fee" => "0",
        //         "text" => "apiv4"
        //       }
        //     )
        //   }
        //
        $channel = $this->safe_string($message, 'channel');
        $trades = $this->safe_value($message, 'result', array());
        if (strlen($trades) > 0) {
            if ($this->myTrades === null) {
                $limit = $this->safe_integer($this->options, 'tradesLimit', 1000);
                $this->myTrades = new ArrayCache ($limit);
            }
            $stored = $this->myTrades;
            $parsedTrades = $this->parse_trades($trades);
            for ($i = 0; $i < count($parsedTrades); $i++) {
                $stored->append ($parsedTrades[$i]);
            }
            $client->resolve ($this->myTrades, $channel);
            for ($i = 0; $i < count($parsedTrades); $i++) {
                $messageHash = $channel . ':' . $parsedTrades[$i]['symbol'];
                $client->resolve ($this->myTrades, $messageHash);
            }
        }
    }

    public function watch_balance($params = array ()) {
        yield $this->load_markets();
        $this->check_required_credentials();
        $url = $this->urls['api']['ws'];
        yield $this->authenticate();
        $requestId = $this->nonce();
        $method = 'balance.update';
        $subscribeMessage = array(
            'id' => $requestId,
            'method' => 'balance.subscribe',
            'params' => array(),
        );
        $subscription = array(
            'id' => $requestId,
            'method' => array($this, 'handle_balance_subscription'),
        );
        return yield $this->watch($url, $method, $subscribeMessage, $method, $subscription);
    }

    public function fetch_balance_snapshot() {
        yield $this->load_markets();
        $this->check_required_credentials();
        $url = $this->urls['api']['ws'];
        yield $this->authenticate();
        $requestId = $this->nonce();
        $method = 'balance.query';
        $subscribeMessage = array(
            'id' => $requestId,
            'method' => $method,
            'params' => array(),
        );
        $subscription = array(
            'id' => $requestId,
            'method' => array($this, 'handle_balance_snapshot'),
        );
        return yield $this->watch($url, $requestId, $subscribeMessage, $method, $subscription);
    }

    public function handle_balance_snapshot($client, $message) {
        $messageHash = $this->safe_string($message, 'id');
        $result = $this->safe_value($message, 'result');
        $this->handle_balance_message($client, $messageHash, $result);
        $client->resolve ($this->balance, 'balance.update');
        if (is_array($client->subscriptions) && array_key_exists('balance.query', $client->subscriptions)) {
            unset($client->subscriptions['balance.query']);
        }
    }

    public function handle_balance($client, $message) {
        $messageHash = $message['method'];
        $result = $message['params'][0];
        $this->handle_balance_message($client, $messageHash, $result);
    }

    public function handle_balance_message($client, $messageHash, $result) {
        $keys = is_array($result) ? array_keys($result) : array();
        for ($i = 0; $i < count($keys); $i++) {
            $account = $this->account();
            $key = $keys[$i];
            $code = $this->safe_currency_code($key);
            $balance = $result[$key];
            $account['free'] = $this->safe_string($balance, 'available');
            $account['used'] = $this->safe_string($balance, 'freeze');
            $this->balance[$code] = $account;
        }
        $this->balance = $this->safe_balance($this->balance);
        $client->resolve ($this->balance, $messageHash);
    }

    public function watch_orders($symbol = null, $since = null, $limit = null, $params = array ()) {
        if ($symbol === null) {
            throw new ArgumentsRequired($this->id . ' watchOrders requires a $symbol argument');
        }
        yield $this->load_markets();
        $market = $this->market($symbol);
        $type = 'spot';
        if ($market['future'] || $market['swap']) {
            $type = 'futures';
        } else if ($market['option']) {
            $type = 'options';
        }
        $method = $type . '.orders';
        $messageHash = $method;
        $messageHash = $method . ':' . $market['id'];
        $url = $this->get_url_by_market_type($market['type'], $market['inverse']);
        $payload = [$market['id']];
        // uid required for non spot markets
        $requiresUid = ($type !== 'spot');
        $orders = yield $this->subscribe_private($url, $method, $messageHash, $payload, $requiresUid);
        if ($this->newUpdates) {
            $limit = $orders->getLimit ($symbol, $limit);
        }
        return $this->filter_by_since_limit($orders, $since, $limit, 'timestamp', true);
    }

    public function handle_order($client, $message) {
        //
        // {
        //     "time" => 1605175506,
        //     "channel" => "spot.orders",
        //     "event" => "update",
        //     "result" => array(
        //       {
        //         "id" => "30784435",
        //         "user" => 123456,
        //         "text" => "t-abc",
        //         "create_time" => "1605175506",
        //         "create_time_ms" => "1605175506123",
        //         "update_time" => "1605175506",
        //         "update_time_ms" => "1605175506123",
        //         "event" => "put",
        //         "currency_pair" => "BTC_USDT",
        //         "type" => "limit",
        //         "account" => "spot",
        //         "side" => "sell",
        //         "amount" => "1",
        //         "price" => "10001",
        //         "time_in_force" => "gtc",
        //         "left" => "1",
        //         "filled_total" => "0",
        //         "fee" => "0",
        //         "fee_currency" => "USDT",
        //         "point_fee" => "0",
        //         "gt_fee" => "0",
        //         "gt_discount" => true,
        //         "rebated_fee" => "0",
        //         "rebated_fee_currency" => "USDT"
        //       }
        //     )
        // }
        //
        $orders = $this->safe_value($message, 'result', array());
        $channel = $this->safe_string($message, 'channel');
        $ordersLength = is_array($orders) ? count($orders) : 0;
        if ($ordersLength > 0) {
            $limit = $this->safe_integer($this->options, 'ordersLimit', 1000);
            if ($this->orders === null) {
                $this->orders = new ArrayCacheBySymbolById ($limit);
            }
            $stored = $this->orders;
            $marketIds = array();
            $parsedOrders = $this->parse_orders($orders);
            for ($i = 0; $i < count($parsedOrders); $i++) {
                $parsed = $parsedOrders[$i];
                // inject order status
                $info = $this->safe_value($parsed, 'info');
                $event = $this->safe_string($info, 'event');
                if ($event === 'put') {
                    $parsed['status'] = 'open';
                } else if ($event === 'finish') {
                    $parsed['status'] = 'closed';
                }
                $stored->append ($parsed);
                $symbol = $parsed['symbol'];
                $market = $this->market($symbol);
                $marketIds[$market['id']] = true;
            }
            $keys = is_array($marketIds) ? array_keys($marketIds) : array();
            for ($i = 0; $i < count($keys); $i++) {
                $messageHash = $channel . ':' . $keys[$i];
                $client->resolve ($this->orders, $messageHash);
            }
        }
    }

    public function handle_authentication_message($client, $message, $subscription) {
        $result = $this->safe_value($message, 'result');
        $status = $this->safe_string($result, 'status');
        if ($status === 'success') {
            // $client->resolve (true, 'authenticated') will delete the $future
            // we want to remember that we are authenticated in subsequent call to private methods
            $future = $this->safe_value($client->futures, 'authenticated');
            if ($future !== null) {
                $future->resolve (true);
            }
        } else {
            // delete authenticate subscribeHash to release the "subscribe lock"
            // allows subsequent calls to subscribe to reauthenticate
            // avoids sending two authentication messages before receiving a reply
            $error = new AuthenticationError ($this->id . ' handleAuthenticationMessage() error');
            $client->reject ($error, 'authenticated');
            if (is_array($client->subscriptions) && array_key_exists('server.sign', $client->subscriptions)) {
                unset($client->subscriptions['server.sign']);
            }
        }
    }

    public function handle_error_message($client, $message) {
        // {
        //     time => 1647274664,
        //     channel => 'futures.orders',
        //     event => 'subscribe',
        //     $error => array( $code => 2, $message => 'unknown contract BTC_USDT_20220318' ),
        // }
        // {
        //     time => 1647276473,
        //     channel => 'futures.orders',
        //     event => 'subscribe',
        //     $error => array(
        //       $code => 4,
        //       $message => 'array("label":"INVALID_KEY","message":"Invalid key provided")\n'
        //     ),
        //     result => null
        //   }
        $error = $this->safe_value($message, 'error', array());
        $code = $this->safe_integer($error, 'code');
        if ($code !== null) {
            $id = $this->safe_string($message, 'id');
            $subscriptionsById = $this->index_by($client->subscriptions, 'id');
            $subscription = $this->safe_value($subscriptionsById, $id);
            if ($subscription !== null) {
                try {
                    $this->throw_exactly_matched_exception($this->exceptions['ws']['exact'], $code, $this->json($message));
                } catch (Exception $e) {
                    $messageHash = $this->safe_string($subscription, 'messageHash');
                    $client->reject ($e, $messageHash);
                    $client->reject ($e, $id);
                    if (is_array($client->subscriptions) && array_key_exists($id, $client->subscriptions)) {
                        unset($client->subscriptions[$id]);
                    }
                }
            }
        }
    }

    public function handle_balance_subscription($client, $message, $subscription) {
        $this->spawn(array($this, 'fetch_balance_snapshot'));
    }

    public function handle_subscription_status($client, $message) {
        $messageId = $this->safe_integer($message, 'id');
        if ($messageId !== null) {
            $subscriptionsById = $this->index_by($client->subscriptions, 'id');
            $subscription = $this->safe_value($subscriptionsById, $messageId, array());
            $method = $this->safe_value($subscription, 'method');
            if ($method !== null) {
                $method($client, $message, $subscription);
            }
            $client->resolve ($message, $messageId);
        }
    }

    public function handle_message($client, $message) {
        // subscribe
        // {
        //     time => 1649062304,
        //     id => 1649062303,
        //     $channel => 'spot.candlesticks',
        //     $event => 'subscribe',
        //     result => array( status => 'success' )
        // }
        // candlestick
        // {
        //     time => 1649063328,
        //     $channel => 'spot.candlesticks',
        //     $event => 'update',
        //     result => {
        //       t => '1649063280',
        //       v => '58932.23174896',
        //       c => '45966.47',
        //       h => '45997.24',
        //       l => '45966.47',
        //       o => '45975.18',
        //       n => '1m_BTC_USDT',
        //       a => '1.281699'
        //     }
        //  }
        // orders
        // {
        //     "time" => 1630654851,
        //     "channel" => "options.orders", or futures.orders or spot.orders
        //     "event" => "update",
        //     "result" => array(
        //        {
        //           "contract" => "BTC_USDT-20211130-65000-C",
        //           "create_time" => 1637897000,
        //             (...)
        //     )
        // }
        $this->handle_error_message($client, $message);
        $methods = array(
            'depth.update' => array($this, 'handle_order_book'),
            'ticker.update' => array($this, 'handle_ticker'),
            'trades.update' => array($this, 'handle_trades'),
            'kline.update' => array($this, 'handle_ohlcv'),
            'balance.update' => array($this, 'handle_balance'),
        );
        $methodType = $this->safe_string($message, 'method');
        $method = $this->safe_value($methods, $methodType);
        if ($method === null) {
            $event = $this->safe_string($message, 'event');
            if ($event === 'subscribe') {
                $this->handle_subscription_status($client, $message);
                return;
            }
            $channel = $this->safe_string($message, 'channel', '');
            $channelParts = explode('.', $channel);
            $channelType = $this->safe_value($channelParts, 1);
            $v4Methods = array(
                'usertrades' => array($this, 'handle_my_trades'),
                'candlesticks' => array($this, 'handle_ohlcv'),
                'orders' => array($this, 'handle_order'),
                'tickers' => array($this, 'handle_ticker'),
            );
            $method = $this->safe_value($v4Methods, $channelType);
        }
        if ($method !== null) {
            $method($client, $message);
        }
    }

    public function get_uniform_type($type) {
        $uniformType = 'spot';
        if ($type === 'future' || $type === 'swap') {
            $uniformType = 'futures';
        } else if ($type === 'option') {
            $uniformType = 'options';
        }
        return $uniformType;
    }

    public function get_url_by_market_type($type, $isInverse = false) {
        if ($type === 'spot') {
            $spotUrl = $this->urls['api']['spot'];
            if ($spotUrl === null) {
                throw new NotSupported($this->id . ' does not have a testnet for the ' . $type . ' market $type->');
            }
            return $spotUrl;
        }
        if ($type === 'swap') {
            $baseUrl = $this->urls['api']['swap'];
            return $isInverse ? $baseUrl['btc'] : $baseUrl['usdt'];
        }
        if ($type === 'future') {
            $baseUrl = $this->urls['api']['future'];
            return $isInverse ? $baseUrl['btc'] : $baseUrl['usdt'];
        }
        if ($type === 'option') {
            return $this->urls['api']['option'];
        }
    }

    public function subscribe_public($url, $channel, $messageHash, $payload) {
        $time = $this->seconds();
        $request = array(
            'id' => $time,
            'time' => $time,
            'channel' => $channel,
            'event' => 'subscribe',
            'payload' => $payload,
        );
        $subscription = array(
            'id' => $time,
            'messageHash' => $messageHash,
        );
        return yield $this->watch($url, $messageHash, $request, $messageHash, $subscription);
    }

    public function subscribe_private($url, $channel, $messageHash, $payload, $requiresUid = false) {
        $this->check_required_credentials();
        // uid is required for some subscriptions only so it's not a part of required credentials
        if ($requiresUid) {
            if ($this->uid === null || strlen($this->uid) === 0) {
                throw new ArgumentsRequired($this->id . ' requires uid to subscribe');
            }
            $idArray = [$this->uid];
            $payload = $this->array_concat($idArray, $payload);
        }
        $time = $this->seconds();
        $event = 'subscribe';
        $signaturePayload = 'channel=' . $channel . '&$event=' . $event . '&$time=' . (string) $time;
        $signature = $this->hmac($this->encode($signaturePayload), $this->encode($this->secret), 'sha512', 'hex');
        $auth = array(
            'method' => 'api_key',
            'KEY' => $this->apiKey,
            'SIGN' => $signature,
        );
        $requestId = $this->nonce();
        $request = array(
            'id' => $requestId,
            'time' => $time,
            'channel' => $channel,
            'event' => 'subscribe',
            'payload' => $payload,
            'auth' => $auth,
        );
        $subscription = array(
            'id' => $requestId,
            'messageHash' => $messageHash,
        );
        return yield $this->watch($url, $messageHash, $request, $messageHash, $subscription);
    }
}
