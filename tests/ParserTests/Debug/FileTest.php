<?php

  namespace ParserTests\Debug;

  /**
   * @author Ivan Shcherbak <dev@funivan.com> 7/31/14
   */
  class FileTest extends \ParserTests\Main {

    public function testFileNameGenerate() {
      $request = new \Fiv\Parser\Request();
      $debugClass = new \Fiv\Parser\Debug\File();
      $request->setDebugAdapter($debugClass);

      $this->assertInstanceOf(get_class($debugClass), $request->getDebugAdapter());

      $file = $debugClass->getOutputFile();
      $this->assertFileExists($file);
      unlink($file);
    }
  } 