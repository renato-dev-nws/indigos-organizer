<?php

namespace App\Notifications\Contracts;

interface ShouldSendWhatsApp
{
    public function toWhatsApp(object $notifiable): array|string;
}
