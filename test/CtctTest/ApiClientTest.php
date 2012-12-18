<?php

class CtctTest_ApiClientTest extends PHPUnit_Framework_TestCase
{
    protected $_key    = 'foo';
    protected $_secret = 'bar';

    public function testCurlRequestSetterAndGetterWorkProperly()
    {
        $client  = $this->_getApiClient();
        $request = new Ctct_CurlRequest('http://www.google.com/');
        $client->setHttpRequest($request);
        $this->assertSame($request, $client->getHttpRequest());
    }

    public function testGetHttpRequestGetsNewCurlRequestByDefault()
    {
        $client = $this->_getApiClient();
        $this->assertInstanceOf('Ctct_CurlRequest', $client->getHttpRequest());
    }

    public function testGetAuthorizeUrlReturnsProperUrl()
    {
        $client    = $this->_getApiClient();
        $returnUrl = 'http://google.com/';
        $url       = $client->getAuthorizeUrl($returnUrl);
        $this->assertContains('https://oauth2.constantcontact.com', $url);
        $this->assertContains($returnUrl, $url);
        $this->assertContains($this->_key, $url);
    }

    public function testApiClientProperlyFetchesAccessToken()
    {
        $response = '{"access_token": "token"}';
        $client   = $this->_getApiClient(null, null, $response);
        $this->assertEquals('token', $client->getAccessToken('code', 'returnUrl'));
    }

    public function testApiClientThrowsExceptionOnServerError()
    {
        $response = '{"error": "invalid_grant", "error_description": "Invalid verification code: xxxxxxxxxxxxxxxxxxxxxxxxxxx"}';
        $client   = $this->_getApiClient(null, null, $response);
        $this->setExpectedException('Ctct_Exception');
        $client->getAccessToken('code', 'returnUrl');
    }

    public function testApiClientThrowsExceptionOnCurlError()
    {
        $error  = 'Some cURL error.';
        $client = $this->_getApiClient(null, null, null, $error);
        $this->setExpectedException('Ctct_Exception');
        $client->getAccessToken('code', 'returnUrl');
    }

    protected function _getApiClient($key = null, $secret = null, $mockResponse = null, $mockCurlError = null)
    {
        $client = new Ctct_ApiClient(
            ($key ? $key : $this->_key),
            ($secret ? $secret : $this->_secret)
        );

        if (!$mockResponse && !$mockCurlError) {
            return $client;
        }

        $request = $this->getMock('Ctct_HttpRequest');

        if ($mockResponse) {
            $request->expects($this->once())
                    ->method('execute')
                    ->will($this->returnValue($mockResponse));
        }

        if ($mockCurlError) {
            $request->expects($this->exactly(2))
                    ->method('error')
                    ->will($this->returnValue($mockCurlError));
        }

        $client->setHttpRequest($request);

        return $client;
    }
}
