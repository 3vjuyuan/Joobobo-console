<?php
/**
 * Created by IntelliJ IDEA.
 * User: Weiye Sun
 * Date: 2017/5/13
 * Time: 10:22
 */

namespace Joobobo\Console\Command\Site;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
class DestroyCommand extends SiteAbstractCommand
{
  const COMMAND_NAME = 'destroy';

  protected function configure()
  {
    //@todo help and description should be saved in language file
    $this->setName(self::COMMAND_NAME)
      ->setDescription('Destroy a new Joobobo site')
      ->addOption('release', 'r', InputOption::VALUE_REQUIRED, 'Give the release version of Joomla!');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $output->writeln('<notice>Destroy Joobobo site</notice>');
  }
}