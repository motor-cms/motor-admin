<?php

namespace Motor\Admin\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Class ResetPassword
 *
 * @package Motor\Admin\Notifications
 */
class ResetPassword extends Notification
{
    use Queueable;

    public $token;

    public $user;

    /**
     * Create a new notification instance.
     *
     * ResetPassword constructor.
     *
     * @param $token
     * @param $user
     */
    public function __construct($token, $user)
    {
        $this->token = $token;
        $this->user = $user;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     *
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param $notifiable
     *
     * @return \Motor\Admin\Mail\ResetPassword
     */
    public function toMail($notifiable)
    {
        return new \Motor\Admin\Mail\ResetPassword($this->token, $this->user);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     *
     * @return array
     */
    public function toArray($notifiable)
    {
        return [//
        ];
    }
}
