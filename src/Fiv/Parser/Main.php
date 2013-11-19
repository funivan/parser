<?php
  namespace Fiv\Parser;

  /**
   * More powerful of curl and html classes
   *
   * @author  Ivan Scherbak <dev@funivan.com>
   */
  class Main {

    /**
     * @var null|Request
     */
    public $request = null;

    /**
     * @var Html|null
     */
    protected $lastPage = null;

    public function __construct() {
      $this->init();
    }

    public function init() {
      $this->request = new Request();
    }


    /**
     *
     * @version 5/4/12
     * @author  Ivan Scherbak <dev@funivan.com>  5/4/12
     * @version 10/24/12
     * @param string  $url
     * @param boolean $follow
     * @param integer $level
     * @return Html object
     */
    public function getHtml($url, $follow = true, $level = 5) {
      $page = $this->request->get($url, $follow, $level);
      $this->lastPage = static::createHtmlObj($page, $this->request->getInfo());
      return $this->lastPage;
    }

    /**
     *
     * @author  Ivan Scherbak <dev@funivan.com> 5/4/12
     * @version 10/24/12
     * @param string                $url
     * @param mixed (array, string) $post
     * @param boolean               $follow
     * @param integer               $level
     * @return Html object
     */
    public function postHtml($url, $post, $follow = true, $level = 5) {
      $page = $this->request->post($url, $post, $follow, $level);
      $this->lastPage = static::createHtmlObj($page, $this->request->getInfo());
      return $this->lastPage;
    }

    /**
     * Info used for charset detection
     *
     * @author  Ivan Scherbak <dev@funivan.com>
     * @version 10/03/12
     * @param string   $page
     * @param stdClass $info
     * @return Html
     */
    public static function createHtmlObj($page, $info) {
      $defaultEncoding = 'utf-8';
      $html = new Html();

      $type = strtolower($info->content_type);

      preg_match('!/(?<type>[a-z]+)(;\s*charset=(?<charset>[a-z0-9-]+)|)!i', $type, $pageInfo);
      if (!empty($pageInfo['type']) and $pageInfo['type'] == 'xml') {
        # document is xml
        $html->docType = 'xml';
        preg_match('!\<\?xml(.*)encoding=\s*("|\')(?<charset>.*)("|\')s*\?\>!', $page, $pageInfo);
      }

      if (empty($pageInfo['charset'])) {
        preg_match('!<meta([^>]*)charset\s*=\s*(?<charset>[^\s]+)\s[^>]*>!', $page, $pageInfoAdditional);
        if (!empty($pageInfoAdditional['charset'])) {
          $pageInfo['charset'] = trim($pageInfoAdditional['charset'], '\'"');
        }
      }
      if (!empty($pageInfo['charset']) and $pageInfo['charset'] = trim(strtolower($pageInfo['charset'])) and $pageInfo['charset'] != $defaultEncoding) {
        $stringInUtf = iconv($pageInfo['charset'], $defaultEncoding, $page);
        if ($stringInUtf !== false) {
          $page = $stringInUtf;
          if ($html->docType != 'xml') {
            $page = preg_replace('!<meta(.*)charset=(.*)>!', '', $page);
          } else {
            $page = preg_replace('!\<\?xml(.*)encoding=\s*("|\')(?<charset>.*)("|\')s*\?\>!', '<?xml$1encoding=$2' . $defaultEncoding . '$2?>', $page);
          }
        }
      }

      $html->load($page);

      # convert href and src to full path
      if (isset($info->url)) {
        $html = self::convertLinksToAbsolute($info->url, $html);
      }

      return $html;
    }

    /**
     * Get Default data of form. Form is get by $path
     * Return key->value array where key is name of field
     *
     * @author  Ivan Scherbak <dev@funivan.com>
     * @version 12/26/12 11:18 PM
     * @param string $path
     * @param Html   $page
     * @return array
     */
    public static function getDefaultFormData($path, Html $page) {
      $formData = array();

      # textarea
      $textarea = $page->keyValue($path . '//textarea', 'name', '_val');
      $formData = array_merge($formData, $textarea);

      # radio and checkboxes
      $checked = $page->keyValue($path . '//input[@checked]', 'name', 'value');
      $formData = array_merge($formData, $checked);

      # hidden, text, submit
      $hiddenAndText = $page->keyValue($path . '//input[@type="hidden" or @type="text" or  @type="submit" or not(@type)]', 'name', 'value');
      $formData = array_merge($formData, $hiddenAndText);

      # select
      $selectItems = $page->_get('.//select');

      $selectNames = $page->name('.//select');
      foreach ($selectItems as $k => $select) {
        $firstValue = $select->value('.//option[1]', 0);
        $selectedValue = $select->value('.//option[@selected]', 0);
        $value = !empty($selectedValue) ? $selectedValue : $firstValue;
        $name = $selectNames[$k];
        $formData[$name] = $value;
      }

      return $formData;
    }

    /**
     * @param      $formData
     * @param      $formPath
     * @param bool $checkForm
     * @return Html
     * @throws \Exception
     */
    public function submitForm($formData, $formPath, $checkForm = false) {
      $page = $this->lastPage;

      if (!$page instanceof Html) {
        throw new \Exception('Property $page must be instance of Html');
      }

      if ($checkForm) {
        $form = $page->_html($formPath, 0);
        if (empty($form)) {
          throw new \Exception('Form not found in current page');
        }
      }

      # get form method
      $formMethod = $page->method($formPath, 0);
      if (strtolower($formMethod) == 'post') {
        $requestMethod = 'postHtml';
      } else {
        $requestMethod = 'getHtml';
      }

      # prepare data to send
      $defaultFormData = self::getDefaultFormData($formPath, $page);
      $postFormData = array_merge($defaultFormData, $formData);
      foreach ($postFormData as $name => $value) {
        if (is_null($value)) {
          unset($postFormData[$name]);
        }
      }
      # get action url
      $actionUrl = $page->action($formPath, 0);
      if (empty($actionUrl)) {
        $actionUrl = $this->request->getInfo()->url;
      }

      return $this->$requestMethod($actionUrl, $postFormData);
    }

    /**
     * Convert relative links to absolute
     *
     * @author  Ivan Scherbak <dev@funivan.com> 10/03/12
     * @version 12/26/12 11:08 PM
     * @param string $currentUrl
     * @param Html   $page
     * @return Html
     */
    public static function convertLinksToAbsolute($currentUrl, Html $page) {

      if (empty($currentUrl)) {
        return $page;
      }

      $link = parse_url($currentUrl);

      $link['path'] = !empty($link['path']) ? $link['path'] : '/';

      $realDomain = $link['scheme'] . '://' . rtrim($link['host'], '/') . '/';
      $linkWithoutParams = $realDomain . trim($link['path'], '/');
      $linkPath = $realDomain . trim(preg_replace('!/([^/]+)$!', '', $link['path']), '/');

      $getBaseUrl = $page->href('//base', 0);
      if (!empty($getBaseUrl)) {
        $getBaseUrl = rtrim($getBaseUrl, '/') . '/';
      }

      $srcElements = $page->_obj('//*[@src] | //*[@href] | //form[@action]');

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
      $data = str_replace(str_split('\t\n\r'), '', $data);
      $data = preg_replace('!\s{2,}!u', ' ', $data);
      return trim($data);
    }

    /**
     * @return Html|null
     */
    public function getLastPage() {
      return $this->lastPage;
    }

  }