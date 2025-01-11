<?php

namespace App\Jobs;

use App\Models\DataLarge;
use App\Models\FailedLeadRecord;
use App\Models\Notification;
use Exception;
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

    public $tries = 1;
    public $timeout = 600;

    private $notificationid;

    /**
     * Create a new job instance.
     */
    public function __construct($notificationid)
    {
        $this->notificationid = $notificationid;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        ini_set('memory_limit', '512M');

        $startTime = microtime(true);
        Log::info('ChunkCSVJob Memory usage before job: ' . round(memory_get_usage() / 1024 / 1024, 2) . ' MB');

        $dataCount = FailedLeadRecord::count();
        $sizeChunk = 2000;
        if($dataCount > 300000)
            $sizeChunk = ceil($dataCount / 80);
        else if($dataCount > 250000)
            $sizeChunk = ceil($dataCount / 70);
        else if($dataCount > 200000)
            $sizeChunk = ceil($dataCount / 60);
        else if($dataCount > 150000)
            $sizeChunk = ceil($dataCount / 50);
        else if($dataCount > 100000)
            $sizeChunk = ceil($dataCount / 40);
        if($dataCount > 50000)
            $sizeChunk = ceil($dataCount / 30);
        else if($dataCount > 20000)
            $sizeChunk = ceil($dataCount / 20);

        /* CREATE FOLDER CSV IF FOLDER NOT EXISTS */
        $folderPath = storage_path("app/public/csv");
        if (!file_exists($folderPath)) 
            mkdir($folderPath, 0777, true);
        /* CREATE FOLDER CSV IF FOLDER NOT EXISTS */

        $randomString = $this->generateRandomString(10);
        $filepath = "failedleadrecords-{$dataCount}row-{$randomString}.csv";
        $path = "csv/$filepath";
        /* IF EXISTS GENERATE RANDOM STRING AGAIN */
        while (Storage::disk('public')->exists($path)) 
        {
            Log::info('masuk while');
            $randomString = $this->generateRandomString(10);
            $filepath = "failedleadrecords-{$dataCount}row-{$randomString}.csv";
            $path = "csv/$filepath";
        }
        /* IF EXISTS GENERATE RANDOM STRING AGAIN */
        $filename = storage_path("app/public/$path");
        $file = fopen($filename, 'w');

        if ($file === false)
        {
            fclose($file);
            return;
        }

        $headers = ['function','type','blocked_type','description','campaign_id','md5_email','url','module_type','data_lead','updated_at','created_at'];
        $headersFormat = $this->formatCsvLine($headers);
        fwrite($file, $headersFormat . PHP_EOL);
        fclose($file);

        /* INSERT NOTIFICATION */
        $app_url = env('APP_URL', '');
        $link = "{$app_url}/download/csv/{$filepath}";
        $data = [
            'link' => $link
        ];

        $notificationid = $this->notificationid;
        Notification::where('id', $notificationid)
                    ->update([
                        'data' => json_encode($data, JSON_UNESCAPED_SLASHES),
                    ]);
        /* INSERT NOTIFICATION */

        /* JOB */
        FailedLeadRecord::select('function','type','blocked_type','description','leadspeek_api_id','email_encrypt','url','leadspeek_type','data_lead','updated_at','created_at')
                        ->chunk($sizeChunk, function ($rows) use ($filename, $dataCount, $notificationid) {
                            $data = $rows->toArray();
                            InsertCSVJob::dispatch($data, $filename, $dataCount, $notificationid)->onQueue('insert_export_csv');
                        });
        /* JOB */

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        Log::info('ChunkCSVJob Memory usage after job: ' . round(memory_get_usage() / 1024 / 1024, 2) . ' MB , duration = ' . $duration . ' Second');
    }

    public function failed(Exception $e)
    {
        Log::info([
            'action' => 'failed ChunkCSVJob',
            'error' => $e->getMessage()
        ]);
        Notification::where('id', $this->notificationid)
                    ->update([
                        'active' => 'T'
                    ]);
    }

    private function formatCsvLine(array $data): string
    {
        // Bungkus setiap elemen dalam tanda kutip ganda dan gabungkan dengan koma
        return implode(',', array_map(function ($item) {
            return '"' . str_replace('"', '""', $item) . '"'; // Escape tanda kutip ganda
        }, $data));
    }


    private function generateRandomString($length) 
    {
        $bytes = random_bytes($length);
        return substr(bin2hex($bytes), 0, $length);
    }

}
