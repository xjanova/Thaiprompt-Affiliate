<?php

namespace App\Services;

use Google\Cloud\Speech\V1\SpeechClient;
use Google\Cloud\Speech\V1\RecognitionAudio;
use Google\Cloud\Speech\V1\RecognitionConfig;
use Google\Cloud\Speech\V1\RecognitionConfig\AudioEncoding;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * LINE Voice Message Service
 *
 * จัดการ:
 * - Download voice messages จาก LINE
 * - แปลง voice เป็น text ด้วย Google Cloud Speech-to-Text
 * - แปลง audio format (m4a → wav/flac)
 * - บันทึก history
 */
class LineVoiceMessageService
{
    /**
     * LINE Message API URL
     *
     * @var string
     */
    protected const LINE_MESSAGE_API = 'https://api-data.line.me/v2/bot/message';

    /**
     * LINE access token
     *
     * @var string|null
     */
    protected $accessToken;

    /**
     * Constructor
     */
    public function __construct()
    {
        $settings = \App\Models\LineOaSetting::getActive();
        $this->accessToken = $settings?->channel_access_token;
    }

    /**
     * Download voice message จาก LINE
     *
     * @param string $messageId
     * @return array{success: bool, path: string|null, error: string|null}
     */
    public function downloadVoiceMessage(string $messageId): array
    {
        if (!$this->accessToken) {
            return [
                'success' => false,
                'path' => null,
                'error' => 'LINE access token not configured',
            ];
        }

        try {
            $url = self::LINE_MESSAGE_API . "/{$messageId}/content";

            // Download content from LINE
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
            ])->get($url);

            if (!$response->successful()) {
                throw new \Exception("Failed to download voice message: " . $response->body());
            }

            // บันทึกไฟล์ชั่วคราว
            $filename = "voice-{$messageId}.m4a"; // LINE ส่งมาเป็น m4a
            $path = "voice-messages/{$filename}";

            Storage::disk('local')->put($path, $response->body());

            Log::info('Voice message downloaded', [
                'message_id' => $messageId,
                'path' => $path,
                'size_kb' => round(Storage::disk('local')->size($path) / 1024, 2),
            ]);

