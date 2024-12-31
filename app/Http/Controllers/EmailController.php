<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Email;

class EmailController extends Controller
{
    public function sendEmail(Request $request)
    {
        $idUser = $request->idUser;
        $to = $request->to;
        $subject = $request->subject;
        $content = nl2br($request->content);

        // ambil user bedasarkan id nya
        $user = User::where('id', $idUser)->first();

        // Set the mail transport
        $mailer = $this->setMailTransport($user);

        // Create and send email
        if ($mailer) 
        {
            try
            {
                // Render Blade template with data
                $emailContent = View::make('emails.template')
                                    ->with('content', $content)
                                    ->render();
    
                $email = (new Email())->from($user->mail_from_address)
                                      ->to($to)
                                      ->subject($subject)
                                      ->html($emailContent);
    
                $mailer->send($email);
    
                return response()->json(['message' => 'Send Email Successfully!',], 200);
            }
            catch (TransportExceptionInterface $e)
            {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        } 
        else 
        {
            return response()->json(['error' => 'Failed to send email. User not found.'], 500);
        }
    }
}
