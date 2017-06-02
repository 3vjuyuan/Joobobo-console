<?php
/**
 * Created by IntelliJ IDEA.
 * User: Weiye Sun
 * Date: 2017/5/9
 * Time: 22:57
 */

namespace Joobobo\Console\Command\Site;

use \Joobobo\Console\JooboboConsoleApplication;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Exception\RuntimeException;
use League\Plates\Engine;

class CreateCommand extends SiteAbstractCommand
{
  const COMMAND_NAME = 'create';
  
  private $server = array(
    'apache' => ['conf' => '/etc/apache2/sites-enabled/', 'restart' => '/etc/init.d/apache2 restart'],
    'nginx' => ['conf' => '/etc/nginx/conf/', 'restart' => '/etc/init.d/nginx restart']
  );
  
  protected function configure()
  {
    //@todo help and description should be saved in language file
    $this->setName(self::COMMAND_NAME)
      ->setDescription('Create a new Joobobo site')
      ->addOption('release', 'r', InputOption::VALUE_REQUIRED, 'Give the release version of Joomla!')
      ->addOption('target', 't', InputOption::VALUE_REQUIRED, 'The directory of the project.')
      ->addOption('server', 's', InputOption::VALUE_REQUIRED, 'The server type of the project configuration file.', 'apache')
      ->addOption('git', 'g', InputOption::VALUE_OPTIONAL, 'Joomla git source', 'https://git.intern.3vjuyuan.com/wenmengyu/joomla-cms.git');
  }
  
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $options = $input->getOptions();
    $temp = realpath(JooboboConsoleApplication::TEMPLATES) . '/Config';

    if (!@mkdir(iconv("UTF-8", "GBK", $options['target']), 0750, true)) {
      $output->writeln('<error>The target directory creation failed.</error>');
      return false;
    }
    
    exec('git clone ' . $options['release'] . ' ' . $options['git'] . ' ' . $options['target'], $out, $return);
    if ($return !== 0) {
      $output->writeln('<error>The git clone joomla-cms failed.</error>');
      exit(1);
    }
    
    $temp = new Engine($temp, 'conf');
    $config = $temp->render($options['server'], ['directory' => $options['target']]);

    if (!file_put_contents($options['target'] . '/' . $options['server'] . '.conf', $config)) {
      $output->writeln('<warning>The project server config writing failed. Need to manually create server config</warning>');
      return true;
    }

    exec('ln -s ' . $options['target'] . '/' . $options['server'] . '.conf ' . $this->server[$options['server']]['conf'] . str_replace('/', '-', trim($options['target'], '/')) . '.conf', $out, $return);
    if ($return !== 0) {
      $output->writeln('<warning>Need to manually create ' . $options['server'] . ' link. Restart ' . $options['server'] . ' </warning>');
    } else {
      exec($this->server[$options['server']]['restart'], $out, $return);
      if ($return !== 0) {
        $output->writeln('<warning>Need restart ' . $options['server'] . ' </warning>');
      }
    }
    $output->writeln('<success>The project create successfully. Project path ' . $options['target'] . '</success>');
    return true;
    
  }
  
  protected function initialize(InputInterface $input, OutputInterface $output)
  {
    parent::initialize($input, $output);
    $options = $input->getOptions();
    $temp = realpath(JooboboConsoleApplication::TEMPLATES) . '/Config';

    if (empty($options['target'])) {
      $output->writeln('<error>The target option required.</error>');
      exit(1);
    }
    $dir = $this->getDirectory($options['target']);
    if (is_dir($dir)) {
      $output->writeln('<error>The target directory already exists.</error>');
      exit(1);
    }
    
    if (!isset($this->server[$options['server']]) || !file_exists($temp . '/' . strtolower($options['server']) . '.conf')) {
      $output->writeln('<error>The server type config file does not exist.</error>');
      exit(1);
    }
    $input->setOption('target', $dir);
    $input->setOption('server', strtolower($options['server']));
    $input->setOption('release', (empty($options['release']) ? '' : '--branch ' . $options['release']));

  }
  
}
