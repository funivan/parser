<?php

  namespace Fiv\ParserV1;

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
      "User-Agent" => "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.90 Safari/537.36",
      "Accept-Language" => "ru-RU,ru;q.9,en;q.8",
      "Accept-Charset" => "iso-8859-1, utf-8, utf-16, *;q.1"
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
      CURLOPT_COOKIEJAR => "/tmp/cookieJar.txt",
      CURLOPT_COOKIEFILE => "/tmp/cookieFile.txt",
    );

    /**
     * Resource of curl session
     *
     * @var mixed (null, resource)
     */
    protected $resource = null;

    public function __construct() {
      $this->resource = curl_init();
      # set default  params
      $this->setHeader($this->defaultHeaders);
      $this->setOptionsArray($this->defaultOptions);
    }

    /**
     * @return static
     */
    public static function init() {
      return new static();
    }

    /**
     * Make post request
     *
     * @param string $url
     * @param mixed (array, string) $post
     * @param boolean $follow
     * @param integer $level
     * @return mixed (string, boolean)
     */
    public function post($url, $post, $follow = true, $level = 5) {

      $this->prepareRequest($url, $follow, $level);

      $this->setOption(CURLOPT_POST, 1);
      $this->setOption(CURLOPT_POSTFIELDS, $post);

      $result = $this->executeRequest();

      return $result;
    }

    /**
     *
     * @param string $url
     * @param boolean $follow
     * @param integer $level
     * @return mixed (string, boolean)
     */
    public function get($url, $follow = true, $level = 5) {

      $this->prepareRequest($url, $follow, $level);
      $this->setOption(CURLOPT_HTTPGET, true);
      $this->setOption(CURLOPT_POSTFIELDS, false);

      $this->setOption(CURLOPT_POST, 0);

      $result = $this->executeRequest();
      return $result;
    }

    /**
     *
     * @author  Ivan Scherbak <dev@funivan.com>
     * @version 7/5/12
     * @param string $url
     * @param boolean $follow
     * @param integer $level
     */
    protected function prepareRequest($url, $follow = true, $level = 5) {

      if (strpos($url, ' ')) {
        $url = str_replace(' ', '%20', $url);
      }

      $this->setOption(CURLOPT_URL, $url);

      if ($follow) {
        $this->setOption(CURLOPT_FOLLOWLOCATION, true);
        $this->setOption(CURLOPT_MAXREDIRS, intval($level));
      } else {
        $this->setOption(CURLOPT_FOLLOWLOCATION, false);
      }
    }

    /**
     *
     * @return mixed
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

      $r = isset($optionsBeforeRequest[CURLOPT_HEADER]) ? $optionsBeforeRequest[CURLOPT_HEADER] : false;

      # set options to default but not all
      $this->setOptionsArray(array(
        CURLOPT_HEADER => isset($optionsBeforeRequest[CURLOPT_HEADER]) ? $optionsBeforeRequest[CURLOPT_HEADER] : false,
        CURLINFO_HEADER_OUT => isset($optionsBeforeRequest[CURLINFO_HEADER_OUT]) ? $optionsBeforeRequest[CURLINFO_HEADER_OUT] : false,
      ));

      $optionsBeforeRequest = $this->getOptions();

      if ($debugClass) {
        $debugClass->afterRequest($this);
      }

      if ($this->stepByStep != false) {
        $url = !empty($this->info->url) ? $this->info->url : false;
        $this->setOptionsArray(array(CURLOPT_REFERER => $url));
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
     *
     * @return \Fiv\ParserV1\Request\Info
     */
    public function getInfo() {
      return new \Fiv\ParserV1\Request\Info(curl_getinfo($this->resource));
    }

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
    public function setOptionsArray(array $optionsArray) {
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
     *
     * @param boolean|string|array $headers
     * @param boolean $raw
     * @param boolean $merge
     * @throws \Exeption
     * @return array
     */
    public function setHeader($headers, $raw = false, $merge = true) {
      if ($raw == true and is_string($headers)) {
        preg_match_all('!([^:]+):(.*?)\n!', $headers, $matches);
        if (!empty($matches[1])) {
          unset($headers);
          foreach ($matches[1] as $k => $name) {
            $headers[trim($name)] = $matches[2][$k];
          }
        } else {
          throw new \Exeption('Could not load raw header');
        }
      }

      if ($merge == false) {
        $curlHttpHeaderArray = $headers != false ? $headers : $this->headers;
      } else {
        $curlHttpHeaderArray = $headers != false ? $headers + $this->headers : $this->headers;
      }
      foreach ($curlHttpHeaderArray as $name => $value) {
        $this->headers[$name] = $name . ":" . $value;
      }
      $this->setOption(CURLOPT_HTTPHEADER, $this->headers);
      return $this->headers;
    }

    /**
     *
     * @return Debug
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

    public function getHeaders() {
      return $this->headers;
    }

  }