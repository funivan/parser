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

      $this->assertInstanceOf('\Fiv\Parser\Grabber', $class);
    }

    public function testCleanPage() {
      $content = Helper::cleanPage(" t  f");
      $this->assertEquals("t f", $content);
    }

    public function testLastPage() {
      $grabber = new Grabber();
      $grabber->getRequest()->setOption(CURLOPT_TIMEOUT, 1);
      $page = $grabber->getHtml("http://127.0.0.1");
      $this->assertEquals($page, $grabber->getLastPage());
    }
  }