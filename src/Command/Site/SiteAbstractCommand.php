<?php
/**
 * Created by IntelliJ IDEA.
 * User: Weiye Sun
 * Date: 2017/5/9
 * Time: 22:34
 */

namespace Joobobo\Console\Command\Site;

use \Joobobo\Console\JooboboConsoleApplication;
use Symfony\Component\Console\Command\Command;

class SiteAbstractCommand extends Command
{
  const BASE_NAME = 'site';

  public function setName($name)
  {
    $name = self::BASE_NAME . ':' . trim($name, ': \t\n\r\0\x0B');
    return parent::setName($name);
  }

  protected function getCommandName()
  {
    return $this->commandName();
  }

  public function getDirectory($dir)
  {
    if (!in_array(substr($dir, 0, 1), ['/', '.'])) {
      $dir = realpath(JooboboConsoleApplication::ROOT) . '/' . $dir;
    }
    $rule = substr($dir, 0, 2);
    if ($rule === './') {
      $dir = realpath(JooboboConsoleApplication::ROOT) . '/' . str_replace($rule, '', $dir);
    }

    if ($rule === '..') {
      preg_match('|^[../]*|', $dir, $match);
      $dir = realpath(JooboboConsoleApplication::ROOT . $match[0]) . '/' . str_replace($match[0], '', $dir);
    }
    return $dir;
  }
}