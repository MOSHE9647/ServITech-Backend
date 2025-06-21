<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetSuccessNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     * 
     * This notification is sent to confirm that the user's password
     * has been successfully reset.
     */
    public function __construct()
    {
        //
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
        $recommendations = [
            __('Use a strong, unique password'),
            __('Keep your login credentials secure'),
        ];

        return (new MailMessage)
            ->subject(__('Password Reset Successful'))
            ->markdown('emails.password-reset-success', [
                'user' => $notifiable,
                'recommendations' => $recommendations
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Password reset successfully',
            'user_id' => $notifiable->id,
            'timestamp' => now(),
        ];
    }
}
