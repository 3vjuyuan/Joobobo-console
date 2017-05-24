<?php
/**
 * Created by IntelliJ IDEA.
 * User: Weiye Sun
 * Date: 2017/5/13
 * Time: 10:24
 */

namespace Joobobo\Console\Command\Site;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Yaml\Yaml;
class InitializeCommand extends SiteAbstractCommand
{
  const COMMAND_NAME = 'initialize';
  const CONFIG_PATH = './src/Config/';

  protected function configure()
  {
    //@todo help and description should be saved in language file
    $this->setName(self::COMMAND_NAME)
      ->setDescription('Set the server configuration file')
      ->addOption('release', 'r', InputOption::VALUE_REQUIRED, 'Give the release version of Joomla!')
      //Apache/Nginx
      ->addArgument('ServerType', InputArgument::REQUIRED, 'The server type of the project configuration file.')
      ->addArgument('ProjectName', InputArgument::REQUIRED, 'The name of the project.')
      ->addArgument('ServerName', InputArgument::REQUIRED, 'The server name of the project.')
      ->addArgument('Port', InputArgument::OPTIONAL, 'The port of the project.');
  }
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $server_type = $input->getArgument('ServerType');
    $server_name = $input->getArgument('ServerName');
    $port = $input->getArgument('Port')? $input->getArgument('Port'): 80;
    $project_name = $input->getArgument('ProjectName');
    $config_path = self::CONFIG_PATH.$project_name.'.yaml';
    //项目配置文件是否存在
    if(!file_exists($config_path)){
      $output->writeln('<error>Failed to get the project configuration file.</error>');
      return false;
    }
    //从项目配置文件获取项目所在目录
    $data = Yaml::parse(file_get_contents($config_path));
    if(empty($data)){
      $output->writeln('<error>The configuration file is empty.</error>');
      return false;
    }
    //只允许有一种服务器配置类型
    if(isset($data['server_type'])){
      $output->writeln('<warning>The project server configuration type already exists.</warning>');
      return false;
    }
    if(!isset($data['project_path'])){
      $output->writeln('<error>The project directory does not exist in the configuration file.</error>');
      return false;
    }
    $document_root = $data['project_path'];
    if(!is_dir($document_root)){
      $output->writeln('<error>The current project directory does not exist.</error>');
      return false;
    }
    $config_filename = $document_root.'/'.$project_name.'.conf';
    //判断服务器配置文件
    if(file_exists($config_filename)){
      $output->writeln('<warning>The server configuration file already exists.</warning>');
      return false;
    }
    $config = '';
    if(strtolower($server_type) == 'apache'){
      $config .= '<VirtualHost *:'.$port.'>
      ServerName  '.$server_name.'
      ServerAdmin  webmaster@localhost
      DocumentRoot '.$document_root.'
      ErrorLog ${APACHE_LOG_DIR}/error.log
      CustomLog ${APACHE_LOG_DIR}/access.log combined
      <Directory '.$document_root.'>
          AllowOverride All
          Order allow,deny
          Allow from All
      </Directory>
    </VirtualHost>';
    }elseif (strtolower($server_type) == 'nginx'){
      $config .='server {
        listen  '.$port.';
        server_name  '.$server_name.';
        location / {
          root  '.$document_root.';
          index  index.php index.html index.htm;
        }
        error_page 500 502 503 504 /50x.html;
        location = /50x.html {
          root /usr/share/nginx/html;
        }
        location ~ \.php$ {
          fastcgi_pass  127.0.0.1:9000;
          fastcgi_index index.php;
          fastcgi_param SCRIPT_FILENAME '.$document_root.'$fastcgi_script_name;
          include       fastcgi_params;
        }
      }';
    }
    //生成服务器配置文件
    if(!file_put_contents($config_filename,$config)){
      $output->writeln('<error>The server configuration file generation failed.</error>');
      return false;
    }
    $data['server_type'] = $server_type;
    $data['server_name'] = $server_name;
    //将项目域名写入相应配置文件
    if(!file_put_contents($config_path,Yaml::dump($data))){
      //删除已创建apache配置文件
      rmdir($config_filename);
      $output->writeln('<error>The project server name writing failed.</error>');
      return false;
    }
    $output->writeln('<success>Successfully created the server configuration file,please create a soft connection to '.$config_filename.'.</success>');
    return true;
  }
}