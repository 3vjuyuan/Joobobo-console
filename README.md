# Joobobo-console

## Installing Joobobo-console

Install Composer by following the [installation instructions](http://https://getcomposer.org/download/) which boils down to this in the simplest case:

`curl -sS https://getcomposer.org/installer | php`

`mv composer.phar /usr/local/bin/composer`

`composer install`


## Command Line Tool

To get an overview of all available commands, enter ./joocon.php:

`sudo chmod 755 joocon.php`

`./joocon.php`

```
Joobobo CLI 0.0.1

Usage:
  command [options] [arguments]

Options:
  -h, --help            Display this help message
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi            Force ANSI output
      --no-ansi         Disable ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Available commands:
  help             Displays help for a command
  list             Lists commands
 site
  site:create      Create a new Joobobo site
  site:destroy     Destroy a new Joobobo site
  site:initialize  Set the server configuration file
```

**The site:create command**
_ _ _

To get an overview of all available commands, enter ./joocon.php site:create -h:

`./joocon.php site:create -h`

```
Usage:
  site:create [options]

Options:
  -r, --release=RELEASE  Give the release version of Joomla!
  -t, --target=TARGET    The directory of the project.
  -s, --server=SERVER    The server type of the project configuration file. [default: "apache"]
  -g, --git[=GIT]        Joomla git source [default: "https://git.intern.3vjuyuan.com/wenmengyu/joomla-cms.git"]
  -h, --help             Display this help message
  -q, --quiet            Do not output any message
  -V, --version          Display this application version
      --ansi             Force ANSI output
      --no-ansi          Disable ANSI output
  -n, --no-interaction   Do not ask any interactive question
  -v|vv|vvv, --verbose   Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  Create a new Joobobo site
```
Let’s create a project test.

`sudo ./joocon.php site:create -t /website/test`


The Joobobo-console will Create a project directory using git clone for the corresponding version of joomla.

```
Cloning into '/website/test'...
remote: Counting objects: 8518, done.
remote: Compressing objects: 100% (6270/6270), done.
remote: Total 8518 (delta 1607), reused 8514 (delta 1607)
Receiving objects: 100% (8518/8518), 11.68 MiB | 0 bytes/s, done.
Resolving deltas: 100% (1607/1607), done.
Checking connectivity... done.
The project create successfully. Project path /website/test
```

In order to provide write access to certain directories for both, you will need to set the directorie permissions accordingly.

`sudo chown -R vagrant:vagrant /website/test`

**The site:initialize command**
_ _ _

To get an overview of all available commands, enter ./joocon.php site:initialize -h:
`./joocon.php site:create -h`

```
Usage:
  site:initialize [options]

Options:
  -c, --config=CONFIG    The yml config file of the initialize project.
  -p, --project=PROJECT  The project directory
  -h, --help             Display this help message
  -q, --quiet            Do not output any message
  -V, --version          Display this application version
      --ansi             Force ANSI output
      --no-ansi          Disable ANSI output
  -n, --no-interaction   Do not ask any interactive question
  -v|vv|vvv, --verbose   Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  Set the server configuration file
```

The Project initialization requires a YML configuration file.

```
options:
    admin_email: '' # required description: Admin user''s email
    admin_password: '' #r equired description: Admin user''s password
    db_name: '' # required description: Database name
    db_user: '' # required description: Database user
    db_pass: '' # required description: Database password
    admin_user: '' # description: Admin user''s username default: admin
    db_host: '' # description: Hostname (or hostname:port) default: 127.0.0.1:3306
    db_old: '' # description: Policy to use with old DB [remove,backup]] default: backup
    db_prefix: '' # description: Table prefix
    db_type: '' # description: Database type [mysql,mysqli,pdomysql,postgresql,sqlsrv,sqlazure] default: mysqli
    helpurl: '' # description: Help URL default: http://help.joomla.org/proxy/index.php?option=com_help&amp;keyref=Help{major}{minor}:{keyref}
    language: '' # description: Language default: en-GB
    site_metadesc: '' # description: Site description default: ''
    site_name: '' # description: Site name default: Joomla
    site_offline: '' # description: Set site as offline default: 0 type: bool
    sample_file: '' # description: Sample SQL file (sample_blog.sql,sample_brochure.sql,...) default: ''
    summary_email: '' # description: Send email notification default: 0 type: bool'
```

The project initialize.

`./joocon.php site:initialize -c /home/vagrant/config.yml -p /website/test/`

```
Successfully initialize.
```




