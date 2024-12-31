<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function setMailTransport($user)
    {
        if($user) 
        {
            $dsn = "smtp://{$user->mail_host}:{$user->mail_port}";

            if (!empty($user->mail_encryption))
                $dsn .= "?encryption={$user->mail_encryption}";

            $transport = Transport::fromDsn($dsn);
            $transport->setUsername($user->mail_username);
            $transport->setPassword($user->mail_password);

            return new Mailer($transport);
        }

        return null;
    }
}
