<?php

  namespace ParserTests\String;

  use Fiv\Parser\Grabber;
  use Fiv\Parser\Helper;

  /**
   * @package ParserTests\String
   */
  class GrabberTest extends \ParserTests\Main {

    public function testInit() {
      $class = \Fiv\Parser\Grabber::init();

      $this->assertInstanceOf(\Fiv\Parser\Grabber::N, $class);
    }

    public function testCleanPage() {
      $content = Helper::cleanPage(" t  f");
      $this->assertEquals("t f", $content);
    }

    public function testLastPage() {
      $grabber = new Grabber();
      $grabber->request->setOption(CURLOPT_TIMEOUT, 1);
      $page = $grabber->getHtml("http://127.0.0.11");
      $this->assertEquals($page, $grabber->getLastPage());
    }
  }