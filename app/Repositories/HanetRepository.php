<?php

namespace App\Repositories;

use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class HanetRepository
{
    protected $url_auth = 'https://oauth.hanet.com';
    protected $url = 'https://partner.hanet.ai';

    public function getCheckinByPlaceIdInTimestamp($accessToken, $placeId, $devicesArr, $from, $to, $aliasID)
    {
        $client = new Client();
        $res = $client->post($this->url. '/person/getCheckinByPlaceIdInTimestamp', [
            'form_params' => [
                'token' => $accessToken,
                'placeID' => $placeId,
                'type' => 0,
                'devices'=> implode(',', $devicesArr),
                'from'=> $from,
                'to'=> $to,
                'aliasID'=> $aliasID,
            ]
        ]);

        $data = json_decode($res->getBody()->getContents());

        return $data;
    }

    public function getDevices(string $accessToken)
    {
        $client = new Client();
        $res = $client->post($this->url. '/device/getListDevice', [
            'form_params' => [
                'token' => $accessToken
            ]
        ]);

        $data = json_decode($res->getBody()->getContents());

        return $data;
    }

    public function getPlaces($accessToken)
    {
        $client = new Client();
        $res = $client->post($this->url. '/place/getPlaces', [
            'form_params' => [
                'token' => $accessToken
            ]
        ]);

        $data = json_decode($res->getBody()->getContents());

        return $data;
    }

    public function getAllUsers($accessToken, $placeId)
    {
        $client = new Client();
        $res = $client->post($this->url. '/person/getListByPlace', [
            'form_params' => [
                'token' => $accessToken,
                'placeID' => $placeId,
                'type' => 0
            ]
        ]);

        $data = json_decode($res->getBody()->getContents());

        return $data;
    }

    public function registerEmployee($accessToken, $placeId, $employee)
    {
        $positions = config('const.positions');

        $formData = [
            'form_params' => [
                'token' => $accessToken,
                'name' => $employee->fullname,
                'url' => 'https://work.horusvn.com/image/'.$employee->avatar,
                'aliasID' => $employee->id,
                'title' => isset($positions[$employee->position]) ? $positions[$employee->position] : 'Nhân viên',
                'placeID' => $placeId,
                'type' => 0
            ]
        ];

        $client = new Client();
        $res = $client->post($this->url. '/person/registerByUrl', $formData);

        $data = json_decode($res->getBody()->getContents());

        return $data;
    }

    public function updateEmployeeFaceIDUrlByAliasID($accessToken, $placeId, $employee)
    {
        $formData = [
            'form_params' => [
                'token' => $accessToken,
                'url' => 'https://work.horusvn.com/image/'.$employee->avatar,
                'aliasID' => $employee->user_code,
                'placeID' => $placeId,
            ]
        ];

        $client = new Client();
        $res = $client->post($this->url. '/person/updateByFaceUrlByAliasID', $formData);

        $data = json_decode($res->getBody()->getContents());

        return $data;
    }
}
