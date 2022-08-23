<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Illuminate\Support\Facades\Config;

class TrengoTest extends TestCase
{
	protected $apiKey;

	protected function setUp(): void
	{
	    parent::setUp();
	    $this->apiKey = env('TRENGO_API_KEY');
	}
    /**
     * Trengo api test.
     *
     * @return void
     */
    public function test_trengo()
    {
    	$trengo = new \App\Utils\Trengo($this->apiKey);
    	// Customfields api test
    	$customFieldsResponse = $trengo->getCustomFields();
    	$this->assertArrayHasKey('response', $customFieldsResponse);

    	// Profiles test
    	$profilesResponse = $trengo->getProfiles();
    	$this->assertArrayHasKey('success', $profilesResponse);
    	$this->assertEquals($profilesResponse['success'], true);
    	$this->assertArrayHasKey('response', $profilesResponse);
    	$this->assertArrayHasKey('data', $profilesResponse['response']);
    	if(count($profilesResponse['response']['data']) > 0 ) {
    		$profilId = $profilesResponse['response']['data'][0]['id'];
    		$profileResponse = $trengo->getProfile($profilId);
    		$this->assertEquals($profilId, $profileResponse['response']['id']);
    	}
    }
}
