<?php

  namespace ParserTests;

  /**
   * @package DemoProjectTests
   * @codeCoverageIgnore
   */
  abstract class Main extends \PHPUnit_Framework_TestCase {

    /**
     * @return string
     */
    protected function getDemoDataDirectoryPath() {
      return __DIR__ . '/../demo-data/';
    }

    /**
     * @return \Fiv\Parser\Dom\ElementFinder
     */
    public function getHtmlTestObject() {
      $fileData = file_get_contents($this->getDemoDataDirectoryPath() . '/test.html');
      $html = new \Fiv\Parser\Dom\ElementFinder($fileData);
      return $html;
    }

    /**
     * @return \Fiv\Parser\Dom\ElementFinder
     */
    public function getHtmlDataObject() {
      $fileData = file_get_contents($this->getDemoDataDirectoryPath() . '/data.html');
      $html = new \Fiv\Parser\Dom\ElementFinder($fileData);
      return $html;
    }

  }