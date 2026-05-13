<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class CustomVerifyEmail extends BaseVerifyEmail
{
    /**
     * Get the verify email notification mail message for the given URL.
     *
     * @param  string  $url
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    protected function buildMailMessage($url)
    {
        return (new MailMessage)
            ->from('no-reply@arzonet.com', 'Arzonet Security')
            ->subject('Verify your email address - Arzonet')
            ->greeting('Welcome to Arzonet!')
            ->line('We are excited to have you on board. Arzonet gives you the power to manage your email and WhatsApp marketing effectively.')
            ->line('To get started, please click the button below to verify your email address.')
            ->action('Verify Email Address', $url)
            ->line('If you did not create an account, no further action is required.');
    }
}
