<?php

  namespace Fiv\Parser\Request;

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
   *
   * @author Ivan Shcherbak <dev@funivan.com> 12/4/13
   */
  class Info {

    protected $data = array();

    protected $dataMap = array(
      "url" => "url",
      "content_type" => "contentType",
      "http_code" => "httpCode",
      "header_size" => "headerSize",
      "request_size" => "requestSize",
      "filetime" => "filetime",
      "ssl_verify_result" => "sslVerifyResult",
      "redirect_count" => "redirectCount",
      "total_time" => "totalTime",
      "namelookup_time" => "namelookupTime",
      "connect_time" => "connectTime",
      "pretransfer_time" => "pretransferTime",
      "size_upload" => "sizeUpload",
      "size_download" => "sizeDownload",
      "speed_download" => "speedDownload",
      "speed_upload" => "speedUpload",
      "download_content_length" => "downloadContentLength",
      "upload_content_length" => "uploadContentLength",
      "starttransfer_time" => "startTransferTime",
      "redirect_time" => "redirectTime",
      "certinfo" => "certinfo",
      "request_header" => "requestHeader",
    );

    public function __construct($data = array()) {
      if (!empty($data)) {
        $this->setData($data);
      }
    }

    public function __call($name, $arguments) {
      $name = strtolower($name);
      if (array_key_exists($name, $this->data)) {
        return $this->data[$name];
      } else {
        throw new \Exception('Invalid method name #' . $name);
      }
    }


    public function setData($data) {

      if (!is_array($data)) {
        throw new \Exception('Invalid data type in ' . get_called_class());
      }
      $this->data = array();

      foreach ($data as $key => $value) {
        if (!is_string($value) and !is_numeric($value) or !is_bool($value)) {
          throw new \Exception('Invalid value: ' . $value);
        }
        if (!isset($this->dataMap[$key])) {
          throw new \Exception('You can not set data with key #' . $key);
        }

        $keyName = "get" . strtolower($this->dataMap[$key]);
        $this->data[$keyName] = $value;
      }

      return $this;
    }

    public function getDataMap() {
      return $this->dataMap;
    }
  }