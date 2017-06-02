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


  protected function configure()
  {
    //@todo help and description should be saved in language file
    $this->setName(self::COMMAND_NAME)
      ->setDescription('Set the server configuration file')
      ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'The yml config file of the initialize project. ')
      ->addOption('project', 'p', InputOption::VALUE_REQUIRED, 'The project directory');

  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    // Parse options
    $options = $this->parseOptions($input);

    $errors = $this->validateOptions($options);

    if (!empty($errors)) {
      foreach ($errors as $error) {
        $output->writeln('<error>' . $error . '</error>');
      }
      exit(1);
    }

    // Attempt to initialise the database.
    $db = new \InstallationModelDatabase;

    if (!$db->createDatabase($options)) {
      $output->writeln("<error>Error executing createDatabase</error>");
      exit(1);
    }

    /*
       FIXME InstallationModelDatabase relies on session manipulation which doesn't work well in cli
       $session = JFactory::getSession();
       $options = $session->get('setup.options', NULL);
    */
    $options['db_created'] = 1;
    $options['db_select'] = 1;

    if ($options['db_old'] == 'backup') {
      if (!$db->handleOldDatabase($options)) {
        $output->writeln("<error>Error executing handleOldDatabase</error>");
        exit(1);
      }
    }
    @\JApplicationWeb::getInstance('InstallationApplicationWeb');
    if (!$db->createTables($options)) {
      $output->writeln("<error>Error executing createTables</error>");
      exit(1);
    }

    // Attempt to setup the configuration.

    $configuration = new \InstallationModelConfiguration;

    if (!$configuration->setup($options)) {
      $output->writeln("<error>Error executing setup</error>");
      exit(1);
    }

    // Attempt to create the database tables.
    if ($options['sample_file']) {
      if (!$db->installSampleData($options)) {
        $output->writeln("<error>Error executing installSampleData</error>");
        exit(1);
      }
    }
    exec('rm -rf ' . $input->getOption('project') . '/installation', $out, $return);
    if ($return !== 0) {
      $output->writeln('<warning>Installation folder could not be deleted. Please manually delete the folder.' . "\n" . 'install path: ' . $input->getOption('project') . '/installation. </warning>');
    }

    if (!file_exists($input->getOption('project') . '/configuration.php')) {
      $output->writeln("<error>Installation configuration failed.</error>");
      exit(1);
    }

    $output->writeln('<success>Successfully initialize.</success>');
    return true;
  }

  protected function validateOptions($options)
  {
    $optionsMetadata = $this->getOptionsMetadata();
    $errors = array();

    foreach ($optionsMetadata as $key => $spec) {
      if (!isset($options[$key]) && isset($spec['required']) && $spec['required']) {
        $errors[] = "Yml file missing required option: $key, description: {$spec['description']}";
      }
    }

    return $errors;
  }
  
  protected function initialize(InputInterface $input, OutputInterface $output)
  {

    parent::initialize($input, $output);
    $options = $input->getOptions();

    if (empty($options['project']) || empty($options['config'])) {
      $output->writeln('<error>The project and config option required.</error>');
      exit(1);
    }
    $project = $this->getDirectory($options['project']);

    if (!is_dir($project) || !file_exists($options['config'])) {
      $output->writeln('<error>The project directory or config file nonexistent.</error>');
      exit(1);
    }

    define("_JEXEC", 1);
    // Bootstrap the application
    if (!file_exists($project . '/installation/application/bootstrap.php')) {
      $output->writeln("<error>Installation application has been removed.\n</error>");
      exit(1);
    }
    require_once $project . '/installation/application/bootstrap.php';
    require_once $project . '/installation/model/database.php';
    require_once $project . '/installation/model/configuration.php';
    chdir($project . '/installation');

    require_once JPATH_LIBRARIES . '/import.legacy.php';
    require_once JPATH_LIBRARIES . '/cms.php';

    $input->setOption('project', $project);
  }
  
  protected function parseOptions(InputInterface $input)
  {

    $optionsMetadata = $this->getOptionsMetadata();
    $options = array();
    $config = Yaml::parse(file_get_contents($input->getOption('config')))['options'];

    foreach ($optionsMetadata as $key => $spec) {
      if (isset($config[$key])) {
        $options[$key] = (isset($spec['type']) && $spec['type'] == 'bool') ? (bool)$config[$key] : $config[$key];
      } elseif (isset($spec['factory'])) {
        $options[$key] = call_user_func($spec['factory']);
      } elseif (isset($spec['default'])) {
        $options[$key] = $spec['default'];
      }
    }

    return $options;
  }

  protected function getOptionsMetadata()
  {
    $optionsMetadata = array(
      'admin_email' => array(
        'description' => 'Admin user\'s email',
        'required' => true,
      ),
      'admin_password' => array(
        'description' => 'Admin user\'s password',
        'required' => true,
      ),
      'admin_user' => array(
        'description' => 'Admin user\'s username',
        'default' => 'admin',
      ),
      'db_host' => array(
        'description' => 'Hostname (or hostname:port)',
        'default' => '127.0.0.1:3306',
      ),
      'db_name' => array(
        'description' => 'Database name',
        'required' => true,
      ),
      'db_old' => array(
        'description' => 'Policy to use with old DB [remove,backup]]',
        'default' => 'backup',
      ),
      'db_pass' => array(
        'description' => 'Database password',
        'required' => true,
      ),
      'db_prefix' => array(
        'description' => 'Table prefix',
        'factory' => function () {
          // FIXME: Duplicated from installation/model/fields/prefix.php
          $size = 5;
          $prefix = '';
          $chars = range('a', 'z');
          $numbers = range(0, 9);

          // We want the fist character to be a random letter:
          shuffle($chars);
          $prefix .= $chars[0];

          // Next we combine the numbers and characters to get the other characters:
          $symbols = array_merge($numbers, $chars);
          shuffle($symbols);

          for ($i = 0, $j = $size - 1; $i < $j; ++$i) {
            $prefix .= $symbols[$i];
          }

          // Add in the underscore:
          $prefix .= '_';

          return $prefix;
        },
      ),
      'db_type' => array(
        'description' => 'Database type [mysql,mysqli,pdomysql,postgresql,sqlsrv,sqlazure]',
        'default' => 'mysqli',
      ),
      'db_user' => array(
        'description' => 'Database user',
        'required' => true,
      ),
      'helpurl' => array(
        'description' => 'Help URL',
        'default' => 'http://help.joomla.org/proxy/index.php?option=com_help&amp;keyref=Help{major}{minor}:{keyref}',
      ),
      'language' => array(
        'description' => 'Language',
        'default' => 'en-GB',
      ),
      'site_metadesc' => array(
        'description' => 'Site description',
        'default' => ''
      ),
      'site_name' => array(
        'description' => 'Site name',
        'default' => 'Joomla'
      ),
      'site_offline' => array(
        'description' => 'Set site as offline',
        'default' => 0,
        'type' => 'bool',
      ),
      'sample_file' => array(
        'description' => 'Sample SQL file (sample_blog.sql,sample_brochure.sql,...)',
        'default' => '',
      ),
      'summary_email' => array(
        'description' => 'Send email notification',
        'default' => 0,
        'type' => 'bool',
      ),
    );

    // Installer internally has an option "admin_password2", but it
    // doesn't seem to be necessary.

    return $optionsMetadata;
  }

}