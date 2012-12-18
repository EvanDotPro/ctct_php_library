<?php

class Ctct_ApiClient
{
    const AUTHORIZE_URL = 'https://oauth2.constantcontact.com/oauth2/oauth/siteowner/authorize?response_type=code&client_id=%s&redirect_uri=%s';

    const ACCESS_TOKEN_URL = 'https://oauth2.constantcontact.com/oauth2/oauth/token?grant_type=authorization_code&client_id=%s&client_secret=%s&code=%s&redirect_uri=%s';

    /**
     * @var string
     */
    protected $_consumerKey;

    /**
     * @var string
     */
    protected $_consumerSecret;

    /**
     * @var Ctct_HttpRequest
     */
    protected $_httpRequest;

    /**
     * Consturctor
     *
     * @param string $consumerKey
     * @param string $consumerSecret
     */
    public function __construct($consumerKey, $consumerSecret)
    {
        $this->_consumerKey    = $consumerKey;
        $this->_consumerSecret = $consumerSecret;
    }

    /**
     * Set the HttpRequest class to use
     *
     * @param Ctct_HttpRequest $httpRequest
     *
     * @return Ctct_ApiClient
     */
    public function setHttpRequest(Ctct_HttpRequest $httpRequest)
    {
        $this->_httpRequest = $httpRequest;

        return $this;
    }

    /**
     * Get the HttpRequest class
     *
     * @param string $url
     *
     * @return Ctct_HttpRequest
     */
    public function getHttpRequest($url = null)
    {
        if (!$this->_httpRequest) {
            $this->_httpRequest = new Ctct_CurlRequest($url);
        }

        return $this->_httpRequest;
    }

    /**
     * Get the URL to redirect the end-user to for authorization.
     *
     * @param string $returnUrl
     *
     * @return string
     */
    public function getAuthorizeUrl($returnUrl)
    {
        return sprintf(self::AUTHORIZE_URL, $this->_consumerKey, $returnUrl);
    }

    /**
     * Verify the code returned by the client is valid and exchange it for an
     * access token.
     *
     * @param string $code
     * @param string $returnUrl
     *
     * @return string
     */
    public function getAccessToken($code, $returnUrl)
    {
        $url = sprintf(self::ACCESS_TOKEN_URL, $this->_consumerKey, $this->_consumerSecret, $code, $returnUrl);

        $request = $this->getHttpRequest($url);
        $request->setOption(CURLOPT_URL, $url);
        $request->setOption(CURLOPT_HEADER, 0);
        $request->setOption(CURLOPT_RETURNTRANSFER, 1);
        $request->setOption(CURLOPT_SSL_VERIFYPEER, 0); // TODO: Sslurp or similar
        $request->setOption(CURLOPT_POST, 1);
        // Constant Contact uses URL parameters instead of POST parameters, but
        // still requires a non-empty request body.
        $request->setOption(CURLOPT_POSTFIELDS, 'ctct_php_library');

        $result = $request->execute();

        if ($request->error()) {
            throw new Ctct_Exception('cURL Error: ' . $request->error());
        }

        $request->close();

        $result = json_decode($result, true);

        if (isset($result['error'])) {
            /**
             * error: invalid_grant, error_description: Invalid verification code: xxxxxxxxxxxxxxxxxxxxxxxxxxx
             * error: redirect_uri_mismatch, error_desciption: Redirect URI mismatch.
             */
            throw new Ctct_Exception($result['error'] . ': ' . $result['error_description']);
        }

        // expires_in and token_type also available but not used by Constant Contact
        return $result['access_token'];
    }
}
