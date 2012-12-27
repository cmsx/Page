<?php

require_once __DIR__ . '/../init.php';

use CMSx\Page;
use CMSx\Template;

class PageTest extends PHPUnit_Framework_TestCase
{
  function testLayoutPath()
  {
    $p = new Page;
    $this->assertTrue(is_file($p->getLayout()), 'Путь к файлу лейаута верный и файл существует');
  }

  function testDoctype()
  {
    $exp = '<!DOCTYPE html>';
    $p   = new Page;

    $this->assertEquals($exp, Page::GetDoctypeHTML(Page::DOCTYPE_HTML_5), 'Существующий доктайп');
    $this->assertFalse(Page::GetDoctypeHTML(123), 'Несуществующий доктайп');

    try {
      $p->setDoctype(123);
      $this->fail('Несуществующий доктайп выбрасывает исключение');
    } catch (\Exception $e) {
      $this->assertEquals('CMSx\Page\Exception', get_class($e), 'Исключение Page');
      $this->assertEquals(\CMSx\Page\Exception::DOCTYPE, $e->getCode(), 'Код исключения');
    }

    $p->setDoctype(Page::DOCTYPE_HTML_5);
    $this->assertEquals($exp . "\n", $p->renderDoctype(), 'Доктайп');
  }

  function testHTMLTag()
  {
    $p = new Page;
    $this->assertEquals('<html>' . "\n", $p->renderHTMLTag(), 'Простой html тег');

    $exp = '<html xmlns="http://www.w3.org/1999/xhtml">' . "\n";

    $p->setDoctype(Page::DOCTYPE_XHTML_TRANSITIONAL);
    $this->assertEquals($exp, $p->renderHTMLTag(), 'Для XHTML должен быть указан xmlns #1');

    $p->setDoctype(Page::DOCTYPE_XHTML_STRICT);
    $this->assertEquals($exp, $p->renderHTMLTag(), 'Для XHTML должен быть указан xmlns #2');
  }

  function testCharset()
  {
    $p = new Page;
    $this->assertEquals('utf-8', $p->getCharset(), 'Стоит UTF-8 по-умолчанию');

    $exp = '<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />' . "\n";
    $this->assertEquals($exp, $p->renderCharset(), 'META-Тег charset');

    $exp = '<meta charset="windows-1251" />' . "\n";
    $p->setDoctype(Page::DOCTYPE_HTML_5);
    $p->setCharset('windows-1251');
    $this->assertEquals($exp, $p->renderCharset(), 'Для HTML5 допускается сокращенная форма');
  }

  function testTitleHeader()
  {
    $p = new Page;
    $p->setTitle('Hello');

    $this->assertEquals('<title>Hello</title>' . "\n", $p->renderTitle(), 'Тайтл страницы');
    $this->assertEquals('<h1>Hello</h1>' . "\n", $p->renderHeader(), 'Заголовок по-умолчанию в H1 и равен тайтлу');

    $p->setHeader('whoah');
    $this->assertEquals('<b class="hi">whoah</b>' . "\n", $p->renderHeader('b', 'hi'), 'Произвольный заголовок');
  }

  function testKeywords()
  {
    $p = new Page;
    $this->assertEquals('<meta content="ой" name="keywords" />' . "\n", $p->renderKeywords('ой'), 'Ключевых слов нет');

    $p->setKeywords('бла бла');
    $this->assertEquals(
      '<meta content="бла бла" name="keywords" />' . "\n", $p->renderKeywords('ой'), 'Ключевые слова заданы'
    );
  }

  function testDescription()
  {
    $p = new Page;
    $this->assertEquals('<meta content="ой" name="description" />' . "\n", $p->renderDescription('ой'), 'Описания нет');

    $p->setDescription('бла бла');
    $this->assertEquals('<meta content="бла бла" name="description" />' . "\n", $p->renderDescription('ой'), 'Описание есть');
  }

