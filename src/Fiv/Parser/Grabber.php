<?php
  namespace Fiv\Parser;

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

    /**
     * @param null|Request $request
     */
    public function __construct($request = null) {

      if (empty($request)) {
        $request = new Request();
      }

      $this->request = $request;
    }

    /**
     * Initialize new grabber class
     *
     * @param null|Object $request
     * @return static
     */
    public static function init($request = null) {
      return new static($request);
    }


    /**
     *
     * @param string $url
     * @return \Fiv\Parser\Dom\ElementFinder object
     */
    public function getHtml($url) {
      $page = $this->request->get($url);
      $info = $this->request->getInfo();
      $this->lastPage = Helper::createElementFinder($page, $info->getUrl(), $info->getContentType());
      return $this->lastPage;
    }

    /**
     *
     * @param string $url Page url
     * @param array $data Array of data
     * @return \Fiv\Parser\Dom\ElementFinder object
     */
    public function postHtml($url, $data) {
      $page = $this->request->post($url, $data);
      $info = $this->request->getInfo();
      $this->lastPage = Helper::createElementFinder($page, $info->getUrl(), $info->getContentType());
      return $this->lastPage;
    }


    /**
     * @param      $formData
     * @param      $formPath
     * @param bool $checkForm
     * @return \Fiv\Parser\Dom\ElementFinder
     * @throws \Fiv\Parser\Exception
     */
    public function submitForm($formData, $formPath, $checkForm = false) {
      $page = $this->getLastPage();

      if (!$page instanceof \Fiv\Parser\Dom\ElementFinder) {
        throw new \Fiv\Parser\Exception('Property $page must be instance of \Fiv\Parser\Dom\ElementFinder');
      }

      if ($checkForm) {
        $form = $page->html($formPath)->item(0);
        if (empty($form)) {
          throw new \Fiv\Parser\Exception('Form not found in current page');
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
     * @return \Fiv\Parser\Dom\ElementFinder|null
     */
    public function getLastPage() {
      return $this->lastPage;
    }

  }