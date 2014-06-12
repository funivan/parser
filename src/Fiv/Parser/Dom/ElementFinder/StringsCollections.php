<?php

  namespace Fiv\Parser\Dom\ElementFinder;

  /**
   * @author Ivan Shcherbak <dev@funivan.com> 6/3/14
   * @method string offsetGet($offset);
   */
  class StringsCollections extends \Fiv\Spl\Collection {

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

  } 