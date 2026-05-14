<?php

namespace App\Domain\Notifications\Contracts;

use App\Domain\Notifications\NotificationPayload;
use App\Domain\Notifications\NotificationResult;

interface NotificationProvider
{
    public function channel(): string;     // email | sms

    public function name(): string;        // e.g. laravel_mailer, twilio, log

    public function send(NotificationPayload $payload): NotificationResult;
}
