<?php
namespace App\Shell\Task;

use Cake\Console\ConsoleOptionParser;
use Cake\Console\Shell;
use Cake\I18n\Time;

/**
 *  Adds scheduled job for running pre-saved searches cleanup once a day.
 */
class Upgrade20190315152328Task extends Shell
{
    /**
     * {@inheritDoc}
     */
    public function getOptionParser()
    {
        $parser = new ConsoleOptionParser('console');

        return $parser;
    }

    /**
     * Main method.
     *
     * @return void
     */
    public function main()
    {
        $task = $this->Tasks->load('ScheduledJobs');

        $task->add('CakeShell::Search:search', [
            'recurrence' => 'FREQ=DAILY;INTERVAL=1',
            'start_date' => new Time('now'),
            'options' => 'cleanup'
        ]);
    }
}