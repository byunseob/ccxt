<?php

namespace ccxt\async;

// PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
// https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

use Exception; // a common import
use \ccxt\ExchangeError;
use \ccxt\AuthenticationError;
use \ccxt\InvalidOrder;
use \ccxt\DDoSProtection;
use \ccxt\Precise;

class btcalpha extends Exchange {

    public function describe() {
        return $this->deep_extend(parent::describe (), array(
            'id' => 'btcalpha',
            'name' => 'BTC-Alpha',
            'countries' => array( 'US' ),
            'version' => 'v1',
            'has' => array(
                'cancelOrder' => true,
                'createOrder' => true,
                'fetchBalance' => true,
                'fetchClosedOrders' => true,
                'fetchMarkets' => true,
                'fetchMyTrades' => true,
                'fetchOHLCV' => true,
                'fetchOpenOrders' => true,
                'fetchOrder' => true,
                'fetchOrderBook' => true,
                'fetchOrders' => true,
                'fetchTicker' => null,
                'fetchTrades' => true,
            ),
            'timeframes' => array(
                '1m' => '1',
                '5m' => '5',
                '15m' => '15',
                '30m' => '30',
                '1h' => '60',
                '4h' => '240',
                '1d' => 'D',
            ),
            'urls' => array(
                'logo' => 'https://user-images.githubusercontent.com/1294454/42625213-dabaa5da-85cf-11e8-8f99-aa8f8f7699f0.jpg',
                'api' => 'https://btc-alpha.com/api',
                'www' => 'https://btc-alpha.com',
                'doc' => 'https://btc-alpha.github.io/api-docs',
                'fees' => 'https://btc-alpha.com/fees/',
                'referral' => 'https://btc-alpha.com/?r=123788',
            ),
            'api' => array(
                'public' => array(
                    'get' => array(
                        'currencies/',
                        'pairs/',
                        'orderbook/{pair_name}/',
                        'exchanges/',
                        'charts/{pair}/{type}/chart/',
                    ),
                ),
                'private' => array(
                    'get' => array(
                        'wallets/',
                        'orders/own/',
                        'order/{id}/',
                        'exchanges/own/',
                        'deposits/',
                        'withdraws/',
                    ),
                    'post' => array(
                        'order/',
                        'order-cancel/',
                    ),
                ),
            ),
            'fees' => array(
                'trading' => array(
                    'maker' => $this->parse_number('0.002'),
                    'taker' => $this->parse_number('0.002'),
                ),
                'funding' => array(
                    'withdraw' => array(),
                ),
            ),
            'commonCurrencies' => array(
                'CBC' => 'Cashbery',
            ),
            'exceptions' => array(
                'exact' => array(),
                'broad' => array(
                    'Out of balance' => '\\ccxt\\InsufficientFunds', // array("date":1570599531.4814300537,"error":"Out of balance -9.99243661 BTC")
                ),
            ),
        ));
    }

    public function fetch_markets($params = array ()) {
        $response = yield $this->publicGetPairs ($params);
        $result = array();
        for ($i = 0; $i < count($response); $i++) {
            $market = $response[$i];
            $id = $this->safe_string($market, 'name');
            $baseId = $this->safe_string($market, 'currency1');
            $quoteId = $this->safe_string($market, 'currency2');
            $base = $this->safe_currency_code($baseId);
            $quote = $this->safe_currency_code($quoteId);
            $symbol = $base . '/' . $quote;
            $pricePrecision = $this->safe_string($market, 'price_precision');
            $priceLimit = $this->parse_precision($pricePrecision);
            $precision = array(
                'amount' => 8,
                'price' => intval($pricePrecision),
            );
            $amountLimit = $this->safe_string($market, 'minimum_order_size');
            $result[] = array(
                'id' => $id,
                'symbol' => $symbol,
                'base' => $base,
                'quote' => $quote,
                'type' => 'spot',
                'spot' => true,
                'active' => true,
                'precision' => $precision,
                'limits' => array(
                    'amount' => array(
                        'min' => $this->parse_number($amountLimit),
                        'max' => $this->safe_number($market, 'maximum_order_size'),
                    ),
                    'price' => array(
                        'min' => $this->parse_number($priceLimit),
                        'max' => null,
                    ),
                    'cost' => array(
                        'min' => $this->parse_number(Precise::string_mul($priceLimit, $amountLimit)),
                        'max' => null,
                    ),
                ),
                'info' => $market,
                'baseId' => null,
                'quoteId' => null,
            );
        }
        return $result;
    }

