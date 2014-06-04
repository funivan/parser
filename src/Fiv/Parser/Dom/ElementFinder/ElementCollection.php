<?php

  namespace Fiv\Parser\Dom\ElementFinder;

  /**
   * @author Ivan Shcherbak <dev@funivan.com> 6/3/14
   */
  class ElementCollection extends \Fiv\Spl\ObjectCollection {

    /**
     * Used for validation
     * Return class name
     *
     * @codeCoverageIgnore
     * @return string
     */
    public function objectsClassName() {
      return '\Fiv\Parser\Dom\ElementFinder\Element';
    }

    /**
     * @param int $index
     * @return null|Element
     */
    public function item($index) {
      if (isset($this->items[$index])) {
        return $this->items[$index];
      } else {
        return null;
      }
    }

    public function getAttributes() {
      $allAttributes = array();
      /** @var Element $element */
      foreach ($this->items as $key => $element) {
        $allAttributes[$key] = array();
        $allAttributes[$key] = $element->getAttributes();

      }

      return $allAttributes;
    }
  } 