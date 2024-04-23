import os
import sys

root = os.path.dirname(os.path.dirname(os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))))
sys.path.append(root)

# ----------------------------------------------------------------------------

# PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
# https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

# ----------------------------------------------------------------------------
# -*- coding: utf-8 -*-

from ccxt.test.base import test_trade  # noqa E402
from ccxt.test.base import test_shared_methods  # noqa E402

async def test_watch_trades_for_symbols(exchange, skipped_properties, symbols):
    method = 'watchTradesForSymbols'
    now = exchange.milliseconds()
    ends = now + 15000
    while now < ends:
        response = None
        try:
            response = await exchange.watch_trades_for_symbols(symbols)
        except Exception as e:
            if not test_shared_methods.is_temporary_failure(e):
                raise e
            now = exchange.milliseconds()
            continue
        assert isinstance(response, list), exchange.id + ' ' + method + ' ' + exchange.json(symbols) + ' must return an array. ' + exchange.json(response)
        now = exchange.milliseconds()
        symbol = None
        for i in range(0, len(response)):
            trade = response[i]
            symbol = trade['symbol']
            test_trade(exchange, skipped_properties, method, trade, symbol, now)
            test_shared_methods.assert_in_array(exchange, skipped_properties, method, trade, 'symbol', symbols)
        if not ('timestamp' in skipped_properties):
            test_shared_methods.assert_timestamp_order(exchange, method, symbol, response)
