<?php

namespace App\Mail;

use App\Models\LoginChallenge;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class LoginChallengeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public LoginChallenge $challenge) {}

    public function build()
    {
        $signedUrl = URL::temporarySignedRoute(
            'login.challenge.verify',
            now()->addMinutes(10),
            ['token' => $this->challenge->token]
        );

        return $this->subject('ログインを完了してください')
            ->markdown('auth.emails.login_challenge', [
                'user'      => $this->challenge->user,
                'signedUrl' => $signedUrl,
                'expiresAt' => $this->challenge->expires_at,
            ]);
    }
}
