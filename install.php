<?php
/**
 * Created by PhpStorm.
 * User: ZhangJiannan
 * Date: 2017/5/19
 * Time: 11:08
 */
/**
 * @package    Joomla.Cli
 *
 * @copyright  Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * TODO description
 */

// Set flag that this is a parent file.
const _JEXEC = 1;

error_reporting(E_ALL | E_NOTICE);
ini_set('display_errors', 1);

// Bootstrap the application
if (!file_exists(dirname(__DIR__) . '/installation/application/bootstrap.php')) {
  die("Installation application has been removed.\n");
}

require_once dirname(__DIR__) . '/installation/application/bootstrap.php';
require_once dirname(__DIR__) . '/installation/model/database.php';
require_once dirname(__DIR__) . '/installation/model/configuration.php';
chdir(dirname(__DIR__) . '/installation');

require_once JPATH_LIBRARIES . '/import.legacy.php';
require_once JPATH_LIBRARIES . '/cms.php';

/**
 * TODO description
 *
 * @package  Joomla.Cli
 * @since    3.4
 */
class JApplicationCliInstaller extends JApplicationCli
{
  /**
   * Get a list of command-line options
   *
   * @return array each item is keyed by the installation system's internal option name; values arrays with keys:
   *   - arg: string, the name of the command-line argument
   *   - required: bool, an indication of whether the value is required
   *   - default: mixed, default value to use if none is provided
   *   - factory: callable, a fnction which produces the default value
   *       - type: string, e.g. "bool"
   *
   * @since  3.4
   */
  public function getOptionsMetadata()
  {
    $optionsMetadata = array(
      'help' => array(
        'arg' => 'help',
        'description' => 'Display help',
        'type' => 'bool',
      ),
      'admin_email' => array(
        'arg' => 'admin-email',
        'description' => 'Admin user\'s email',
        'required' => true,
      ),
      'admin_password' => array(
        'arg' => 'admin-pass',
        'description' => 'Admin user\'s password',
        'required' => true,
      ),
      'admin_user' => array(
        'arg' => 'admin-user',
        'description' => 'Admin user\'s username',
        'default' => 'admin',
      ),
      'db_host' => array(
        'arg' => 'db-host',
        'description' => 'Hostname (or hostname:port)',
        'default' => '127.0.0.1:3306',
      ),
      'db_name' => array(
        'arg' => 'db-name',
        'description' => 'Database name',
        'required' => true,
      ),
      'db_old' => array(
        'arg' => 'db-old',
        'description' => 'Policy to use with old DB [remove,backup]]',
        'default' => 'backup',
      ),
      'db_pass' => array(
        'arg' => 'db-pass',
        'description' => 'Database password',
        'required' => true,
      ),
      'db_prefix' => array(
        'arg' => 'db-prefix',
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
        'arg' => 'db-type',
        'description' => 'Database type [mysql,mysqli,pdomysql,postgresql,sqlsrv,sqlazure]',
        'default' => 'mysqli',
      ),
      'db_user' => array(
        'arg' => 'db-user',
        'description' => 'Database user',
        'required' => true,
      ),
      'helpurl' => array(
        'arg' => 'help-url',
        'description' => 'Help URL',
        'default' => 'http://help.joomla.org/proxy/index.php?option=com_help&amp;keyref=Help{major}{minor}:{keyref}',
      ),
      // FIXME: Not clear if this is useful. Seems to be "the language of the installation application"
      // and not "the language of the installed CMS"
      'language' => array(
        'arg' => 'lang',
        'description' => 'Language',
        'default' => 'en-GB',
      ),
      'site_metadesc' => array(
        'arg' => 'desc',
        'description' => 'Site description',
        'default' => ''
      ),
      'site_name' => array(
        'arg' => 'name',
        'description' => 'Site name',
        'default' => 'Joomla'
      ),
      'site_offline' => array(
        'arg' => 'offline',
        'description' => 'Set site as offline',
        'default' => 0,
        'type' => 'bool',
      ),
      'sample_file' => array(
        'arg' => 'sample',
        'description' => 'Sample SQL file (sample_blog.sql,sample_brochure.sql,...)',
        'default' => '',
      ),
      'summary_email' => array(
        'arg' => 'email',
        'description' => 'Send email notification',
        'default' => 0,
        'type' => 'bool',
      ),
    );

    // Installer internally has an option "admin_password2", but it
    // doesn't seem to be necessary.
    foreach (array_keys($optionsMetadata) as $key) {
      if (!isset($optionsMetadata[$key]['type'])) {
        $optionsMetadata[$key]['type'] = 'raw';
      }
      if (!isset($optionsMetadata[$key]['syntax'])) {
        if ($optionsMetadata[$key]['type'] == 'bool') {
          $optionsMetadata[$key]['syntax'] = '--' . $optionsMetadata[$key]['arg'];
        } else {
          $optionsMetadata[$key]['syntax'] = '--' . rtrim($optionsMetadata[$key]['arg'], ':') . '="..."';
        }
      }
    }

    return $optionsMetadata;
  }

