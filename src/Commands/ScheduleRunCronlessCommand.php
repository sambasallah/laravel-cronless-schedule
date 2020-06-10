<?php

namespace Spatie\CronlessSchedule\Commands;

use Clue\React\Stdio\Stdio;
use Illuminate\Console\Command;
use React\EventLoop\LoopInterface;

class ScheduleRunCronlessCommand extends Command
{
    public $signature = 'cronless-schedule:run {--frequency=60} {--command=schedule:run}';

    public $description = 'Run the scheduler';

    protected ?string $command = null;

    protected LoopInterface $loop;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;

        parent::__construct();
    }

    public function handle()
    {
        $frequency = $this->option('frequency');
        $this->command = $this->option('command');

        $this
            ->outputHeader($frequency, $this->command)
            ->scheduleCommand($this->loop, $frequency)
            ->registerKeypressHandler($this->loop)
            ->runSchedule();

        $this->loop->run();
    }

    protected function outputHeader(int $frequency, string $command): self
    {
        $this->comment("Will execute {$command} every {$frequency} seconds...");
        $this->comment("Press enter to manually invoke a run...");
        $this->comment('-------------------------------------------------------');
        $this->comment('');

        return $this;
    }

    protected function scheduleCommand(LoopInterface $loop, int $frequency): self
    {
        $loop->addPeriodicTimer($frequency, fn () => $this->runSchedule());

        return $this;
    }

    protected function registerKeypressHandler(LoopInterface $loop): self
    {
        $stdio = new Stdio($loop);

        $stdio->setEcho(false);

        $stdio->on('data', fn () => $this->runSchedule());

        return $this;
    }

    protected function runSchedule()
    {
        $this->comment($this->timestamp('Running schedule...'));

        $this->call($this->command);

        $this->comment($this->timestamp('Schedule run finished.'));
        $this->comment('');
    }

    protected function timestamp(string $message): string
    {
        $currentTime = now()->format('Y-m-d H:i:s');

        return "[{$currentTime}] - {$message}";
    }
}