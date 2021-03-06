<?php

  namespace Fiv\Parser\Dom\ElementFinder;

  /**
   * @author Ivan Shcherbak <dev@funivan.com> 6/3/14
   * @method \Fiv\Parser\Dom\ElementFinder offsetGet($offset);
   */
  class ObjectCollection extends \Fiv\Spl\ObjectCollection {

    /**
     * Used for validation
     * Return class name
     *
     * @codeCoverageIgnore
     * @return string
     */
    public function objectsClassName() {
      return '\Fiv\Parser\Dom\ElementFinder';
    }


    /**
     * @param int $index
     * @return null|\Fiv\Parser\Dom\ElementFinder
     */
    public function item($index) {
      if (isset($this->items[$index])) {
        return $this->items[$index];
      } else {
        return null;
      }
    }

    /**
     * @param string $regexp
     * @param string $to
     * @return $this
     */
    public function replace($regexp, $to = '') {
      foreach ($this as $item) {
        $item->replace($regexp, $to);
      }

      return $this;
    }

  } 