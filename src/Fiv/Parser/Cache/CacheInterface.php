<?php

  namespace Fiv\Parser\Cache;

  /**
   * @author Ivan Shcherbak <dev@funivan.com> 6/27/14
   */
  interface CacheInterface {

    CONST TYPE_GET = 1;

    CONST TYPE_POST = 2;

    /**
     * @param integer $type CacheInterface::TYPE_GET or CacheInterface::TYPE_POST
     * @param string $key
     * @param \Fiv\Parser\Cache\Data $data
     * @return boolean
     */
    public function storeRequestData($type, $key, \Fiv\Parser\Cache\Data $data);

    /**
     * @param integer $type CacheInterface::TYPE_GET or CacheInterface::TYPE_POST
     * @param string $key
     * @return \Fiv\Parser\Cache\Data
     */
    public function getRequestData($type, $key);

  } 