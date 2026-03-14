<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Mail;

class MailHelper
{
    /**
     * Gmail + Mailpit üzerinden mail gönder
     *
     * @param \Illuminate\Mail\Mailable $mailable
     * @param string|string[] $to
     * @return void
     */
    public static function sendWithCopy($mailable, $to)
    {
        // Gmail üzerinden canlı gönderim
        Mail::mailer('smtp')->to($to)->send($mailable);

        // Mailpit’e kopya (test için)
        Mail::mailer('mailpit')->to('info@localhost')->send($mailable);
    }
}