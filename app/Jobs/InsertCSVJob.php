<?php

namespace App\Jobs;

use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class InsertCSVJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private array $data;
    private $filename;
    private $datacount;
    private $notificationid;

    /**
     * Create a new job instance.
     */
    public function __construct(array $data, $filename, $datacount, $notificationid)
    {
        $this->data = $data;
        $this->filename = $filename;
        $this->datacount = $datacount;
        $this->notificationid = $notificationid;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Log::info('InsertCSVJob Memory usage before job: ' . round(memory_get_usage() / 1024 / 1024, 2) . ' MB');

        $file = fopen($this->filename, 'a');

        if ($file === false) 
            return;

        $lines = [];
        foreach ($this->data as $row) 
        {
            $updated_at = Carbon::parse($row['updated_at'])->format('Y-m-d H:i:s');
            $created_at = Carbon::parse($row['created_at'])->format('Y-m-d H:i:s');
            
            $data = [
                $row['function'],
                $row['type'],
                $row['blocked_type'],
                $row['campaign_id'],
                $row['md5_email'],
                $row['url'],
                $row['module_type'],
                $updated_at,
                $created_at,
            ];
            $lines[] = implode(',', $data);
        }

        fwrite($file, implode(PHP_EOL, $lines) . PHP_EOL);

        fclose($file);

        $totalline = $this->getTotalLinesInCSV($this->filename);
   
        // Log::info(['totalline' => $totalline]);
   
        if($totalline >= $this->datacount)
        {
            Notification::where('id', $this->notificationid)
                        ->update([
                            'active' => 'T'
                        ]);
        }

        // Log::info('InsertCSVJob Memory usage after job: ' . round(memory_get_usage() / 1024 / 1024, 2) . ' MB');
    }

    public function getTotalLinesInCSV($filename)
    {
        $file = fopen($filename, 'r');
        $lineCount = 0;

        if ($file !== false) 
        {
            while (fgetcsv($file)) 
            {
                $lineCount++;
            }

            fclose($file);
        }

        return $lineCount;
    }
}
