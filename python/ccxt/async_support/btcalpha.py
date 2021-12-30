# -*- coding: utf-8 -*-

# PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
# https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

from ccxt.async_support.base.exchange import Exchange
from ccxt.base.errors import ExchangeError
from ccxt.base.errors import AuthenticationError
from ccxt.base.errors import InsufficientFunds
from ccxt.base.errors import InvalidOrder
from ccxt.base.errors import DDoSProtection
from ccxt.base.precise import Precise


class btcalpha(Exchange):

    def describe(self):
        return self.deep_extend(super(btcalpha, self).describe(), {
            'id': 'btcalpha',
            'name': 'BTC-Alpha',
            'countries': ['US'],
            'version': 'v1',
            'has': {
                'cancelOrder': True,
                'createOrder': True,
                'fetchBalance': True,
                'fetchClosedOrders': True,
                'fetchMarkets': True,
                'fetchMyTrades': True,
                'fetchOHLCV': True,
                'fetchOpenOrders': True,
                'fetchOrder': True,
                'fetchOrderBook': True,
                'fetchOrders': True,
                'fetchTicker': None,
                'fetchTrades': True,
            },
            'timeframes': {
                '1m': '1',
                '5m': '5',
                '15m': '15',
                '30m': '30',
                '1h': '60',
                '4h': '240',
                '1d': 'D',
            },
            'urls': {
                'logo': 'https://user-images.githubusercontent.com/1294454/42625213-dabaa5da-85cf-11e8-8f99-aa8f8f7699f0.jpg',
                'api': 'https://btc-alpha.com/api',
                'www': 'https://btc-alpha.com',
                'doc': 'https://btc-alpha.github.io/api-docs',
                'fees': 'https://btc-alpha.com/fees/',
                'referral': 'https://btc-alpha.com/?r=123788',
            },
            'api': {
                'public': {
                    'get': [
                        'currencies/',
                        'pairs/',
                        'orderbook/{pair_name}/',
                        'exchanges/',
                        'charts/{pair}/{type}/chart/',
                    ],
                },
                'private': {
                    'get': [
                        'wallets/',
                        'orders/own/',
                        'order/{id}/',
                        'exchanges/own/',
                        'deposits/',
                        'withdraws/',
                    ],
                    'post': [
                        'order/',
                        'order-cancel/',
                    ],
                },
            },
            'fees': {
                'trading': {
                    'maker': self.parse_number('0.002'),
                    'taker': self.parse_number('0.002'),
                },
                'funding': {
                    'withdraw': {},
                },
            },
            'commonCurrencies': {
                'CBC': 'Cashbery',
            },
            'exceptions': {
                'exact': {},
                'broad': {
                    'Out of balance': InsufficientFunds,  # {"date":1570599531.4814300537,"error":"Out of balance -9.99243661 BTC"}
                },
            },
        })

    async def fetch_markets(self, params={}):
        response = await self.publicGetPairs(params)
        result = []
        for i in range(0, len(response)):
            market = response[i]
            id = self.safe_string(market, 'name')
            baseId = self.safe_string(market, 'currency1')
            quoteId = self.safe_string(market, 'currency2')
            base = self.safe_currency_code(baseId)
            quote = self.safe_currency_code(quoteId)
            symbol = base + '/' + quote
            pricePrecision = self.safe_string(market, 'price_precision')
            priceLimit = self.parse_precision(pricePrecision)
            precision = {
                'amount': 8,
                'price': int(pricePrecision),
            }
            amountLimit = self.safe_string(market, 'minimum_order_size')
            result.append({
                'id': id,
                'symbol': symbol,
                'base': base,
                'quote': quote,
                'type': 'spot',
                'spot': True,
                'active': True,
                'precision': precision,
                'limits': {
                    'amount': {
                        'min': self.parse_number(amountLimit),
                        'max': self.safe_number(market, 'maximum_order_size'),
                    },
                    'price': {
                        'min': self.parse_number(priceLimit),
                        'max': None,
                    },
                    'cost': {
                        'min': self.parse_number(Precise.string_mul(priceLimit, amountLimit)),
                        'max': None,
                    },
                },
                'info': market,
                'baseId': None,
                'quoteId': None,
            })
        return result

    async def fetch_order_book(self, symbol, limit=None, params={}):
        await self.load_markets()
        request = {
            'pair_name': self.market_id(symbol),
        }
        if limit:
            request['limit_sell'] = limit
            request['limit_buy'] = limit
        response = await self.publicGetOrderbookPairName(self.extend(request, params))
        return self.parse_order_book(response, symbol, None, 'buy', 'sell', 'price', 'amount')

    def parse_bids_asks(self, bidasks, priceKey=0, amountKey=1):
        result = []
        for i in range(0, len(bidasks)):
            bidask = bidasks[i]
            if bidask:
                result.append(self.parse_bid_ask(bidask, priceKey, amountKey))
        return result

    def parse_trade(self, trade, market=None):
        #
        # fetchTrades(public)
        #
        #      {
        #          "id": "202203440",
        #          "timestamp": "1637856276.264215",
        #          "pair": "AAVE_USDT",
        #          "price": "320.79900000",
        #          "amount": "0.05000000",
        #          "type": "buy"
        #      }
        #
        # fetchMyTrades(private)
        #
        #      {
        #          "id": "202203440",
        #          "timestamp": "1637856276.264215",
        #          "pair": "AAVE_USDT",
        #          "price": "320.79900000",
        #          "amount": "0.05000000",
        #          "type": "buy",
        #          "my_side": "buy"
        #      }
        #
        symbol = None
        if market is None:
            market = self.safe_value(self.markets_by_id, trade['pair'])
        if market is not None:
            symbol = market['symbol']
        timestamp = self.safe_timestamp(trade, 'timestamp')
        priceString = self.safe_string(trade, 'price')
        amountString = self.safe_string(trade, 'amount')
        id = self.safe_string(trade, 'id')
        side = self.safe_string_2(trade, 'my_side', 'type')
        return self.safe_trade({
            'id': id,
            'info': trade,
            'timestamp': timestamp,
            'datetime': self.iso8601(timestamp),
            'symbol': symbol,
            'order': id,
            'type': 'limit',
            'side': side,
            'takerOrMaker': None,
            'price': priceString,
            'amount': amountString,
            'cost': None,
            'fee': None,
        }, market)

    async def fetch_trades(self, symbol, since=None, limit=None, params={}):
        await self.load_markets()
        market = None
        request = {}
        if symbol is not None:
            market = self.market(symbol)
            request['pair'] = market['id']
        if limit is not None:
            request['limit'] = limit
        trades = await self.publicGetExchanges(self.extend(request, params))
        return self.parse_trades(trades, market, since, limit)

    def parse_ohlcv(self, ohlcv, market=None):
        #
        #     {
        #         "time":1591296000,
        #         "open":0.024746,
        #         "close":0.024728,
        #         "low":0.024728,
        #         "high":0.024753,
        #         "volume":16.624
        #     }
        #
        return [
            self.safe_timestamp(ohlcv, 'time'),
            self.safe_number(ohlcv, 'open'),
            self.safe_number(ohlcv, 'high'),
            self.safe_number(ohlcv, 'low'),
            self.safe_number(ohlcv, 'close'),
            self.safe_number(ohlcv, 'volume'),
        ]

    async def fetch_ohlcv(self, symbol, timeframe='5m', since=None, limit=None, params={}):
        await self.load_markets()
        market = self.market(symbol)
        request = {
            'pair': market['id'],
            'type': self.timeframes[timeframe],
        }
        if limit is not None:
            request['limit'] = limit
        if since is not None:
            request['since'] = int(since / 1000)
        response = await self.publicGetChartsPairTypeChart(self.extend(request, params))
        #
        #     [
        #         {"time":1591296000,"open":0.024746,"close":0.024728,"low":0.024728,"high":0.024753,"volume":16.624},
        #         {"time":1591295700,"open":0.024718,"close":0.02475,"low":0.024711,"high":0.02475,"volume":31.645},
        #         {"time":1591295400,"open":0.024721,"close":0.024717,"low":0.024711,"high":0.02473,"volume":65.071}
        #     ]
        #
        return self.parse_ohlcvs(response, market, timeframe, since, limit)

    async def fetch_balance(self, params={}):
        await self.load_markets()
        response = await self.privateGetWallets(params)
        result = {'info': response}
        for i in range(0, len(response)):
            balance = response[i]
            currencyId = self.safe_string(balance, 'currency')
            code = self.safe_currency_code(currencyId)
            account = self.account()
            account['used'] = self.safe_string(balance, 'reserve')
            account['total'] = self.safe_string(balance, 'balance')
            result[code] = account
        return self.safe_balance(result)

    def parse_order_status(self, status):
        statuses = {
            '1': 'open',
            '2': 'canceled',
            '3': 'closed',
        }
        return self.safe_string(statuses, status, status)

    def parse_order(self, order, market=None):
        #
        # fetchClosedOrders / fetchOrder
        #     {
        #       "id": "923763073",
        #       "date": "1635451090368",
        #       "type": "sell",
        #       "pair": "XRP_USDT",
        #       "price": "1.00000000",
        #       "amount": "0.00000000",
        #       "status": "3",
        #       "amount_filled": "10.00000000",
        #       "amount_original": "10.0"
        #       "trades": [],
        #     }
        #
        # createOrder
        #     {
        #       "success": True,
        #       "date": "1635451754.497541",
        #       "type": "sell",
        #       "oid": "923776755",
        #       "price": "1.0",
        #       "amount": "10.0",
        #       "amount_filled": "0.0",
        #       "amount_original": "10.0",
        #       "trades": []
        #     }
        #
        marketId = self.safe_string(order, 'pair')
        market = self.safe_market(marketId, market, '_')
        symbol = market['symbol']
        success = self.safe_value(order, 'success', False)
        timestamp = None
        if success:
            timestamp = self.safe_timestamp(order, 'date')
        else:
            timestamp = self.safe_integer(order, 'date')
        price = self.safe_string(order, 'price')
        remaining = self.safe_string(order, 'amount')
        filled = self.safe_string(order, 'amount_filled')
        amount = self.safe_string(order, 'amount_original')
        status = self.parse_order_status(self.safe_string(order, 'status'))
        id = self.safe_string_2(order, 'oid', 'id')
        trades = self.safe_value(order, 'trades')
        side = self.safe_string_2(order, 'my_side', 'type')
        return self.safe_order({
            'id': id,
            'clientOrderId': None,
            'datetime': self.iso8601(timestamp),
            'timestamp': timestamp,
            'status': status,
            'symbol': symbol,
            'type': 'limit',
            'timeInForce': None,
            'postOnly': None,
            'side': side,
            'price': price,
            'stopPrice': None,
            'cost': None,
            'amount': amount,
            'filled': filled,
            'remaining': remaining,
            'trades': trades,
            'fee': None,
            'info': order,
            'lastTradeTimestamp': None,
            'average': None,
        }, market)

    async def create_order(self, symbol, type, side, amount, price=None, params={}):
        await self.load_markets()
        market = self.market(symbol)
        request = {
            'pair': market['id'],
            'type': side,
            'amount': amount,
            'price': self.price_to_precision(symbol, price),
        }
        response = await self.privatePostOrder(self.extend(request, params))
        if not response['success']:
            raise InvalidOrder(self.id + ' ' + self.json(response))
        order = self.parse_order(response, market)
        amount = order['amount'] if (order['amount'] > 0) else amount
        return self.extend(order, {
            'amount': amount,
        })

    async def cancel_order(self, id, symbol=None, params={}):
        request = {
            'order': id,
        }
        response = await self.privatePostOrderCancel(self.extend(request, params))
        return response

    async def fetch_order(self, id, symbol=None, params={}):
        await self.load_markets()
        request = {
            'id': id,
        }
        order = await self.privateGetOrderId(self.extend(request, params))
        return self.parse_order(order)

    async def fetch_orders(self, symbol=None, since=None, limit=None, params={}):
        await self.load_markets()
        request = {}
        market = None
        if symbol is not None:
            market = self.market(symbol)
            request['pair'] = market['id']
        if limit is not None:
            request['limit'] = limit
        orders = await self.privateGetOrdersOwn(self.extend(request, params))
        return self.parse_orders(orders, market, since, limit)

    async def fetch_open_orders(self, symbol=None, since=None, limit=None, params={}):
        request = {
            'status': '1',
        }
        return await self.fetch_orders(symbol, since, limit, self.extend(request, params))

    async def fetch_closed_orders(self, symbol=None, since=None, limit=None, params={}):
        request = {
            'status': '3',
        }
        return await self.fetch_orders(symbol, since, limit, self.extend(request, params))

    async def fetch_my_trades(self, symbol=None, since=None, limit=None, params={}):
        await self.load_markets()
        request = {}
        if symbol is not None:
            market = self.market(symbol)
            request['pair'] = market['id']
        if limit is not None:
            request['limit'] = limit
        trades = await self.privateGetExchangesOwn(self.extend(request, params))
        return self.parse_trades(trades, None, since, limit)

    def nonce(self):
        return self.milliseconds()

    def sign(self, path, api='public', method='GET', params={}, headers=None, body=None):
        query = self.urlencode(self.keysort(self.omit(params, self.extract_params(path))))
        url = self.urls['api'] + '/'
        if path != 'charts/{pair}/{type}/chart/':
            url += 'v1/'
        url += self.implode_params(path, params)
        headers = {'Accept': 'application/json'}
        if api == 'public':
            if len(query):
                url += '?' + query
        else:
            self.check_required_credentials()
            payload = self.apiKey
            if method == 'POST':
                headers['Content-Type'] = 'application/x-www-form-urlencoded'
                body = query
                payload += body
            elif len(query):
                url += '?' + query
            headers['X-KEY'] = self.apiKey
            headers['X-SIGN'] = self.hmac(self.encode(payload), self.encode(self.secret))
            headers['X-NONCE'] = str(self.nonce())
        return {'url': url, 'method': method, 'body': body, 'headers': headers}

    def handle_errors(self, code, reason, url, method, headers, body, response, requestHeaders, requestBody):
        if response is None:
            return  # fallback to default error handler
        #
        #     {"date":1570599531.4814300537,"error":"Out of balance -9.99243661 BTC"}
        #
        error = self.safe_string(response, 'error')
        feedback = self.id + ' ' + body
        if error is not None:
            self.throw_exactly_matched_exception(self.exceptions['exact'], error, feedback)
            self.throw_broadly_matched_exception(self.exceptions['broad'], error, feedback)
        if code == 401 or code == 403:
            raise AuthenticationError(feedback)
        elif code == 429:
            raise DDoSProtection(feedback)
        if code < 400:
            return
        raise ExchangeError(feedback)
