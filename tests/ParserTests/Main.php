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

  }