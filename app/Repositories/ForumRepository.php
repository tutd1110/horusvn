<?php

namespace App\Repositories;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ForumRepository
{
    protected $url = 'https://forum.horusvn.com';

    public function getUser($apiKey)
    {
        // Create a new instance of the Guzzle client
        $client = new Client();

        // Define the headers you want to include in the request
        $headers = [
            'XF-Api-Key' => $apiKey,
        ];
        
        // Define the base URL and the URI with the user ID as a placeholder
        $baseUrl = $this->url;
        $uri = "api/users/".Auth()->user()->id;

        try {
            // Send the GET request with the URI and headers
            $response = $client->get($baseUrl . '/' . $uri, [
                'headers' => $headers,
            ]);

            return true;
        } catch (GuzzleException $e) {
            return false;
        }
    }

    public function getUserByEmail($apiKey)
    {
        // Create a new instance of the Guzzle client
        $client = new Client();

        // Define the headers you want to include in the request
        $headers = [
            'XF-Api-Key' => $apiKey,
        ];
        
        // Define the base URL and the URI with the user ID as a placeholder
        $baseUrl = $this->url;
        $uri = "api/users/find-email";
        $queryParams = [
            'email' => Auth()->user()->email,
            'api_bypass_permissions' => 1,
        ];

        try {
            // Send the GET request with the URI and headers
            $response = $client->get($baseUrl . '/' . $uri, [
                'headers' => $headers,
                'query' => $queryParams,
            ]);

            // Process the response as needed
            $body = $response->getBody()->getContents();

            $data = json_decode($body, true);

            if (empty($data['user'])) {
                return false;
            }

            return $data['user']['user_id'];
        } catch (GuzzleException $e) {
            // This exception is thrown for 4xx client errors (e.g., 404, 401)
            $response = $e->getResponse();
            $statusCode = $response->getStatusCode();
            $errorBody = $response->getBody()->getContents();

            // ... Handle the error response ...
            // You can decode the error response if it's in JSON format:
            $errorData = json_decode($errorBody, true);

            // Access the error message or other details from the error response
            $errorMessage = $errorData['errors'] ?? 'Unknown error';

            // ... Handle the error message ...
            Log::error($errorMessage);

            return false;
        }
    }

    public function loginToken($apiKey, $xfUserId)
    {
        // Create a new instance of the Guzzle client
        $client = new Client();

        // Define the headers you want to include in the request
        $headers = [
            'XF-Api-Key' => $apiKey,
        ];
        
        // Define the base URL and the URI with the user ID as a placeholder
        $baseUrl = $this->url;
        $url = "api/auth/login-token";

        try {
            // Send the POST request with the body and headers
            $response = $client->post($baseUrl . '/' . $url, [
                'form_params' => [
                    'user_id' => $xfUserId
                ],
                'headers' => $headers,
            ]);

            // Process the response as needed
            $body = $response->getBody()->getContents();

            $data = json_decode($body, true);

            return $data['login_url'];
        } catch (GuzzleException $e) {
            return false;
        }
    }

    public function createUser($apiKey)
    {
        // Create a new instance of the Guzzle client
        $client = new Client();

        // Define the headers you want to include in the request
        $headers = [
            'XF-Api-Key' => $apiKey,
        ];
        
        // Define the base URL and the URI with the user ID as a placeholder
        $baseUrl = $this->url;
        $url = "api/users/";

        //employee info
        $employee = Auth()->user();
        $username = explode('@', $employee->email)[0];
        $id = $employee->id;

        try {
            // Send the POST request with the body and headers
            $response = $client->post($baseUrl . '/' . $url, [
                'form_params' => [
                    "api_bypass_permissions" => 1,
                    "username" => $username,
                    "email" => $employee->email,
                    "dob[day]" => Carbon::parse($employee->birthday)->day,
                    "dob[month]" => Carbon::parse($employee->birthday)->month,
                    "dob[year]" => Carbon::parse($employee->birthday)->year,
                    "password" => "Chamchi123"
                ],
                'headers' => $headers,
            ]);

            // Process the response as needed
            $body = $response->getBody()->getContents();

            $data = json_decode($body, true);

            if ($data['success']) {
                return $data['user']['user_id'];
            }

            return false;
        } catch (GuzzleException $e) {
            // This exception is thrown for 4xx client errors (e.g., 404, 401)
            $response = $e->getResponse();
            $statusCode = $response->getStatusCode();
            $errorBody = $response->getBody()->getContents();

            // ... Handle the error response ...
            // You can decode the error response if it's in JSON format:
            $errorData = json_decode($errorBody, true);

            // Access the error message or other details from the error response
            $errorMessage = $errorData['errors'] ?? 'Unknown error';

            // ... Handle the error message ...
            Log::error($errorMessage);

            return false;
        }
    }

    public function getLatestPosts($apiKey)
    {
        // Create a new instance of the Guzzle client
        $client = new Client();

        // Define the headers you want to include in the request
        $headers = [
            'XF-Api-Key' => $apiKey,
        ];
        
        // Define the base URL and the URI with the user ID as a placeholder
        $baseUrl = $this->url;

        // Define the parameters to use in the API request
        $lastPostDate = 'last_post_date';
        $order = 'desc';
        $page = 1;
        $apiBypassPermissions = 1;

        try {
            // Send the GET request with the URI and headers
            $response = $client->get($baseUrl . '/api/threads', [
                'headers' => $headers,
                'query' => [
                    'api_bypass_permissions' => $apiBypassPermissions,
                    'order' => $lastPostDate,
                    'direction' => $order,
                    'page' => $page
                ],
            ]);

            // Process the response as needed
            $body = $response->getBody()->getContents();

            $data = json_decode($body, true);

            return $data['threads'];
        } catch (GuzzleException $e) {
            // This exception is thrown for 4xx client errors (e.g., 404, 401)
            $response = $e->getResponse();
            $statusCode = $response->getStatusCode();
            $errorBody = $response->getBody()->getContents();

            // ... Handle the error response ...
            // You can decode the error response if it's in JSON format:
            $errorData = json_decode($errorBody, true);

            // Access the error message or other details from the error response
            $errorMessage = $errorData['errors'] ?? 'Unknown error';

            // ... Handle the error message ...
            Log::error($errorMessage);

            return false;
        }
    }
}
