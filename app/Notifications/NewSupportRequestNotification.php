<?php

namespace App\Notifications;

use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewSupportRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public SupportRequest $supportRequest;
    public User $user;

    /**
     * Create a new notification instance.
     */
    public function __construct(SupportRequest $supportRequest, User $user)
    {
        $this->supportRequest = $supportRequest;
        $this->user = $user;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nueva Solicitud de Soporte Creada')
            ->greeting('¡Hola Administrador!')
            ->line('Se ha creado una nueva solicitud de soporte en el sistema.')
            ->line('**Detalles de la solicitud:**')
            ->line('**Usuario:** ' . $this->user->name . ' (' . $this->user->email . ')')
            ->line('**Fecha:** ' . $this->supportRequest->date->format('d/m/Y H:i'))
            ->line('**Ubicación:** ' . $this->supportRequest->location)
            ->line('**Detalles:** ' . $this->supportRequest->detail)
            ->line('Por favor, revisa y gestiona esta solicitud lo antes posible.')
            ->salutation('Saludos,' . "\n" . config('app.name'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'support_request_id' => $this->supportRequest->id,
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'user_email' => $this->user->email,
            'date' => $this->supportRequest->date->format('Y-m-d H:i:s'),
            'location' => $this->supportRequest->location,
            'detail' => $this->supportRequest->detail,
            'message' => 'Nueva solicitud de soporte creada por ' . $this->user->name,
        ];
    }
}