  /**
   * Entry point for the script
   *
   * @return  void
   *
   * @since   3.4
   */
  public function doExecute()
  {
    JFactory::getApplication('CliInstaller');

    // Parse options
    $options = $this->parseOptions();

    if (array_key_exists('help', $options)) {
      $this->displayUsage();
      $this->close(0);
    }

    $errors = $this->validateOptions($options);

    if (!empty($errors)) {
      foreach ($errors as $error) {
        $this->enqueueMessage($error, 'fatal');
      }

      $this->displayUsage();
      $this->close(1);
    }

    // Attempt to initialise the database.
    $db = new InstallationModelDatabase;
    if (!$db->createDatabase($options)) {
      $this->fatal("Error executing createDatabase");
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
        $this->fatal("Error executing handleOldDatabase");
      }
    }

    if (!$db->createTables($options)) {
      $this->fatal("Error executing createTables");
    }

    // Attempt to setup the configuration.
    $configuration = new InstallationModelConfiguration;

    if (!$configuration->setup($options)) {
      $this->fatal("Error executing setup");
    }

    // Attempt to create the database tables.
    if ($options['sample_file']) {
      if (!$db->installSampleData($options)) {
        $this->fatal("Error executing installSampleData");
      }
    }

    $this->out('Successfully installed.');
  }

  /**
   * Display help text
   *
   * @return void
   *
   * @since  3.4
   */
  public function displayUsage()
  {
    $this->out("Install Joomla");
    $this->out("usage: php install.php [options]");

    foreach ($this->getOptionsMetadata() as $spec) {
      $syntax = sprintf("%-25s", $spec['syntax']);

      if (isset($spec['description'])) {
        $syntax .= $spec['description'];
      }

      if (isset($spec['required']) && $spec['required']) {
        $syntax .= ' (required)';
      }

      if (isset($spec['default'])) {
        $syntax .= " (default: {$spec['default']})";
      }

      if (isset($spec['factory'])) {
        $syntax .= " (default: auto-generated)";
      }

      $this->out("	" . $syntax);
    }
  }

  /**
   * Validate the inputs
   *
   * @param   array $options parsed input values
   *
   * @return  array  An array of error messages
   *
   * @since   3.4
   */
  public function validateOptions($options)
  {
    $optionsMetadata = $this->getOptionsMetadata();
    $errors = array();

    foreach ($optionsMetadata as $key => $spec) {
      if (!isset($options[$key]) && isset($spec['required']) && $spec['required']) {
        $errors[] = "Missing required option: {$spec['syntax']}";
      }
    }

    return $errors;
  }

  /**
   * Parse all options from the command-line
   *
   * @return array
   *
   * @since  3.4
   */
  public function parseOptions()
  {
    global $argv;

    if (count($argv) <= 1) {
      return array('help' => 1);
    }

    $optionsMetadata = $this->getOptionsMetadata();
    $options = array();

    foreach ($optionsMetadata as $key => $spec) {
      if ($this->input->get($spec['arg'], null, $spec['type'])) {
        $options[$key] = $this->input->get($spec['arg'], null, $spec['type']);
      } elseif (isset($spec['factory'])) {
        $options[$key] = call_user_func($spec['factory']);
      } elseif (isset($spec['default'])) {
        $options[$key] = $spec['default'];
      }
    }

    return $options;
  }

  /**
   * Enqueue a system message.
   *
   * @param   string $msg The message to enqueue.
   * @param   string $type The message type. Default is message.
   *
   * @return  void
   *
   * @since   3.4
   */
  public function enqueueMessage($msg, $type = 'message')
  {
    $this->out("[$type] $msg");
  }

  /**
   * Trigger a fatal error
   *
   * @param   string $msg The message to enqueue.
   *
   * @return  void
   *
   * @since   3.4
   */
  public function fatal($msg)
  {
    $this->enqueueMessage($msg, 'fatal');
    $this->close(1);
  }

  /**
   * Returns the installed language files in the administrative and
   * front-end area.
   *
   * @param   mixed $db JDatabaseDriver instance.
   *
   * @return  array  Array with installed language packs in admin and site area.
   *
   * @since   3.4
   */
  public function getLocaliseAdmin($db = false)
  {
    // Read the files in the admin area
    $path = JLanguage::getLanguagePath(JPATH_ADMINISTRATOR);
    $langfiles['admin'] = JFolder::folders($path);

    // Read the files in the site area
    $path = JLanguage::getLanguagePath(JPATH_SITE);
    $langfiles['site'] = JFolder::folders($path);

    if ($db) {
      $langfiles_disk = $langfiles;
      $langfiles = array();
      $langfiles['admin'] = array();
      $langfiles['site'] = array();

      $query = $db->getQuery(true)
        ->select($db->quoteName(array('element', 'client_id')))
        ->from($db->quoteName('#__extensions'))
        ->where($db->quoteName('type') . ' = ' . $db->quote('language'));
      $db->setQuery($query);
      $langs = $db->loadObjectList();

      foreach ($langs as $lang) {
        switch ($lang->client_id) {
          // Site
          case 0 :
            if (in_array($lang->element, $langfiles_disk['site'])) {
              $langfiles['site'][] = $lang->element;
            }

            break;

          // Administrator
          case 1 :
            if (in_array($lang->element, $langfiles_disk['admin'])) {
              $langfiles['admin'][] = $lang->element;
            }

            break;
        }
      }
    }

    return $langfiles;
  }
}

JApplicationCli::getInstance('JApplicationCliInstaller')->execute();