<?php

namespace App\Repositories;

use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Storage;

class WZAndroid0022Repository
{
    private function getToken()
    {
        return Storage::get('wzandroid0022/token.txt');
    }

    public function getTrackingInOut($url, $from, $to, $day, $checkType, $userType, $version, $checkCompare)
    {
        $token = $this->getToken();

        $client = new Client();
        $res = $client->post($url. 'api/analytic/GetTrackingInOut/', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json', // set the Content-Type header to JSON
            ],
            'json' => [
                'Data' => [
                    'From' => $from,
                    'To' => $to,
                    'Day' => $day,
                    'CheckType' => $checkType,
                    'UserType' => $userType,
                    'CheckCompare' => $checkCompare,
                    'DataVersion' => $version
                ]
            ],
        ]);

        $data = json_decode($res->getBody()->getContents());

        return $data;
    }
}
