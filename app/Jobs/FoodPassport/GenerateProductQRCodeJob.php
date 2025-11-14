<?php

namespace App\Jobs\FoodPassport;

use App\Models\FoodProduct;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class GenerateProductQRCodeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public FoodProduct $product
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Generate QR code content
            $qrContent = config('app.url') . '/food-passport/' . $this->product->food_passport_id;

            // Generate QR code image
            $qrCode = QrCode::format('png')
                ->size(400)
                ->margin(2)
                ->errorCorrection('H')
                ->generate($qrContent);

            // Store QR code
            $filename = 'qr-codes/' . $this->product->food_passport_id . '.png';
            Storage::disk('public')->put($filename, $qrCode);

            // Update product with QR code URL
            $this->product->qr_code_url = Storage::disk('public')->url($filename);
            $this->product->saveQuietly();

            Log::info('QR code generated successfully', [
                'product_id' => $this->product->id,
                'passport_id' => $this->product->food_passport_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate QR code', [
                'product_id' => $this->product->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateProductQRCodeJob failed permanently', [
            'product_id' => $this->product->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
