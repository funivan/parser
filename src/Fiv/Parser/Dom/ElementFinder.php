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
    public $docType = 'html';

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
     * @return \Fiv\Parser\Dom\ElementFinder\StringsCollections
     */
    public function html($xpath, $outerHtml = false) {

      $items = $this->xpath->query($xpath);

      $collection = new \Fiv\Parser\Dom\ElementFinder\StringsCollections();

      foreach ($items as $key => $node) {
        if ($outerHtml) {
          $html = Helper::getOuterHtml($node);
        } else {
          $html = Helper::getInnerHtml($node);
        }

        $collection->append(new \Fiv\Parser\Dom\ElementFinder\String($html));

      }

      return $collection;
    }

    /**
     * @param string $xpath
     * @return $this
     */
    public function remove($xpath) {

      $items = $this->xpath->query($xpath);

      foreach ($items as $key => $node) {
        $node->parentNode->removeChild($node);
      }

      return $this;
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
     * @return \Fiv\Parser\Dom\ElementFinder\StringsCollections
     */
    public function attribute($xpath) {
      $items = $this->xpath->query($xpath);

      $collection = new \Fiv\Parser\Dom\ElementFinder\StringsCollections();
      foreach ($items as $item) {
        /** @var \DOMAttr $item */
        $collection->append(new \Fiv\Parser\Dom\ElementFinder\String($item->value));
      }

      return $collection;
    }

    /**
     * @param $xpath
     * @param bool $fromOuterHtml
     * @throws \Exception
     * @return \Fiv\Parser\Dom\ElementFinder\HtmlCollection
     */
    public function object($xpath, $fromOuterHtml = false) {
      $items = $this->xpath->query($xpath);

      $collection = new \Fiv\Parser\Dom\ElementFinder\HtmlCollection();
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
     * @throws \Exception
     * @return \DomXPath
     */
    public function load($htmlCode, $options = false) {
      $htmlCode = trim($htmlCode);

      libxml_use_internal_errors($this->hideErrors);

      if (empty($htmlCode)) {
        $htmlCode = '<document-is-empty></document-is-empty>';
      }

      if ($this->docType == 'html') {
        $htmlCode = \Fiv\Parser\Dom\Helper::safeEncodeStr($htmlCode);
        $htmlCode = mb_convert_encoding($htmlCode, 'HTML-ENTITIES', "UTF-8");
        $result = $this->dom->loadHTML($htmlCode);
      } elseif ($this->docType == 'xml') {
        $options = !empty($options) ? $options : LIBXML_NOCDATA ^ LIBXML_NOERROR;
        $result = $this->dom->loadXML($htmlCode, $options);
      } else {
        throw new \Exception('Doc type not valid. use xml or html');
      }

      # set new save function
      $this->isHtml = ($this->docType == 'html');

      # create xpath obj
      $this->xpath = new \DomXPath($this->dom);

      return $this;
    }


    /**
     * Match regex in document
     * <code>
     *  $tels = $html->matchDoc('!([0-9]{4,6})!');
     * </code>
     *
     * @param string $regex
     * @param integer $i
     * @return array
     */
    public function matchDoc($regex, $i = 1) {
      $elements = $this->html('.')->getFirst();
      return $elements;
    }


    /**
     * Replace in document and refresh it
     *
     * <code>
     *  $html->replaceDoc('!00!', '11');
     * </code>
     *
     * @param string $regex
     * @param string $to
     * @return $this
     */
    public function replaceDoc($regex, $to = '') {
      $this->replace($regex, $to);
      $newDoc = $this->_outerHtml('.', 0);
      $this->load($newDoc);
      return $this;
    }

    /**
     *
     * <code>
     *  $elements = array(
     *    'link'      => '//a@href',
     *    'title'     => '//a',
     *    'shortText' => '//p[2]',
     *    'img'       => '//img/@src',
     *  );
     * $news = $html->getNodeItems('//*[@class="news"]', $params);
     * </code>
     *
     * By default we get first element
     * By default we get _html property of element
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
          $elementNode = $nodeDocument->html($elementResultPath)->item(0);

          $value = !empty($elementNode) ? $elementNode->value() : '';

          $nodeValues[$elementResultIndex] = $value;
        }
        $result[$nodeIndex] = $nodeValues;
      }

      return $result;
    }

  }