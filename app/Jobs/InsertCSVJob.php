<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
        $file = fopen($this->filename, 'a');

        if ($file === false) 
            return;

        foreach ($this->data as $row) 
        {
            $line = [
                $row['data1'],
                $row['data2'],
                $row['data3'],
                $row['created_at'],
                $row['updated_at'],
            ];
            fwrite($file, implode(',', $line) . PHP_EOL);
        }

        fclose($file);
    }
}
