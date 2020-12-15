<?php
error_reporting(E_ERROR);
include_once './vendor/autoload.php';

use \jamesRUS52\TinkoffInvest\TIClient;
use \jamesRUS52\TinkoffInvest\TISiteEnum;
use \jamesRUS52\TinkoffInvest\TICurrencyEnum;
use \jamesRUS52\TinkoffInvest\TIInstrument;
use \jamesRUS52\TinkoffInvest\TIPortfolio;
use \jamesRUS52\TinkoffInvest\TIOperationEnum;
use \jamesRUS52\TinkoffInvest\TIIntervalEnum;
use \jamesRUS52\TinkoffInvest\TICandleIntervalEnum;
use \jamesRUS52\TinkoffInvest\TICandle;
use \jamesRUS52\TinkoffInvest\TIOrderBook;
use \jamesRUS52\TinkoffInvest\TIInstrumentInfo;


$client = new TIClient($TINKOFF_API_KEY, TISiteEnum::EXCHANGE);


$stockes = $client->getStocks();
$count_stocks = count($stockes);
echo '<table class="stocks">
        <tr>
            <th>STOCK</th>
            <th>PRICE</th>
            <th>TP</th>
            <th>SL</th>
            <th>DON</th>
        </tr>
    ';
try {
    foreach ($stockes as $stock) {
        $count_stocks--;
        $figi = $stock->getFigi();
        $stock_name = $stock->getTicker();

        $from = new \DateTime();
        $from->sub(new \DateInterval("P99D"));
        $to = new \DateTime();

        $candles = $client->getHistoryCandles($figi, $from, $to, TIIntervalEnum::DAY);
        $book = $client->getHistoryOrderBook($figi, 1);

        $array = [];
        $array_time = [];
        foreach ($candles as $candle) {
            $array[] = $candle->getClose();
            $array_time[] = $candle->getTime();
        }

        $count_array = count($array);

        $array_20 = array_slice($array, $count_array - 20, $count_array);
        $array_60 = array_slice($array, $count_array - 60, $count_array);
        $array_30 = array_slice($array, $count_array - 30, $count_array);

        $min_20 = min($array_20);
        $max_20 = max($array_20);



        $min_60 = min($array_60);
        $max_60 = max($array_60);

        $current = $book->getLastPrice();
        $max_20_percentage = ($current / $max_20) * 100 - 100;
        $max_60_percentage = ($current / $max_60) * 100 - 100;

        $percent_profit = 0.03;
        $take_profit = $current + ($current * $percent_profit);
        $stop_loss = min($array_30);

        

        if (
            ($max_20_percentage > -0.2 && $max_20_percentage < 0.4)
        ) {

            $class = ($max_20_percentage > 0) ? 'warning' : '';

            echo '<tr data-stock="' . $stock_name . '" onclick=onClickStock("' . $stock_name . '") class="' . $class . '">
                    <td>' . $stock_name . '</td>
                    <td>' . $current . '</td>
                    <td>' . $take_profit . '</td>
                    <td>' . $stop_loss . '</td>
                    <td>' . round($max_20_percentage, 4) . '%</td>
                </tr>';
        }
        sleep(0.5);
    }
    echo '</table>';
} catch (Exception $e) {
    echo '</table>';
}

?>
