<?php

namespace App\Jobs;

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
    private string $filename;

    /**
     * Create a new job instance.
     */
    public function __construct(array $data, string $filename)
    {
        $this->data = $data;
        $this->filename = $filename;
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
            $data = [
                $row['data1'],
                $row['data2'],
                $row['data3'],
                $row['created_at'],
                $row['updated_at'],
            ];
            $lines[] = implode(',', $data);
        }

        fwrite($file, implode(PHP_EOL, $lines) . PHP_EOL);

        fclose($file);

        // Log::info('InsertCSVJob Memory usage after job: ' . round(memory_get_usage() / 1024 / 1024, 2) . ' MB');
    }
}
