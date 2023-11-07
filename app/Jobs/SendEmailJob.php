<?php

namespace App\Jobs;

use App\Mail\SimpleMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendEmailJob implements ShouldQueue, ShouldBeUniqueUntilProcessing, ShouldBeEncrypted
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $shouldBeEncrypted = true;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $emailData, $user)
    {
        $this->emailData = $emailData;
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
        $recipient = $this->emailData['recipient'];
        $subject = $this->emailData['subject'];
        $body = $this->emailData['body'];

        $mail = new SimpleMail($subject, $body);
        Mail::to($recipient)->send($mail);
        }
        catch (Throwable $e) {
            Log::info('[SendEmailJob][handle][Failed]: '.$e->getMessage());

        }
    }

}
