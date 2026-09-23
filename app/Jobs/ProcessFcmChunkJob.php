<?php

namespace App\Jobs;

use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessFcmChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120; // 2 minutes per chunk is plenty

    protected array $tokens;
    protected string $title;
    protected string $message;
    protected string $type;
    protected array $customBodyFields;
    protected bool $sendToAll;

    public function __construct(
        array $tokens,
        string $title,
        string $message,
        array $customBodyFields = [],
        string $type = 'default',
        // bool $sendToAll = false
    ) {
        $this->tokens = $tokens;
        $this->title = $title;
        $this->message = $message;
        $this->type = $type;
        $this->customBodyFields = $customBodyFields;
        // $this->sendToAll = $sendToAll;
    }

    public function handle(): void
    {
        Log::info("🔹 ProcessFcmChunkJob started for " . count($this->tokens) . " tokens.");

        try {
            NotificationService::sendFcmNotification(
                $this->tokens,
                $this->title,
                $this->message,
                $this->customBodyFields,
                $this->type
            );
        } catch (\Throwable $th) {
            Log::error('ProcessFcmChunkJob failed', [
                'message' => $th->getMessage(),
                'file'    => $th->getFile(),
                'line'    => $th->getLine(),
            ]);
            throw $th;
        }

        Log::info("✅ ProcessFcmChunkJob finished.");
    }
}
