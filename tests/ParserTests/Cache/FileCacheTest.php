<?php

  namespace ParserTests\Cache;

  /**
   * @author Ivan Shcherbak <dev@funivan.com> 6/27/14
   */
  class FileCacheTest extends \ParserTests\Main {

    public function testGetFileCache() {
      $request = new \Fiv\Parser\Request();
      $fileCache = new \Fiv\Parser\Cache\FileCache();
      $prefix = 'cache-get-' . md5(time()) . '-';
      $directory = '/tmp/';

      $fileCache->setFileDirectory($directory, $prefix);

      $request->setCacheAdapter($fileCache);

      $data = $request->get('http://localhost/');
      $this->assertFalse($request->isCached());
      $this->assertNotEmpty($data);

      $secondData = $request->get('http://localhost/');
      $this->assertTrue($request->isCached());
      $this->assertNotEmpty($secondData);

      $this->assertEquals($data, $secondData);

      $this->removeCachedFiles($directory . $prefix);
    }


    public function testPostWithoutCache() {
      $request = new \Fiv\Parser\Request();
      $fileCache = new \Fiv\Parser\Cache\FileCache();
      $prefix = 'cache-post-' . md5(time()) . '-';
      $directory = '/tmp/';
      $fileCache->setFileDirectory($directory, $prefix);

      $request->setCacheAdapter($fileCache);
      $request->post('http://localhost/', ['a']);
      $this->assertFalse($request->isCached());

      $request->post('http://localhost/', ['a']);
      $this->assertFalse($request->isCached());

      $this->removeCachedFiles($directory . $prefix);
    }

    public function testPostWithCache() {

      $request = new \Fiv\Parser\Request();
      $fileCache = new \Fiv\Parser\Cache\FileCache();
      $filePrefix = 'cache-post-' . md5(time()) . '-';
      $directory = '/tmp/';
      $fileCache->setFileDirectory($directory, $filePrefix);
      $fileCache->setStorePostRequest(true);

      $request->setCacheAdapter($fileCache);
      $data = $request->post('http://localhost/', ['a']);
      $info = $request->getInfo();
      $this->assertFalse($request->isCached());

      $request->post('http://localhost/', ['b']);
      $this->assertFalse($request->isCached());

      $cachedData = $request->post('http://localhost/', ['a']);
      $this->assertTrue($request->isCached());

      $cachedInfo = $request->getInfo();

      $this->assertEquals($data, $cachedData);
      $this->assertEquals($info, $cachedInfo);

      $this->removeCachedFiles($directory . $filePrefix);
    }

    /**
     * @expectedException \Fiv\Parser\Exception
     */
    public function testInvalidCacheRequestType() {
      $cache = new \Fiv\Parser\Cache\FileCache();
      $cache->getRequestData('123', 'a');
    }

    /**
     * @param $prefix
     */
    protected function removeCachedFiles($prefix) {
      $files = glob($prefix . '*');
      foreach ($files as $filePath) {
        unlink($filePath);
      }
    }

  } 