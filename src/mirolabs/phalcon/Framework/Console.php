<?php

namespace mirolabs\phalcon\Framework;

use mirolabs\phalcon\Framework\Container\Parser;
use Phalcon\CLI\Console as ConsoleApp;
use mirolabs\phalcon\Framework\Services\Console as ConsoleDi;
use Symfony\Component\Yaml\Yaml;

class Console extends ConsoleApp
{
    /**
     * @var Yml
     */
    private $modules;

    private $projectPath;

    private $dev;

    private $args;

    public function __construct($args, $projectPath, $dev = true)
    {
        $this->args = $args;
        $this->projectPath = $projectPath;
        $this->dev = $dev;
        parent::__construct();
    }

    protected  function loadModules()
    {
        $this->modules = Yaml::parse($this->projectPath. '/config/modules.yml');
        $this->registerModules($this->modules);
    }

    protected function loadServices()
    {
        $services = new ConsoleDi($this->projectPath, $this->modules, $this->dev);
        $di = $services->createContainer();
        $services->setListenerManager($di);
        $services->registerUserServices($di);
        $services->setDb($di);
        $services->setTranslation($di);
        $this->setDI($di);
    }

    protected function getArguments()
    {
        $tasks = $this->getTaskList();
        $this->addStandardTask($tasks);
        if(count($this->args) < 2) {
            $this->displayList($tasks);
        }
        if (!array_key_exists($this->args[1], $tasks)) {
            throw new Phalcon\Exception('command unavailable');
        }
        return $this->getArgumentsFromTask($tasks[$this->args[1]]);
    }

    public function main()
    {
        try {
            $this->loadModules();
            $this->loadServices();
            $this->handle($this->getArguments());
        } catch (Phalcon\Exception $e) {
            echo $e->getMessage();
            exit(1);
        }

    }


    protected function addStandardTask(&$tasks)
    {
        $tasks['createModule'] = [
            'class' => 'mirolabs\phalcon\Framework\Tasks\Project',
            'action' => 'createModule',
            'description' => 'createModule',
            'params' => [$this->projectPath]
        ];
    }


    protected function getTaskList()
    {
        return file_get_contents($this->projectPath . '/' . Module::COMMON_CACHE . '/' . Parser::CACHE_TASKS);
    }

    protected function displayList($tasks)
    {
        foreach($tasks as $name => $param) {
            echo sprintf("%s - %s\n", $name, $param['description']);
        }
        exit(0);
    }

    protected function getArgumentsFromTask($task)
    {
        $data['task'] = $task['class'];
        $data['action'] = $task['action'];
        $data['params'] = [];
        foreach($task['params'] as $param) {
            if ($param['type'] == 'service') {
                $data['params'][] = $this->getDI()->get($param['name']);
            } else {
                $data['params'][] = $param['value'];
            }
        }

        return $data;
    }

} 