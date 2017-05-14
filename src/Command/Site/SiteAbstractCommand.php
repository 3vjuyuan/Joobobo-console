<?php
/**
 * Created by IntelliJ IDEA.
 * User: Weiye Sun
 * Date: 2017/5/9
 * Time: 22:34
 */

namespace Joobobo\Console\Command\Site;

use Symfony\Component\Console\Command\Command;

class SiteAbstractCommand extends Command
{
    private const BASE_NAME = 'site';

    public function setName($name)
    {
        $name = self::BASE_NAME . ':' . trim($name, ': \t\n\r\0\x0B');
        return parent::setName($name);
    }

    protected function getCommandName() {
        return $this->commandName();
    }
}