    public function fetch_order_book($symbol, $limit = null, $params = array ()) {
        yield $this->load_markets();
        $request = array(
            'pair_name' => $this->market_id($symbol),
        );
        if ($limit) {
            $request['limit_sell'] = $limit;
            $request['limit_buy'] = $limit;
        }
        $response = yield $this->publicGetOrderbookPairName (array_merge($request, $params));
        return $this->parse_order_book($response, $symbol, null, 'buy', 'sell', 'price', 'amount');
    }

    public function parse_bids_asks($bidasks, $priceKey = 0, $amountKey = 1) {
        $result = array();
        for ($i = 0; $i < count($bidasks); $i++) {
            $bidask = $bidasks[$i];
            if ($bidask) {
                $result[] = $this->parse_bid_ask($bidask, $priceKey, $amountKey);
            }
        }
        return $result;
    }

    public function parse_trade($trade, $market = null) {
        //
        // fetchTrades (public)
        //
        //      {
        //          "id" => "202203440",
        //          "timestamp" => "1637856276.264215",
        //          "pair" => "AAVE_USDT",
        //          "price" => "320.79900000",
        //          "amount" => "0.05000000",
        //          "type" => "buy"
        //      }
        //
        // fetchMyTrades (private)
        //
        //      {
        //          "id" => "202203440",
        //          "timestamp" => "1637856276.264215",
        //          "pair" => "AAVE_USDT",
        //          "price" => "320.79900000",
        //          "amount" => "0.05000000",
        //          "type" => "buy",
        //          "my_side" => "buy"
        //      }
        //
        $symbol = null;
        if ($market === null) {
            $market = $this->safe_value($this->markets_by_id, $trade['pair']);
        }
        if ($market !== null) {
            $symbol = $market['symbol'];
        }
        $timestamp = $this->safe_timestamp($trade, 'timestamp');
        $priceString = $this->safe_string($trade, 'price');
        $amountString = $this->safe_string($trade, 'amount');
        $id = $this->safe_string($trade, 'id');
        $side = $this->safe_string_2($trade, 'my_side', 'type');
        return $this->safe_trade(array(
            'id' => $id,
            'info' => $trade,
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601($timestamp),
            'symbol' => $symbol,
            'order' => $id,
            'type' => 'limit',
            'side' => $side,
            'takerOrMaker' => null,
            'price' => $priceString,
            'amount' => $amountString,
            'cost' => null,
            'fee' => null,
        ), $market);
    }

    public function fetch_trades($symbol, $since = null, $limit = null, $params = array ()) {
        yield $this->load_markets();
        $market = null;
        $request = array();
        if ($symbol !== null) {
            $market = $this->market($symbol);
            $request['pair'] = $market['id'];
        }
        if ($limit !== null) {
            $request['limit'] = $limit;
        }
        $trades = yield $this->publicGetExchanges (array_merge($request, $params));
        return $this->parse_trades($trades, $market, $since, $limit);
    }

    public function parse_ohlcv($ohlcv, $market = null) {
        //
        //     {
        //         "time":1591296000,
        //         "open":0.024746,
        //         "close":0.024728,
        //         "low":0.024728,
        //         "high":0.024753,
        //         "volume":16.624
        //     }
        //
        return array(
            $this->safe_timestamp($ohlcv, 'time'),
            $this->safe_number($ohlcv, 'open'),
            $this->safe_number($ohlcv, 'high'),
            $this->safe_number($ohlcv, 'low'),
            $this->safe_number($ohlcv, 'close'),
            $this->safe_number($ohlcv, 'volume'),
        );
    }

