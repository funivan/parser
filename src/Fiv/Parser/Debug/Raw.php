<?php

  namespace Fiv\Parser\Debug;

  /**
   * DebugInterface class for request
   * Output log information to screen
   *
   * @author Ivan Scherbak <dev@funivan.com>
   */
  class Raw implements \Fiv\Parser\Debug\DebugInterface {

    protected $showBodyStatus = false;

    /**
     * @inheritdoc
     */
    public function beforeRequest(\Fiv\Parser\Request $request) {

      $options = $request->getOptions();
      $startSymbol = " >> ";

      # prepare header for output
      if (!empty($options[CURLOPT_HTTPHEADER])) {
        $requestHeader = implode("\n" . $startSymbol, $options[CURLOPT_HTTPHEADER]);
      } else {
        $requestHeader = '';
      }

      $this->showLine('Request Header');
      $requestHeader = preg_replace("!^([^:]+)\s *:\s * !mi", "$1 : ", $requestHeader);

      echo $startSymbol . $requestHeader;

      if (!empty($options[CURLOPT_POSTFIELDS])) {
        $this->showLine('Send Post Fields ');
        echo '<pre>' . __LINE__ . '***' . print_r($options[CURLOPT_POSTFIELDS], true) . '</pre>';
      }
    }

    /**
     * @inheritdoc
     */
    public function afterRequest(\Fiv\Parser\Request $request) {
      $this->showLine('Response Header');
      $responseHeader = preg_replace("!^([A - z])!im", " << $1", $request->getResponseHeader());
      echo $responseHeader;

      if ($this->getShowBodyStatus()) {
        $this->showLine('Response Body');
        echo $request->getResponseBody();
        $this->showLine('---');
      }
    }

    /**
     * @param string $text
     */
    protected function showLine($text) {
      echo "\n\n" . str_repeat('=', 10) . ' ' . $text . ' ' . str_repeat('=', 50 - strlen($text)) . "\n";
    }

    /**
     * @return bool
     */
    public function getShowBodyStatus() {
      return $this->showBodyStatus;
    }

    /**
     * @param boolean $showBodyStatus
     * @return $this
     */
    public function setShowBodyStatus($showBodyStatus) {
      $this->showBodyStatus = $showBodyStatus;
      return $this;
    }

  }

