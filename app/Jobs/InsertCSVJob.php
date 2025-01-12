<?php

namespace App\Jobs;

use App\Models\DownloadProgress;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class InsertCSVJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 60;

    private array $data;
    private $filename;
    private $datacount;
    private $downloadProgressID;

    /**
     * Create a new job instance.
     */
    public function __construct(array $data, $filename, $datacount, $downloadProgressID)
    {
        $this->data = $data;
        $this->filename = $filename;
        $this->datacount = $datacount;
        $this->downloadProgressID = $downloadProgressID;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Log::info('InsertCSVJob Memory usage before job: ' . round(memory_get_usage() / 1024 / 1024, 2) . ' MB');

        $file = fopen($this->filename, 'a');

        if ($file === false) 
        {
            fclose($file);
            return;
        }

        $lines = [];
        foreach ($this->data as $row) 
        {
            $updated_at = (strtotime($row['updated_at']) !== false) ? Carbon::parse($row['updated_at'])->format('Y-m-d H:i:s') : null;
            $created_at = (strtotime($row['created_at']) !== false) ? Carbon::parse($row['created_at'])->format('Y-m-d H:i:s') : null;
            
            $data = [
                $row['function'],
                $row['type'],
                $row['blocked_type'],
                $row['description'],
                $row['leadspeek_api_id'],
                $row['email_encrypt'],
                $row['url'],
                $row['leadspeek_type'],
                $row['data_lead'],
                $updated_at,
                $created_at,
            ];
            $lines[] = $this->formatCsvLine($data);
        }

        fwrite($file, implode(PHP_EOL, $lines) . PHP_EOL);

        fclose($file);

        $totalline = $this->getTotalLinesInCSV($this->filename);
   
        // Log::info(['totalline' => $totalline, 'datacount' => $this->datacount]);
   
        if($totalline >= $this->datacount)
        {
            DownloadProgress::where('id', $this->downloadProgressID)
                            ->update([
                                'status' => 'done'
                            ]);
        }

        // Log::info('InsertCSVJob Memory usage after job: ' . round(memory_get_usage() / 1024 / 1024, 2) . ' MB');
    }

    public function failed(Exception $e)
    {
        Log::info([
            'action' => 'failed InsertCSVJob',
            'error' => $e->getMessage()
        ]);

        DownloadProgress::where('id', $this->downloadProgressID)
                        ->update([
                            'status' => 'done'
                        ]);

    }

    private function formatCsvLine(array $data): string
    {
        // Bungkus setiap elemen dalam tanda kutip ganda dan gabungkan dengan koma
        return implode(',', array_map(function ($item) {
            return '"' . str_replace('"', '""', $item) . '"'; // Escape tanda kutip ganda
        }, $data));
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
