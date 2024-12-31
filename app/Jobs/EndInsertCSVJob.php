<?php

namespace App\Jobs;

use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EndInsertCSVJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    private string $filepath;

    /**
     * Create a new job instance.
     */
    public function __construct(string $filepath)
    {
        $this->filepath = $filepath;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $app_url = env('APP_URL', '');
        $link = "{$app_url}/storage/{$this->filepath}";

        $data = [
            'message' => "export successfully link",
            'link' => $link
        ];

        Notification::create([
            'name' => 'export_large_csv',
            'data' => json_encode($data)
        ]);
    }
}
