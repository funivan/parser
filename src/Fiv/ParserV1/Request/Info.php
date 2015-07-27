<?php

  namespace Fiv\ParserV1\Request;

  /**
   * @method getUrl()
   * @method getContentType()
   * @method getHttpCode()
   * @method getHeaderSize()
   * @method getRequestSize()
   * @method getFileTime()
   * @method getSslVerifyResult()
   * @method getRedirectCount()
   * @method getTotalTime()
   * @method getNameLookupTime()
   * @method getConnectTime()
   * @method getPreTransferTime()
   * @method getSizeUpload()
   * @method getSizeDownload()
   * @method getSpeedDownload()
   * @method getSpeedUpload()
   * @method getDownloadContentLength()
   * @method getUploadContentLength()
   * @method getStartTransferTime()
   * @method getRedirectTime()
   * @method getCertInfo()
   * @method getRequestHeader()  This is only set if the CURLINFO_HEADER_OUT is set by a previous call to curl_setopt())
   * @method getPrimaryIp()
   *
   * @author Ivan Shcherbak <dev@funivan.com> 12/4/13
   */
  class Info {

    protected $data = array();

    protected $dataMap = array(
      "url" => "url",
      "contenttype" => "content_type",
      "httpcode" => "http_code",
      "headersize" => "header_size",
      "requestsize" => "request_size",
      "filetime" => "filetime",
      "sslverifyresult" => "ssl_verify_result",
      "redirectcount" => "redirect_count",
      "totaltime" => "total_time",
      "namelookuptime" => "namelookup_time",
      "connecttime" => "connect_time",
      "pretransfertime" => "pretransfer_time",
      "sizeupload" => "size_upload",
      "sizedownload" => "size_download",
      "speeddownload" => "speed_download",
      "speedupload" => "speed_upload",
      "downloadcontentlength" => "download_content_length",
      "uploadcontentlength" => "upload_content_length",
      "starttransfertime" => "starttransfer_time",
      "redirecttime" => "redirect_time",
      "certinfo" => "certinfo",
      "requestheader" => "request_header",
      "primaryip" => "primary_ip",
    );

    public function __construct($data = array()) {
      if (!empty($data)) {
        $this->setData($data);
      }
    }

    public function __call($name, $arguments) {
      $name = strtolower($name);
      $methodName = substr($name, 3);
      if (strpos($name, 'get') === 0 and isset($this->dataMap[$methodName])) {
        return isset($this->data[$methodName]) ? $this->data[$methodName] : null;
      }

      throw new \Exception('Invalid method name #' . $name);
    }


    public function setData($data) {

      if (!is_array($data)) {
        throw new \Exception('Invalid data type in ' . get_called_class());
      }
      $this->data = array();

      foreach ($data as $key => $value) {
        $methodName = array_search($key, $this->dataMap);
        $methodName = !empty($methodName) ? $methodName : $key;
        $keyName = strtolower($methodName);
        $this->data[$keyName] = $value;
      }

      return $this;
    }

    public function getDataMap() {
      return $this->dataMap;
    }
  }