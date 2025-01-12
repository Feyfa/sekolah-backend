<?php

namespace App\Http\Controllers;

use App\Exports\StudentsExport;
use App\Imports\StudentsImport;
use App\Jobs\ChunkCSVJob;
use App\Jobs\EndInsertCSVJob;
use App\Jobs\InsertCSVJob;
use App\Models\DataLarge;
use App\Models\DownloadProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class ExcelController extends Controller
{
    public function export(Request $request)
    {
        return Excel::download(new StudentsExport($request->user_id), 'students.xlsx');
    }

    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => ['required', 'integer'],
            'file' => ['required', 'file', 'mimes:xlsx']
        ]); 

        if($validator->fails())
            return response()->json(['status' => 422, 'message' => $validator->messages()], 422);

        Excel::import(new StudentsImport($request->user_id), $request->file('file')->getRealPath());
        return response()->json(['status' => 200, 'message'=> 'Import Add Successfully', 'file' => $request->file('file')], 200);
    }
    
    public function largeExport(Request $request)
    {
        $user_id = $request->user_id;

        $downloadProgress = DownloadProgress::create([
            'user_id' => $user_id,
            'name' => 'download_failed_lead',
            'link' => '',
            'status' => 'queue'
        ]);
        $downloadProgressID = $downloadProgress->id;
        ChunkCSVJob::dispatch($downloadProgressID)->onQueue('chunk_download_csv');

        return response()->json(['result' => 'success', 'message' => "export is running in background processing, when it is finished you will get a notification"]);
    }

    public function downloadLargeCSV($filename)
    {
        Log::info('downloadLargeCSV Memory usage before job: ' . round(memory_get_usage() / 1024 / 1024, 2) . ' MB');

        $filePath = "csv/$filename";

        // Cek jika file ada di penyimpanan dan memiliki ekstensi csv
        if (Storage::disk('public')->exists($filePath) && pathinfo($filename, PATHINFO_EXTENSION) == 'csv') 
        {
            return response()->streamDownload(function () use ($filePath) {
                $stream = Storage::disk('public')->readStream($filePath);

                while (!feof($stream)) 
                {
                    echo fread($stream, 1024 * 8); // Mengirim data dalam potongan kecil
                    flush();
                }
                fclose($stream);
        
                // Hapus file setelah streaming selesai
                Storage::disk('public')->delete($filePath);

                Log::info('downloadLargeCSV Memory usage after job: ' . round(memory_get_usage() / 1024 / 1024, 2) . ' MB');
            }, $filename, ['Content-Type' => 'text/csv']);
        } else 
        {
            Log::info('downloadLargeCSV Memory usage after job: ' . round(memory_get_usage() / 1024 / 1024, 2) . ' MB');
            return redirect()->back();
        }
    }

    public function getNotificationExport(Request $request)
    {
        $user_id = $request->user_id;
        $downloadProgress = DownloadProgress::where('user_id', $user_id)
                                            ->get();

        $isDownloadProgress = [
            'download_failed_lead' => false
        ];
        $downloadProgressTotal = count($downloadProgress);
        $downloadProgressDone = [];
        
        foreach($downloadProgress as $downloadProgres)
        {
            if($downloadProgres->name == 'download_failed_lead' && in_array($downloadProgres->status, ['queue', 'progress']))
            {
                $isDownloadProgress['download_failed_lead'] = true;
            }
            
            if($downloadProgres->status == 'done')
            {
                $downloadProgressDone[] = $downloadProgres;
                $downloadProgres->delete();
            }
        }

        return response()->json(['status' => 200, 'message' => 'token valid', 'downloadProgressTotal' => $downloadProgressTotal, 'downloadProgressDone' => $downloadProgressDone, 'isDownloadProgress' => $isDownloadProgress], 200);
    }

}
