<?php
namespace Package\R3m\Io\Test\Trait;

use R3m\Io\Config;

use R3m\Io\Module\Core;
use R3m\Io\Module\Event;
use R3m\Io\Module\Dir;
use R3m\Io\Module\File;

use Exception;

use R3m\Io\Exception\FileWriteException;
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

        if(!$dir_vendor){
            $exception = new Exception('No vendor directory found...');
            Event::trigger($object, 'r3m.io.test.main.run.test', [
                'options' => $options,
                'exception' => $exception
            ]);
            throw $exception;
        }

        $testable = [];
        $testable[] = 'r3m_io';
        if(
            property_exists($options, 'testable') &&
            is_array($options->testable)
        ){
            $testable = $options->testable;
        }
        $dir_tests = null;
        if(property_exists($options, 'directory_tests')){
            if(is_string($options->directory_tests)){
                $dir_tests = [$options->directory_tests];
            }
            elseif(is_array($options->directory_tests)){
                $dir_tests = $options->directory_tests;
            }
        }
        if($dir_tests === null){
            $dir_tests = [
                'test',
                'tests',
                'Test',
                'Tests'
            ];
        }
        if(!Dir::is($object->config('project.dir.test'))){
            Dir::create($object->config('project.dir.test'), Dir::CHMOD);
        }
        $testsuite = [];
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
                if($dir_inner){
                    foreach($dir_inner as $dir_inner_nr => $dir_record){
                        foreach($dir_tests as $dir_test){
                            $dir_test_url = $dir_record->url . $dir_test . $object->config('ds');
                            if(
                                File::exist($dir_test_url) &&
                                Dir::is($dir_test_url)
                            ){
//                                $command = 'vendor/bin/pest' # cannot be here
                                $dir_test_read = $dir->read($dir_test_url);
                                if($dir_test_read){
                                    foreach($dir_test_read as $dir_test_nr => $file){
                                        if($file->type === File::TYPE){
                                            $read = File::read($file->url);
                                            if(str_contains($read, 'PHPUnit\Framework\TestCase')){
                                                //we want pest tests
                                                continue;
                                            }
                                            $dir_target = $object->config('project.dir.test') .
                                                $dir_record->name .
                                                $object->config('ds');
                                            $target =
                                                $dir_target .
                                                $file->name
                                            ;
                                            if(!Dir::is($dir_target)){
                                                Dir::create($dir_target, Dir::CHMOD);
                                            }
                                            $testsuite[] = [
                                                'name' => $dir_record->name,
                                                'directory' => $dir_target
                                            ];
                                            if(!File::exist($target)){
                                                File::copy($file->url, $target);
                                            }
                                            //determine test type (pest / phpunit)
                                            //cp $dir_test_record->url to test directory
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        $url_xml = $object->config('project.dir.root') . 'phpunit.xml';
        $write = [];
        $write[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $write[] = '<phpunit bootstrap="Test/bootstrap.php"';
        $write[] = '         colors="true">';
        $write[] = '    <testsuites>';
        foreach($testsuite as $nr => $record){
            $write[] = '        <testsuite name="' . $record['name'] . '">';
            $write[] = '            <directory>' . $record['directory'] . '</directory>';
            $write[] = '        </testsuite>';
        }
        $write[] = '    </testsuites>';
        $write[] = '</phpunit>';
        File::write($url_xml, implode(PHP_EOL, $write));
        $write = [];
        $write[] = '<?php';
        $write[] = '';
        $write[] = 'require_once __DIR__ . \'/../vendor/autoload.php\';';
        $write[] = '';
        File::write($object->config('project.dir.test') . 'bootstrap.php', implode(PHP_EOL, $write));
        $command = './vendor/bin/pest --init';
        $code = Core::execute($object, $command, $output, $notification);
        if($output){
            echo $output;
        }
        if($notification){
            echo $notification;
        }
        if($code !== 0){
            $exception = new Exception('Pest initialization failed...');
            Event::trigger($object, 'r3m.io.test.main.run.test', [
                'options' => $options,
                'exception' => $exception,
                'output' => $output,
                'notification' => $notification
            ]);
            exit($code);
        }
        $command = './vendor/bin/pest';
        $code = Core::execute($object, $command, $output, $notification);
        if($output){
            echo $output;
        }
        if($notification){
            echo $notification;
        }
        if($code !== 0){
            $exception = new Exception('Pest Tests failed...');
            Event::trigger($object, 'r3m.io.test.main.run.test', [
                'options' => $options,
                'exception' => $exception,
                'output' => $output,
                'notification' => $notification
            ]);
            exit($code);
        }
//        $dir_output = $dir->read($object->config('project.dir.test'), true);
        return null;
    }
}
