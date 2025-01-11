<?php

namespace App\Http\Controllers;

use App\Exports\StudentsExport;
use App\Imports\StudentsImport;
use App\Jobs\ChunkCSVJob;
use App\Jobs\EndInsertCSVJob;
use App\Jobs\InsertCSVJob;
use App\Models\DataLarge;
use App\Models\Notification;
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
        $user_id = auth()->user()->id;

        $notification = Notification::create([
            'user_id' => $user_id,
            'status' => 'success',
            'name' => 'download',
            'message' => 'export csv failed lead record successfully',
            'data' => '',
            'active' => 'F'
        ]);
        $notificationid = $notification->id;
        ChunkCSVJob::dispatch($notificationid)->onQueue('chunk_export_csv');
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

    public function getNotificationExport()
    {
        /* GET NOTIFICATION WHERE USER ID AND ACTIVE = 'T' */
        $user_id = auth()->user()->id;
        $notifications = Notification::where('user_id', $user_id)
                                    ->where('active', 'T')
                                    ->get();
    
        $notificationFormat = [];
        
        $notificationDownloadTotal = Notification::where('user_id', $user_id)
                                                 ->where('name', 'download')
                                                 ->count();
        
        foreach ($notifications as $notification)
        {
            $data = [];
            if(!empty($notification->data) && trim($notification->data) != '')
                $data = json_decode($notification->data, true); 
        
            $notificationFormat[] = [
                'id' => $notification->id, 
                'status' => $notification->status,
                'message' => $notification->message,
                'name' => $notification->name,
                'data' => $data
            ];
    
            $notification->delete();
        }
        /* GET NOTIFICATION WHERE USER ID AND ACTIVE = 'T' */
    
        /* CHECK IS EXPORTING */
        $isExporting = [
            'largeCSV' => false
        ];
    
        $isExporting['largeCSV'] = Notification::where('user_id', $user_id)
                                               ->where('name', 'download')
                                               ->where('active', 'F')
                                               ->exists(); 
        /* CHECK IS EXPORTING */

        return response()->json(['status' => 200, 'notifications' => $notificationFormat, 'notificationDownloadTotal' => $notificationDownloadTotal, 'isExporting' => $isExporting], 200);
    }

}
