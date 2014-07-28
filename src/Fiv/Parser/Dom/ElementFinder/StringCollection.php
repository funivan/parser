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

    /**
     * Match strings and return new collection
     *
     * @param string $regexp
     * @param int $index
     * @return StringCollection
     */
    public function match($regexp, $index = 1) {
      $matchedItems = new StringCollection();

      foreach ($this->items as $item) {
        preg_match_all($regexp, $item, $matchedData);

        if (empty($matchedData[$index])) {
          continue;
        }

        foreach ($matchedData[$index] as $matchedString) {
          $matchedItems[] = $matchedString;
        }

      }

      return $matchedItems;
    }

    /**
     * Split strings by regexp
     *
     * @param string $regexp
     * @return StringCollection
     */
    public function split($regexp) {
      $items = new StringCollection();

      foreach ($this->items as $item) {
        
        $data = preg_split($regexp, $item);
        foreach ($data as $string) {
          $items[] = $string;
        }
      }

      return $items;
    }

  } 