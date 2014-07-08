<?php

  namespace ParserTests\Dom\ElementFinder;

  /**
   * @author Ivan Shcherbak <dev@funivan.com> 7/8/14
   */
  class ElementCollectionTest extends \ParserTests\Main {

    public function testAttributes() {
      $html = $this->getHtmlTestObject();

      $spanElements = $html->elements("//span");
      $spanItems = $spanElements->getAttributes();

      $this->assertCount(count($spanElements), $spanItems);
    }

    public function testItem() {
      $html = $this->getHtmlTestObject();

      $spanElements = $html->elements("//span");
      $this->assertCount(4, $spanElements);
      $this->assertNull($spanElements->item(20));

      $this->assertInstanceOf('\Fiv\Parser\Dom\ElementFinder\Element', $spanElements->item(0));

    }
  } 