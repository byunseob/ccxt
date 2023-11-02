import os
import sys

root = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
sys.path.append(root + '/python')

# ----------------------------------------------------------------------------

# PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
# https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

# ----------------------------------------------------------------------------
import asyncio
import ccxt.pro as ccxt  # noqa: E402


# AUTO-TRANSPILE #
async def example():
    binance = ccxt.binance({})
    symbol = ['BTC/USDT', 'ETH/USDT', 'DOGE/USDT']
    while True:
        orderbook = await binance.watch_order_book_for_symbols(symbol)
        print(orderbook['symbol'], orderbook['asks'][0], orderbook['bids'][0])

    await binance.close()


asyncio.run(example())
