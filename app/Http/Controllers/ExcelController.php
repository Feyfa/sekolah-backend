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
        $notification = Notification::create([
            'user_id' => 1,
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
        ini_set('memory_limit', '512M');

        $filePath = "csv/$filename";

        // Cek jika file ada di penyimpanan dan memiliki ekstensi csv
        if (Storage::disk('public')->exists($filePath) && pathinfo($filename, PATHINFO_EXTENSION) == 'csv') {
            // Mengunduh file
            $fileContent = Storage::disk('public')->get($filePath);
            // Menghapus file setelah diunduh
            Storage::disk('public')->delete($filePath);
            
            return response($fileContent, 200)->header('Content-Type', 'text/csv')
                                              ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        } else {
            return response()->json([], 400);
        }
    }
}
