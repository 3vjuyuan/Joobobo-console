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
use Symfony\Component\Yaml\Yaml;

class CreateCommand extends SiteAbstractCommand
{
  const COMMAND_NAME = 'create';
  const CONFIG_PATH = './src/Config/';
  const DEFAULT_PACKAGE = './src/Package/Joomla_3.7.1.zip';

  protected function configure()
  {
    //@todo help and description should be saved in language file
    $this->setName(self::COMMAND_NAME)
      ->setDescription('Create a new Joobobo site')
      ->addOption('release', 'r', InputOption::VALUE_REQUIRED, 'Give the release version of Joomla!')
      ->addArgument('project_name', InputArgument::REQUIRED, 'The name of the project.')
      ->addArgument('directory', InputArgument::REQUIRED, 'The directory of the project.');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $project_name = $input->getArgument('project_name');
    $dir = $input->getArgument('directory');
    $config_path = self::CONFIG_PATH . $project_name . '.yaml';

    if (!is_dir(self::CONFIG_PATH) && !@mkdir(iconv("UTF-8", "GBK", self::CONFIG_PATH), 0750, true)) {
      $output->writeln('<error>The Config directory creation failed.</error>');
      return false;
    }
    //确保目录带根目录
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

    //判断配置文件是否存在
    if (file_exists($config_path)) {
      $output->writeln('<warning>The project name already exists. Please anew it</warning>');
      return true;
    }
    if (is_dir($dir)) {
      //若目录已存在，不允许在此目录下创建项目
      $output->writeln('<warning>The directory already exists.</warning>');
      return true;
    }

    if (!@mkdir(iconv("UTF-8", "GBK", $dir), 0750, true)) {
      $output->writeln('<error>The directory creation failed.</error>');
      return false;
    }
    $data = array();
    $data['project_path'] = $dir;
    //将项目名称和所在目录写入相应配置文件
    if (!file_put_contents($config_path, Yaml::dump($data))) {
      //删除已创建目录
      rmdir($dir);
      $output->writeln('<error>The configuration file generation failed.</error>');
      return false;
    }
    //解压安装包
    exec('unzip ' . self::DEFAULT_PACKAGE . ' -d  ' . $dir, $out, $return_val);
    if ($return_val) {
      //删除已创建目录和生成的配置文件
      unlink($config_path);
      rmdir($dir);
      $output->writeln('<error>The project installation package decompresses failed.</error>');
      return false;
    }
    //复制install.php文件到项目的安装包目录
    exec('cp ' . realpath(JooboboConsoleApplication::ROOT) . '/joobobo/install.php  ' . $dir . '/installation', $res, $return);
    if ($return) {
      //删除已创建目录和生成的配置文件
      unlink($config_path);
      rmdir($dir);
      $output->writeln('<error>Failed to copy the installation file.</error>');
      return false;
    }
    $output->writeln('<success>Successfully created the project,please install the project by ' . $dir . '/installation/install.php.</success>');
    return true;
  }
}
