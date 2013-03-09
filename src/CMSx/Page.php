<?php

namespace CMSx;

use CMSx\Page\Exception;

/**
 * @method Page set($name, $value) Установить значение
 */
class Page extends Container
{
  const DOCTYPE_HTML_4_STRICT       = 'html4strict';
  const DOCTYPE_HTML_4_TRANSITIONAL = 'html4transitional';
  const DOCTYPE_HTML_5              = 'html5';
  const DOCTYPE_XHTML_STRICT        = 'xhtml_strict';
  const DOCTYPE_XHTML_TRANSITIONAL  = 'xhtml_transitional';

  /** Полный путь к файлу лейаута */
  protected $layout;
  protected $doctype;
  protected $charset = 'utf-8';
  protected $css = array();
  protected $js = array();
  protected $body_attr;
  protected $domain;
  protected $template;

  protected static $doctype_arr = array(
    self::DOCTYPE_HTML_5              => '<!DOCTYPE html>',
    self::DOCTYPE_HTML_4_STRICT       => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">',
    self::DOCTYPE_HTML_4_TRANSITIONAL => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">',
    self::DOCTYPE_XHTML_STRICT        => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
    self::DOCTYPE_XHTML_TRANSITIONAL  => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
  );

  function __construct()
  {
    $this->layout = realpath(__DIR__ . '/../../templates/layout.php');
    $this->init();
  }

  function __toString()
  {
    return $this->render();
  }

  /** Отрисовка страницы */
  public function render()
  {
    return $this->getLayoutTemplate()->render();
  }

  /** Отрисовка доктайп */
  public function renderDoctype()
  {
    return $this->doctype
      ? static::GetDoctypeHTML($this->doctype) . "\n"
      : null;
  }

  /** Отрисовка тега HTML */
  public function renderHTMLTag()
  {
    return ($this->doctype == self::DOCTYPE_XHTML_STRICT || $this->doctype == self::DOCTYPE_XHTML_TRANSITIONAL
      ? '<html xmlns="http://www.w3.org/1999/xhtml">'
      : '<html>') . "\n";
  }

  /**
   * Отрисовка тела страницы.
   * По-умолчанию также отрисовываются и JS в конце BODY
   */
  public function renderBody($attr = null, $withJS = true)
  {
    $v         = $this->vars;
    $v['page'] = $this;
    if ($this->template) {
      $t    = new \CMSx\Template($this->template, $v);
      $body = $t->render();
    } else {
      $body = $this->renderHeader() . $this->getText();
    }

    return HTML::Tag(
      'body',
      $body . "\n" . ($withJS ? $this->renderJS() : ''),
      $attr ? : $this->body_attr,
      false,
      true
    ) . "\n";
  }

  /** Шаблон для тела страницы */
  public function setTemplate($template)
  {
    $this->template = $template;

    return $this;
  }

  /** Шаблон для тела страницы */
  public function getTemplate()
  {
    return $this->template;
  }

  /** Установка атрибутов для тега BODY */
  public function setBodyAttr($attr)
  {
    $this->body_attr = $attr;

    return $this;
  }

  /** Отрисовка МЕТА-Тега Charset */
  public function renderCharset()
  {
    $attr = $this->doctype == self::DOCTYPE_HTML_5
      ? array('charset' => $this->charset)
      : array('http-equiv' => 'Content-Type', 'content' => 'text/html; charset=' . $this->charset);

    return HTML::Tag('meta', null, $attr, true) . "\n";
  }

  /** Название страницы в теге title */
  public function renderTitle()
  {
    return HTML::Tag('title', $this->getTitle()) . "\n";
  }

  /** Заголовок на странице. Можно указать в какой тег выводить и атрибуты */
  public function renderHeader($tag = 'h1', $attr = null)
  {
    return HTML::Tag($tag, $this->getHeader() ? : $this->getTitle(), $attr) . "\n";
  }

  /** Мета-тег keywords */
  public function renderKeywords($default = null)
  {
    return
      HTML::Tag('meta', null, array('name' => 'keywords', 'content' => $this->getKeywords() ? : $default), true) . "\n";
  }

  /** Мета-тег description */
  public function renderDescription($default = null)
  {
    return HTML::Tag(
      'meta', null, array('name' => 'description', 'content' => $this->getDescription() ? : $default), true
    ) . "\n";
  }

  /** Список подключаемых CSS */
  public function renderCSS()
  {
    $out = array();
    if (count($this->css)) {
      foreach ($this->css as $arr) {
        $attr  = array(
          'rel'   => 'stylesheet',
          'type'  => 'text/css',
          'href'  => $arr['file'],
          'media' => $arr['media'] ? : 'all'
        );
        $tag   = HTML::Tag('link', null, $attr, true);
        $out[] = (!empty($arr['if'])
          ? '<!--[if ' . $arr['if'] . ']>' . $tag . '<![endif]-->'
          : $tag);
      }
    }
    $out = join("\n", $out);

    return $out ? $out . "\n" : false;
  }

