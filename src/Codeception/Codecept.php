<?php
namespace Codeception;

use \Symfony\Component\Yaml\Yaml;
use \Symfony\Component\Finder\Finder;
use \Symfony\Component\EventDispatcher\EventDispatcher;

class Codecept
{
    const VERSION = "1.0.0beta";

    /**
     * @var \Codeception\Runner
     */
    protected $runner;
    /**
     * @var \PHPUnit_Framework_TestResult
     */
    protected $result;

    /**
     * @var \Monolog\Handler\StreamHandler
     */
    protected $logHandler;

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcher
     */
    protected $dispatcher;

    /**
     * @var array
     */
    protected $options = array(
        'silent' => false,
        'debug' => false,
        'steps' => false,
        'html' => false,
        'report' => false,
        'colors' => false,
        'log' => true
    );

    public function __construct($options = array()) {
        $this->result = new \PHPUnit_Framework_TestResult;
        $this->dispatcher = new EventDispatcher();
        $this->config = \Codeception\Configuration::config();
        $this->options = $this->mergeOptions($options);
        $this->path = $this->config['paths']['tests'];
        $this->registerSubscribers();
        $this->registerListeners();

    }

    private function mergeOptions($options) {

        foreach ($this->options as $option => $default) {
            $value = isset($options[$option]) ? $options[$option] : $default;
            if (!$value) {
                $options[$option] = isset($this->config['settings'][$option]) ? $this->config['settings'][$option] : $this->options[$option];
            }
        }

        if ($options['report']) $options['silent'] = true;
        if ($options['html']) $options['html'] = $this->config['paths']['output'] . '/result.html';

        return $options;
    }

    protected function registerListeners() {
        $listener = new \Codeception\PHPUnit\Listener($this->dispatcher);
        $this->result->addListener($listener);
    }

    public function registerSubscribers() {
        $this->dispatcher->addSubscriber(new \Codeception\Subscriber\Module());
        $this->dispatcher->addSubscriber(new \Codeception\Subscriber\Cest());
        $this->dispatcher->addSubscriber(new \Codeception\Subscriber\Console($this->options));
        $this->dispatcher->addSubscriber(new \Codeception\Subscriber\Logger());
        $this->dispatcher->addSubscriber(new \Codeception\Subscriber\ErrorHandler());
    }

    public function runSuite($suite, $test = null) {
        $settings = \Codeception\Configuration::suiteSettings($suite, $this->config);
        $suiteManager = new \Codeception\SuiteManager($this->dispatcher, $suite, $settings);

        $test ? $suiteManager->loadTest($settings['path'].$test) : $suiteManager->loadTests();

        $this->runner = $suiteManager->run($this->result, $this->options);

        return $this->result;
    }

    public static function versionString() {
   	    return 'Codeception PHP Testing Framework v'.self::VERSION;
   	}
    
    public function printResult() {
        $this->runner->getPrinter()->printResult($this->getResult());
    }

    /**
     * @return \PHPUnit_Framework_TestResult
     */
    public function getResult()
    {
        return $this->result;
    }

    public function getOptions() {
        return $this->options;
    }

}
