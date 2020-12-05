<?php
include './constants.php';
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

echo '<div class="currencies_info_main_block">';
$USD = $client->getHistoryOrderBook("BBG0013HGFT4", 1);
echo '<div class="currencies_info">
        <h1>USD/RUB</h1>
        <h2>' . ($USD->getLastPrice()) . '</h2>
    </div>
';

$EUR = $client->getHistoryOrderBook("BBG0013HJJ31", 1);
echo '<div class="currencies_info">
        <h1>EUR/RUB</h1>
        <h2>' . ($EUR->getLastPrice()) . '</h2>
    </div>
';
echo '</div>';

$port = $client->getPortfolio();
$instruments = $port->getAllinstruments();
$profit = 0;

echo '<table class="stocks">
        <tr>
            <th>INSTRUMENT</th>
            <th>TYPE</th>
            <th>PROFIT</th>
        </tr>
';
foreach ($instruments as $instrument) {

    $value = $instrument->getExpectedYieldValue();
    $currency = $instrument->getExpectedYieldCurrency();

    $class_profit = ($value > 0) ? 'positive' : 'negative';

    echo '
        <tr>
            <td>' . $instrument->getTicker() . '</td>
            <td>' . $instrument->getInstrumentType() . '</td>
            <td class='.$class_profit.'>' . $value . ' '.$currency.'</td>
        </tr>
    ';


    if ($currency == 'USD') {
        $profit = $profit + ($value * ($USD->getLastPrice()));
    } else {
        $profit = $profit + $value;
    }
}
echo '</table>';

$class_profit = ($profit > 0) ? 'positive' : 'negative';
echo '<h1>PROFIT: <span class='.$class_profit.'>' . $profit . '</span> RUB</h1>';
