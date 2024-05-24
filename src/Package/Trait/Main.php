<?php
namespace Package\R3m\Io\Test\Trait;

use R3m\Io\Config;

use R3m\Io\Exception\FileWriteException;
use R3m\Io\Module\Core;
use R3m\Io\Module\Event;
use R3m\Io\Module\File;

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
        Core::execute($object, 'composer show --dev', $output, $notification);
        if($output){
            echo $output;
        }
        if($notification){
            echo $notification;
        }
        return $object->config();
    }
}
