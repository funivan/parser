<?php

  namespace Fiv\Parser\Dom;

  /**
   *
   *
   * @author  Ivan Scherbak <dev@funivan.com> 03.08.2011 10:25:00
   * @link    <funivan.com>
   *
   */
  class ElementFinder {

    /**
     * Html document type
     */
    const DOCUMENT_HTML = 'html';

    /**
     * Xml document type
     */
    const DOCUMENT_XML = 'xml';

    /**
     * Hide errors
     *
     * @var boolean
     */
    public $hideErrors = true;

    /**
     * html or xml
     *
     * @var string
     */
    public $docType = null;

    /**
     * @var \DOMDocument
     */
    protected $dom = null;

    /**
     * @var \DomXPath
     */
    public $xpath = null;

    /**
     * Holder for regex
     *
     * @var array
     */
    protected $matchRegex = array();

    /**
     * Save function ( saveHTML | saveXML )
     *
     * @var string
     */
    protected $isHtml = true;

    /**
     * @param bool|string $rawHtml
     */
    public function __construct($rawHtml = false) {
      $this->dom = new \DomDocument();
      $this->docType = static::DOCUMENT_HTML;

      if (!empty($rawHtml)) {
        $this->load($rawHtml);
      }
    }

    public function __destruct() {
      unset($this->dom);
      unset($this->xpath);
    }

    /**
     * @param $xpath
     * @param bool $outerHtml
     * @return \Fiv\Parser\Dom\ElementFinder\StringCollection
     */
    public function html($xpath, $outerHtml = false) {

      $items = $this->xpath->query($xpath);

      $collection = new \Fiv\Parser\Dom\ElementFinder\StringCollection();

      foreach ($items as $key => $node) {
        if ($outerHtml) {
          $html = Helper::getOuterHtml($node);
        } else {
          $html = Helper::getInnerHtml($node);
        }

        $collection->append($html);

      }

      return $collection;
    }

    /**                                 
     * You can remove elements and attributes
     * 
     * ```php               
     * $html->remove("//span/@class");
     *  
     * $html->remove("//input");   
     * ```      
     * 
     * @param string $xpath
     * @return $this
     */
    public function remove($xpath) {

      $items = $this->xpath->query($xpath);

      foreach ($items as $key => $node) {
        if ($node instanceof \DOMAttr) {
          $node->ownerElement->removeAttribute($node->name);
        } else {
          $node->parentNode->removeChild($node);
        }

      }

      return $this;
    }


    /**
     * @param $xpath
     * @return \Fiv\Parser\Dom\ElementFinder\StringCollection
     */
    public function value($xpath) {
      $items = $this->xpath->query($xpath);
      $collection = new \Fiv\Parser\Dom\ElementFinder\StringCollection();
      foreach ($items as $node) {
        $collection->append($node->nodeValue);
      }
      return $collection;
    }


    /**
     * ```
     * // return all href elements
     *
     * $page->attribute('//a/@href');
     *
     * // get title of first link
     * $page->attribute('//a[1]/@title')-item(0);
     *
     * ```
     * @param $xpath
     * @return \Fiv\Parser\Dom\ElementFinder\StringCollection
     */
    public function attribute($xpath) {
      $items = $this->xpath->query($xpath);

      $collection = new \Fiv\Parser\Dom\ElementFinder\StringCollection();
      foreach ($items as $item) {
        /** @var \DOMAttr $item */
        $collection->append($item->value);
      }

      return $collection;
    }

    /**
     * @param $xpath
     * @param bool $fromOuterHtml
     * @throws \Fiv\Parser\Exception
     * @return \Fiv\Parser\Dom\ElementFinder\ObjectCollection
     */
    public function object($xpath, $fromOuterHtml = false) {
      $items = $this->xpath->query($xpath);

      $collection = new \Fiv\Parser\Dom\ElementFinder\ObjectCollection();
      foreach ($items as $node) {
        /** @var \DOMElement $node */
        if ($fromOuterHtml) {
          $html = Helper::getOuterHtml($node);
        } else {
          $html = Helper::getInnerHtml($node);
        }

        $obj = new ElementFinder();
        $obj->docType = $this->docType;
        $obj->load($html);

        $collection->append($obj);
      }

      return $collection;
    }

    /**
     * @param string $xpath
     * @return \DOMNodeList
     */
    public function node($xpath) {
      return $this->xpath->query($xpath);
    }


    /**
     * @param string $xpath
     * @return \Fiv\Parser\Dom\ElementFinder\ElementCollection
     */
    public function elements($xpath) {
      $this->dom->registerNodeClass("DOMElement", "\Fiv\Parser\Dom\ElementFinder\Element");
      $nodeList = $this->xpath->query($xpath);

      $collection = new \Fiv\Parser\Dom\ElementFinder\ElementCollection();
      foreach ($nodeList as $item) {
        $collection->append($item);
      }

      return $collection;
    }


    /**
     *
     * @return string
     */
    public function __toString() {
      $result = $this->html('.')->item(0);
      return (string)$result;
    }

    /**
     * Used to load document content
     * Example:
     * $html->load($string);
     *
     * @param string $htmlCode
     * @param boolean $options (only for xml)
     * @throws \Fiv\Parser\Exception
     * @return \DomXPath
     */
    public function load($htmlCode, $options = false) {
      $htmlCode = trim($htmlCode);

      libxml_use_internal_errors($this->hideErrors);

      if (empty($htmlCode)) {
        $htmlCode = '<body><document-is-empty/></body>';
      }

      if ($this->docType == static::DOCUMENT_HTML) {
        $htmlCode = \Fiv\Parser\Dom\Helper::safeEncodeStr($htmlCode);
        $htmlCode = mb_convert_encoding($htmlCode, 'HTML-ENTITIES', "UTF-8");
        $this->dom->loadHTML($htmlCode);
      } elseif ($this->docType == static::DOCUMENT_XML) {
        $options = !empty($options) ? $options : LIBXML_NOCDATA ^ LIBXML_NOERROR;
        $this->dom->loadXML($htmlCode, $options);
      } else {
        throw new \Fiv\Parser\Exception('Doc type not valid. use xml or html');
      }

      # set new save function
      $this->isHtml = ($this->docType == static::DOCUMENT_HTML);

      # create xpath obj
      $this->xpath = new \DomXPath($this->dom);

      return $this;
    }


    /**
     * Match regex in document
     * ```php
     *  $tels = $html->match('!([0-9]{4,6})!');
     * ```
     *
     * @param string $regex
     * @param integer $i
     * @return array
     */
    public function match($regex, $i = 1) {
      $documentHtml = $this->html('.')->getFirst();
      preg_match_all($regex, $documentHtml, $matchedData);

      $elements = new \Fiv\Parser\Dom\ElementFinder\StringCollection();
      if (isset($matchedData[$i])) {
        $elements->setItems($matchedData[$i]);
        return $elements;
      } else {
        return $elements;
      }
    }


    /**
     * Replace in document and refresh it
     *
     * ```php
     *  $html->replace('!00!', '11');
     * ```
     *
     * @param string $regex
     * @param string $to
     * @return $this
     */
    public function replace($regex, $to = '') {
      $newDoc = $this->html('.', true)->getFirst();
      $newDoc = preg_replace($regex, $to, $newDoc);
      $this->load($newDoc);
      return $this;
    }

    /**
     *
     * ```php
     *  $elements = array(
     *    'link'      => '//a@href',
     *    'title'     => '//a',
     *    'shortText' => '//p[2]',
     *    'img'       => '//img/@src',
     *  );
     * $news = $html->getNodeItems('//*[@class="news"]', $params);
     * ```
     *
     * By default we get first element
     * By default we get html property of element
     * Properties to fetch can be set in path //a@rel  for rel property of tag A
     *
     * @param string $path
     * @param array $itemsParams
     * @return array
     */
    public function getNodeItems($path, array $itemsParams) {
      $result = array();
      $nodes = $this->object($path);
      foreach ($nodes as $nodeIndex => $nodeDocument) {
        $nodeValues = array();

        foreach ($itemsParams as $elementResultIndex => $elementResultPath) {
          /** @var ElementFinder $nodeDocument */
          $nodeValues[$elementResultIndex] = $nodeDocument->html($elementResultPath)->item(0);
        }
        $result[$nodeIndex] = $nodeValues;
      }

      return $result;
    }

  }