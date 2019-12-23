<?php
namespace ccxtpro;
include_once __DIR__ . '/../../vendor/autoload.php';
// ----------------------------------------------------------------------------

// PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
// https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

// -----------------------------------------------------------------------------

function equals($a, $b) {
    return json_encode($a) === json_encode($b);
}

// --------------------------------------------------------------------------------------------------------------------

$orderBookInput = array(
    'bids' => array( array( 10.0, 10 ), array( 9.1, 11 ), array( 8.2, 12 ), array( 7.3, 13 ), array( 6.4, 14 ), array( 4.5, 13 ), array( 4.5, 0 ) ),
    'asks' => array( array( 16.6, 10 ), array( 15.5, 11 ), array( 14.4, 12 ), array( 13.3, 13 ), array( 12.2, 14 ), array( 11.1, 13 ) ),
    'timestamp' => 1574827239000,
    'nonce' => 69,
);

$orderBookTarget = array(
    'bids' => array( array( 10.0, 10 ), array( 9.1, 11 ), array( 8.2, 12 ), array( 7.3, 13 ), array( 6.4, 14 ) ),
    'asks' => array( array( 11.1, 13 ), array( 12.2, 14 ), array( 13.3, 13 ), array( 14.4, 12 ), array( 15.5, 11 ), array( 16.6, 10 ) ),
    'timestamp' => 1574827239000,
    'datetime' => '2019-11-27T04:00:39.000Z',
    'nonce' => 69,
);

$limitedOrderBookTarget = array(
    'bids' => array( array( 10.0, 10 ), array( 9.1, 11 ), array( 8.2, 12 ), array( 7.3, 13 ), array( 6.4, 14 ) ),
    'asks' => array( array( 11.1, 13 ), array( 12.2, 14 ), array( 13.3, 13 ), array( 14.4, 12 ), array( 15.5, 11 ) ),
    'timestamp' => 1574827239000,
    'datetime' => '2019-11-27T04:00:39.000Z',
    'nonce' => 69,
);

$indexedOrderBookInput = array(
    'bids' => array( array( 10.0, 10, '1234' ), array( 9.1, 11, '1235' ), array( 8.2, 12, '1236' ), array( 7.3, 13, '1237' ), array( 6.4, 14, '1238' ), array( 4.5, 13, '1239' ) ),
    'asks' => array( array( 16.6, 10, '1240' ), array( 15.5, 11, '1241' ), array( 14.4, 12, '1242' ), array( 13.3, 13, '1243' ), array( 12.2, 14, '1244' ), array( 11.1, 13, '1244' ) ),
    'timestamp' => 1574827239000,
    'nonce' => 69,
);

$indexedOrderBookTarget = array(
    'bids' => array( array( 10.0, 10, '1234' ), array( 9.1, 11, '1235' ), array( 8.2, 12, '1236' ), array( 7.3, 13, '1237' ), array( 6.4, 14, '1238' ), array( 4.5, 13, '1239' ) ),
    'asks' => array( array( 11.1, 13, '1244' ), array( 13.3, 13, '1243' ), array( 14.4, 12, '1242' ), array( 15.5, 11, '1241' ), array( 16.6, 10, '1240' ) ),
    'timestamp' => 1574827239000,
    'datetime' => '2019-11-27T04:00:39.000Z',
    'nonce' => 69,
);

$countedOrderBookInput = array(
    'bids' => array( array( 10.0, 10, 1 ), array( 9.1, 11, 1 ), array( 8.2, 12, 1 ), array( 7.3, 13, 1 ), array( 7.3, 0, 1 ), array( 6.4, 14, 5 ), array( 4.5, 13, 5 ), array( 4.5, 13, 0 ) ),
    'asks' => array( array( 16.6, 10, 1 ), array( 15.5, 11, 1 ), array( 14.4, 12, 1 ), array( 13.3, 13, 3 ), array( 12.2, 14, 3 ), array( 11.1, 13, 3 ), array( 11.1, 13, 12 ) ),
    'timestamp' => 1574827239000,
    'nonce' => 69,
);

$countedOrderBookTarget = array(
    'bids' => array( array( 10.0, 10, 1 ), array( 9.1, 11, 1 ), array( 8.2, 12, 1 ), array( 6.4, 14, 5 ) ),
    'asks' => array( array( 11.1, 13, 12 ), array( 12.2, 14, 3 ), array( 13.3, 13, 3 ), array( 14.4, 12, 1 ), array( 15.5, 11, 1 ), array( 16.6, 10, 1 ) ),
    'timestamp' => 1574827239000,
    'datetime' => '2019-11-27T04:00:39.000Z',
    'nonce' => 69,
);

$incrementalOrderBookInput = array(
    'bids' => array( array( 10.0, 1 ), array( 10.0, 2 ), array( 9.1, 0 ), array( 8.2, 1 ), array( 7.3, 1 ), array( 6.4, 1 ) ),
    'asks' => array( array( 11.1, 5 ), array( 11.1, -6 ), array( 11.1, 2 ), array( 12.2, 10 ), array( 12.2, -9.875 ), array( 12.2, 0 ), array( 13.3, 3 ), array( 14.4, 4 ) ),
    'timestamp' => 1574827239000,
    'nonce' => 69,
);

$incremetalOrderBookTarget = array(
    'bids' => array( array( 10.0, 3 ), array( 8.2, 1 ), array( 7.3, 1 ), array( 6.4, 1 ) ),
    'asks' => array( array( 11.1, 2 ), array( 12.2, 0.125 ), array( 13.3, 3 ), array( 14.4, 4 ) ),
    'timestamp' => 1574827239000,
    'datetime' => '2019-11-27T04:00:39.000Z',
    'nonce' => 69,
);

$limitedIndexedOrderBookTarget = array(
    'bids' => array( array( 10.0, 10, '1234' ), array( 9.1, 11, '1235' ), array( 8.2, 12, '1236' ), array( 7.3, 13, '1237' ), array( 6.4, 14, '1238' ) ),
    'asks' => array( array( 11.1, 13, '1244' ), array( 13.3, 13, '1243' ), array( 14.4, 12, '1242' ), array( 15.5, 11, '1241' ), array( 16.6, 10, '1240' ) ),
    'timestamp' => 1574827239000,
    'datetime' => '2019-11-27T04:00:39.000Z',
    'nonce' => 69,
);

// --------------------------------------------------------------------------------------------------------------------

$orderBook = new OrderBook ($orderBookInput);
$orderBook->limit ();
$limitedOrderBook = new LimitedOrderBook ($orderBookInput, 5);
$limitedOrderBook->limit ();
$indexedOrderBook = new IndexedOrderBook ($indexedOrderBookInput);
$indexedOrderBook->limit ();
$countedOrderBook = new CountedOrderBook ($countedOrderBookInput);
$countedOrderBook->limit ();
$incrementalOrderBook = new IncrementalOrderBook ($incrementalOrderBookInput);
$incrementalOrderBook->limit ();
$limitedIndexedOrderBook = new LimitedIndexedOrderBook ($indexedOrderBookInput, 5);
$limitedIndexedOrderBook->limit ();

assert (equals ($orderBook, $orderBookTarget));
assert (equals ($limitedOrderBook, $limitedOrderBookTarget));
assert (equals ($indexedOrderBook, $indexedOrderBookTarget));
assert (equals ($countedOrderBook, $countedOrderBookTarget));
assert (equals ($incrementalOrderBook, $incremetalOrderBookTarget));
assert (equals ($limitedIndexedOrderBook, $limitedIndexedOrderBookTarget));
