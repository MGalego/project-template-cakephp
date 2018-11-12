<?php
namespace App\Test\TestCase\Shell;

use App\Shell\CronShell;
use Cake\Console\Shell;
use Cake\TestSuite\ConsoleIntegrationTestCase;

/**
 * App\Shell\CronShell Test Case
 */
class CronShellTest extends ConsoleIntegrationTestCase
{

    public $fixtures = [
        'app.users',
        'app.scheduled_jobs',
        'app.scheduled_job_logs',
    ];

    /**
     * ConsoleIo mock
     *
     * @var \Cake\Console\ConsoleIo|\PHPUnit_Framework_MockObject_MockObject
     */
    public $io;

    /**
     * Test subject
     *
     * @var \App\Shell\CronShell
     */
    public $CronShell;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->io = $this->getMockBuilder('Cake\Console\ConsoleIo')->getMock();
        $this->CronShell = new CronShell($this->io);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->CronShell);

        parent::tearDown();
    }

    /**
     * Test main method
     *
     * @return void
     */
    public function testMain()
    {
        $this->exec('cron');
        $this->assertExitCode(Shell::CODE_SUCCESS);
    }

    /**
     * @dataProvider fileAndClassNamesProvider
     * @return void
     */
    public function testLock($file, $class, $normalized) : void
    {
        $this->exec(sprintf('cron lock %s %s', $file, $class));

        $expected = sprintf('%s%s_%s.lock.lock', sys_get_temp_dir() . DS, $normalized, md5($file));
        $this->assertTrue(file_exists($expected));
    }

    public function fileAndClassNamesProvider() : array
    {
        return [
            ['foo', 'bar', 'bar'],
            ['foo1', 'bar1', 'bar1'],
            ['foo_1234', 'bar__1', 'bar__1'],
            ['foo\1234', 'b*a@r!5', 'b_a_r_5'],
            ['foos', null, ''],
            ['foos', '', ''],
            ['foos', '1', '1']
        ];
    }
}
