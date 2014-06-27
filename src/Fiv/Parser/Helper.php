<?php

  namespace Fiv\Parser;

  use Fiv\Parser\Dom\ElementFinder;

  /**
   * @author Ivan Shcherbak <dev@funivan.com> 6/12/14
   */
  class Helper {

    /**
     * Get Default data of form. Form is get by $path
     * Return key->value array where key is name of field
     *
     * @author  Ivan Scherbak <dev@funivan.com>
     * @version 12/26/12 11:18 PM
     * @param string $path Xpath to form
     * @param \Fiv\Parser\Dom\ElementFinder $page
     * @return array
     */
    public static function getDefaultFormData($path, \Fiv\Parser\Dom\ElementFinder $page) {
      $formData = array();

      # textarea
      foreach ($page->elements($path . '//textarea') as $textArea) {
        $formData[$textArea->getAttribute('name')] = $textArea->nodeValue;
      }

      # radio and checkboxes
      foreach ($page->elements($path . '//input[@checked]') as $textArea) {
        $formData[$textArea->getAttribute('name')] = $textArea->getAttribute('value');
      }

      # hidden, text, submit
      $hiddenAndTextElements = $page->elements($path . '//input[@type="hidden" or @type="text" or  @type="submit" or not(@type)]');
      foreach ($hiddenAndTextElements as $element) {
        $formData[$element->getAttribute('name')] = $element->getAttribute('value');
      }

      # select
      $selectItems = $page->object($path . '//select', true);
      foreach ($selectItems as $select) {
        $name = $select->attribute('//select/@name')->item(0);

        $firstValue = $select->value('//option[1]')->item(0);
        $selectedValue = $select->value('//option[@selected]')->item(0);

        $formData[$name] = !empty($selectedValue) ? $selectedValue : $firstValue;;
      }

      return $formData;
    }

    /**
     * Convert relative links to absolute
     *
     * @author  Ivan Scherbak <dev@funivan.com> 10/03/12
     * @version 12/26/12 11:08 PM
     * @param string $currentUrl
     * @param \Fiv\Parser\Dom\ElementFinder $page
     * @return \Fiv\Parser\Dom\ElementFinder
     */
    public static function convertLinksToAbsolute($currentUrl, \Fiv\Parser\Dom\ElementFinder $page) {

      $link = parse_url($currentUrl);

      $link['path'] = !empty($link['path']) ? $link['path'] : '/';

      $realDomain = $link['scheme'] . '://' . rtrim($link['host'], '/') . '/';
      $linkWithoutParams = $realDomain . trim($link['path'], '/');
      $linkPath = $realDomain . trim(preg_replace('!/([^/]+)$!', '', $link['path']), '/');

      $getBaseUrl = $page->attribute('//base/@href')->item(0);
      if (!empty($getBaseUrl)) {
        $getBaseUrl = rtrim($getBaseUrl, '/') . '/';
      }

      $srcElements = $page->elements('//*[@src] | //*[@href] | //form[@action]');

      foreach ($srcElements as $element) {
        if ($element->hasAttribute('src') == true) {
          $attrName = 'src';
        } elseif ($element->hasAttribute('href') == true) {
          $attrName = 'href';
        } elseif ($element->hasAttribute('action') == true and $element->tagName == 'form') {
          $attrName = 'action';
        } else {
          continue;
        }

        $oldPath = $element->getAttribute($attrName);

        # don`t change javascript in href
        if (preg_match('!^\s*javascript\s*:\s*!', $oldPath)) {
          continue;
        }
        if (empty($oldPath)) {
          # URL is empty. So current url is used
          $newPath = $currentUrl;
        } else if ((strpos($oldPath, './') === 0)) {
          # Current level
          $newPath = $linkPath . substr($oldPath, 2);
        } else if (strpos($oldPath, '//') === 0) {
          # Current level
          $newPath = $link['scheme'] . ':' . $oldPath;
        } else if ($oldPath[0] == '/') {
          # start with single slash
          $newPath = $realDomain . ltrim($oldPath, '/');
        } else if ($oldPath[0] == '?') {
          # params only
          $newPath = $linkWithoutParams . $oldPath;
        } elseif ((!preg_match('!^[a-z]+://!', $oldPath))) {
          # url without schema
          if (empty($getBaseUrl)) {
            $newPath = $linkPath . '/' . $oldPath;
          } else {
            $newPath = $getBaseUrl . $oldPath;
          }
        } else {
          $newPath = $oldPath;
        }

        $element->setAttribute($attrName, $newPath);
      }
      
      return $page;
    }

    /**
     * Simple clean function
     *
     * @version 5/4/12
     * @author  Ivan Scherbak <dev@funivan.com>
     * @param string $data
     * @return string
     */
    public static function cleanPage($data) {
      $data = str_replace(str_split("\t\n\r"), '', $data);
      $data = preg_replace('!\s{2,}!u', ' ', $data);
      return trim($data);
    }

    /**
     * Info used for charset detection
     *
     * @param string $page
     * @param string|null $affectedUrl
     * @param string|null $pageType
     * @throws \Fiv\Parser\Exception
     * @return \Fiv\Parser\Dom\ElementFinder
     */
    public static function createElementFinder($page, $affectedUrl = null, $pageType = null) {
      $defaultEncoding = 'utf-8';
      $elementFinder = new \Fiv\Parser\Dom\ElementFinder();

      $pageInfo = array(
        'charset' => null,
        'type' => null,
      );
      preg_match('!/(?<type>[a-z]+)(;\s*charset=(?<charset>[a-z0-9-]+)|)!i', strtolower($pageType), $pageInfo);

      if (!empty($pageInfo['type']) and $pageInfo['type'] == 'xml') {
        $elementFinder->docType = ElementFinder::DOCUMENT_XML;
      }

      if (empty($pageInfo['charset'])) {
        preg_match('!^\<\?xml(.*)encoding=\s*("|\')(?<charset>.*)("|\')s*\?\>!', $page, $pageInfo);
        if (!empty($pageInfo['charset'])) {
          $elementFinder->docType = ElementFinder::DOCUMENT_XML;
        }
      }

      if (empty($pageInfo['charset'])) {
        preg_match('!<meta([^>]*)charset\s*=\s*(?<charset>[^>]+)\s*[^>]*>!u', $page, $pageInfoAdditional);
        if (!empty($pageInfoAdditional['charset'])) {
          $pageInfoAdditional['charset'] = preg_replace('![\'\"].*$!', "", $pageInfoAdditional['charset']);
          $pageInfo['charset'] = strtolower(trim($pageInfoAdditional['charset'], '\'"'));
        }
      }
      if (!empty($pageInfo['charset'])) {
        $pageInfo['charset'] = trim(strtolower($pageInfo['charset']));
      }

      if (!empty($pageInfo['charset']) and $pageInfo['charset'] != $defaultEncoding) {
        $stringInUtf = @iconv($pageInfo['charset'], $defaultEncoding, $page);
        if ($stringInUtf !== false) {
          $page = $stringInUtf;
          if ($elementFinder->docType == ElementFinder::DOCUMENT_HTML) {
            $page = preg_replace('!<meta(.*)charset=(.*)>!', '', $page);
          } else {
            $page = preg_replace('!\<\?xml(.*)encoding=\s*("|\')(?<charset>.*)("|\')s*\?\>!', '<?xml$1encoding=$2' . $defaultEncoding . '$2?>', $page);
          }
        }
      }

      $elementFinder->load($page);

      # convert href and src to full path
      if ($affectedUrl) {
        \Fiv\Parser\Helper::convertLinksToAbsolute($affectedUrl, $elementFinder);
      }

      return $elementFinder;
    }

  } 