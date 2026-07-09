<?php

namespace App\Services;

use App\DTO\FormalNotificationData;
use App\Mail\FormalNotification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class NotificationService
{
    public function sendFormal(User|string $recipient, FormalNotificationData $data): bool
    {
        try {
            Mail::to($recipient)->send(new FormalNotification($data));

            return true;
        } catch (Throwable $exception) {
            Log::channel('security')->error('mail.send_failed', [
                'recipient' => is_string($recipient) ? $recipient : $recipient->email,
                'subject' => $data->subject,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function notifyAdmins(FormalNotificationData $data): bool
    {
        $email = config('conductor-ledger.security.admin_email')
            ?? config('conductor-ledger.backup.notify_email');

        if (! $email) {
            return false;
        }

        return $this->sendFormal($email, $data);
    }
}
