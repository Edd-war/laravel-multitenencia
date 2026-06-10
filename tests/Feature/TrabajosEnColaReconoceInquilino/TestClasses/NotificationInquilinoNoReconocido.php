<?php

namespace Eddwar\Multitenencia\Tests\Feature\TrabajosEnColaReconoceInquilino\TestClasses;

use Eddwar\Multitenencia\Jobs\InquilinoNoReconocido;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotificationInquilinoNoReconocido extends Notification implements InquilinoNoReconocido, ShouldQueue
{
    use Queueable;

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Mensaje')
            ->greeting('¡Hola!')
            ->line('Saludos');
    }

    public function toArray($notifiable)
    {
        return [];
    }
}
