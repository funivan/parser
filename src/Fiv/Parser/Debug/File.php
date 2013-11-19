<?php

  namespace Fiv\Parser\Debug;

  use Fiv\Parser\Request;

  /**
   * @author Ivan Scherbak <dev@funivan.com>
   */
  class File implements \Fiv\Parser\Debug {

    protected $outputFile = '';

    public function __construct($outputFile = false) {
      if (empty($outputFile)) {
        $outputFile = '/tmp/' . preg_replace('!([^a-z0-9]{1,})!i', '_', get_called_class());
      }
      $this->outputFile = $outputFile;
      # create empty file name
      file_put_contents($this->outputFile, '');
    }


    /**
     * @return bool|string
     */
    public function getOutputFile() {
      return $this->outputFile;
    }

    protected function writeData($data, $titleText = false) {

      $lineId = '#' . strftime('#%s - %F %T', time());

      if ($titleText) {
        $data = str_repeat('=', 50 - strlen($titleText)) . ' ' . $titleText . '/'  . PHP_EOL . '' . $data . ' ' . PHP_EOL . str_repeat('=', 50 - strlen($titleText)) . ' /' . $titleText . PHP_EOL;
      }

      $data = $lineId . PHP_EOL . $data . PHP_EOL;

      file_put_contents($this->outputFile, $data, FILE_APPEND);
      return $this;
    }

    public function beforeRequest(Request $request) {

      $options = $request->getOptions();
      $startSymbol = ">> ";

      # prepare header for output
      if (!empty($options[CURLOPT_HTTPHEADER])) {
        $requestHeader = implode(PHP_EOL . $startSymbol, $options[CURLOPT_HTTPHEADER]);
      } else {
        $requestHeader = '';
      }

      $requestHeader = $startSymbol . preg_replace("!^([^:]+)\s*:\s*!mi", "$1 : ", $requestHeader);

      $this->writeData($requestHeader, 'Request Header');

      if (!empty($options[CURLOPT_POSTFIELDS])) {
        $this->writeData(print_r($options[CURLOPT_POSTFIELDS], 'Send Post Fields '));
      }
    }

    public function afterRequest(Request $request) {
      $responseHeader = preg_replace("!^([A-z])!im", "<< $1", $request->getResponseHeader());
      $this->writeData($responseHeader, 'Response Header');

      $this->writeData($request->getResponseBody(), 'Response Body');
    }

  }

