<?php

  namespace ParserTests\Cache;

  /**
   * @author Ivan Shcherbak <dev@funivan.com> 6/27/14
   */
  class CacheTest extends \ParserTests\Main {

    public function testGetFileCache() {
      $request = new \Fiv\Parser\Request();
      $fileCache = new \Fiv\Parser\Cache\FileCache();
      $fileCache->setFileDirectory('/tmp/', 'cache-get-' . md5(time()) . '-');

      $request->setCacheAdapter($fileCache);

      $data = $request->get('http://localhost/');
      $this->assertFalse($request->isCached());
      $this->assertNotEmpty($data);

      $secondData = $request->get('http://localhost/');
      $this->assertTrue($request->isCached());
      $this->assertNotEmpty($secondData);

      $this->assertEquals($data, $secondData);
    }


    public function testPostWithoutCache() {
      $request = new \Fiv\Parser\Request();
      $fileCache = new \Fiv\Parser\Cache\FileCache();
      $fileCache->setFileDirectory('/tmp/', 'cache-post-' . md5(time()) . '-');

      $request->setCacheAdapter($fileCache);
      $request->post('http://localhost/', ['a']);
      $this->assertFalse($request->isCached());

      $request->post('http://localhost/', ['a']);
      $this->assertFalse($request->isCached());

    }

    public function testPostWithCache() {

      $request = new \Fiv\Parser\Request();
      $fileCache = new \Fiv\Parser\Cache\FileCache();
      $fileCache->setFileDirectory('/tmp/', 'cache-post-' . md5(time()) . '-');
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

    }

    /**
     * @expectedException \Fiv\Parser\Exception
     */
    public function testInvalidCacheRequestType() {
      $cache = new \Fiv\Parser\Cache\FileCache();
      $cache->getRequestData('123', 'a');
    }

  } 