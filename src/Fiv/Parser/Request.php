<?php

  namespace Fiv\Parser;

  /**
   * ```php
   * $request = new \Fiv\Parser\Request();
   * $request->setOption(CURLOPT_PROXY, $proxy);
   * ```
   *
   * @author  Ivan Scherbak <dev@funivan.com>
   * @created 11/10/2009
   * @link    http://funivan.com
   *
   */
  class Request {

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
     * Containing the last error for the current session
     *
     * http://curl.haxx.se/libcurl/c/libcurl-errors.html
     *
     * @var int
     */
    protected $error;

    /**
     * Information about request
     *
     * @var \Fiv\Parser\Request\Info
     */
    protected $info;

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
     * Indicate that response header, body, and info taken from cache
     *
     * @var bool
     */
    protected $requestFromCache = false;

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
     * If true set referrer automatically when request page
     *
     * @var boolean
     */
    protected $automaticallyChangeReferer = true;


    /**
     *
     * @var \Fiv\Parser\Debug\DebugInterface
     */
    protected $debugAdapter = null;

    /**
     *
     * @var \Fiv\Parser\Cache\FileCache
     */
    protected $cacheAdapter = null;

    /**
     * Indicate that request is restored from cache
     *
     * @var bool
     */
    protected $cached = false;


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
      $this->restoreDefaultOptions();
    }


    /**
     * Make post request
     *
     * @param string $url
     * @param array $postData
     * @return string
     */
    public function post($url, $postData) {
      $cacheKey = md5($url . serialize($postData));

      $data = $this->getFromCache(\Fiv\Parser\Cache\CacheInterface::TYPE_POST, $cacheKey);
      if ($data !== null) {
        return $data;
      }

      $this->prepareRequest($url);

      $this->setOption(CURLOPT_POST, 1);
      $this->setOption(CURLOPT_POSTFIELDS, $postData);

      $result = $this->executeRequest();

      $this->saveToCache(\Fiv\Parser\Cache\CacheInterface::TYPE_POST, $cacheKey);

      return $result;
    }

    /**
     *
     * @param string $url
     * @return string
     */
    public function get($url) {

      $cacheKey = md5($url);
      $data = $this->getFromCache(\Fiv\Parser\Cache\CacheInterface::TYPE_GET, $cacheKey);
      if ($data !== null) {
        return $data;
      }

      $this->prepareRequest($url);

      $this->setOption(CURLOPT_HTTPGET, true);
      $this->setOption(CURLOPT_POSTFIELDS, false);

      $this->setOption(CURLOPT_POST, 0);

      $result = $this->executeRequest();

      $this->saveToCache(\Fiv\Parser\Cache\CacheInterface::TYPE_GET, $cacheKey);

      return $result;
    }

    /**
     * @param string $type
     * @param string $key
     * @return null|string
     */
    protected function getFromCache($type, $key) {
      if (!empty($this->cacheAdapter)) {
        $data = $this->cacheAdapter->getRequestData($type, $key);
        if (!empty($data)) {
          return $this->restoreFromCachedData($data);
        }
      }

      return null;
    }

    /**
     * @param $type
     * @param $key
     * @return bool|int
     */
    protected function saveToCache($type, $key) {
      if (!empty($this->cacheAdapter)) {
        $data = \Fiv\Parser\Cache\Data::initFromRequest($this);
        return $this->cacheAdapter->storeRequestData($type, $key, $data);
      }
      return false;
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
      $debugClass = $this->getDebugAdapter();
      if ($debugClass) {
        $debugClass->beforeRequest($this);
      }

      $this->cached = false;
      $this->error = null;
      $this->info = null;
      # remember state of header in body
      $optionsBeforeRequest = $this->getOptions();

      $this->setOption(CURLOPT_HEADER, true); # track response header
      $this->setOption(CURLINFO_HEADER_OUT, true); # track request header

      # execute request
      $response = curl_exec($this->resource);
      $this->error = curl_error($this->resource);

      $this->info = new \Fiv\Parser\Request\Info(curl_getinfo($this->resource));

      $this->responseHeader = substr($response, 0, $this->info->getHeaderSize());
      $this->responseBody = substr($response, $this->info->getHeaderSize());

      # set changed options to default
      $this->setOptions(array(
        CURLOPT_HEADER => isset($optionsBeforeRequest[CURLOPT_HEADER]) ? $optionsBeforeRequest[CURLOPT_HEADER] : false,
        CURLINFO_HEADER_OUT => isset($optionsBeforeRequest[CURLINFO_HEADER_OUT]) ? $optionsBeforeRequest[CURLINFO_HEADER_OUT] : false,
      ));

      if ($debugClass) {
        $debugClass->afterRequest($this);
      }

      if ($this->automaticallyChangeReferer) {
        # set referrer from last affected url
        $this->setOption(CURLOPT_REFERER, $this->getInfo()->getUrl());
      }

      return $this->responseBody;
    }

    /**
     * Return curl error
     *
     * @link http://curl.haxx.se/libcurl/c/libcurl-errors.html    Error codes
     * @return int Returns the error number or 0 (zero) if no error occurred.
     */
    public function getError() {
      return $this->error;
    }

    /**
     * Return information about latest request
     *
     * @return \Fiv\Parser\Request\Info
     */
    public function getInfo() {
      return $this->info;
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
     * @param string $key
     * @return null|mixed
     */
    public function getOption($key) {
      if (!isset($this->options[$key])) {
        return null;
      }

      return $this->options[$key];
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
     * Set headers from key => value array
     *
     * @param array $headers
     * @return $this
     */
    public function setHeaders($headers) {

      if (!is_array($headers)) {
        throw new \InvalidArgumentException('Expect key=> value array');
      }

      foreach ($headers as $name => $value) {
        $this->headers[$name] = $value;
      }

      $curlHeaders = array();
      foreach ($this->headers as $name => $value) {
        $curlHeaders[] = $name . ":" . $value;
      }

      $this->setOption(CURLOPT_HTTPHEADER, $curlHeaders);

      return $this;
    }

    /**
     * Return request headers
     *
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
      $this->setOption(CURLOPT_HTTPHEADER, array());
      $this->headers = array();
      return $this;
    }

    /**
     * Return debug adapter
     *
     * @return null|\Fiv\Parser\Debug\DebugInterface
     */
    public function getDebugAdapter() {
      return $this->debugAdapter;
    }

    /**
     * Set debug adapter
     *
     * @param \Fiv\Parser\Debug\DebugInterface $debugClass
     * @return $this
     */
    public function setDebugAdapter(\Fiv\Parser\Debug\DebugInterface $debugClass) {
      $this->debugAdapter = $debugClass;
      return $this;
    }

    /**
     * Return response body
     *
     * @return string
     */
    public function getResponseBody() {
      return $this->responseBody;
    }

    /**
     * Return response header
     *
     * @return string
     */
    public function getResponseHeader() {
      return $this->responseHeader;
    }

    /**
     * Remove current curl options and set default
     *
     * @return $this
     */
    public function restoreDefaultOptions() {
      $this->cleanOptions();
      $this->cleanHeaders();
      $this->setOptions($this->defaultOptions);
      $this->setHeaders($this->defaultHeaders);
      return $this;
    }

    /**
     * Set CURLOPT_COOKIEJAR and CURLOPT_COOKIEFILE
     *
     * @param string $filePath
     * @return $this
     */
    public function setCookieFile($filePath) {
      $this->setOptions([
        CURLOPT_COOKIEJAR => $filePath,
        CURLOPT_COOKIEFILE => $filePath,
      ]);
      return $this;
    }

    /**
     * Indicate if we change referer automatically
     *
     * @return boolean
     */
    public function isAutomaticallyChangeReferer() {
      return $this->automaticallyChangeReferer;
    }

    /**
     * @param boolean $automaticallyChangeReferer
     * @return $this
     */
    public function setAutomaticallyChangeReferer($automaticallyChangeReferer) {
      $this->automaticallyChangeReferer = $automaticallyChangeReferer;
      return $this;
    }

    /**
     * @return \Fiv\Parser\Cache\CacheInterface
     */
    public function getCacheAdapter() {
      return $this->cacheAdapter;
    }

    /**
     * @param \Fiv\Parser\Cache\CacheInterface $cacheAdapter
     * @return $this
     */
    public function setCacheAdapter($cacheAdapter) {
      $this->cacheAdapter = $cacheAdapter;
      return $this;
    }

    /**
     * Indicate if our response is cached
     *
     * @return bool
     */
    public function isCached() {
      return !empty($this->cached);
    }

    /**
     * @param \Fiv\Parser\Cache\Data $data
     * @return string
     */
    protected function restoreFromCachedData(\Fiv\Parser\Cache\Data $data) {
      $this->responseBody = $data->getBody();
      $this->responseHeader = $data->getHeader();
      $this->error = $data->getError();
      $this->info = $data->getInfo();
      $this->cached = true;
      return $this->responseBody;
    }

  }