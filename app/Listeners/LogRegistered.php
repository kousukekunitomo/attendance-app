<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Log;

class LogRegistered
{
    public function handle(Registered $event): void
    {
        $user = $event->user;
        Log::info('DBG Registered::fired', [
            'where'     => __CLASS__,
            'user_id'   => $user->id ?? null,
            'email'     => $user->email ?? null,
            'request'   => request()->method().' '.request()->path(),
            'session'   => session()->getId(),
            'time'      => microtime(true),
        ]);
    }
}
