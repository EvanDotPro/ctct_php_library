<?php

abstract class Ctct_AbstractIterator implements Iterator
{
    const API_BASE_URL = 'https://api.constantcontact.com';

    const API_BASE_PATH = '/ws/customers/%s/%s';

    /**
     * @var Ctct_ApiClient
     */
    protected $_apiClient;

    /**
     * @var SimpleXmlElement
     */
    protected $_currentXml;

    /**
     * @var int
     */
    protected $_position = 0;

    /**
     * @var string
     */
    protected $_action;

    /**
     * @var mixed
     */
    protected $_current;

    /**
     * @var Ctct_HttpRequest
     */
    protected $_httpRequest;

    /**
     * Constructor
     *
     * @param Ctct_ApiClient $apiClient
     */
    public function __construct(Ctct_ApiClient $apiClient)
    {
        $this->_apiClient = $apiClient;
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

    public function rewind()
    {
        $this->_currentXml = $this->_requestXml($this->_getUrl());
        $this->_position = 0;
        $this->_current = $this->_populateCurrent($this->_currentXml->entry[$this->_position]);
    }

    public function current()
    {
        return $this->_current;
    }

    public function key()
    {
        return $this->_currentXml->entry[$this->_position]->id;
    }

    public function valid()
    {
        return isset($this->_currentXml->entry[$this->_position]);
    }

    public function next()
    {
        $this->_position++;
        if (isset($this->_currentXml->entry[$this->_position])) {
            $this->_current = $this->_populateCurrent($this->_currentXml->entry[$this->_position]);
            return;
        }

        $nextBatch = $this->_findNextLink($this->_currentXml);

        if (!$nextBatch) return;

        $this->_currentXml = $this->_requestXml($this->_getUrl(false, $nextBatch));
        $this->_position = 0;
        $this->_current = $this->_populateCurrent($this->_currentXml->entry[$this->_position]);
    }

    protected function _getUrl($action = false, $relativeUrl = false)
    {
        if ($relativeUrl) {
            return self::API_BASE_URL . $relativeUrl;
        }

        $url = self::API_BASE_URL . self::API_BASE_PATH;

        $action = $action ? $action : $this->_action;

        return sprintf($url, $this->_apiClient->getUsername(), $action);
    }

    /**
     * Perform a GET/POST API request, return the raw XML response
     *
     * @param string $url
     * @param string $postBody (or false for GET -- default)
     *
     * @return SimpleXmlElement The response XML
     */
    protected function _requestXml($url, $postBody = false)
    {
        $url .= (strpos($url, '?') !== false) ? '&' : '?';
        $url .= 'access_token=' . $this->_apiClient->getAccessToken();
        $request = $this->getHttpRequest($url);
        $request->setOption(CURLOPT_URL, $url);
        $request->setOption(CURLOPT_HEADER, 0);
        $request->setOption(CURLOPT_RETURNTRANSFER, 1);
        $request->setOption(CURLOPT_SSL_VERIFYPEER, 0); // TODO: Sslurp or similar
        if ($postBody) {
            $request->setOption(CURLOPT_POST, 1);
            $request->setOption(CURLOPT_POSTFIELDS, $postBody);
            $request->setOption(CURLOPT_HTTPHEADER, array('Content-type: application/atom+xml;type=entry'));
        }

        $result = $request->execute();

        if ($request->error()) {
            throw new Ctct_Exception('cURL Error: ' . $request->error());
        }

        if (function_exists('tidy_repair_string')) {
            $result = tidy_repair_string($result, array('output-xml' => true, 'input-xml' => true));
        }

        return simplexml_load_string($result);
    }

    /**
     * Get the URL to the next page in the collection
     *
     * @param SimpleXMLElement $item
     *
     * @return string URL path to the next page or false
     */
    protected function _findNextLink($item)
    {
        $nextLink = $item->xpath("//*[@rel='next']");
        return ($nextLink) ? (string) $nextLink[0]->Attributes()->href : false;
    }

    /**
     * Populate the current model from the XML representation
     *
     * @param SimpleXmlElement $current
     *
     * @return mixed
     */
    abstract protected function _populateCurrent($current);
}