  function testCSS()
  {
    $p = new Page;
    $this->assertEmpty($p->renderCSS(), 'Стили не заданы');

    $p->addCSS('test.css')
      ->addCSS('print.css', 'print')
      ->addCSS('ie.css', null, 'IE gt 7');

    $exp = '<link href="test.css" media="all" rel="stylesheet" type="text/css" />' . "\n"
      . '<link href="print.css" media="print" rel="stylesheet" type="text/css" />' . "\n"
      . '<!--[if IE gt 7]><link href="ie.css" media="all" rel="stylesheet" type="text/css" /><![endif]-->' . "\n";
    $this->assertEquals($exp, $p->renderCSS(), 'Сформированный HTML');

    $p->clearCSS();
    $this->assertEmpty($p->renderCSS(), 'Стили удалены');
  }

  function testJS()
  {
    $p = new Page;
    $this->assertEmpty($p->renderJS(), 'Скрипты не заданы');

    $p->addJS('test.js')
      ->addJS('js.js');

    $exp = '<!-- JS -->' . "\n"
      . '<script src="test.js" type="text/javascript" />' . "\n"
      . '<script src="js.js" type="text/javascript" />' . "\n"
      . '<!-- /JS -->' . "\n";
    $this->assertEquals($exp, $p->renderJS(), 'Сформированный HTML');

    $p->clearJS();
    $this->assertEmpty($p->renderJS(), 'Скрипты удалены');
  }

  function testCanonical()
  {
    $d = 'http://www.cmsx.ru';
    $p = new Page;
    $this->assertFalse($p->getCanonical($d), 'Адрес не указан');
    $this->assertEmpty($p->renderCanonical($d), 'Тег не рендерится');

    $page = '/page.html';
    $exp = $d . $page;
    $p->setCanonical($page);
    $this->assertEquals($page, $p->getCanonical(), 'Значение установлено');
    $this->assertEquals($exp, $p->getCanonical($d), 'Путь вместе с доменом');
    $this->assertEquals($exp, $p->getCanonical($d . '/'), 'Путь вместе с доменом и слешом');

    $exp = '<link href="http://www.cmsx.ru/page.html" rel="canonical" />' . "\n";
    $this->assertEquals($exp, $p->renderCanonical($d), 'Адрес указан');
    $this->assertEquals($exp, $p->renderCanonical($d . '/'), 'Домен с слешом на конце');

    $this->assertEmpty($p->renderCanonical(), 'Без домена ссылка не отображается');
    $p->setDomain($d);
    $this->assertEquals($exp, $p->renderCanonical(), 'Адрес использует адрес из объекта');
  }

  function testBody()
  {
    $p = new Page;
    $p->setTemplate('body.php')
      ->set('hello', '<b>World</b>');

    $exp = "<body class=\"hi\">\nHello, <b>World</b>!\n</body>\n";
    $this->assertEquals($exp, $p->renderBody('hi'), 'Рендер тела страницы');
  }

  function testRender()
  {
    $p = new Page;
    $p->setTemplate('body.php')
      ->set('hello', '<b>World</b>')
      ->setDomain('http://www.cmsx.ru')
      ->setCanonical('/')
      ->addCSS('file.css')
      ->addCSS('ie.css')
      ->addJS('hi.js')
      ->addJS('js.js');

    $html = $p->render();
    $this->assertNotEmpty($html, 'Рендер не пустой');
    $this->assertEquals($html, (string)$p, 'Приведение к строке');

    $this->assertSelectCount('head title', true, $html, 'Тайтл');
    $this->assertSelectCount('head meta[name=keywords]', true, $html, 'Keywords');
    $this->assertSelectCount('head meta[name=description]', true, $html, 'Description');
    $this->assertSelectCount('link[rel=canonical]', true, $html, '');
    $this->assertSelectCount('link[rel=stylesheet]', 2, $html, 'Стили CSS');
    $this->assertSelectCount('script[type=text/javascript]', 2, $html, 'Скрипты');
    $this->assertSelectCount('html body b', true, $html, 'Тело страницы');
  }

  function testNoTemplate()
  {
    $p = new Page;
    $p->setTitle('Hello')
      ->setText('<p>World</p>');

    $html = $p->render();
    $this->assertSelectCount('html body h1', true, $html, 'Заголовок на странице');
    $this->assertSelectCount('html body p', true, $html, 'Текст на странице');
  }

  public static function setUpBeforeClass()
  {
    Template::EnableDebug();
    Template::SetPath(realpath(__DIR__ . '/../tmpl'));
  }

  public static function tearDownAfterClass()
  {
    Template::EnableDebug(false);
    Template::SetPath(false);
  }
}