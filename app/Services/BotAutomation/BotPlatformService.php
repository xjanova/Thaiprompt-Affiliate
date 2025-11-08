<?php

namespace App\Services\BotAutomation;

use App\Models\BotAutomation\BotScheduledPost;
use App\Models\BotAutomation\BotPlatformConnection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BotPlatformService
{
    /**
     * Publish post to platform
     */
    public function publishPost(BotScheduledPost $post): array
    {
        $connection = $post->platformConnection;
        $platform = $connection->platform;

        if (!$connection->isValid()) {
            return [
                'success' => false,
                'error' => 'Platform connection is not valid or token expired',
            ];
        }

        try {
            $result = match($platform->code) {
                'facebook' => $this->publishToFacebook($post, $connection),
                'instagram' => $this->publishToInstagram($post, $connection),
                'twitter' => $this->publishToTwitter($post, $connection),
                'tiktok' => $this->publishToTikTok($post, $connection),
                'line' => $this->publishToLine($post, $connection),
                'youtube' => $this->publishToYouTube($post, $connection),
                default => throw new \Exception("Platform {$platform->code} not supported"),
            };

            if ($result['success']) {
                $post->markAsPublished(
                    $result['post_id'],
                    $result['post_url'] ?? '',
                    $result['response'] ?? []
                );

                $connection->recordPost();

                Log::info("Post published successfully", [
                    'post_id' => $post->id,
                    'platform' => $platform->code,
                    'platform_post_id' => $result['post_id'],
                ]);
            } else {
                $post->markAsFailed($result['error'] ?? 'Unknown error');

                Log::warning("Post publication failed", [
                    'post_id' => $post->id,
                    'platform' => $platform->code,
                    'error' => $result['error'] ?? 'Unknown error',
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            $post->markAsFailed($e->getMessage());

            Log::error("Post publication exception", [
                'post_id' => $post->id,
                'platform' => $platform->code,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Publish to Facebook
     */
    protected function publishToFacebook(BotScheduledPost $post, BotPlatformConnection $connection): array
    {
        try {
            $endpoint = "https://graph.facebook.com/v18.0/{$connection->account_id}/feed";

            $data = [
                'message' => $post->content,
                'access_token' => $connection->access_token,
            ];

            // Add media if present
            if (!empty($post->media_urls)) {
                // For multiple photos, use different endpoint
                $endpoint = "https://graph.facebook.com/v18.0/{$connection->account_id}/photos";
                $data['url'] = $post->media_urls[0];
                $data['caption'] = $post->content;
            }

            $response = Http::post($endpoint, $data);

            if ($response->successful()) {
                $result = $response->json();
                return [
                    'success' => true,
                    'post_id' => $result['id'] ?? $result['post_id'] ?? '',
                    'post_url' => "https://facebook.com/{$result['id']}",
                    'response' => $result,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['error']['message'] ?? 'Facebook API error',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Publish to Instagram
     */
    protected function publishToInstagram(BotScheduledPost $post, BotPlatformConnection $connection): array
    {
        try {
            // Instagram requires 2-step process: create media, then publish
            $igUserId = $connection->account_id;

            // Step 1: Create media container
            $createEndpoint = "https://graph.facebook.com/v18.0/{$igUserId}/media";
            $createData = [
                'caption' => $post->content,
                'access_token' => $connection->access_token,
            ];

            // Add media URL
            if (!empty($post->media_urls)) {
                $createData['image_url'] = $post->media_urls[0];
            } else {
                return [
                    'success' => false,
                    'error' => 'Instagram requires at least one image',
                ];
            }

            $createResponse = Http::post($createEndpoint, $createData);

            if (!$createResponse->successful()) {
                return [
                    'success' => false,
                    'error' => $createResponse->json()['error']['message'] ?? 'Instagram create media error',
                ];
            }

            $containerId = $createResponse->json()['id'];

            // Step 2: Publish media
            $publishEndpoint = "https://graph.facebook.com/v18.0/{$igUserId}/media_publish";
            $publishData = [
                'creation_id' => $containerId,
                'access_token' => $connection->access_token,
            ];

            $publishResponse = Http::post($publishEndpoint, $publishData);

            if ($publishResponse->successful()) {
                $result = $publishResponse->json();
                return [
                    'success' => true,
                    'post_id' => $result['id'],
                    'post_url' => "https://instagram.com/p/{$result['id']}",
                    'response' => $result,
                ];
            }

            return [
                'success' => false,
                'error' => $publishResponse->json()['error']['message'] ?? 'Instagram publish error',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Publish to Twitter
     */
    protected function publishToTwitter(BotScheduledPost $post, BotPlatformConnection $connection): array
    {
        try {
            // Twitter v2 API
            $endpoint = "https://api.twitter.com/2/tweets";

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$connection->access_token}",
                'Content-Type' => 'application/json',
            ])->post($endpoint, [
                'text' => $post->content,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                return [
                    'success' => true,
                    'post_id' => $result['data']['id'],
                    'post_url' => "https://twitter.com/user/status/{$result['data']['id']}",
                    'response' => $result,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['detail'] ?? 'Twitter API error',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Publish to TikTok
     */
    protected function publishToTikTok(BotScheduledPost $post, BotPlatformConnection $connection): array
    {
        // TikTok API implementation
        // Note: TikTok has stricter requirements for video content
        return [
            'success' => false,
            'error' => 'TikTok API integration pending implementation',
        ];
    }

    /**
     * Publish to LINE
     */
    protected function publishToLine(BotScheduledPost $post, BotPlatformConnection $connection): array
    {
        try {
            // LINE Broadcast API
            $endpoint = "https://api.line.me/v2/bot/message/broadcast";

            $messages = [
                [
                    'type' => 'text',
                    'text' => $post->content,
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$connection->access_token}",
                'Content-Type' => 'application/json',
            ])->post($endpoint, [
                'messages' => $messages,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'post_id' => 'broadcast_' . time(),
                    'post_url' => '',
                    'response' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'LINE API error',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Publish to YouTube
     */
    protected function publishToYouTube(BotScheduledPost $post, BotPlatformConnection $connection): array
    {
        // YouTube API implementation
        return [
            'success' => false,
            'error' => 'YouTube API integration pending implementation',
        ];
    }

    /**
     * Fetch analytics for published post
     */
    public function fetchAnalytics(BotScheduledPost $post): array
    {
        $connection = $post->platformConnection;
        $platform = $connection->platform;

        if (!$post->platform_post_id) {
            return [];
        }

        try {
            return match($platform->code) {
                'facebook' => $this->fetchFacebookAnalytics($post, $connection),
                'instagram' => $this->fetchInstagramAnalytics($post, $connection),
                'twitter' => $this->fetchTwitterAnalytics($post, $connection),
                default => [],
            };
        } catch (\Exception $e) {
            Log::error("Failed to fetch analytics", [
                'post_id' => $post->id,
                'platform' => $platform->code,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Fetch Facebook analytics
     */
    protected function fetchFacebookAnalytics(BotScheduledPost $post, BotPlatformConnection $connection): array
    {
        $endpoint = "https://graph.facebook.com/v18.0/{$post->platform_post_id}";
        $response = Http::get($endpoint, [
            'fields' => 'likes.summary(true),comments.summary(true),shares',
            'access_token' => $connection->access_token,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'likes' => $data['likes']['summary']['total_count'] ?? 0,
                'comments' => $data['comments']['summary']['total_count'] ?? 0,
                'shares' => $data['shares']['count'] ?? 0,
            ];
        }

        return [];
    }

    /**
     * Fetch Instagram analytics
     */
    protected function fetchInstagramAnalytics(BotScheduledPost $post, BotPlatformConnection $connection): array
    {
        $endpoint = "https://graph.facebook.com/v18.0/{$post->platform_post_id}/insights";
        $response = Http::get($endpoint, [
            'metric' => 'engagement,impressions,reach',
            'access_token' => $connection->access_token,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'engagement' => $data['data'][0]['values'][0]['value'] ?? 0,
                'impressions' => $data['data'][1]['values'][0]['value'] ?? 0,
                'reach' => $data['data'][2]['values'][0]['value'] ?? 0,
            ];
        }

        return [];
    }

    /**
     * Fetch Twitter analytics
     */
    protected function fetchTwitterAnalytics(BotScheduledPost $post, BotPlatformConnection $connection): array
    {
        // Twitter v2 API for metrics
        return [];
    }
}
