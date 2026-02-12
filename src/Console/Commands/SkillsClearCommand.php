<?php

namespace Laravel\Ai\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SkillsClearCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'skill:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the AI skill cache';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        Cache::store(config('ai.skills.cache'))->forget('ai_sdk_skills');

        $this->info('Skill cache cleared successfully.');

        return self::SUCCESS;
    }
}
