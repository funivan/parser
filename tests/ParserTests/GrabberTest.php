<?php

  namespace ParserTests\String;

  use Fiv\ParserV1\Grabber;

  /**
   * @package ParserTests\String
   */
  class GrabberTest extends \ParserTests\Main {

    public function testInit() {
      $class = \Fiv\ParserV1\Grabber::init();

      $this->assertInstanceOf(\Fiv\ParserV1\Grabber::N, $class);
    }

    public function testCleanPage() {
      $content = Grabber::cleanPage(" t  f");
      $this->assertEquals("t f", $content);
    }

    public function testLastPage() {
      $grabber = new Grabber();
      $grabber->request->setOption(CURLOPT_TIMEOUT, 1);
      $page = $grabber->getHtml("http://127.0.0.11");
      $this->assertEquals($page, $grabber->getLastPage());
    }
  }