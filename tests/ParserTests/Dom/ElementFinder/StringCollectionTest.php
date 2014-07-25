<?php

  namespace ParserTests\Dom\ElementFinder;

  /**
   * @author Ivan Shcherbak <dev@funivan.com> 7/8/14
   */
  class StringCollectionTest extends \ParserTests\Main {

    public function testInvalidObjectIndex() {
      $html = $this->getHtmlTestObject();
      $spanItems = $html->html('//span');
      $this->assertCount(4, $spanItems);

      $span = $spanItems->item(5);
      $this->assertEquals('', $span);

      $span = $spanItems->item(0);
      $this->assertNotEmpty($span);
    }


    public function testReplace() {
      $html = $this->getHtmlTestObject();
      $spanItems = $html->html('//span[@class]');
      $this->assertCount(3, $spanItems);

      $spanItems->replace('!<[\/]*[a-z]+>!');

      foreach ($spanItems as $index => $item) {
        $expectClass = ($index + 1) . ' r';
        $this->assertEquals($expectClass, $item);
      }

      $spanItems->replace('![a-z<\/>]!U', '0');

      foreach ($spanItems as $index => $item) {
        $expectClass = ($index + 1) . ' 0';
        $this->assertEquals($expectClass, $item);
      }

    }

    public function testMatch() {
      $html = $this->getHtmlTestObject();
      $spanItems = $html->html('//span[@class]');
      $this->assertCount(3, $spanItems);

      $tags = $spanItems->match('!(<[a-z]+>.)!');

      $this->assertCount(6, $tags);
      foreach ($tags as $index => $item) {
        $result = preg_match('!^<[b|i]!', $item);
        $this->assertTrue(!empty($result));
      }

      $tags = $spanItems->match('!<([a-z]+)>.!');

      $this->assertCount(6, $tags);
      foreach ($tags as $index => $item) {
        $result = preg_match('!^[b|i]$!', $item);
        $this->assertTrue(!empty($result));
      }

    }

  } 