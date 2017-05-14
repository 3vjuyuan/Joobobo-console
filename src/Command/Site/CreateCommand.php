<?php
/**
 * Created by IntelliJ IDEA.
 * User: Weiye Sun
 * Date: 2017/5/9
 * Time: 22:57
 */

namespace Joobobo\Console\Command\Site;


use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCommand extends SiteAbstractCommand
{
    private const COMMAND_NAME = 'create';

    protected function configure()
    {
        //@todo help and description should be saved in language file
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Create a new Joobobo site')
            ->addOption('release', 'r', InputOption::VALUE_REQUIRED, 'Give the release version of Joomla!');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $output->writeln('<notice>create Joobobo site</notice>');
    }
}