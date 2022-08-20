<?php
namespace App\Utils;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;


class Trengo
{

    private $apiKey;
    private $apiRequestUrl = 'https://app.trengo.com/api/v2/';

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function createProfile($name){
        return $this->request($this->apiRequestUrl . "profiles" , 'POST', ['name' => $name]);
    }

    public function getProfile($id){
        return $this->request($this->apiRequestUrl . "profiles/".$id , 'GET');
    }
    public function getCustomFields() {
        $fullData = ['response' => []];
        while (true) {
            $response = $this->request($this->apiRequestUrl . "custom_fields/" , 'GET');
            $fullData['response'] = array_merge($fullData['response'], $response['response']['data']);
            if(empty($response['response']['links']['next']) ){
                break;
            }
        }
        return $fullData;

    }

    public function createCustomField($title, $type) {
        return $this->request($this->apiRequestUrl . "custom_fields" , 'POST', ['title' => $title, 'type' => $type]);
    }

    public function createContact($email, $name) {
        return $this->request($this->apiRequestUrl . "channels/".config('trengo.channel_id')."/contacts" , 'POST', ['identifier' => $email, 'name' => $name, 'channel_id' => config('trengo.channel_id')]);
    }

    public function attachContact($profileId, $contactId, $type = 'EMAIL') {
        return $this->request($this->apiRequestUrl . "profiles/".$profileId."/contacts" , 'POST', ['contact_id' => $contactId, 'type' => $type]);
    }

    public function addCustomFieldToContact($contactId, $customFieldId, $value) {
        return $this->request($this->apiRequestUrl . "contacts/".$contactId."/custom_fields" , 'POST', ['custom_field_id' => $customFieldId, 'value' => $value]);
    }
    /**
     * Function to make GET/POST requests
     *
     * @param $url
     * @param string $method
     * @param array $data
     *
     * @return array|bool
     */
    private function request($url, $method = 'POST', array $data = [] )
    {
        $config =['headers' => ['Authorization' => "Bearer {$this->apiKey}", 'Content-Type' => 'application/json', 'Accept' => 'application/json'], 'http_errors' => false];
        $client = new Client($config);
        $result = [];
        try {
            // We can add all methods here
            switch ($method){
                case "GET":
                    $response = $client->get($url);
                    break;
                case "POST":
                    $response = $client->post($url , ['form_params' => $data]);
                    break;
            }
        } catch (ClientException $e){
            $result['success'] = false;
            $result['response'] = $e->getMessage();
            return $result;
        }

        $code = $response->getStatusCode();

        if ($code >= 400) {
            $result['success'] = false;
             if($code == 429) {
                // $secondsRemaining = $response->getHeaderLine('Retry-After');
                // Cache::put(
                //     'api-limit',
                //     now()->addSeconds($secondsRemaining)->timestamp,
                //     $secondsRemaining
                // );
             }
        } else {
            $result['success'] = true;
        }
        $result['response'] = json_decode($response->getBody()->getContents(), true);
        $result['X-RateLimit-Remaining'] = $response->getHeaderLine('X-RateLimit-Remaining') ?? 0;
       
        return $result;
    }
}