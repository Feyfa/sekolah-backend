<?php

namespace App\Jobs;

use App\Models\DataLarge;
use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ChunkCSVJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Log::info('ChunkCSVJob Memory usage before job: ' . round(memory_get_usage() / 1024 / 1024, 2) . ' MB');

        $dataCount = DataLarge::count();
        $sizeChunk = 2000;
        if($dataCount > 50000)
            $sizeChunk = ceil($dataCount / 20); // mempertahankan agar yang masuk ke chain itu 20 job insertcsv + 1 job endinsertcsv
        else if($dataCount > 20000)
            $sizeChunk = ceil($dataCount / 15); // mempertahankan agar yang masuk ke chain itu 15 job insertcsv + 1 job endinsertcsv

        /* CREATE FOLDER CSV IF FOLDER NOT EXISTS */
        $folderPath = storage_path("app/public/csv");
        if (!file_exists($folderPath)) 
            mkdir($folderPath, 0777, true);
        /* CREATE FOLDER CSV IF FOLDER NOT EXISTS */

        $randomString = $this->generateRandomString(10);
        $filepath = "datalarge-{$dataCount}row-{$randomString}.csv";
        $path = "csv/$filepath";
        /* IF EXISTS GENERATE RANDOM STRING AGAIN */
        while (Storage::disk('public')->exists($path)) 
        {
            Log::info('masuk while');
            $randomString = $this->generateRandomString(10);
            $filepath = "datalarge-{$dataCount}row-{$randomString}.csv";
            $path = "csv/$filepath";
        }
        /* IF EXISTS GENERATE RANDOM STRING AGAIN */
        $filename = storage_path("app/public/$path");
        $file = fopen($filename, 'w');

        if ($file === false)
            return;

        $headers = ['data1','data2','data3','created_at','updated_at'];
        fwrite($file, implode(',', $headers) . PHP_EOL);
        fclose($file);

        /* INSERT NOTIFICATION */
        $app_url = env('APP_URL', '');
        $link = "{$app_url}/download/csv/{$filepath}";
        $data = [
            'link' => $link
        ];

        $notification = Notification::create([
            'user_id' => 1,
            'status' => 'success',
            'name' => 'download',
            'message' => 'export successfully link',
            'data' => json_encode($data),
            'active' => 'F'
        ]);
        $notification_id = $notification->id;
        /* INSERT NOTIFICATION */

        /* JOB INSERT JOB */
        $jobs = [];
        DataLarge::select('data1','data2','data3','created_at','updated_at')
                 ->chunk($sizeChunk, function ($rows) use (&$jobs, $filename) {
                    $data = $rows->toArray();
                    $jobs[] = (new InsertCSVJob($data, $filename))->onQueue('export_large_csv');
                 });
        $jobs[] = (new EndInsertCSVJob($notification_id))->onQueue('export_large_csv');
        
        Bus::chain($jobs)->dispatch();
        /* JOB INSERT JOB */

        // Log::info('ChunkCSVJob Memory usage after job: ' . round(memory_get_usage() / 1024 / 1024, 2) . ' MB');
    }

    private function generateRandomString($length) 
    {
        $bytes = random_bytes($length);
        return substr(bin2hex($bytes), 0, $length);
    }

}
