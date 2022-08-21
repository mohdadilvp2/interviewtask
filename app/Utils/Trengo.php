<?php
namespace App\Utils;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

/**
* Trengo SDK v0.1
* SDK to communicate with Trengo API
*
* @author Adil <mohdadilvp@gmail.com>
*
**/

class Trengo
{

    private $apiKey;
    private $apiRequestUrl = 'https://app.trengo.com/api/v2/';

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /*
    * create profile
    *
    * @author Adil <mohdadilvp@gmail.com>
    * @access public
    * @param string $name Name
    *
    * @return array.
    */
    public function createProfile($name){
        return $this->request($this->apiRequestUrl . "profiles" , 'POST', ['name' => $name]);
    }

    /*
    * get profile
    *
    * @author Adil <mohdadilvp@gmail.com>
    * @access public
    * @param int $id Id
    *
    * @return array.
    */
    public function getProfile($id){
        return $this->request($this->apiRequestUrl . "profiles/".$id , 'GET');
    }

    /*
    * Get all customfields
    *
    * @author Adil <mohdadilvp@gmail.com>
    * @access public
    *
    * @return array.
    */
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

    /*
    * Create customfields
    *
    * @access public
    * @author Adil <mohdadilvp@gmail.com>
    * @param string $title Title
    * @param string $type [PROFILE, CONTACT, TICKET]
    * @return array.
    */
    public function createCustomField($title, $type) {
        return $this->request($this->apiRequestUrl . "custom_fields" , 'POST', ['title' => $title, 'type' => $type]);
    }

    /*
    * Create contact
    *
    * @access public
    * @author Adil <mohdadilvp@gmail.com>
    * @param string $email Email
    * @param string $name
    * @return array.
    */
    public function createContact($email, $name) {
        return $this->request($this->apiRequestUrl . "channels/".config('trengo.channel_id')."/contacts" , 'POST', ['identifier' => $email, 'name' => $name, 'channel_id' => config('trengo.channel_id')]);
    }

    /*
    * Create contact
    *
    * @access public
    * @author Adil <mohdadilvp@gmail.com>
    * @param string $email Email
    * @param string $name
    * @return array.
    */
    public function attachContact($profileId, $contactId, $type = 'EMAIL') {
        return $this->request($this->apiRequestUrl . "profiles/".$profileId."/contacts" , 'POST', ['contact_id' => $contactId, 'type' => $type]);
    }

    /*
    * add custom field to contact
    *
    * @access public
    * @author Adil <mohdadilvp@gmail.com>
    * @param string $contactId Email
    * @param int $customFieldId 
    * @param string $value
    * @return array.
    */
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
        if($code == 429) {
            $secondsRemaining = $response->getHeaderLine('Retry-After');
            // Rate limit issue, So wait for remaining seconds and retry again
            sleep($secondsRemaining + 1);
            return $this->request($url, $method ,$data);
        } elseif ($code >= 400) {
            $result['success'] = false;
             
        } else {
            $result['success'] = true;
        }
        $result['response'] = json_decode($response->getBody()->getContents(), true);
        $result['X-RateLimit-Remaining'] = $response->getHeaderLine('X-RateLimit-Remaining') ?? 0;
       
        return $result;
    }
}