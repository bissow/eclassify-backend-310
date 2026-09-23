<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendHtmlMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        protected string $html,
        protected string $to,
        protected ?string $toName,
        protected ?string $fromAddress,
        protected ?string $fromName,
        protected string $subject,
    ) {
    }

    public function handle(): void
    {
        try {
            Mail::html($this->html, function ($msg) {
                $msg->to($this->to, $this->toName);
                if (! empty($this->fromAddress)) {
                    $msg->from($this->fromAddress, $this->fromName);
                }
                $msg->subject($this->subject);
            });
        } catch (\Throwable $e) {
            Log::error('SendHtmlMailJob failed to send mail to ' . $this->to . ': ' . $e->getMessage());
        }
    }
}
