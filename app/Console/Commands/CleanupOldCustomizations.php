<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ModelCustomization;
use Carbon\Carbon;

class CleanupOldCustomizations extends Command
{
    protected $signature = 'customizations:cleanup';
    protected $description = 'Delete customizations not opened for 30 days';

    public function handle()
    {
        $limit = Carbon::now()->subDays(30);

        $old = ModelCustomization::where(function ($q) use ($limit) {
            $q->whereNull('last_opened_at')
              ->where('created_at', '<', $limit);
        })->orWhere('last_opened_at', '<', $limit)
          ->get();

        foreach ($old as $custom) {
            $custom->delete();
        }

        $this->info('Old customizations cleaned.');
    }
}
