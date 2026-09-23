<?php

namespace App\Services\Payment;

use Illuminate\Http\Request;

// Optional — only plugin gateways with a dashboard-configured webhook
// implement this. See WebhookController::pluginWebhook().
interface WebhookVerifiable
{
    /**
     * Verify the request is genuinely from the gateway and identify which
     * transaction it's about. Throw if it doesn't check out. Takes the full
     * Request, not just the body — some gateways sign via headers.
     *
     * @return array{payment_transaction_id: int|string|null}
     */
    public function verifyWebhook(Request $request): array;
}
