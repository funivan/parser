<?php

  namespace Fiv\Parser\Debug;

  /**
   * DebugInterface interface for Request
   *
   * @author Ivan Scherbak <dev@funivan.com>
   */
  interface DebugInterface {

    /**
     * @param \Fiv\Parser\Request $request
     */
    public function beforeRequest(\Fiv\Parser\Request $request);

    /**
     * @param \Fiv\Parser\Request $request
     */
    public function afterRequest(\Fiv\Parser\Request $request);

  }