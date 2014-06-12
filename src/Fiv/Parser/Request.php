<?php

  namespace Fiv\Parser;

  /**
   * <code>
   * $request = new Request();
   * $request->setOption(CURLOPT_PROXY, $proxy);
   * </code>
   *
   * @author  Ivan Scherbak <dev@funivan.com>
   * @created 11/10/2009
   * @link    http://funivan.com
   *
   */
  class Request {

    /**
     * If true set referrer automatically when request page
     *
     * @var boolean
     */
    public $stepByStep = true;

    /**
     *
     * @var string
     */
    protected $responseBody = '';

    /**
     *
     * @var string
     */
    protected $responseHeader = '';

    /**
     * Current options of curl session
     *
     * @var array
     */
    protected $options = array();

    /**
     * Current headers of curl session
     *
     * @var array
     */
    protected $headers = array();

    /**
     *
     * @var Debug
     */
    protected $debugClass = null;

    /**
     * Default headers
     *
     * @var array
     */
    private $defaultHeaders = array(
      "User-Agent" => "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.64 Safari/537.11",
      "Accept-Language" => "ru-RU,ru;q.9,en;q.8",
      "Accept-Charset" => "iso-8859-1, utf-8, utf-16, *;q.1",
      "Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8"
    );

    /**
     * Default options
     *
     * @var array
     */
    private $defaultOptions = array(
      CURLOPT_ENCODING => '',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HEADER => false,
      CURLINFO_HEADER_OUT => true,
      CURLOPT_CONNECTTIMEOUT => 10,
      CURLOPT_TIMEOUT => 10,
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_SSL_VERIFYHOST => false,
      CURLOPT_COOKIEJAR => "/tmp/fiv-cookie.txt",
      CURLOPT_COOKIEFILE => "/tmp/fiv-cookie.txt",
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_MAXREDIRS => 5,
    );

    /**
     * Resource of curl session
     *
     * @var mixed (null, resource)
     */
    protected $resource = null;

    /**
     * Init curl
     * Set default headers and options
     */
    public function __construct() {
      $this->resource = curl_init();
      # set default  params
      $this->restoreDefaultHeaders();
      $this->restoreDefaultOptions();
    }


    /**
     * Make post request
     *
     * @param string $url
     * @param array $data
     * @return mixed (string, boolean)
     */
    public function post($url, $data) {

      $this->prepareRequest($url);

      $this->setOption(CURLOPT_POST, 1);
      $this->setOption(CURLOPT_POSTFIELDS, $data);

      $result = $this->executeRequest();

      return $result;
    }

    /**
     *
     * @param string $url
     * @return string
     */
    public function get($url) {

      $this->prepareRequest($url);

      $this->setOption(CURLOPT_HTTPGET, true);
      $this->setOption(CURLOPT_POSTFIELDS, false);

      $this->setOption(CURLOPT_POST, 0);

      $result = $this->executeRequest();

      return $result;
    }

    /**
     * @param string $url
     * @return $this
     */
    protected function prepareRequest($url) {

      if (strpos($url, ' ')) {
        $url = str_replace(' ', '%20', $url);
      }

      curl_setopt($this->resource, CURLOPT_URL, $url);

      return $this;
    }

    /**
     *
     * @return string
     */
    protected function executeRequest() {
      $debugClass = $this->getDebugClass();
      if ($debugClass) {
        $debugClass->beforeRequest($this);
      }

      # remember state of header in body
      $optionsBeforeRequest = $this->getOptions();

      $this->setOption(CURLOPT_HEADER, true); // track response url
      $this->setOption(CURLINFO_HEADER_OUT, true); // track request url

      # execute request
      $response = curl_exec($this->resource);

      $info = $this->getInfo();

      $this->responseHeader = substr($response, 0, $info->getHeaderSize());
      $this->responseBody = substr($response, $info->getHeaderSize());
      if (!isset($optionsBeforeRequest[CURLOPT_HEADER]) or $optionsBeforeRequest[CURLOPT_HEADER] == false) {
        $response = $this->responseBody;
      }

      # set changed options to default
      $this->setOptions(array(
        CURLOPT_HEADER => isset($optionsBeforeRequest[CURLOPT_HEADER]) ? $optionsBeforeRequest[CURLOPT_HEADER] : false,
        CURLINFO_HEADER_OUT => isset($optionsBeforeRequest[CURLINFO_HEADER_OUT]) ? $optionsBeforeRequest[CURLINFO_HEADER_OUT] : false,
      ));

      if ($debugClass) {
        $debugClass->afterRequest($this);
      }

      if ($this->stepByStep) {
        # set referrer from last affected url
        $this->setOption(CURLOPT_REFERER, $this->getInfo()->getUrl());
      }

      return $response;
    }

    /**
     * Return curl error
     *
     * @link http://curl.haxx.se/libcurl/c/libcurl-errors.html    Error codes
     * @return int Returns the error number or 0 (zero) if no error occurred.
     */
    public function getError() {
      return curl_error($this->resource);
    }

    /**
     * Return information about latest request
     *
     * @return \Fiv\Parser\Request\Info
     */
    public function getInfo() {
      return new \Fiv\Parser\Request\Info(curl_getinfo($this->resource));
    }

    /**
     * Set single option CURLOPT_*
     *
     * @param int $option
     * @param mixed $value
     * @return $this
     */
    public function setOption($option, $value) {
      curl_setopt($this->resource, $option, $value);
      $this->options[$option] = $value;
      return $this;
    }

    /**
     * Array of CURLOPT_*
     *
     * @param array $optionsArray
     * @return $this
     */
    public function setOptions(array $optionsArray) {
      foreach ($optionsArray as $key => $value) {
        $this->options[$key] = $value;
      }

      curl_setopt_array($this->resource, $optionsArray);
      return $this;
    }

    /**
     * @return array
     */
    public function getOptions() {
      return $this->options;
    }

    /**
     * Remove current options
     *
     * @return $this
     */
    public function cleanOptions() {
      foreach ($this->options as $name => $value) {
        curl_setopt($this->resource, $name, null);
      }
      $this->options = array();
      return $this;
    }

    /**
     * Set headers from string
     *
     * @param string $headers
     * @return $this
     */
    public function setRawHeaders($headers) {

      if (!is_string($headers)) {
        throw new \InvalidArgumentException("Expect string.");
      }

      preg_match_all('!([^:]+):(.*?)(\n|$)!', $headers, $matchedHeaders);

      if (empty($matchedHeaders[1])) {
        throw new \InvalidArgumentException('Could not load raw header');
      }

      $headersArray = array();
      foreach ($matchedHeaders[1] as $k => $name) {
        $headersArray[trim($name)] = $matchedHeaders[2][$k];
      }

      $this->setHeaders($headersArray);

      return $this;
    }

    /**
     *
     * @param array $headers
     * @return $this
     */
    public function setHeaders($headers) {

      if (!is_array($headers)) {
        throw new \InvalidArgumentException('Expect array');
      }

      foreach ($headers as $name => $value) {
        $this->headers[$name] = $name . ":" . $value;
      }

      curl_setopt($this->resource, CURLOPT_HTTPHEADER, $this->headers);

      return $this;
    }

    /**
     * @return array
     */
    public function getHeaders() {
      return $this->headers;
    }


    /**
     * Remove current headers
     *
     * @return $this
     */
    public function cleanHeaders() {
      curl_setopt($this->resource, CURLOPT_HTTPHEADER, []);
      $this->headers = array();
      return $this;
    }

    /**
     *
     * @return null|Debug
     */
    public function getDebugClass() {
      return $this->debugClass;
    }

    /**
     * @param Debug $debugClass
     * @return $this
     */
    public function setDebugClass(Debug $debugClass) {
      $this->debugClass = $debugClass;
      return $this;
    }

    /**
     * @return string
     */
    public function getResponseBody() {
      return $this->responseBody;
    }

    /**
     * @return string
     */
    public function getResponseHeader() {
      return $this->responseHeader;
    }

    /**
     * Remove current headers and set default
     *
     * @return $this
     */
    public function restoreDefaultHeaders() {
      $this->cleanHeaders();
      $this->setHeaders($this->defaultHeaders);
      return $this;
    }

    /**
     * Remove current curl options and set default
     *
     * @return $this
     */
    public function restoreDefaultOptions() {
      $this->cleanOptions();
      $this->setOptions($this->defaultOptions);
      return $this;
    }

  }