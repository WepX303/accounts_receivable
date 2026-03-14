<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view) {
            $view->with('user', Auth::user());
        });

        // --- mail event listener ekle ---
        Event::listen(MessageSent::class, function (MessageSent $event) {
            $mailable = $event->data['mailable'] ?? null;
            if ($mailable) {
                Mail::mailer('mailpit')->send($mailable);
            }
        });
    }
}
