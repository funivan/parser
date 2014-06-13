<?php
  namespace Fiv\Parser;

  use Fiv\Parser\Dom\ElementFinder;

  /**
   * More powerful of curl and html classes
   *
   * ```
   * // get copyright text from page
   * $author = \Fiv\Parser\Grabber::init()->getHtml()->_val('//*[@class="copyright"]', 0)
   * ```
   * @author  Ivan Scherbak <dev@funivan.com>
   */
  class Grabber {

    const N = __CLASS__;

    /**
     * @var null|Request
     */
    public $request = null;

    /**
     * @var ElementFinder|null
     */
    protected $lastPage = null;

    public function __construct() {
      $this->request = new Request();
    }

    /**
     * @return static
     */
    public static function init() {
      return new static();
    }


    /**
     *
     * @param string $url
     * @param boolean $follow
     * @param integer $level
     * @return ElementFinder object
     */
    public function getHtml($url, $follow = true, $level = 5) {
      $page = $this->request->get($url, $follow, $level);
      $info = $this->request->getInfo();
      $this->lastPage = Helper::createElementFinder($page, $info->getUrl(), $info->getContentType());
      return $this->lastPage;
    }

    /**
     *
     * @param string $url Page url
     * @param array $post Array of data
     * @param boolean $follow Follow location
     * @param integer $level Maximum redirect level
     * @return ElementFinder object
     */
    public function postHtml($url, $post, $follow = true, $level = 5) {
      $page = $this->request->post($url, $post, $follow, $level);
      $info = $this->request->getInfo();
      $this->lastPage = Helper::createElementFinder($page, $info->getUrl(), $info->getContentType());
      return $this->lastPage;
    }


    /**
     * @param      $formData
     * @param      $formPath
     * @param bool $checkForm
     * @return ElementFinder
     * @throws \Exception
     */
    public function submitForm($formData, $formPath, $checkForm = false) {
      $page = $this->getLastPage();

      if (!$page instanceof ElementFinder) {
        throw new \Exception('Property $page must be instance of ElementFinder');
      }

      if ($checkForm) {
        $form = $page->html($formPath)->item(0);
        if (empty($form)) {
          throw new \Exception('Form not found in current page');
        }
      }

      # get form method
      $formMethod = $page->attribute($formPath . '/@method')->item(0);
      if (strtolower($formMethod) == 'post') {
        $requestMethod = 'postHtml';
      } else {
        $requestMethod = 'getHtml';
      }

      # prepare data to send
      $defaultFormData = Helper::getDefaultFormData($formPath, $page);
      $postFormData = array_merge($defaultFormData, $formData);
      foreach ($postFormData as $name => $value) {
        if (is_null($value)) {
          unset($postFormData[$name]);
        }
      }
      # get action url
      $actionUrl = $page->attribute($formPath . '/@action')->item(0);
      if (empty($actionUrl)) {
        $actionUrl = $this->request->getInfo()->getUrl();
      }

      return $this->$requestMethod($actionUrl, $postFormData);
    }


    /**
     * @return ElementFinder|null
     */
    public function getLastPage() {
      return $this->lastPage;
    }

  }