<?php
header('Content-Type: application/json');

include_once '../vendor/autoload.php';

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
use Scheb\YahooFinanceApi\ApiClientFactory;

use function GuzzleHttp\json_encode;

$clientYahoo = ApiClientFactory::createApiClient();

$client = new TIClient($TINKOFF_API_KEY, TISiteEnum::EXCHANGE);


function getFormData($method)
{
    // GET или POST: данные возвращаем как есть
    if ($method === 'GET') return $_GET;
    if ($method === 'POST') return $_POST;
    // PUT, PATCH или DELETE
    $data = array();
    $exploded = explode('&', file_get_contents('php://input'));
    foreach ($exploded as $pair) {
        $item = explode('=', $pair);
        if (count($item) == 2) {
            $data[urldecode($item[0])] = urldecode($item[1]);
        }
    }
    return $data;
}

$method = $_SERVER['REQUEST_METHOD'];
$formData = getFormData($method);

if (array_key_exists('s', $formData)) {

    $stock = $formData['s'];

    if (array_key_exists('f', $formData)) {
        $func = $formData['f'];

        if ($func == 'getLastPrice') {
            $instr = $client->getInstrumentByTicker($stock);
            $figi = $instr->getFigi();
            $book = $client->getHistoryOrderBook($figi, 1); 
            
            $response_array = [];
            $response_array['price'] = $book -> getLastPrice();
            
            echo json_encode($response_array);
        }
    } else {
        $from = new \DateTime();
        $from->sub(new \DateInterval("P90D"));
        $to = new \DateTime();

        $instr = $client->getInstrumentByTicker($stock);
        $figi = $instr->getFigi();

        $candles = $client->getHistoryCandles($figi, $from, $to, TIIntervalEnum::DAY);
        $response_array = [];

        for ($i = 0; $i < count($candles); $i++) {
            $a = [];
            $a['open'] = $candles[$i]->getOpen();
            $a['close'] = $candles[$i]->getClose();
            $a['volume'] = $candles[$i]->getVolume();
            $a['time'] = $candles[$i]->getTime();
            $response_array[] = $a;
        }

        echo json_encode($response_array);
    }
}
