<?php

namespace MrBohem\Larasync\Commands;

use Illuminate\Console\Command;

class LarasyncCommand extends Command
{
    public $signature = 'larasync';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
