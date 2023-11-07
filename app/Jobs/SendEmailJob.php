<?php

namespace App\Jobs;

use App\Mail\SimpleMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private array $emailData, private User $user)
    {}

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
        $mail->from('laravel.9@test.com', 'Admin');

        Mail::to($recipient)->send($mail);
        }
        catch (Throwable $e) {
            Log::info('[SendEmailJob][handle][Failed]: '.$e->getMessage());
            $this->fail();
        }
    }

}
