<?php

  namespace Fiv\Parser\Cache;

  /**
   * @author Ivan Shcherbak <dev@funivan.com> 6/26/14
   */
  class FileCache implements CacheInterface {

    /**
     * By default we do not store POST request in cache
     *
     * @var bool
     */
    protected $storePostRequest = false;

    /**
     * @var string
     */
    protected $fileDirectory = '/tmp/';

    /**
     * @var string
     */
    protected $filePrefix = 'cache';


    /**
     * @inheritdoc
     */
    public function storeRequestData($type, $key, \Fiv\Parser\Cache\Data $data) {
      $this->validateRequestType($type);

      if (CacheInterface::TYPE_POST == $type and $this->storePostRequest == false) {
        return false;
      }
      $file = $this->getFilePath($key);
      return file_put_contents($file, serialize($data));
    }

    /**
     * @inheritdoc
     */
    public function getRequestData($type, $key) {
      $this->validateRequestType($type);

      if (CacheInterface::TYPE_POST == $type and $this->storePostRequest == false) {
        return null;
      }

      $file = $this->getFilePath($key);

      if (!is_file($file)) {
        return null;
      }

      $data = file_get_contents($file);
      return unserialize($data);
    }

    /**
     * @param string $key
     * @return string
     */
    protected function getFilePath($key) {
      return $this->fileDirectory . $this->filePrefix . $key;
    }

    /**
     * @param boolean $state
     * @return $this
     */
    public function setStorePostRequest($state) {
      $this->storePostRequest = $state;
      return $this;
    }

    /**
     * @param string $fileDirectory
     * @param string $filePrefix
     * @return $this
     */
    public function setFileDirectory($fileDirectory, $filePrefix = '') {
      $this->fileDirectory = $fileDirectory;
      $this->filePrefix = $filePrefix;
      return $this;
    }


    /**
     * @param int $type
     * @throws \Fiv\Parser\Exception
     */
    protected function validateRequestType($type) {
      if (!in_array($type, array(CacheInterface::TYPE_GET, CacheInterface::TYPE_POST))) {
        throw new \Fiv\Parser\Exception("Invalid request type. Expect CacheInterface::TYPE_GET or CacheInterface::TYPE_POST");
      }
    }

  } 