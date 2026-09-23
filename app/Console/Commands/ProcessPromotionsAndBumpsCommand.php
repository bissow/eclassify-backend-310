<?php

namespace App\Console\Commands;

use App\Services\PromotionService;
use Illuminate\Console\Command;

class ProcessPromotionsAndBumpsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'promotions:rotate-deals-and-bumps';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotate Deals of the Day, process scheduled Daily Bump Ups, and expire outdated promotions and items';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting scheduled promotions and daily bumps processing...');

        $results = PromotionService::processScheduledBumpsAndExpirations();

        $this->info("Successfully processed Daily Bumps: {$results['bumped_items']}");
        $this->info("Successfully expired outdated items: {$results['expired_items']}");

        return Command::SUCCESS;
    }
}
