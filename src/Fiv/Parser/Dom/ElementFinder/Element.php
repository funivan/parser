<?php

  namespace Fiv\Parser\Dom\ElementFinder;

  /**
   * @author Ivan Shcherbak <dev@funivan.com> 6/3/14
   */
  class Element extends \DOMElement {

    /**
     * Array of element attributes
     *
     * @return array
     */
    public function getAttributes() {
      $attributes = array();
      foreach ($this->attributes as $attr) {
        $attributes[$attr->name] = $attr->value;
      }

      return $attributes;
    }

  } 