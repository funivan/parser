<?php

  namespace Fiv\Parser\Debug;

  /**
   * @author Ivan Shcherbak <dev@funivan.com> 7/30/14
   */
  class Har implements DebugInterface {

    /**
     * @var string
     */
    protected $outputFile = '';


    /**
     * @var \stdClass();
     */
    protected $log = null;

    /**
     * @var \stdClass();
     */
    protected $lastPage = null;

    /**
     * @param string $outputFile
     */
    public function __construct($outputFile = '') {
      if (empty($outputFile)) {
        $outputFile = '/tmp/' . preg_replace('!([^a-z0-9]{1,})!i', '_', get_called_class()) . '.log';
      }
      $this->outputFile = $outputFile;
      # create empty file
      file_put_contents($this->outputFile, '');

      $this->log = new \stdClass();
      $this->log->version = '1.0';
      $this->log->creator = (object)array("name" => "Parser", "version" => "~");
      $this->log->browser = (object)array("name" => "Parser", "version" => "~");
      $this->log->pages = array();

    }


    /**
     * @return bool|string
     */
    public function getOutputFile() {
      return $this->outputFile;
    }

    protected function setPage() {

    }

    /**
     * @return $this
     */
    protected function writeData() {
      $data = json_encode((object)array("log" => $this->log));
      file_put_contents($this->outputFile, $data);
      return $this;
    }

    /**
     * @inheritdoc
     */
    public function beforeRequest(\Fiv\Parser\Request $request) {

      $page = new \stdClass();
      $page->startedDateTime = date("c");
      $pageId = "page_" . (count($this->log->pages) + 1);

      $page->id = $pageId;
      $page->title = "";
      $page->comment = "";
      $page->pageTimings = new \stdClass();
      $page->pageTimings->onContentLoad = 0;
      $page->pageTimings->onLoad = 0;

      $entry = new \stdClass();

      $entry->time = "~~~";
      $entry->url = "~~~";
      $entry->httpVersion = "~~~";

      $entry->pageref = $pageId;
      $entry->startedDateTime = $page->startedDateTime;

      $entry->request = new \stdClass();
      $entry->request->method = "~";
      $entry->request->cookies = array();
      $entry->request->headers = array();

      $entry->request->url = "";
      $entry->request->httpVersion = "";
      $entry->request->queryString = [];
      $entry->request->headersSize = "";
      $entry->request->bodySize = "";

      $entry->cache = new \stdClass();
      $entry->timings = new \stdClass();

      $entry->response = new \stdClass();
      $entry->response->cookies = array();
      $entry->response->headers = array();
      $entry->response->redirectURL = "";
      $entry->response->headersSize = 0;
      $entry->response->bodySize = 0;
      $entry->response->content = new \stdClass();
      $entry->response->size = 0;
      $entry->response->compression = 0;
      $entry->response->text = "";
      $entry->response->comment = "";

      $headers = $request->getHeaders();
      foreach ($headers as $name => $value) {
        $value = preg_replace("!^(" . $name . ")\s*:\s*!", "", $value);
        $entry->request->headers[] = (object)array("name" => $name, 'value' => $value);
      }

      $this->lastPage = new \stdClass();
      $this->lastPage->info = $page;
      $this->lastPage->entry = $entry;

      $this->log->pages[] = $page;
      $this->log->entries[] = $entry;

      $this->writeData();
    }

    /**
     * @inheritdoc
     */
    public function afterRequest(\Fiv\Parser\Request $request) {

      $responseHeader = explode("\n", $request->getResponseHeader());

      $entry = $this->lastPage->entry;
      $response = $entry->response;

      if (!empty($responseHeader[0]) and preg_match("!^(?<httpVersion>.*) (?<status>\d+) (?<statusText>.*)[\s]*$!U", $responseHeader[0], $responseStatus)) {
        $response->status = $responseStatus['status'];
        $response->httpVersion = $responseStatus['httpVersion'];
        $response->statusText = $responseStatus['statusText'];
        unset($responseStatus);
        unset($responseHeader[0]);
      }
      foreach ($responseHeader as $headerLine) {
        if (preg_match('!^(?<name>[^:]+):\s(?<value>.*)$!', $headerLine, $headerInfo)) {
          $response->headers[] = (object)array("name" => $headerInfo['name'], 'value' => $headerInfo['value']);
        }
      }
      $info = $request->getInfo();

      $requestHeader = $info->getRequestHeader();

      $entry->request->url = $info->getUrl();
      $entry->url = $info->getUrl();

      if (!empty($requestHeader[0]) and preg_match("!^(?<method>[A-Z]+) .* (?<httpVersion>[^\n]+)\n!U", $requestHeader, $requestInfo)) {
        $entry->request->method = $requestInfo['method'];
        $entry->request->httpVersion = $requestInfo['httpVersion'];
      }

      $entry->time = $info->getTotalTime() * 1000;

      $response->content->size = $info->getDownloadContentLength();
      $response->content->mimeType = preg_replace('!^([^:]+);.*$!', '$1', $info->getContentType());
      $response->content->text = $request->getResponseBody();

      $entry->timings->send = 0;
      $entry->timings->wait = 0;
      $entry->timings->receive = 0;

      $this->writeData();
    }

  } 