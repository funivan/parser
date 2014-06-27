<?php

  namespace ParserTests;

  /**
   * @author Ivan Shcherbak <dev@funivan.com> 6/12/14
   */
  class HelperTest extends \ParserTests\Main {

    public function testCreateFinder() {

      $rawHtmlData[] = '<?xml version="1.0" encoding="windows-1251"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ru" xml:lang="ru">
<head>   
<title>Рега</title>

';
      $rawHtmlData[] = '<head> <meta charset="utf-8" /></head>';
      $rawHtmlData[] = "<head><meta content='text/html; charset=utf-8' http-equiv='content-type' /></head>";
      $rawHtmlData[] = '<head><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head>';

      foreach ($rawHtmlData as $html) {
        $finder = \Fiv\Parser\Helper::createElementFinder($html);
        $this->assertTrue(is_object($finder));
      }

    }
    

  } 