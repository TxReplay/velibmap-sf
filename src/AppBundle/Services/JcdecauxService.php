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

    public function getDataByContractAndId($contract, $id)
    {
        $data = $this->client->get($this->base_url . $id, [
            'query' => [
                'contract' => $contract,
                'apiKey' => $this->api_key,
            ]
        ]);

        return json_decode($data->getBody()->getContents());
    }

    public function getDataByContract($contract)
    {
        $data = $this->client->get($this->base_url, [
            'query' => [
                'contract' => $contract,
                'apiKey' => $this->api_key,
            ]
        ]);

        return json_decode($data->getBody()->getContents());
    }

    public function pushToFirebase($data)
    {
        $logger = new Logger('firebase');
        $logger->pushHandler(new StreamHandler('php://stdout'));

        $configuration = new Configuration();
        $configuration->setLogger($logger);

        if (count($data) == 1) {
            $firebase = new Firebase('https://velibmap.firebaseio.com', $configuration);
            $station = $firebase->getReference('data/'.$data->contract_name.'/'.$data->number);

            $timestamp = substr($data->last_update, 0, -3);

            $station_now = $station->getReference(date('N', $timestamp).'/'.date('H', $timestamp).'/'.$timestamp);

            $station_now->set([
                'available_bike_stands' => $data->available_bike_stands,
                'available_bikes' => $data->available_bikes,
                'timestamp' => date('Y-m-d H:i:s', $timestamp)
            ]);

            $station_hour = $station->getReference(date('N', $timestamp).'/'.date('H', $timestamp));

            $available_bike_stands = $available_bikes = $count = 0;

            foreach($station_hour->getData() as $datum){
                $available_bike_stands += $datum['available_bike_stands'];
                $available_bikes += $datum['available_bikes'];
                $count++;
            }

            $resume = $firebase->getReference('resume/'.$data->contract_name.'/'.$data->number.'/'.date('N', $timestamp).'/'.date('H', $timestamp));

            $resume->set([
                'contract' => $data->contract_name,
                'name' => $data->name,
                'position' => [
                    'lat' => 48.8403981721562,
                    'lng' => 2.40922942058058
                ],
                'day' => date('N', $timestamp),
                'hour' => date('H', $timestamp),
                'bike_stands' => 42,
                'available_bike_stands' => round($available_bike_stands/$count),
                'available_bikes' => round($available_bikes/$count),
            ]);
        } else {
            foreach ($data as $single_data) {
                $firebase = new Firebase('https://velibmap.firebaseio.com', $configuration);
                $station = $firebase->getReference('data/'.$single_data->contract_name.'/'.$single_data->number);

                $timestamp = substr($single_data->last_update, 0, -3);

                $station_now = $station->getReference(date('N', $timestamp).'/'.date('H', $timestamp).'/'.$timestamp);

                $station_now->set([
                    'available_bike_stands' => $single_data->available_bike_stands,
                    'available_bikes' => $single_data->available_bikes,
                    'timestamp' => date('Y-m-d H:i:s', $timestamp)
                ]);

                $station_hour = $station->getReference(date('N', $timestamp).'/'.date('H', $timestamp));

                $available_bike_stands = $available_bikes = $count = 0;

                foreach($station_hour->getData() as $datum){
                    $available_bike_stands += $datum['available_bike_stands'];
                    $available_bikes += $datum['available_bikes'];
                    $count++;
                }

                $resume = $firebase->getReference('resume/'.$single_data->contract_name.'/'.$single_data->number.'/'.date('N', $timestamp).'/'.date('H', $timestamp));

                $resume->set([
                    'contract' => $single_data->contract_name,
                    'name' => $single_data->name,
                    'position' => [
                        'lat' => 48.8403981721562,
                        'lng' => 2.40922942058058
                    ],
                    'day' => date('N', $timestamp),
                    'hour' => date('H', $timestamp),
                    'bike_stands' => 42,
                    'available_bike_stands' => round($available_bike_stands/$count),
                    'available_bikes' => round($available_bikes/$count),
                ]);
            }
        }

        return [
            'data' => $station_now->getData(),
            'resume' => $resume->getData()
        ];
    }

    public function retrieveFromFirebase($contract, $id, $day, $hour)
    {
        $logger = new Logger('firebase');
        $logger->pushHandler(new StreamHandler('php://stdout'));

        $configuration = new Configuration();
        $configuration->setLogger($logger);

        $firebase = new Firebase('https://velibmap.firebaseio.com', $configuration);
        $resume = $firebase->getReference('resume/'.$contract.'/'.$id.'/'.$day.'/'.$hour);

        return $resume->getData();
    }
}
