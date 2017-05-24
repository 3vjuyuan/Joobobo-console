<?php
/**
 * Created by IntelliJ IDEA.
 * User: Weiye Sun
 * Date: 2017/5/11
 * Time: 22:01
 */

namespace Joobobo\Console\Factory;

use Joobobo\Console\Command\Site\CreateCommand;
use Joobobo\Console\Command\Site\DestroyCommand;
use Joobobo\Console\Command\Site\InitializeCommand;

class CommandFactory implements IFactory
{
  public function createCommands()
  {
    return array(
        new CreateCommand(),
    );
  }
  public function destroyCommands()
  {
    return array(
      new DestroyCommand(),
    );
  }
  public function initializeCommands()
  {
    return array(
      new InitializeCommand(),
    );
  }
}