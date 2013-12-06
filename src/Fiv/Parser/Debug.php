<?php

  namespace Fiv\Parser;

  /**
   * Debug interface for Request
   *
   * @author Ivan Scherbak <dev@funivan.com>
   */
  interface Debug {

    public function beforeRequest(Request $request);

    public function afterRequest(Request $request);

  }