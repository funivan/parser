<?php
  /**
   * @author Ivan Shcherbak <dev@funivan.com>
   */

  namespace ParserTests;

  use Fiv\Parser\Html;

  class HtmlTest extends \ParserTests\Main {

    protected function getTestFilePath() {
      return $this->getDemoDataDirectoryPath() . '/test.html';
    }

    public function testLoad() {
      $html = $this->getTestHtmlObject();
      $this->assertContains('<title>test doc</title>', (string)$html);
    }

    public function testDelete() {
      $html = $this->getTestHtmlObject();

      $title = $html->_val('//title', 0);
      $this->assertEquals('test doc', $title);

      $html->_del('//title');

      $title = $html->_val('//title', 0);
      $this->assertEmpty($title);

    }

    public function testValSelector() {
      $html = $this->getTestHtmlObject();
      $title = $html->_val('//td', 0);
      $this->assertEquals('custom link', $title);
    }

    public function testHtmlSelector() {
      $html = $this->getTestHtmlObject();
      $titles = $html->_html('//td');
      $this->assertCount(1, $titles);
      $this->assertEquals('custom <a href="http://funivan.com/" title="my blog">link</a>', $titles[0]);
    }

    public function testAttributesSelector() {
      $html = $this->getTestHtmlObject();
      $links = $html->_attributes('//a');
      $this->assertCount(1, $links);
      $this->assertCount(2, $links[0]);

      $this->assertEquals('http://funivan.com/', $links[0]['href']);
      $this->assertEquals('my blog', $links[0]['title']);
    }

    public function testPropertiesSelector() {
      $html = $this->getTestHtmlObject();
      $links = $html->_properties('//a');
      $this->assertCount(1, $links);
      $this->assertCount(5, $links[0]);

      $this->assertEquals(null, $links[0]['_schemaTypeInfo']);
      $this->assertEquals('a', $links[0]['_tagName']);
      $this->assertEquals('link', $links[0]['_nodeValue']);
    }

    /**
     * @return Html
     */
    public function getTestHtmlObject() {
      $fileData = file_get_contents($this->getTestFilePath());
      $html = new Html($fileData);
      return $html;
    }

    public function testHtmlEncoding(){
      
    }
  } 