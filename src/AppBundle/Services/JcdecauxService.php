<?php

namespace AppBundle\Services;

use GuzzleHttp\Client as GuzzleClient;
use Kreait\Firebase\Configuration;
use Kreait\Firebase\Firebase;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class JcdecauxService {

    public $api_key;
    public $base_url;

    public function __construct($api_key, $base_url)
    {
        $this->client = new GuzzleClient();
        $this->base_url = $base_url;
        $this->api_key = $api_key;
    }

    public function getData($contract, $id)
    {

        $data = $this->client->get($this->base_url . $id, array(
            'query' => [
                'contract' => $contract,
                'apiKey' => $this->api_key,
            ]
        ));

        return json_decode($data->getBody()->getContents());
    }

    public function pushToFirebase($data)
    {
        $logger = new Logger('firebase');
        $logger->pushHandler(new StreamHandler('php://stdout'));

        $configuration = new Configuration();
        $configuration->setLogger($logger);

        $firebase = new Firebase('https://velibmap.firebaseio.com', $configuration);

        $station = $firebase->getReference('data/' . $data->contract_name . '/' . $data->number);

        $timestamp = substr($data->last_update, 0, -3);

        $station_now = $station->getReference(date('N', $timestamp) .'/'. date('H', $timestamp) .'/'. $timestamp);

        $station_now->set([
            'available_bike_stands' => $data->available_bike_stands,
            'available_bikes' => $data->available_bikes,
            'timestamp' => date('Y-m-d H:i:s',$timestamp)
        ]);

        return $station_now->getData();
    }

    public function retrieveFromFirebase($contract, $id, $day, $hour)
    {
        $logger = new Logger('firebase');
        $logger->pushHandler(new StreamHandler('php://stdout'));

        $configuration = new Configuration();
        $configuration->setLogger($logger);

        $firebase = new Firebase('https://velibmap.firebaseio.com', $configuration);

        $station = $firebase->getReference('data/'.$contract.'/'.$id.'/'.$day.'/'.$hour);

        $data = $station->getData();

        $available_bike_stands = 0;
        $available_bikes = 0;
        $count = 0;

        foreach($data as $datum){
            $available_bike_stands += $datum['available_bike_stands'];
            $available_bikes += $datum['available_bikes'];
            $count++;
        }

        $resume = $firebase->getReference('resume/'.$contract.'/'.$id.'/'.$day.'/'.$hour);

        $resume->set([
            'contract' => $contract,
            'day' => $day,
            'hour' => $hour,
            'available_bike_stands' => round($available_bike_stands/$count),
            'available_bikes' => round($available_bikes/$count),
        ]);

        return $resume->getData();
    }
}
