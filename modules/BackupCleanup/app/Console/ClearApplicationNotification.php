<?php

namespace Modules\BackupCleanup\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Notification\Models\Notification;

class ClearApplicationNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:old-notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deleting old notification from application';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $days = (int) config('app.notification_clear_after', '0');
            if ($days <= 0) {
                return 0;
            }
            $date = Carbon::now()->subDays($days);
            $count = Notification::where('created_at', '<', $date)->count();
            Notification::where('created_at', '<', $date)->delete();
            echo $count." notifications has been Cleaned. \n";
            Log::info('Notification cleaned. Total: '.$count);
        } catch (\Exception $e) {
            Log::error('Notification Delete command: '.$e->getMessage());
        }

        return 0;
    }
}
