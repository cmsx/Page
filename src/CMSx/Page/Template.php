<?php

namespace CMSx\Page;

class Template extends \CMSx\Template
{
  /** Для страницы проверки пути не происходит */
  protected function getTemplatePath($template)
  {
    if (!is_file($template)) {
      \CMSx\Template\Exception::NotExists($template);
    }

    return $template;
  }
}