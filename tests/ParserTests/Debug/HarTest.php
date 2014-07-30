<?php

  namespace ParserTests\Debug;

  /**
   * @author Ivan Shcherbak <dev@funivan.com> 7/30/14
   */
  class HarTest extends \ParserTests\Main {

    public function testHarLog() {

      $request = new \Fiv\Parser\Request();
      $outputFile = "/tmp/" . md5(time()) . "-" . time() . "_log.har";
      $harAdapter = new \Fiv\Parser\Debug\Har($outputFile);
      $request->setDebugClass($harAdapter);

      $request->get("http://localhost/");

      $data = file_get_contents($outputFile);

      $this->assertNotEmpty($data);
      $jsonData = json_decode($data);
      $this->assertInstanceOf('\stdClass', $jsonData);

    }

  } 