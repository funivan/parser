<?php

  namespace Fiv\Parser\Dom\ElementFinder;

  /**
   * @author Ivan Shcherbak <dev@funivan.com> 6/3/14
   * @method string offsetGet($offset);
   */
  class StringCollection extends \Fiv\Spl\Collection {

    /**
     * @param int $index
     * @return string
     */
    public function item($index) {
      if (isset($this->items[$index])) {
        return $this->items[$index];
      } else {
        return "";
      }
    }

    /**
     * @param string $regexp
     * @param string $to
     * @return $this
     */
    public function replace($regexp, $to = '') {
      foreach ($this->items as $index => $item) {
        $this->items[$index] = preg_replace($regexp, $to, $item);
      }

      return $this;
    }

  } 