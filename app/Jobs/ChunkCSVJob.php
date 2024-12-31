<?php

namespace App\Jobs;

use App\Models\DataLarge;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;

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
        $data = DataLarge::select('id','data1','data2','data3','created_at','updated_at')
                         ->get()
                         ->toArray();
        $dataCount = count($data);

        $sizeChunk = 2000;        
        if($dataCount > 20000)
            $sizeChunk = ceil($dataCount / 5); // mempertahankan agar yang masuk ke chain itu 5 job + 1 job
        // info("sizeChunk = $sizeChunk");
        $data = array_chunk($data, $sizeChunk);

        $dataCount = DataLarge::count();
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
        foreach($data as $item)
        {
            $jobs[] = new InsertCSVJob($item, $filename);
        }
        $jobs[] = new EndInsertCSVJob($filepath);

        Bus::chain($jobs)->dispatch();
        /* JOB INSERT JOB */
    }

    private function generateRandomString($length) 
    {
        $bytes = random_bytes($length);
        return substr(bin2hex($bytes), 0, $length);
    }

}
