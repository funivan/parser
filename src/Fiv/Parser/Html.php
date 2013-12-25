<?php

  namespace Fiv\Parser;

  /**
   *
   * @method Html _del ($selector) Delete elements in document
   * @method string _val ($selector) Return nodeValue
   * @method string _html ($selector) Return inner html of elements
   * @method string _outerHtml ($selector) Return outer html of elements
   * @method Html _get ($selector) Return array of objects based in inner html
   * @method Html _outerGet ($selector) Return array of Fiv_Html objects based on outer html
   * @method DOMNodeList _obj ($selector)
   * @method array _attributes ($selector) Return attributes of elements
   * @method array _properties ($selector) Return properties of elements
   *
   * @author  Ivan Scherbak <dev@funivan.com> 03.08.2011 10:25:00
   * @link    <funivan.com>
   *
   */
  class Html {

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
     * @var DOMDocument
     */
    private $dom = null;

    /**
     * @var \DomXPath
     */
    public $xpath = null;

    /**
     * Holder for regex
     *
     * @var array
     */
    private $matchRegex = array();

    /**
     * Save function ( saveHTML | saveXML )
     *
     * @var string
     */
    private $saveFunction = 'saveHTML';

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
     *
     * @param string $name
     * @param array  $arguments
     * @return Html
     */
    public function __call($name, $arguments) {

      $indexOfElement = isset($arguments[1]) ? $arguments[1] : null;

      $pathToElement = isset($arguments[0]) ? $arguments[0] : '.';
      $result = $this->RunQuery($name, $pathToElement, $indexOfElement);
      return $result;
    }

    /**
     *
     * @return string
     */
    public function __toString() {
      $result = $this->_html('.', 0);
      return (string)$result;
    }

    /**
     * Helpers method to extract 2 attributes from node
     * one of them is key other value
     *
     * @param string $selector
     * @param string $key
     * @param string $value
     * @return array
     */
    public function keyValue($selector, $key, $value) {
      $keys = $this->$key($selector);
      $values = $this->$value($selector);
      if (empty($values) or empty($key)) {
        return array();
      }
      return array_combine($keys, $values);
    }

    /**
     * Used to load document content
     * Example:
     * $html->load($string);
     *
     * @param string  $htmlCode
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
        $htmlCode = $this->safeEncodeStr($htmlCode);
        $htmlCode = mb_convert_encoding($htmlCode, 'HTML-ENTITIES', "UTF-8");
        $result = $this->dom->loadHTML($htmlCode);
      } elseif ($this->docType == 'xml') {
        $options = !empty($options) ? $options : LIBXML_NOCDATA ^ LIBXML_NOERROR;
        $result = $this->dom->loadXML($htmlCode, $options);
      } else {
        throw new \Exception('Doc type not valid. use xml or html');
      }

      # set new save function
      $this->saveFunction = 'save' . strtoupper($this->docType);

      # create xpath obj
      $this->xpath = new \DomXPath($this->dom);

      return $this;
    }

    /**
     *
     * @param string $attribute
     * @param string $expression (default expression)
     * @param null   $indexOfElement
     * @return type
     */
    public function RunQuery($attribute, $expression, $indexOfElement = null) {
      if (!isset($this->xpath) or !is_object($this->xpath)) {
        return false;
      }

      # get items
      $itemsObj = $this->xpath->query($expression);

      $result = array();
      # if is method
      if (!empty($itemsObj)) {
        $methodName = 'Attr' . $attribute;
        if (method_exists($this, $methodName)) {
          $result = $this->$methodName($itemsObj);
        } else {
          foreach ($itemsObj as $key => $itemObj) {
            $result[$key] = $itemObj->getAttribute($attribute);
          }
        }
      }

      $result = $this->BeforeGet($result, $indexOfElement);

      return $result;
    }

    /**
     * Run before return elements
     *
     * @param array                     $result
     * @param mixed (boolean | integer) $indexOfElement
     * @return mixed (array | string | false | DOMNodeList | DOMElement)
     */
    private function BeforeGet($result, $indexOfElement = null) {
      if ($indexOfElement !== null) {
        if ($result instanceof DOMNodeList) {
          $data = $result->item($indexOfElement);
        } else {
          $data = isset($result[$indexOfElement]) ? $result[$indexOfElement] : false;
        }
        unset($result);
        $result = $data;
      }

      if (!empty($this->matchRegex)) {
        foreach ($this->matchRegex as $params) {
          $methodName = $params['fn'];
          if (is_array($result)) {
            foreach ($result as $k => $val) {
              $result[$k] = $this->$methodName($val, $params);
            }
          } else {
            $result = $this->$methodName($result, $params);
          }
        }
        $this->matchRegex = array();
      }

      return $result;
    }

    /**
     * <code>
     * $table = $html->match('!(\d{4,})!')->_html('//table');
     * $table = $html->match('!(\d{4,})!', 3, 'all')->_html('//table');
     * </code>
     * @param string  $regex
     * @param integer $i
     * @param bool    $matchMethod
     * @return $this
     */
    public function match($regex, $i = 1, $matchMethod = false) {
      $matchMethod = !empty($matchMethod) ? ('_' . $matchMethod) : '';
      $this->matchRegex[] = array('regex' => $regex, 'i' => $i, 'fn' => '_preg_match' . $matchMethod);
      return $this;
    }

    /**
     * Match regex in document
     * <code>
     *  $tels = $html->matchDoc('!([0-9]{4,6})!');
     * </code>
     *
     * @param string  $regex
     * @param integer $i
     * @return array
     */
    public function matchDoc($regex, $i = 1) {
      $this->match($regex, $i);
      $elements = $this->_html('.', 0);
      return $elements;
    }

    /**
     * <code>
     *  $table = $replace->replace('!00!')->_html('//table');
     * </code>
     *
     * @param string $regex
     * @param string $to
     * @return $this
     */
    public function replace($regex, $to = '') {
      $this->matchRegex[] = array('regex' => $regex, 'to' => $to, 'fn' => '_preg_replace');
      return $this;
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
     * @param string $value
     * @param array  $params
     * @return array
     */
    private function _preg_match_all($value, $params) {
      preg_match_all($params['regex'], $value, $match);
      if (isset($match[$params['i']])) {
        return $match[$params['i']];
      } else {
        return array();
      }
    }

    /**
     *
     * @param string $value
     * @param array  $params
     * @return string
     */
    private function _preg_match($value, $params) {
      preg_match($params['regex'], $value, $match);
      if (isset($match[$params['i']])) {
        return $match[$params['i']];
      } else {
        return '';
      }
    }

    /**
     * @param string $value
     * @param array  $params
     * @return array
     */
    private function _preg_replace($value, $params) {
      $value = preg_replace($params['regex'], $params['to'], $value);
      return $value;
    }

    /**
     * Magic method to get objects DOMNodeList
     *
     * <code>
     * $td = $html->_obj('.//td');
     * foreach($td as $el){
     *    echo $el->nodeValue;
     * }
     * </code>
     *
     * @param DOMNodeList $objects
     * @return DOMNodeList
     */
    protected function Attr_obj($objects) {
      return $objects;
    }

    /**
     *
     * @param DOMNodeList $objects
     * @return Html[]
     */
    protected function Attr_get($objects) {
      $html = $this->Attr_html($objects);
      $tempHtml = $this->createNodesFromHtml($html);
      return $tempHtml;
    }

    /**
     *
     * @param DOMNodeList $objects
     * @return Html[]
     */
    protected function Attr_outerGet($objects) {
      $html = $this->Attr_outerHtml($objects);
      $tempHtml = $this->createNodesFromHtml($html);
      return $tempHtml;
    }

    /**
     *
     * @todo make clone of properties
     *
     * @param array $html
     * @return array
     */

    private function createNodesFromHtml($html) {
      $result = array();
      foreach ($html as $i => $value) {
        $obj = new Html();
        $obj->docType = $this->docType;
        if (!empty($value)) {
          $obj->load($value);
        }
        $result[$i] = $obj;
      }
      return $result;
    }

    /**
     * Return inner html of elements
     *
     * <code>
     *    $td = $html->_html('//td');
     * </code>
     * @param DOMNodeList $objects
     * @return array
     */
    protected function Attr_html($objects) {
      $items = array();
      foreach ($objects as $key => $itemObj) {
        $innerHtml = '';
        $children = $itemObj->childNodes;
        foreach ($children as $child) {
          $innerHtml .= $child->ownerDocument->saveXML($child);
        }
        $items[$key] = $this->safeEncodeStr($innerHtml);
      }
      return $items;
    }

    /**
     * Return outer html content of elements
     * <code>
     * $links = $html->_outerHtml('//a');
     * </code>
     *
     * @param DOMNodeList $objects
     * @return array
     */
    protected function Attr_outerHtml($objects) {

      $items = array();
      $saveMethod = $this->saveFunction;
      foreach ($objects as $key => $itemObj) {
        $d = new \DOMDocument('1.0');
        $b = $d->importNode($itemObj->cloneNode(true), true);
        $d->appendChild($b);
        $items[$key] = $this->safeEncodeStr($d->$saveMethod());
      }

      return $items;
    }

    /**
     * Return nodeValue of elements
     * <code>
     * $td = $html->_val('//td');
     * </code>
     * @param objects $objects
     * @return array
     */
    protected function Attr_val($objects) {
      $items = array();
      foreach ($objects as $key => $itemObj) {
        $items[$key] = $itemObj->nodeValue;
      }
      return $items;
    }

    /**
     * Delete elements from document
     *
     * <code>
     *  $html->_del('//a')
     * </code>
     *
     * @param DOMNodeList $objects
     * @return $this
     */
    protected function Attr_del($objects) {
      if (!empty($objects)) {
        foreach ($objects as $el) {
          $el->parentNode->removeChild($el);
        }
      }
      return $this;
    }

    /**
     * Method to get attributes of elements.
     * Return 2 dimension array
     *
     * <code>
     * $attrs = $html->_attributes('//p/a');
     * </code>
     *
     * @author vkey <dev@vkey.org>
     * @param DOMNodeList $objects
     * @return array
     */
    protected function Attr_attributes($objects) {
      $items = array();
      foreach ($objects as $key => $itemObj) {
        $attributes = $itemObj->attributes;
        $items[$key] = array();
        if (!is_null($attributes)) {
          foreach ($attributes as $attr) {
            $items[$key][$attr->name] = $attr->value;
          }
        }
      }
      return $items;
    }

    /**
     * Method to get All properties of object
     * Additional schemaTypeInfo, tagName and nodeValue
     *
     * <code>
     *  $properties = $html->_properties('//p/a');
     * </code>
     *
     * @author Ivan Scherbak <dev@funivan.com>
     * @param DOMNodeList $objects
     * @return array
     */
    protected function Attr_properties($objects) {
      $items = array();
      foreach ($objects as $key => $itemObj) {
        $attributes = $itemObj->attributes;
        $items[$key] = array();
        if (!is_null($attributes)) {
          foreach ($attributes as $attr) {
            $items[$key][$attr->name] = $attr->value;
          }
        }
        $items[$key]['_schemaTypeInfo'] = $itemObj->schemaTypeInfo;
        $items[$key]['_tagName'] = $itemObj->tagName;
        $items[$key]['_nodeValue'] = $itemObj->nodeValue;
      }
      return $items;
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
     * @param array  $itemsParams
     * @return array
     */
    public function getNodeItems($path, array $itemsParams) {
      $result = array();
      $nodes = $this->_get($path);
      foreach ($nodes as $nodeIndex => $nodeDocument) {
        $nodeValues = array();
        foreach ($itemsParams as $elementResultIndex => $elementResultPath) {
          $elementsNode = $nodeDocument->_obj($elementResultPath);
          $value = false;
          if ($elementsNode instanceof DOMNodeList) {
            $item = $elementsNode->item(0);
            if ($item instanceof DOMElement) {
              $values = $this->Attr_html($elementsNode);
              $value = $values[0];
            } elseif (is_object($item)) {
              $value = $item->value;
            }
          }
          $nodeValues[$elementResultIndex] = $value;
        }
        $result[$nodeIndex] = $nodeValues;
      }

      return $result;
    }

    /**
     * Simple helper function for str encoding
     *
     * @param string $str
     * @return string
     */
    public function safeEncodeStr($str) {
      return preg_replace_callback("/&#([a-z\d]+);/i", function ($m) {
        $m[0] = (string)$m[0];
        $m[0] = mb_convert_encoding($m[0], "UTF-8", "HTML-ENTITIES");
        return $m[0];
      }, $str);
    }

  }