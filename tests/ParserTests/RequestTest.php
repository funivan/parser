<?php

  namespace ParserTests;

  /**
   * @author Ivan Shcherbak <dev@funivan.com> 6/3/14
   */
  class RequestTest extends \ParserTests\Main {

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetInvalidHeaders() {
      $request = new \Fiv\Parser\Request();
      $request->setHeaders(123);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetInvalidStringRawHeaders() {
      $request = new \Fiv\Parser\Request();
      $request->setRawHeaders("asdfasdf");
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetInvalidRawHeaders() {
      $request = new \Fiv\Parser\Request();
      $request->setRawHeaders(false);
    }

    public function testSetRawHeaders() {

      $request = new \Fiv\Parser\Request();
      $rawHeaders = "Accept-Language:ru-RU,ru;q.9,en;q.8 
          Accept-Charset:iso-8859-1, utf-8, utf-16, *;q.1     
      ";

      $request->cleanHeaders();

      $headers = $request->getOption(CURLOPT_HEADER);
      $this->assertEmpty($headers);

      $request->setRawHeaders($rawHeaders);

      $this->assertCount(2, $request->getHeaders());

      $rawHeaders = "Accept-Language:ru-RU,ru;q.9,en;q.8";

      $request->cleanHeaders();
      $request->setRawHeaders($rawHeaders);

      $this->assertCount(1, $request->getHeaders());

    }

    public function testPost() {
      $request = new \Fiv\Parser\Request();
      $request->post('http://127.0.0.1/', ['a']);
      $this->assertEmpty($request->getError());
    }

  } 