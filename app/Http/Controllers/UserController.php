<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Email;
use Yaza\LaravelGoogleDriveStorage\Gdrive;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    public function uploadImage(Request $request)
    {
        $user = User::where('id', $request->id)
                    ->first();
    
        $validator = Validator::make($request->all(), [
            'id' => ['required', 'integer'],
            'file' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:1024']
        ]);

        if($validator->fails())
            return response()->json(['status' => 422, 'message' => $validator->messages()], 422);

        if(!$user)
            return response()->json(['status' => 404, 'message' => 'User Not Found'], 404);

        if($user->img) 
            Gdrive::delete($user->img);

        $filename = $request->id . "-" . Carbon::now()->timestamp . "." .$request->file('file')->getClientOriginalExtension();
        Gdrive::put($filename, $request->file('file')->getRealPath());

        $user->img = $filename;
        $user->save();

        $img = Gdrive::get($user->img);
        $img->file = base64_encode($img->file);
        $userImage = "data:$img->ext;base64,$img->file";
        
        return response()->json(['status' => 200, 'message' => 'Upload Image Successfully', 'user' => $user, 'userImage' => $userImage], 200);
    }

    public function deleteImage(string $id)
    {
        $user = User::where('id', $id)
                    ->first();

        if(!$user)
            return response()->json(['status' => 404, 'message' => 'User Not Found'], 404);

        if(!$user->img) 
            return response()->json(['status' => 404, 'message' => 'Image Not Found'], 404);

        Gdrive::delete($user->img);
        $user->img = "";
        $user->save();

        return response()->json(['status' => 200, 'message' => 'Delete Image Successfully', 'user' => $user, 'userImage' => ""], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::where('id', $id)
                    ->first();

        return ($user) ? 
               response()->json(['status' => 200, 'user' => $user], 200) : 
               response()->json(['status' => 404, 'message' => 'User Not Found'], 404) ;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::where('id', $id)
                    ->first();

        /* VALIDATION USER */        
        if(!$user)
            return response()->json(['status' => 404, 'message' => 'User Not Found'], 404);

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string']
        ]);
        
        if($validator->fails())
            return response()->json(['status' => 422, 'message' => $validator->messages()], 422);
        /* VALIDATION USER */

        /* VALIDATION JABATAN */
        $jabatanExists = User::where('id', '<>', $id)
                            ->where('jabatan', $request->jabatan)
                            ->exists();
        if($jabatanExists)
            return response()->json(['status' => 409, 'message' => 'Jabatan Has Been Used'], 409);
        /* VALIDATION JABATAN */
        
        $user->name = $request->name;
        $user->jenis_kelamin = $request->jenis_kelamin;
        $user->tanggal_lahir = $request->tanggal_lahir;
        $user->jabatan = $request->jabatan;
        $user->alamat = $request->alamat;
        $user->pendidikan = $request->pendidikan;
        $user->save();

        return response()->json(['status' => 200, 'message' => 'User Update Successfully', 'user' => $user], 200);
    }

    public function updateEmail(Request $request, string $id)
    {
        /* IF USER NOT FOUND */
        $user = User::where('id', $id)
                    ->first();

        if(!$user)
            return response()->json(['status' => 404, 'message' => 'User Not Found'], 404);
        /* IF USER NOT FOUND */

        /* VALIDATOR EMAIL */
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email'],
        ]);

        if($validator->fails())
            return response()->json(['status' => 422, 'message' => $validator->messages()], 422);
        /* VALIDATOR EMAIL */

        /* IF THE EMAIL HAS BEEN USED BY OTHER PEOPLE */
        $emailExists = User::where('id', '<>', $id)
                           ->where('email', $request->email)
                           ->exists();

        if($emailExists)
            return response()->json(['status' => 409, 'message' => 'Email Has Been Used'], 409);
        /* IF THE EMAIL HAS BEEN USED BY OTHER PEOPLE */

        /* IF ONLY UPDATE EMAIL */
        if(!$request->mail_mailer && !$request->mail_host && !$request->mail_port && !$request->mail_password && !$request->mail_encryption) {
            $result = $user->update(['email' => $request->email]);
    
            return $result ?
                   response()->json(['status' => 200, 'message' => 'Email Update Successfully', 'user' => $user], 200) : 
                   response()->json(['status' => 500, 'message' => 'Something Went Error'], 500) ;
        }
        /* IF ONLY UPDATE EMAIL */

        $emailSetting = (object) [
            'mail_host' => $request->mail_host,
            'mail_port' => $request->mail_port,
            'mail_encryption' => $request->mail_encryption,
            'mail_username' => $request->email,
            'mail_password' => $request->mail_password,
        ];

        // Set the mail transport
        $mailer = $this->setMailTransport($emailSetting);

        // Create and send email
        if ($mailer) 
        {
            try
            {
                $subject = "Set Email SMK TAMANASISWA 2 JAKARTA";
                $content = <<<EOT
                    {$request->email} successfully set
                    mailer: {$request->mail_mailer}
                    email: {$request->email}
                    host: {$request->mail_host}
                    port: {$request->mail_port}
                    password: {$request->mail_password}
                    encryption: {$request->mail_encryption}
                EOT;
                $content = nl2br($content);

                // Render Blade template with data
                $emailContent = View::make('emails.template')
                                    ->with('content', $content)
                                    ->render();
    
                $email = (new Email())->from($user->mail_from_address)
                                      ->to($user->mail_from_address)
                                      ->subject($subject)
                                      ->html($emailContent);
    
                $mailer->send($email);
    
                $result = $user->update([
                    'email' => $request->email,
                    'mail_mailer' => $request->mail_mailer,
                    'mail_host' => $request->mail_host,
                    'mail_port' => $request->mail_port,
                    'mail_password' => $request->mail_password,
                    'mail_encryption' => $request->mail_encryption,
                    'mail_username' => $request->email,
                    'mail_from_address' => $request->email,
                    'mail_from_name' => explode('@', $request->email)[0],
                ]);
        
                return $result ?
                       response()->json(['status' => 200, 'message' => 'Email Update Successfully', 'user' => $user], 200) : 
                       response()->json(['status' => 500, 'message' => 'Something Went Error'], 500) ;
            }
            catch (TransportExceptionInterface $e)
            {
                return response()->json(['message' => $e->getMessage()], 500);
            }
        } 
        else 
        {
            return response()->json(['message' => 'Failed to send email. User not found.'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
