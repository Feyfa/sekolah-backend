<?php

namespace App\Jobs;

use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EndInsertCSVJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    private string $notification_id;

    /**
     * Create a new job instance.
     */
    public function __construct($notification_id)
    {
        $this->notification_id = $notification_id;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Log::info('EndInsertCSVJob Memory usage before job: ' . round(memory_get_usage() / 1024 / 1024, 2) . ' MB');

        /* UPDATE STATUS ACTIVE NOTIFICATION */
        Notification::where('id', $this->notification_id)
                    ->update([
                        'active' => 'T'
                    ]);
        /* UPDATE STATUS ACTIVE NOTIFICATION */

        // Log::info('EndInsertCSVJob Memory usage after job: ' . round(memory_get_usage() / 1024 / 1024, 2) . ' MB');
    }
}