            return [
                'success' => true,
                'path' => Storage::disk('local')->path($path),
                'error' => null,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to download voice message', [
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'path' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * แปลง voice เป็น text ด้วย Google Cloud Speech-to-Text
     *
     * @param string $audioFilePath
     * @param string $languageCode (default: 'th-TH')
     * @return array{success: bool, text: string|null, confidence: float|null, error: string|null}
     */
    public function speechToText(string $audioFilePath, string $languageCode = 'th-TH'): array
    {
        try {
            // ตรวจสอบว่ามี Google Cloud credentials หรือไม่
            if (!env('GOOGLE_APPLICATION_CREDENTIALS')) {
                Log::warning('Google Cloud Speech-to-Text not configured, using fallback');
                return $this->fallbackSpeechToText($audioFilePath);
            }

            // แปลง m4a เป็น wav/flac (ถ้าจำเป็น)
            $convertedPath = $this->convertAudioFormat($audioFilePath);

            // สร้าง Speech Client
            $speechClient = new SpeechClient();

            // อ่านไฟล์ audio
            $audioContent = file_get_contents($convertedPath);

            // สร้าง RecognitionAudio
            $audio = (new RecognitionAudio())
                ->setContent($audioContent);

            // สร้าง RecognitionConfig
            $config = (new RecognitionConfig())
                ->setEncoding(AudioEncoding::LINEAR16) // WAV format
                ->setSampleRateHertz(16000)
                ->setLanguageCode($languageCode)
                ->setEnableAutomaticPunctuation(true) // เพิ่มเครื่องหมายวรรคตอนอัตโนมัติ
                ->setModel('default'); // หรือ 'phone_call', 'video', 'command_and_search'

            // Recognize speech
            $response = $speechClient->recognize($config, $audio);

            // ดึงข้อความจาก response
            $transcription = '';
            $confidence = 0.0;

            foreach ($response->getResults() as $result) {
                $alternatives = $result->getAlternatives();
                if ($alternatives && count($alternatives) > 0) {
                    $transcription .= $alternatives[0]->getTranscript() . ' ';
                    $confidence = max($confidence, $alternatives[0]->getConfidence());
                }
            }

            $transcription = trim($transcription);

            // Clean up
            $speechClient->close();

            // ลบไฟล์ชั่วคราว
            if (file_exists($convertedPath) && $convertedPath !== $audioFilePath) {
                @unlink($convertedPath);
            }

            if (empty($transcription)) {
                Log::warning('Speech-to-Text returned empty result');
                return [
                    'success' => false,
                    'text' => null,
                    'confidence' => null,
                    'error' => 'ไม่สามารถแปลงเสียงเป็นข้อความได้ (อาจเป็นเสียงรบกวนหรือไม่มีการพูด)',
                ];
            }

            Log::info('Speech-to-Text successful', [
                'text' => $transcription,
                'confidence' => round($confidence * 100, 2) . '%',
                'language' => $languageCode,
            ]);

            return [
                'success' => true,
                'text' => $transcription,
                'confidence' => $confidence,
                'error' => null,
            ];

        } catch (\Exception $e) {
            Log::error('Speech-to-Text error', [
                'error' => $e->getMessage(),
                'file' => $audioFilePath,
            ]);

            return [
                'success' => false,
                'text' => null,
                'confidence' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * แปลง audio format จาก m4a เป็น wav (ใช้ FFmpeg)
     *
     * @param string $inputPath
     * @return string Path ของไฟล์ที่แปลงแล้ว
     *
     * @throws \Exception
     */
    protected function convertAudioFormat(string $inputPath): string
    {
        // ถ้าเป็น WAV อยู่แล้ว ไม่ต้องแปลง
        if (pathinfo($inputPath, PATHINFO_EXTENSION) === 'wav') {
            return $inputPath;
        }

        // ตรวจสอบว่ามี FFmpeg หรือไม่
        $ffmpegPath = env('FFMPEG_PATH', 'ffmpeg');

        // Test FFmpeg
        exec("which {$ffmpegPath}", $output, $returnCode);
        if ($returnCode !== 0) {
            Log::warning('FFmpeg not found, skipping audio conversion');
            // ถ้าไม่มี FFmpeg ให้ใช้ไฟล์เดิม (Google Speech API รองรับหลาย format)
            return $inputPath;
        }

        $outputPath = str_replace('.m4a', '.wav', $inputPath);

        // แปลงด้วย FFmpeg (16000 Hz, mono, 16-bit)
        $command = sprintf(
            '%s -i %s -ar 16000 -ac 1 -sample_fmt s16 %s 2>&1',
            escapeshellarg($ffmpegPath),
            escapeshellarg($inputPath),
            escapeshellarg($outputPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('FFmpeg conversion failed: ' . implode("\n", $output));
        }

        Log::info('Audio converted', [
            'from' => basename($inputPath),
            'to' => basename($outputPath),
        ]);

        return $outputPath;
    }

    /**
     * Fallback Speech-to-Text (ถ้าไม่มี Google Cloud)
     *
     * @param string $audioFilePath
     * @return array
     */
    protected function fallbackSpeechToText(string $audioFilePath): array
    {
        // TODO: Implement fallback using other services
        // Options:
        // 1. OpenAI Whisper API
        // 2. AssemblyAI
        // 3. DeepGram

        return [
            'success' => false,
            'text' => null,
            'confidence' => null,
            'error' => 'Speech-to-Text service not configured (ต้องตั้งค่า Google Cloud credentials)',
        ];
    }

    /**
     * ประมวลผล voice message ทั้งหมด (download + speech-to-text)
     *
     * @param string $messageId
     * @param string $languageCode
     * @return array{success: bool, text: string|null, confidence: float|null, error: string|null}
     */
    public function processVoiceMessage(string $messageId, string $languageCode = 'th-TH'): array
    {
        // Step 1: Download voice message
        $downloadResult = $this->downloadVoiceMessage($messageId);

        if (!$downloadResult['success']) {
            return [
                'success' => false,
                'text' => null,
                'confidence' => null,
                'error' => 'Download failed: ' . $downloadResult['error'],
            ];
        }

        // Step 2: Speech-to-Text
        $sttResult = $this->speechToText($downloadResult['path'], $languageCode);

        // Step 3: Clean up downloaded file
        if (file_exists($downloadResult['path'])) {
            @unlink($downloadResult['path']);
        }

        return $sttResult;
    }
}
