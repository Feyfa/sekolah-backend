<?php

namespace App\Http\Controllers;

use App\Models\DownloadProgress;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Yaza\LaravelGoogleDriveStorage\Gdrive;

class AuthController extends Controller
{
    public function tokenValidation(Request $request)
    {
        /* GET IP */
        Log::info("ip = " . $request->ip());
        /* GET IP */

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
    
    public function register(Request $request)
    {
        $validate = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'max:255','email', 'unique:users'],
            'password' => ['required', 'string', 'min:6'],
        ]);
        $validate['password'] = Hash::make($validate['password']);

        User::create($validate);

        return response()->json(['status' => 201, 'message' => 'register success'], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'max:255', 'email'],
            'password' => ['required', 'string'],
        ]);

        if(!Auth::attempt($request->only('email', 'password')))
            return response()->json(['status' => 401, 'message' => 'invalid login details'], 401);
        
        $token = $request->user()->createToken('authToken')->plainTextToken;
        $user = User::where('email', $request->email)
                    ->first();

        /* CHECK WHETHER THE EMAIL SETTINGS HAVE BEEN FILLED IN SUCCESSFULLY */
        if($user->email && $user->mail_mailer && $user->mail_host && $user->mail_port && $user->mail_username && $user->mail_password && $user->mail_encryption && $user->mail_from_address && $user->mail_from_name)
            $user->emailActive = true;
        else
            $user->emailActive = false;
        /* CHECK WHETHER THE EMAIL SETTINGS HAVE BEEN FILLED IN SUCCESSFULLY */

        /* GET IMAGE FROM GOOGLE DRIVE */
        $userImage = '';
        if($user->img)
        {
            $img = Gdrive::get($user->img);
            $img->file = base64_encode($img->file);
            $userImage = "data:$img->ext;base64,$img->file";
        }
        /* GET IMAGE FROM GOOGLE DRIVE */

        return response()->json(['status' => 200, 'message' => 'login success', 'token' => $token, 'user' => $user, 'userImage' => $userImage], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['status' => 200, 'message' => 'logout success'], 200);
    }
}