    public function fetch_ohlcv($symbol, $timeframe = '5m', $since = null, $limit = null, $params = array ()) {
        yield $this->load_markets();
        $market = $this->market($symbol);
        $request = array(
            'pair' => $market['id'],
            'type' => $this->timeframes[$timeframe],
        );
        if ($limit !== null) {
            $request['limit'] = $limit;
        }
        if ($since !== null) {
            $request['since'] = intval($since / 1000);
        }
        $response = yield $this->publicGetChartsPairTypeChart (array_merge($request, $params));
        //
        //     array(
        //         array("time":1591296000,"open":0.024746,"close":0.024728,"low":0.024728,"high":0.024753,"volume":16.624),
        //         array("time":1591295700,"open":0.024718,"close":0.02475,"low":0.024711,"high":0.02475,"volume":31.645),
        //         array("time":1591295400,"open":0.024721,"close":0.024717,"low":0.024711,"high":0.02473,"volume":65.071)
        //     )
        //
        return $this->parse_ohlcvs($response, $market, $timeframe, $since, $limit);
    }

    public function fetch_balance($params = array ()) {
        yield $this->load_markets();
        $response = yield $this->privateGetWallets ($params);
        $result = array( 'info' => $response );
        for ($i = 0; $i < count($response); $i++) {
            $balance = $response[$i];
            $currencyId = $this->safe_string($balance, 'currency');
            $code = $this->safe_currency_code($currencyId);
            $account = $this->account();
            $account['used'] = $this->safe_string($balance, 'reserve');
            $account['total'] = $this->safe_string($balance, 'balance');
            $result[$code] = $account;
        }
        return $this->safe_balance($result);
    }

    public function parse_order_status($status) {
        $statuses = array(
            '1' => 'open',
            '2' => 'canceled',
            '3' => 'closed',
        );
        return $this->safe_string($statuses, $status, $status);
    }

    public function parse_order($order, $market = null) {
        //
        // fetchClosedOrders / fetchOrder
        //     {
        //       "id" => "923763073",
        //       "date" => "1635451090368",
        //       "type" => "sell",
        //       "pair" => "XRP_USDT",
        //       "price" => "1.00000000",
        //       "amount" => "0.00000000",
        //       "status" => "3",
        //       "amount_filled" => "10.00000000",
        //       "amount_original" => "10.0"
        //       "trades" => array(),
        //     }
        //
        // createOrder
        //     {
        //       "success" => true,
        //       "date" => "1635451754.497541",
        //       "type" => "sell",
        //       "oid" => "923776755",
        //       "price" => "1.0",
        //       "amount" => "10.0",
        //       "amount_filled" => "0.0",
        //       "amount_original" => "10.0",
        //       "trades" => array()
        //     }
        //
        $marketId = $this->safe_string($order, 'pair');
        $market = $this->safe_market($marketId, $market, '_');
        $symbol = $market['symbol'];
        $success = $this->safe_value($order, 'success', false);
        $timestamp = null;
        if ($success) {
            $timestamp = $this->safe_timestamp($order, 'date');
        } else {
            $timestamp = $this->safe_integer($order, 'date');
        }
        $price = $this->safe_string($order, 'price');
        $remaining = $this->safe_string($order, 'amount');
        $filled = $this->safe_string($order, 'amount_filled');
        $amount = $this->safe_string($order, 'amount_original');
        $status = $this->parse_order_status($this->safe_string($order, 'status'));
        $id = $this->safe_string_2($order, 'oid', 'id');
        $trades = $this->safe_value($order, 'trades');
        $side = $this->safe_string_2($order, 'my_side', 'type');
        return $this->safe_order(array(
            'id' => $id,
            'clientOrderId' => null,
            'datetime' => $this->iso8601($timestamp),
            'timestamp' => $timestamp,
            'status' => $status,
            'symbol' => $symbol,
            'type' => 'limit',
            'timeInForce' => null,
            'postOnly' => null,
            'side' => $side,
            'price' => $price,
            'stopPrice' => null,
            'cost' => null,
            'amount' => $amount,
            'filled' => $filled,
            'remaining' => $remaining,
            'trades' => $trades,
            'fee' => null,
            'info' => $order,
            'lastTradeTimestamp' => null,
            'average' => null,
        ), $market);
    }

    public function create_order($symbol, $type, $side, $amount, $price = null, $params = array ()) {
        yield $this->load_markets();
        $market = $this->market($symbol);
        $request = array(
            'pair' => $market['id'],
            'type' => $side,
            'amount' => $amount,
            'price' => $this->price_to_precision($symbol, $price),
        );
        $response = yield $this->privatePostOrder (array_merge($request, $params));
        if (!$response['success']) {
            throw new InvalidOrder($this->id . ' ' . $this->json($response));
        }
        $order = $this->parse_order($response, $market);
        $amount = ($order['amount'] > 0) ? $order['amount'] : $amount;
        return array_merge($order, array(
            'amount' => $amount,
        ));
    }

