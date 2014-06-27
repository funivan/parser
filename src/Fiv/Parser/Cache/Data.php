<?php

  namespace Fiv\Parser\Cache;

  /**
   * @author Ivan Shcherbak <dev@funivan.com> 6/27/14
   */
  class Data {

    protected $header = '';

    protected $body = '';

    protected $info = '';

    protected $error = '';

    /**
     * Store data from request
     *
     * @param \Fiv\Parser\Request $request
     * @return Data
     */
    public static function initFromRequest(\Fiv\Parser\Request $request) {
      $cacheData = new Data();
      $cacheData->header = $request->getResponseHeader();
      $cacheData->body = $request->getResponseBody();
      $cacheData->info = $request->getInfo();
      $cacheData->error = $request->getError();

      return $cacheData;
    }

    /**
     * @return string
     */
    public function getBody() {
      return $this->body;
    }

    /**
     * @return string
     */
    public function getError() {
      return $this->error;
    }

    /**
     * @return string
     */
    public function getHeader() {
      return $this->header;
    }

    /**
     * @return string
     */
    public function getInfo() {
      return $this->info;
    }

  } 