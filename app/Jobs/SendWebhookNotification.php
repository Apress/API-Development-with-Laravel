<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWebhookNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $signature;

    /**
     * Create a new job instance.
     *
     * @param string $webhookUrl
     * @param string $subscriberSecret
     * @param array  $payload
     */
    public function __construct(
        protected string $webhookUrl,
        protected string $subscriberSecret,
        protected array $payload
    ) {
        $this->signature = $this->generateSignature(json_encode($this->payload), $this->subscriberSecret);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $response = Http::withHeaders([
                'X-Signature'  => $this->signature,
                'Content-Type' => 'application/json',
            ])->post($this->webhookUrl, $this->payload);
            // Throws an exception for client or server error responses
            $response->throw();
            Log::info('Webhook sent successfully', ['url' => $this->webhookUrl]);

        } catch (\Exception $e) {
            Log::error('Failed to send webhook', [
                'url'   => $this->webhookUrl,
                'error' => $e->getMessage(),
            ]);
            // Optionally, implement retry logic or mark the subscriber for review
        }
    }

    /**
     * Generate an HMAC signature for the payload using the user-provided secret.
     *
     * @param string $payload
     * @param string $secret
     * @return string
     */
    protected function generateSignature(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    public function failed(\Throwable $e)
    {
        \Log::error('Job failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
    }
}

