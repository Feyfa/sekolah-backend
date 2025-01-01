<?php

namespace App\Jobs;

use App\Models\DataLarge;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

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

        $randomString = $this->generateRandomString(10);
        $filepath = "datalarge-{$dataCount}row-{$randomString}.csv";
        $path = "app/public/$filepath";
        $filename = storage_path($path);
        $file = fopen($filename, 'w');

        if ($file === false)
            return;

        $headers = ['data1','data2','data3','created_at','updated_at'];
        fwrite($file, implode(',', $headers) . PHP_EOL);
        fclose($file);

        /* JOB INSERT JOB */
        $jobs = [];
        DataLarge::select('data1','data2','data3','created_at','updated_at')
                 ->chunk($sizeChunk, function ($rows) use (&$jobs, $filename) {
                    $data = $rows->toArray();
                    $jobs[] = (new InsertCSVJob($data, $filename))->onQueue('export_large_csv');
                 });
        $jobs[] = (new EndInsertCSVJob($filepath))->onQueue('export_large_csv');
        
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
