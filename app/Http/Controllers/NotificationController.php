<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

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

        return response()->json(['result' => 'success', 'dataFormat' => $dataFormat]);
    }
}