  /**
   * Список подключаемых JS
   * $closed_tag - выводить закрытый тег или полный
   */
  public function renderJS($closed_tag = false)
  {
    $out = array();
    if (count($this->js)) {
      $out[] = '<!-- JS -->';
      foreach ($this->js as $file) {
        $attr  = array(
          'type' => 'text/javascript',
          'src'  => $file
        );
        $out[] = HTML::Tag('script', null, $attr, $closed_tag);
      }
      $out[] = '<!-- /JS -->';
    }
    $out = join("\n", $out);

    return $out ? $out . "\n" : false;
  }

  /**
   * Отрисовка канонического адреса страницы.
   * Домен должен быть указан при вызове, или заранее через setDomain
   * $domain - домен с http://
   */
  public function renderCanonical($domain = null)
  {
    if (is_null($domain)) {
      $domain = $this->getDomain();
    }

    if (!$domain || !$c = $this->getCanonical($domain)) {
      return false;
    }

    $attr = array(
      'rel'  => 'canonical',
      'href' => $c
    );

    return HTML::Tag('link', null, $attr, true) . "\n";
  }

  /** Текст страницы */
  public function setText($text)
  {
    $this->set('text', $text);

    return $this;
  }

  /** Текст страницы */
  public function getText()
  {
    return $this->get('text');
  }

  /** Произвольные теги в HEAD */
  public function setMeta($meta)
  {
    $this->set('meta', $meta);

    return $this;
  }

  /** Произвольные теги в HEAD */
  public function getMeta()
  {
    return $this->get('meta');
  }

  /** Канонический адрес страницы (link rel="canonical") */
  public function setCanonical($url)
  {
    $this->set('canonical', $url);

    return $this;
  }

  /** Домен. Используется в ссылках */
  public function setDomain($domain)
  {
    $this->domain = $domain;

    return $this;
  }

  /** Домен. Используется в ссылках */
  public function getDomain()
  {
    return $this->domain;
  }

  /** Канонический адрес страницы (link rel="canonical") */
  public function getCanonical($domain = null)
  {
    if (!$c = $this->get('canonical')) {
      return false;
    }

    return is_null($domain)
      ? $c
      : rtrim($domain, '/') . $c;
  }

  /**
   * Добавление CSS.
   * $media - аттрибут media, по-умолчанию = all
   * $if - для формирования условной конструкции <!--[if $if]><link ... /><![endif]-->
   */
  public function addCSS($file, $media = null, $if = null)
  {
    $this->css[$file] = array(
      'file'  => $file,
      'media' => $media,
      'if'    => $if
    );

    return $this;
  }

  /** Добавление скрипта */
  public function addJS($file)
  {
    $this->js[$file] = $file;

    return $this;
  }

  /** Удаление добавленных скриптов */
  public function clearJS()
  {
    $this->js = array();

    return $this;
  }

  /** Удаление добавленных стилей */
  public function clearCSS()
  {
    $this->css = array();

    return $this;
  }

  /** Мета-тег кодировка */
  public function setCharset($charset)
  {
    $this->charset = $charset;

    return $this;
  }

  /** Мета-тег кодировка */
  public function getCharset()
  {
    return $this->charset;
  }

  /** Мета-тег keywords */
  public function setKeywords($value)
  {
    $this->set('keywords', $value);

    return $this;
  }

  /** Мета-тег keywords */
  public function getKeywords()
  {
    return $this->get('keywords');
  }

  /** Мета-тег description */
  public function setDescription($value)
  {
    $this->set('description', $value);

    return $this;
  }

  /** Мета-тег description */
  public function getDescription()
  {
    return $this->get('description');
  }

  /** Тег title */
  public function setTitle($value)
  {
    $this->set('title', $value);

    return $this;
  }

  /** Тег title */
  public function getTitle()
  {
    return $this->get('title');
  }

  /** Заголовок на странице */
  public function setHeader($value)
  {
    $this->set('header', $value);

    return $this;
  }

  /** Заголовок на странице */
  public function getHeader()
  {
    return $this->get('header');
  }

  /** Доктайп - одна из констант класса Page::DOCTYPE_* */
  public function setDoctype($doctype)
  {
    if (!static::GetDoctypeHTML($doctype)) {
      Exception::Doctype($doctype);
    }
    $this->doctype = $doctype;

    return $this;
  }

  /** Доктайп */
  public function getDoctype()
  {
    return $this->doctype;
  }

  /** Полный путь к файлу шаблона для формирования страницы */
  public function setLayout($layout)
  {
    $this->layout = $layout;

    return $this;
  }

  /** Полный путь к файлу шаблона для формирования страницы */
  public function getLayout()
  {
    return $this->layout;
  }

  /** Получение HTML для доктайпа */
  public static function GetDoctypeHTML($doctype)
  {
    return isset(self::$doctype_arr[$doctype])
      ? self::$doctype_arr[$doctype]
      : false;
  }

  /** Получение шаблона страницы */
  protected function getLayoutTemplate()
  {
    $vars         = $this->vars;
    $vars['page'] = $this;

    return new Page\Template($this->layout, $vars);
  }

  /** Дополнительная инициализация */
  protected function init()
  {
  }
}