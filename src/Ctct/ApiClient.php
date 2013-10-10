<?php

class Ctct_ApiClient
{
    const AUTHORIZE_URL = 'https://oauth2.constantcontact.com/oauth2/oauth/siteowner/authorize?response_type=code&client_id=%s&redirect_uri=%s';

    const ACCESS_TOKEN_URL = 'https://oauth2.constantcontact.com/oauth2/oauth/token?grant_type=authorization_code&client_id=%s&client_secret=%s&code=%s&redirect_uri=%s';

    const TOKEN_INFO_URL = 'https://oauth2.constantcontact.com/oauth2/tokeninfo.htm?access_token=%s';

    /**
     * @var string
     */
    protected $_consumerKey;

    /**
     * @var string
     */
    protected $_consumerSecret;

    /**
     * @var string
     */
    protected $_accessToken;

    /**
     * @var string
     */
    protected $_username;

    /**
     * @var Ctct_HttpRequest
     */
    protected $_httpRequest;

    /**
     * @var Ctct_ListIterator
     */
    protected $_lists;

    /**
     * @var Ctct_ContactIterator
     */
    protected $_contacts;

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
     * Get the consumer key
     *
     * @return string
     */
    public function getConsumerKey()
    {
        return $this->_consumerKey;
    }

    /**
     * Get the consumer secret
     *
     * @return string
     */
    public function getConsumerSecret()
    {
        return $this->_consumerSecret;
    }

    /**
     * Set the OAuth access token
     *
     * @param string $accessToken
     *
     * @return Ctct_ApiClient
     */
    public function setAccessToken($accessToken)
    {
        $this->_accessToken = $accessToken;

        return $this;
    }

    /**
     * Get the OAuth access token
     *
     * @return string
     */
    public function getAccessToken()
    {
        return $this->_accessToken;
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
    public function fetchAccessToken($code, $returnUrl)
    {
        $url         = sprintf(self::ACCESS_TOKEN_URL, $this->_consumerKey, $this->_consumerSecret, $code, $returnUrl);
        $result      = $this->_postRequestJson($url);
        $accessToken = $result['access_token'];

        $this->setAccessToken($accessToken);
        // expires_in and token_type also available but not really used by Constant Contact
        return $accessToken;
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
     * Get the username for a given access token.
     *
     * @return string
     */
    public function getUsername()
    {
        if (!$this->_username) {
            $url             = sprintf(self::TOKEN_INFO_URL, $this->_accessToken);
            $response        = $this->_postRequestJson($url);
            $this->_username = $response['user_name'];
        }

        return $this->_username;
    }

    /**
     * Return an iterator of contact lists
     *
     * @return Ctct_ListIterator
     */
    public function getLists()
    {
        if (!$this->_lists) {
            $this->_lists = new Ctct_ListIterator($this);
        }

        return $this->_lists;
    }

    /**
     * Return an iterator of contact
     *
     * @return Ctct_ContactIterator
     */
    public function getContacts()
    {
        if (!$this->_contacts) {
            $this->_contacts = new Ctct_ContactIterator($this);
        }

        return $this->_contacts;
    }

    /**
     * Perform a POST request to a Ctct URL.
     *
     * All of Ctct's examples use GET parameters and a bogus POST value, so we do the same here...
     *
     * @param string $url
     *
     * @return array
     */
    protected function _postRequestJson($url)
    {
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

        return $result;
    }
}
