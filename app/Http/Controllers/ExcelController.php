<?php

namespace App\Http\Controllers;

use App\Exports\StudentsExport;
use App\Imports\StudentsImport;
use App\Jobs\ChunkCSVJob;
use App\Jobs\EndInsertCSVJob;
use App\Jobs\InsertCSVJob;
use App\Models\DataLarge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
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
        $dataCount = DataLarge::count();
        
        if($dataCount > 100000)
            return response()->json(['result' => 'error', 'message' => 'maximum export mysql to csv 100K row'], 400);

        ChunkCSVJob::dispatch()->onQueue('export_large_csv');
        return response()->json(['result' => 'success', 'message' => "export is running in background processing, when it is finished you will get a notification"]);
    }
}