    public function cancel_order($id, $symbol = null, $params = array ()) {
        $request = array(
            'order' => $id,
        );
        $response = yield $this->privatePostOrderCancel (array_merge($request, $params));
        return $response;
    }

    public function fetch_order($id, $symbol = null, $params = array ()) {
        yield $this->load_markets();
        $request = array(
            'id' => $id,
        );
        $order = yield $this->privateGetOrderId (array_merge($request, $params));
        return $this->parse_order($order);
    }

    public function fetch_orders($symbol = null, $since = null, $limit = null, $params = array ()) {
        yield $this->load_markets();
        $request = array();
        $market = null;
        if ($symbol !== null) {
            $market = $this->market($symbol);
            $request['pair'] = $market['id'];
        }
        if ($limit !== null) {
            $request['limit'] = $limit;
        }
        $orders = yield $this->privateGetOrdersOwn (array_merge($request, $params));
        return $this->parse_orders($orders, $market, $since, $limit);
    }

    public function fetch_open_orders($symbol = null, $since = null, $limit = null, $params = array ()) {
        $request = array(
            'status' => '1',
        );
        return yield $this->fetch_orders($symbol, $since, $limit, array_merge($request, $params));
    }

    public function fetch_closed_orders($symbol = null, $since = null, $limit = null, $params = array ()) {
        $request = array(
            'status' => '3',
        );
        return yield $this->fetch_orders($symbol, $since, $limit, array_merge($request, $params));
    }

    public function fetch_my_trades($symbol = null, $since = null, $limit = null, $params = array ()) {
        yield $this->load_markets();
        $request = array();
        if ($symbol !== null) {
            $market = $this->market($symbol);
            $request['pair'] = $market['id'];
        }
        if ($limit !== null) {
            $request['limit'] = $limit;
        }
        $trades = yield $this->privateGetExchangesOwn (array_merge($request, $params));
        return $this->parse_trades($trades, null, $since, $limit);
    }

    public function nonce() {
        return $this->milliseconds();
    }

    public function sign($path, $api = 'public', $method = 'GET', $params = array (), $headers = null, $body = null) {
        $query = $this->urlencode($this->keysort($this->omit($params, $this->extract_params($path))));
        $url = $this->urls['api'] . '/';
        if ($path !== 'charts/{pair}/{type}/chart/') {
            $url .= 'v1/';
        }
        $url .= $this->implode_params($path, $params);
        $headers = array( 'Accept' => 'application/json' );
        if ($api === 'public') {
            if (strlen($query)) {
                $url .= '?' . $query;
            }
        } else {
            $this->check_required_credentials();
            $payload = $this->apiKey;
            if ($method === 'POST') {
                $headers['Content-Type'] = 'application/x-www-form-urlencoded';
                $body = $query;
                $payload .= $body;
            } else if (strlen($query)) {
                $url .= '?' . $query;
            }
            $headers['X-KEY'] = $this->apiKey;
            $headers['X-SIGN'] = $this->hmac($this->encode($payload), $this->encode($this->secret));
            $headers['X-NONCE'] = (string) $this->nonce();
        }
        return array( 'url' => $url, 'method' => $method, 'body' => $body, 'headers' => $headers );
    }

    public function handle_errors($code, $reason, $url, $method, $headers, $body, $response, $requestHeaders, $requestBody) {
        if ($response === null) {
            return; // fallback to default $error handler
        }
        //
        //     array("date":1570599531.4814300537,"error":"Out of balance -9.99243661 BTC")
        //
        $error = $this->safe_string($response, 'error');
        $feedback = $this->id . ' ' . $body;
        if ($error !== null) {
            $this->throw_exactly_matched_exception($this->exceptions['exact'], $error, $feedback);
            $this->throw_broadly_matched_exception($this->exceptions['broad'], $error, $feedback);
        }
        if ($code === 401 || $code === 403) {
            throw new AuthenticationError($feedback);
        } else if ($code === 429) {
            throw new DDoSProtection($feedback);
        }
        if ($code < 400) {
            return;
        }
        throw new ExchangeError($feedback);
    }
}
