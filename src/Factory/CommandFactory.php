<?php
/**
 * Created by IntelliJ IDEA.
 * User: Weiye Sun
 * Date: 2017/5/11
 * Time: 22:01
 */

namespace Joobobo\Console\Factory;

use Joobobo\Console\Command\Site\{CreateCommand};

class CommandFactory implements IFactory
{
    public function createCommands()
    {
        return array(
            new CreateCommand(),
        );
    }
}