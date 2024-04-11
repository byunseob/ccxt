'use strict';

var coinbase$1 = require('../coinbase.js');
var errors = require('../base/errors.js');
var Cache = require('../base/ws/Cache.js');
var sha256 = require('../static_dependencies/noble-hashes/sha256.js');

//  ---------------------------------------------------------------------------
//  ---------------------------------------------------------------------------
class coinbase extends coinbase$1 {
    describe() {
        return this.deepExtend(super.describe(), {
            'has': {
                'ws': true,
                'cancelAllOrdersWs': false,
                'cancelOrdersWs': false,
                'cancelOrderWs': false,
                'createOrderWs': false,
                'editOrderWs': false,
                'fetchBalanceWs': false,
                'fetchOpenOrdersWs': false,
                'fetchOrderWs': false,
                'fetchTradesWs': false,
                'watchBalance': false,
                'watchMyTrades': false,
                'watchOHLCV': false,
                'watchOrderBook': true,
                'watchOrders': true,
                'watchTicker': true,
                'watchTickers': true,
                'watchTrades': true,
            },
            'urls': {
                'api': {
                    'ws': 'wss://advanced-trade-ws.coinbase.com',
                },
            },
            'options': {
                'tradesLimit': 1000,
                'ordersLimit': 1000,
                'myTradesLimit': 1000,
                'sides': {
                    'bid': 'bids',
                    'offer': 'asks',
                },
            },
        });
    }
    async subscribe(name, symbol = undefined, params = {}) {
        /**
         * @ignore
         * @method
         * @description subscribes to a websocket channel
         * @see https://docs.cloud.coinbase.com/advanced-trade-api/docs/ws-overview#subscribe
         * @param {string} name the name of the channel
         * @param {string|string[]} [symbol] unified market symbol
         * @param {object} [params] extra parameters specific to the exchange API endpoint
         * @returns {object} subscription to a websocket channel
         */
        await this.loadMarkets();
        this.checkRequiredCredentials();
        let market = undefined;
        let messageHash = name;
        let productIds = [];
        if (Array.isArray(symbol)) {
            const symbols = this.marketSymbols(symbol);
            const marketIds = this.marketIds(symbols);
            productIds = marketIds;
            messageHash = messageHash + '::' + symbol.join(',');
        }
        else if (symbol !== undefined) {
            market = this.market(symbol);
            messageHash = name + '::' + market['id'];
            productIds = [market['id']];
        }
        const url = this.urls['api']['ws'];
        const timestamp = this.numberToString(this.seconds());
        const auth = timestamp + name + productIds.join(',');
        const subscribe = {
            'type': 'subscribe',
            'product_ids': productIds,
            'channel': name,
            'api_key': this.apiKey,
            'timestamp': timestamp,
            'signature': this.hmac(this.encode(auth), this.encode(this.secret), sha256.sha256),
        };
        return await this.watch(url, messageHash, subscribe, messageHash);
    }
    async watchTicker(symbol, params = {}) {
        /**
         * @method
         * @name coinbasepro#watchTicker
         * @description watches a price ticker, a statistical calculation with the information calculated over the past 24 hours for a specific market
         * @see https://docs.cloud.coinbase.com/advanced-trade-api/docs/ws-channels#ticker-channel
         * @param {string} [symbol] unified symbol of the market to fetch the ticker for
         * @param {object} [params] extra parameters specific to the exchange API endpoint
         * @returns {object} a [ticker structure]{@link https://docs.ccxt.com/#/?id=ticker-structure}
         */
        const name = 'ticker';
        return await this.subscribe(name, symbol, params);
    }
    async watchTickers(symbols = undefined, params = {}) {
        /**
         * @method
         * @name coinbasepro#watchTickers
         * @description watches a price ticker, a statistical calculation with the information calculated over the past 24 hours for a specific market
         * @see https://docs.cloud.coinbase.com/advanced-trade-api/docs/ws-channels#ticker-batch-channel
         * @param {string[]} [symbols] unified symbol of the market to fetch the ticker for
         * @param {object} [params] extra parameters specific to the exchange API endpoint
         * @returns {object} a [ticker structure]{@link https://docs.ccxt.com/#/?id=ticker-structure}
         */
        if (symbols === undefined) {
            symbols = this.symbols;
        }
        const name = 'ticker_batch';
        const tickers = await this.subscribe(name, symbols, params);
        if (this.newUpdates) {
            return tickers;
        }
        return this.tickers;
    }
    handleTickers(client, message) {
        //
        //    {
        //        "channel": "ticker",
        //        "client_id": "",
        //        "timestamp": "2023-02-09T20:30:37.167359596Z",
        //        "sequence_num": 0,
        //        "events": [
        //            {
        //                "type": "snapshot",
        //                "tickers": [
        //                    {
        //                        "type": "ticker",
        //                        "product_id": "BTC-USD",
        //                        "price": "21932.98",
        //                        "volume_24_h": "16038.28770938",
        //                        "low_24_h": "21835.29",
        //                        "high_24_h": "23011.18",
        //                        "low_52_w": "15460",
        //                        "high_52_w": "48240",
        //                        "price_percent_chg_24_h": "-4.15775596190603"
        //                    }
        //                ]
        //            }
        //        ]
        //    }
        //
        //    {
        //        "channel": "ticker_batch",
        //        "client_id": "",
        //        "timestamp": "2023-03-01T12:15:18.382173051Z",
        //        "sequence_num": 0,
        //        "events": [
        //            {
        //                "type": "snapshot",
        //                "tickers": [
        //                    {
        //                        "type": "ticker",
        //                        "product_id": "DOGE-USD",
        //                        "price": "0.08212",
        //                        "volume_24_h": "242556423.3",
        //                        "low_24_h": "0.07989",
        //                        "high_24_h": "0.08308",
        //                        "low_52_w": "0.04908",
        //                        "high_52_w": "0.1801",
        //                        "price_percent_chg_24_h": "0.50177456859626"
        //                    }
        //                ]
        //            }
        //        ]
        //    }
        //
        const channel = this.safeString(message, 'channel');
        const events = this.safeValue(message, 'events', []);
        const newTickers = [];
        for (let i = 0; i < events.length; i++) {
            const tickersObj = events[i];
            const tickers = this.safeValue(tickersObj, 'tickers', []);
            for (let j = 0; j < tickers.length; j++) {
                const ticker = tickers[j];
                const result = this.parseWsTicker(ticker);
                const symbol = result['symbol'];
                this.tickers[symbol] = result;
                const wsMarketId = this.safeString(ticker, 'product_id');
                const messageHash = channel + '::' + wsMarketId;
                newTickers.push(result);
                client.resolve(result, messageHash);
                if (messageHash.endsWith('USD')) {
                    client.resolve(result, messageHash + 'C'); // sometimes we subscribe to BTC/USDC and coinbase returns BTC/USD
                }
            }
        }
        const messageHashes = this.findMessageHashes(client, 'ticker_batch::');
        for (let i = 0; i < messageHashes.length; i++) {
            const messageHash = messageHashes[i];
            const parts = messageHash.split('::');
            const symbolsString = parts[1];
            const symbols = symbolsString.split(',');
            const tickers = this.filterByArray(newTickers, 'symbol', symbols);
            if (!this.isEmpty(tickers)) {
                client.resolve(tickers, messageHash);
                if (messageHash.endsWith('USD')) {
                    client.resolve(tickers, messageHash + 'C'); // sometimes we subscribe to BTC/USDC and coinbase returns BTC/USD
                }
            }
        }
        return message;
    }
    parseWsTicker(ticker, market = undefined) {
        //
        //     {
        //         "type": "ticker",
        //         "product_id": "DOGE-USD",
        //         "price": "0.08212",
        //         "volume_24_h": "242556423.3",
        //         "low_24_h": "0.07989",
        //         "high_24_h": "0.08308",
        //         "low_52_w": "0.04908",
        //         "high_52_w": "0.1801",
        //         "price_percent_chg_24_h": "0.50177456859626"
        //     }
        //
        const marketId = this.safeString(ticker, 'product_id');
        const timestamp = undefined;
        const last = this.safeNumber(ticker, 'price');
        return this.safeTicker({
            'info': ticker,
            'symbol': this.safeSymbol(marketId, market, '-'),
            'timestamp': timestamp,
            'datetime': this.iso8601(timestamp),
            'high': this.safeString(ticker, 'high_24_h'),
            'low': this.safeString(ticker, 'low_24_h'),
            'bid': undefined,
            'bidVolume': undefined,
            'ask': undefined,
            'askVolume': undefined,
            'vwap': undefined,
            'open': undefined,
            'close': last,
            'last': last,
            'previousClose': undefined,
            'change': undefined,
            'percentage': this.safeString(ticker, 'price_percent_chg_24_h'),
            'average': undefined,
            'baseVolume': this.safeString(ticker, 'volume_24_h'),
            'quoteVolume': undefined,
        });
    }
    async watchTrades(symbol, since = undefined, limit = undefined, params = {}) {
        /**
         * @method
         * @name coinbasepro#watchTrades
         * @description get the list of most recent trades for a particular symbol
         * @see https://docs.cloud.coinbase.com/advanced-trade-api/docs/ws-channels#market-trades-channel
         * @param {string} symbol unified symbol of the market to fetch trades for
         * @param {int} [since] timestamp in ms of the earliest trade to fetch
         * @param {int} [limit] the maximum amount of trades to fetch
         * @param {object} [params] extra parameters specific to the exchange API endpoint
         * @returns {object[]} a list of [trade structures]{@link https://docs.ccxt.com/#/?id=public-trades}
         */
        await this.loadMarkets();
        symbol = this.symbol(symbol);
        const name = 'market_trades';
        const trades = await this.subscribe(name, symbol, params);
        if (this.newUpdates) {
            limit = trades.getLimit(symbol, limit);
        }
        return this.filterBySinceLimit(trades, since, limit, 'timestamp', true);
    }
    async watchOrders(symbol = undefined, since = undefined, limit = undefined, params = {}) {
        /**
         * @method
         * @name coinbasepro#watchOrders
         * @description watches information on multiple orders made by the user
         * @see https://docs.cloud.coinbase.com/advanced-trade-api/docs/ws-channels#user-channel
         * @param {string} [symbol] unified market symbol of the market orders were made in
         * @param {int} [since] the earliest time in ms to fetch orders for
         * @param {int} [limit] the maximum number of order structures to retrieve
         * @param {object} [params] extra parameters specific to the exchange API endpoint
         * @returns {object[]} a list of [order structures]{@link https://docs.ccxt.com/#/?id=order-structure}
         */
        await this.loadMarkets();
        const name = 'user';
        const orders = await this.subscribe(name, symbol, params);
        if (this.newUpdates) {
            limit = orders.getLimit(symbol, limit);
        }
        return this.filterBySinceLimit(orders, since, limit, 'timestamp', true);
    }
    async watchOrderBook(symbol, limit = undefined, params = {}) {
        /**
         * @method
         * @name coinbasepro#watchOrderBook
         * @description watches information on open orders with bid (buy) and ask (sell) prices, volumes and other data
         * @see https://docs.cloud.coinbase.com/advanced-trade-api/docs/ws-channels#level2-channel
         * @param {string} symbol unified symbol of the market to fetch the order book for
         * @param {int} [limit] the maximum amount of order book entries to return
         * @param {object} [params] extra parameters specific to the exchange API endpoint
         * @returns {object} A dictionary of [order book structures]{@link https://docs.ccxt.com/#/?id=order-book-structure} indexed by market symbols
         */
        await this.loadMarkets();
        const name = 'level2';
        const market = this.market(symbol);
        symbol = market['symbol'];
        const orderbook = await this.subscribe(name, symbol, params);
        return orderbook.limit();
    }
    handleTrade(client, message) {
        //
        //    {
        //        "channel": "market_trades",
        //        "client_id": "",
        //        "timestamp": "2023-02-09T20:19:35.39625135Z",
        //        "sequence_num": 0,
        //        "events": [
        //            {
        //                "type": "snapshot",
        //                "trades": [
        //                    {
        //                        "trade_id": "000000000",
        //                        "product_id": "ETH-USD",
        //                        "price": "1260.01",
        //                        "size": "0.3",
        //                        "side": "BUY",
        //                        "time": "2019-08-14T20:42:27.265Z",
        //                    }
        //                ]
        //            }
        //        ]
        //    }
        //
        const events = this.safeValue(message, 'events');
        const event = this.safeValue(events, 0);
        const trades = this.safeValue(event, 'trades');
        const trade = this.safeValue(trades, 0);
        const marketId = this.safeString(trade, 'product_id');
        const messageHash = 'market_trades::' + marketId;
        const symbol = this.safeSymbol(marketId);
        let tradesArray = this.safeValue(this.trades, symbol);
        if (tradesArray === undefined) {
            const tradesLimit = this.safeInteger(this.options, 'tradesLimit', 1000);
            tradesArray = new Cache.ArrayCacheBySymbolById(tradesLimit);
            this.trades[symbol] = tradesArray;
        }
        for (let i = 0; i < events.length; i++) {
            const currentEvent = events[i];
            const currentTrades = this.safeValue(currentEvent, 'trades');
            for (let j = 0; j < currentTrades.length; j++) {
                const item = currentTrades[i];
                tradesArray.append(this.parseTrade(item));
            }
        }
        client.resolve(tradesArray, messageHash);
        if (marketId.endsWith('USD')) {
            client.resolve(tradesArray, messageHash + 'C'); // sometimes we subscribe to BTC/USDC and coinbase returns BTC/USD
        }
        return message;
    }
    handleOrder(client, message) {
        //
        //    {
        //        "channel": "user",
        //        "client_id": "",
        //        "timestamp": "2023-02-09T20:33:57.609931463Z",
        //        "sequence_num": 0,
        //        "events": [
        //            {
        //                "type": "snapshot",
        //                "orders": [
        //                    {
        //                        "order_id": "XXX",
        //                        "client_order_id": "YYY",
        //                        "cumulative_quantity": "0",
        //                        "leaves_quantity": "0.000994",
        //                        "avg_price": "0",
        //                        "total_fees": "0",
        //                        "status": "OPEN",
        //                        "product_id": "BTC-USD",
        //                        "creation_time": "2022-12-07T19:42:18.719312Z",
        //                        "order_side": "BUY",
        //                        "order_type": "Limit"
        //                    },
        //                ]
        //            }
        //        ]
        //    }
        //
        const events = this.safeValue(message, 'events');
        const marketIds = [];
        if (this.orders === undefined) {
            const limit = this.safeInteger(this.options, 'ordersLimit', 1000);
            this.orders = new Cache.ArrayCacheBySymbolById(limit);
        }
        for (let i = 0; i < events.length; i++) {
            const event = events[i];
            const responseOrders = this.safeValue(event, 'orders');
            for (let j = 0; j < responseOrders.length; j++) {
                const responseOrder = responseOrders[j];
                const parsed = this.parseWsOrder(responseOrder);
                const cachedOrders = this.orders;
                const marketId = this.safeString(responseOrder, 'product_id');
                if (!(marketId in marketIds)) {
                    marketIds.push(marketId);
                }
                cachedOrders.append(parsed);
            }
        }
        for (let i = 0; i < marketIds.length; i++) {
            const marketId = marketIds[i];
            const messageHash = 'user::' + marketId;
            client.resolve(this.orders, messageHash);
            if (messageHash.endsWith('USD')) {
                client.resolve(this.orders, messageHash + 'C'); // sometimes we subscribe to BTC/USDC and coinbase returns BTC/USD
            }
        }
        client.resolve(this.orders, 'user');
        return message;
    }
    parseWsOrder(order, market = undefined) {
        //
        //    {
        //        "order_id": "XXX",
        //        "client_order_id": "YYY",
        //        "cumulative_quantity": "0",
        //        "leaves_quantity": "0.000994",
        //        "avg_price": "0",
        //        "total_fees": "0",
        //        "status": "OPEN",
        //        "product_id": "BTC-USD",
        //        "creation_time": "2022-12-07T19:42:18.719312Z",
        //        "order_side": "BUY",
        //        "order_type": "Limit"
        //    }
        //
        const id = this.safeString(order, 'order_id');
        const clientOrderId = this.safeString(order, 'client_order_id');
        const marketId = this.safeString(order, 'product_id');
        const datetime = this.safeString(order, 'time');
        market = this.safeMarket(marketId, market);
        return this.safeOrder({
            'info': order,
            'symbol': this.safeString(market, 'symbol'),
            'id': id,
            'clientOrderId': clientOrderId,
            'timestamp': this.parse8601(datetime),
            'datetime': datetime,
            'lastTradeTimestamp': undefined,
            'type': this.safeString(order, 'order_type'),
            'timeInForce': undefined,
            'postOnly': undefined,
            'side': this.safeString(order, 'side'),
            'price': undefined,
            'stopPrice': undefined,
            'triggerPrice': undefined,
            'amount': undefined,
            'cost': undefined,
            'average': this.safeString(order, 'avg_price'),
            'filled': this.safeString(order, 'cumulative_quantity'),
            'remaining': this.safeString(order, 'leaves_quantity'),
            'status': this.safeStringLower(order, 'status'),
            'fee': {
                'amount': this.safeString(order, 'total_fees'),
                'currency': this.safeString(market, 'quote'),
            },
            'trades': undefined,
        });
    }
    handleOrderBookHelper(orderbook, updates) {
        for (let i = 0; i < updates.length; i++) {
            const trade = updates[i];
            const sideId = this.safeString(trade, 'side');
            const side = this.safeString(this.options['sides'], sideId);
            const price = this.safeNumber(trade, 'price_level');
            const amount = this.safeNumber(trade, 'new_quantity');
            const orderbookSide = orderbook[side];
            orderbookSide.store(price, amount);
        }
    }
    handleOrderBook(client, message) {
        //
        //    {
        //        "channel": "l2_data",
        //        "client_id": "",
        //        "timestamp": "2023-02-09T20:32:50.714964855Z",
        //        "sequence_num": 0,
        //        "events": [
        //            {
        //                "type": "snapshot",
        //                "product_id": "BTC-USD",
        //                "updates": [
        //                    {
        //                        "side": "bid",
        //                        "event_time": "1970-01-01T00:00:00Z",
        //                        "price_level": "21921.73",
        //                        "new_quantity": "0.06317902"
        //                    },
        //                    {
        //                        "side": "bid",
        //                        "event_time": "1970-01-01T00:00:00Z",
        //                        "price_level": "21921.3",
        //                        "new_quantity": "0.02"
        //                    },
        //                ]
        //            }
        //        ]
        //    }
        //
        const events = this.safeValue(message, 'events');
        const datetime = this.safeString(message, 'timestamp');
        for (let i = 0; i < events.length; i++) {
            const event = events[i];
            const updates = this.safeValue(event, 'updates', []);
            const marketId = this.safeString(event, 'product_id');
            const messageHash = 'level2::' + marketId;
            const subscription = this.safeValue(client.subscriptions, messageHash, {});
            const limit = this.safeInteger(subscription, 'limit');
            const symbol = this.safeSymbol(marketId);
            const type = this.safeString(event, 'type');
            if (type === 'snapshot') {
                this.orderbooks[symbol] = this.orderBook({}, limit);
                const orderbook = this.orderbooks[symbol];
                this.handleOrderBookHelper(orderbook, updates);
                orderbook['timestamp'] = undefined;
                orderbook['datetime'] = undefined;
                orderbook['symbol'] = symbol;
                client.resolve(orderbook, messageHash);
                if (messageHash.endsWith('USD')) {
                    client.resolve(orderbook, messageHash + 'C'); // sometimes we subscribe to BTC/USDC and coinbase returns BTC/USD
                }
            }
            else if (type === 'update') {
                const orderbook = this.orderbooks[symbol];
                this.handleOrderBookHelper(orderbook, updates);
                orderbook['datetime'] = datetime;
                orderbook['timestamp'] = this.parse8601(datetime);
                orderbook['symbol'] = symbol;
                client.resolve(orderbook, messageHash);
                if (messageHash.endsWith('USD')) {
                    client.resolve(orderbook, messageHash + 'C'); // sometimes we subscribe to BTC/USDC and coinbase returns BTC/USD
                }
            }
        }
        return message;
    }
    handleSubscriptionStatus(client, message) {
        //
        //     {
        //         "type": "subscriptions",
        //         "channels": [
        //             {
        //                 "name": "level2",
        //                 "product_ids": [ "ETH-BTC" ]
        //             }
        //         ]
        //     }
        //
        return message;
    }
    handleMessage(client, message) {
        const channel = this.safeString(message, 'channel');
        const methods = {
            'subscriptions': this.handleSubscriptionStatus,
            'ticker': this.handleTickers,
            'ticker_batch': this.handleTickers,
            'market_trades': this.handleTrade,
            'user': this.handleOrder,
            'l2_data': this.handleOrderBook,
        };
        const type = this.safeString(message, 'type');
        if (type === 'error') {
            const errorMessage = this.safeString(message, 'message');
            throw new errors.ExchangeError(errorMessage);
        }
        const method = this.safeValue(methods, channel);
        method.call(this, client, message);
    }
}

module.exports = coinbase;
