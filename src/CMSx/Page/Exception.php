<?php

namespace CMSx\Page;

class Exception extends \Exception
{
  const DOCTYPE = 1;

  protected static $errors = array(
    self::DOCTYPE => 'Неверный doctype: "%s"',
  );

  /** @throws Exception */
  public static function Doctype($doctype)
  {
    self::ThrowError(self::DOCTYPE, $doctype);
  }

  /** @throws Exception */
  public static function ThrowError($code, $args = null, $_ = null)
  {
    $args = func_get_args();
    array_shift($args);
    throw new static(sprintf(self::$errors[$code], $args), $code);
  }
}