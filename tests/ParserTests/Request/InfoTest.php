<?php

  namespace ParserTests\Request;

  /**
   * @author Ivan Shcherbak <dev@funivan.com> 6/27/14
   */
  class InfoTest extends \ParserTests\Main {

    /**
     * @expectedException \Fiv\Parser\Exception
     */
    public function testInvalidMethod() {
      $requestInfo = new \Fiv\Parser\Request\Info();
      $requestInfo->getInvalidInfoMethod();
    }

    /**
     * @expectedException \Fiv\Parser\Exception
     */
    public function testInvalidData() {
      $requestInfo = new \Fiv\Parser\Request\Info();
      $requestInfo->setData(new \stdClass());
    }

  } 