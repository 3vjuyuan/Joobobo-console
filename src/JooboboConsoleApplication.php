<?php
namespace Joobobo\Console;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\{OutputInterface, ConsoleOutput};
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Joobobo\Console\Factory\IFactory;

class JooboboConsoleApplication extends Application
{
    const ROOT = __DIR__.'/../../';
    /**
     * @var IFactory
     */
    protected $factory;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    public function __construct($name = '', $vesion = '', IFactory $factory = null)
    {
        // @todo read from composer.json and compare with MD5
        parent::__construct('Joobobo CLI', '0.0.1');

        if(!is_null($factory)) {
            $this->setFactory($factory);
        }
    }

    public function setFactory(IFactory $factory) {
        $this->factory = $factory;
    }

    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        if(!$this->factory) {
            throw new RuntimeException('No Command Factory is set.');
        }

        $this->addCommands($this->factory->createCommands());
        $this->addCommands($this->factory->destroyCommands());
        $this->addCommands($this->factory->initializeCommands());
        $this->input = $input;
        return parent::run($input, isset($output) ? $output : $this->getOutput($output));
    }

    /**
     * @return ConsoleOutput|OutputInterface
     */
    public function getOutput() { 
        if(!isset($this->output)) {
            $this->output = new ConsoleOutput();
            $this->output->getFormatter()->setStyle('b', new OutputFormatterStyle(null, null, array('bold')));
            $this->output->getFormatter()->setStyle('i', new OutputFormatterStyle('black', 'white'));
            $this->output->getFormatter()->setStyle('u', new OutputFormatterStyle(null, null, array('underscore')));
            $this->output->getFormatter()->setStyle('em', new OutputFormatterStyle(null, null, array('reverse')));
            $this->output->getFormatter()->setStyle('strike', new OutputFormatterStyle(null, null, array('conceal')));
            $this->output->getFormatter()->setStyle('success', new OutputFormatterStyle('green'));
            $this->output->getFormatter()->setStyle('warning', new OutputFormatterStyle('black', 'yellow'));
            $this->output->getFormatter()->setStyle('notice', new OutputFormatterStyle('yellow'));
            $this->output->getFormatter()->setStyle('info', new OutputFormatterStyle('white', null, array('bold')));
            $this->output->getFormatter()->setStyle('debug', new OutputFormatterStyle('white'));
        }

        return $this->output;
    }
}