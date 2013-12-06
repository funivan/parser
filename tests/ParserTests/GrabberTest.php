<?php

  namespace ParserTests\String;


  /**
   * @package ParserTests\String
   */
  class GrabberTest extends \ParserTests\Main {

    public function testInit() {
      $class = \Fiv\Parser\Grabber::init();

      $this->assertInstanceOf(\Fiv\Parser\Grabber::N, $class);
    }
  }