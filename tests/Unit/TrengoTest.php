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
	    $this->apiKey = env('TRENGO_API_KEY', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxIiwianRpIjoiNDRiY2ZiMWJiYTlkNjUzNjJlM2NmODc1ZWY1OTNhY2NlMzE4NmEyMTVhYTM0YTE5YTE2YWQ2Mjk0ZWU1YmRmYmQzYzYzZDBmMzI2ZDRlODMiLCJpYXQiOjE2NjA5NTQxNDYuODk3NTgyLCJuYmYiOjE2NjA5NTQxNDYuODk3NTg0LCJleHAiOjQ3ODUwOTE3NDYuODkyMDUyLCJzdWIiOiI1MTcwODQiLCJzY29wZXMiOltdfQ.K7oVWkSAdTMI6dcb3tQBHg6OHfw5pHad0Di51Bt9-KwVZugjI_ysbPofJ314jna6v6NMX_8Nx5Ehz1dBJyF7sw');

	}
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function test_trengo()
    {
    	$trengo = new \App\Utils\Trengo($this->apiKey);
    	// Customfields api test
    	$response = $trengo->getCustomFields();
    	$this->assertArrayHasKey('response', $response);

    	// Profiles test
    	$response = $trengo->getProfiles();
    	$this->assertArrayHasKey('success', $response);
    	$this->assertEquals($response['success'], true);
    	$this->assertArrayHasKey('response', $response);
    }
}
