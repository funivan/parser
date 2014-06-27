<?php
  /**
   * @author Ivan Shcherbak <dev@funivan.com>
   */

  namespace ParserTests\Dom;

  use Fiv\Parser\Dom\ElementFinder;

  /**
   *
   * @package ParserTests\Dom
   */
  class ElementFinderTest extends \ParserTests\Main {

    protected function getTestFilePath() {
      return $this->getDemoDataDirectoryPath() . '/test.html';
    }

    public function testLoad() {
      $html = $this->getTestHtmlObject();
      $this->assertContains('<title>test doc</title>', (string)$html);
    }

    public function testAttributes() {
      $html = $this->getTestHtmlObject();

      $links = $html->attribute("//a/@href");

      $this->assertCount(1, $links);

      foreach ($html->html("//a") as $htmlString) {
        $this->assertTrue(is_string($htmlString));
      }

      $firstLink = $html->html("//a", true)->item(0);

      $this->assertContains('<a href="http://funivan.com/" title="my blog">link</a>', (string)$firstLink);
    }


    public function testObjects() {
      $html = $this->getTestHtmlObject();

      $spanItems = $html->object("//span");

      $this->assertCount(4, $spanItems);

      /** @var ElementFinder $span */
      foreach ($spanItems->extractItems(0, 3) as $index => $span) {
        $itemHtml = $span->html('//i')->item(0);

        $this->assertEquals('r', trim($itemHtml));

      }

      $html->remove('//span[2]');

      $spanItems = $html->html("//span");
      $this->assertCount(3, $spanItems);

      $html->remove('//span[@class]');

      $spanItems = $html->html("//span");
      $this->assertCount(1, $spanItems);

    }

    public function testElements() {
      $html = $this->getTestHtmlObject();

      $spanElements = $html->elements("//span");
      $spanItems = $spanElements->getAttributes();

      $this->assertCount(count($spanElements), $spanItems);
    }

    public function testDelete() {
      $html = $this->getTestHtmlObject();

      $title = $html->value('//title')->item(0);
      $this->assertEquals('test doc', $title);

      $html->remove('//title');

      $title = $html->value('//title')->item(0);
      $this->assertEmpty($title);

    }


    public function testHtmlSelector() {
      $html = $this->getTestHtmlObject();
      $title = $html->html('//td')->item(0);
      $this->assertEquals('custom <a href="http://funivan.com/" title="my blog">link</a>', (string)$title);

      $title = $html->html('//td/@df')->item(0);
      $this->assertEmpty((string)$title);
    }

    public function testGetNodeItems() {
      $html = $this->getTestHtmlObject();
      $group = $html->getNodeItems('//span', array(
        'b' => '//b[1]',
        'i' => '//o',
        'if' => '//i/@df',
      ));

      $this->assertCount(4, $group);

      $this->assertNotEmpty($group[0]['b']);

      foreach ($group as $i => $item) {
        $this->assertEmpty($item['if']);
      }

    }

    /**
     * @return ElementFinder
     */
    public function getTestHtmlObject() {
      $fileData = file_get_contents($this->getTestFilePath());
      $html = new ElementFinder($fileData);
      return $html;
    }

  } 