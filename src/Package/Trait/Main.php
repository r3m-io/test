<?php
namespace Package\R3m\Io\Test\Trait;

use R3m\Io\Config;

use R3m\Io\Exception\FileWriteException;
use R3m\Io\Module\Core;
use R3m\Io\Module\Event;
use R3m\Io\Module\Dir;

use Exception;

use R3m\Io\Exception\FileAppendException;
use R3m\Io\Exception\ObjectException;

trait Main {

    /**
     * @throws ObjectException
     * @throws FileAppendException
     * @throws Exception
     */
    public function run_test($flags, $options): mixed
    {
        $object = $this->object();
        if($object->config(Config::POSIX_ID) !== 0){
            $exception = new Exception('Only root can run tests...');
            Event::trigger($object, 'r3m.io.test.main.run.test', [
                'options' => $options,
                'exception' => $exception
            ]);
            throw $exception;
        }
        Core::execute($object, 'composer show', $output, $notification);
        $packages = [];
        if($output){
            $data = explode(PHP_EOL, $output);
            foreach($data as $nr => $line){
                $line = trim($line);
                if($line){
                    $line = explode(' ', $line, 2);
                    $package = $line[0];
                    $record = trim($line[1]);
                    $line = explode(' ', $record, 2);
                    $version = $line[0];
                    $description = trim($line[1]);
                    $packages[$package] = [
                        'name' => $package,
                        'version' => $version,
                        'description' => $description
                    ];
                }
            }
            echo $output;
        }
        if($notification){
            echo $notification;
        }
        $dir = new Dir();
        $dir_vendor = $dir->read($object->config('project.dir.vendor'));

        $testable = [];
        $testable[] = 'r3m_io';

        foreach($dir_vendor as $nr => $record){
            $package = $record->name;
            if(
                in_array(
                    $package,
                    $testable,
                    true
                ) &&
                $record->type === Dir::TYPE
            ){
                $dir_inner = $dir->read($record->url);
                d($dir_inner);
            }
        }
        //collect every test directory and move them to the test directory
        //by default if file exist it wont be overwritten, so we need to implement option force & patch


        return $dir_vendor;
    }
}
