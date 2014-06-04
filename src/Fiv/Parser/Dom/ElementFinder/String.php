<?php

  namespace Fiv\Parser\Dom\ElementFinder;

  /**
   * @author Ivan Shcherbak <dev@funivan.com> 6/3/14
   */
  class String {

    protected $value = '';

    /**
     * @param string $value
     */
    public function __construct($value) {
      $this->value = $value;
    }

    public function __toString() {
      return $this->value;
    }

    public function value() {
      return $this->value;
    }

    public function getValue() {
      return $this->value;
    }

  } 