<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function notifExportLargeCSV(Request $request)
    {
        $name = "export_large_csv";

        $notification = Notification::where('name', $name)
                                    ->orderBy('id', 'desc')
                                    ->first();

        $dataFormat = "";

        if(!empty($notification))
        {
            $dataFormat = json_decode($notification->data, true);
            $notification->delete();
        }

        $isExportingLargeCSV = DB::table('jobs')
                                 ->where('queue', 'export_large_csv')
                                 ->exists();

        return response()->json(['result' => 'success', 'dataFormat' => $dataFormat, 'isExportingLargeCSV' => $isExportingLargeCSV]);
    }
}
