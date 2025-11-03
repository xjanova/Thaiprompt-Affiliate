<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AiProvider;
use App\Models\AiModel;

class AiProvidersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // OpenAI Provider
        $openai = AiProvider::create([
            'name' => 'openai',
            'display_name' => 'OpenAI',
            'provider_type' => 'cloud',
            'api_endpoint' => 'https://api.openai.com/v1',
            'api_version' => 'v1',
            'is_active' => true,
            'is_available' => true,
            'config' => [
                'api_key_required' => true,
                'organization_id_supported' => true,
                'logo_url' => 'https://cdn.simpleicons.org/openai/412991',
            ],
            'pricing' => [
                'currency' => 'USD',
            ],
        ]);

        // OpenAI Models
        AiModel::create([
            'provider_id' => $openai->id,
            'model_identifier' => 'gpt-4-turbo',
            'display_name' => 'GPT-4 Turbo',
            'description' => 'Most capable GPT-4 model, optimized for speed and cost',
            'context_window' => 128000,
            'max_output_tokens' => 4096,
            'supports_functions' => true,
            'supports_vision' => true,
            'supports_streaming' => true,
            'is_active' => true,
            'pricing' => [
                'input' => 0.01,   // $0.01 per 1K tokens
                'output' => 0.03,  // $0.03 per 1K tokens
            ],
        ]);

        AiModel::create([
            'provider_id' => $openai->id,
            'model_identifier' => 'gpt-4',
            'display_name' => 'GPT-4',
            'description' => 'Original GPT-4 model with high capability',
            'context_window' => 8192,
            'max_output_tokens' => 4096,
            'supports_functions' => true,
            'supports_vision' => false,
            'supports_streaming' => true,
            'is_active' => true,
            'pricing' => [
                'input' => 0.03,
                'output' => 0.06,
            ],
        ]);

        AiModel::create([
            'provider_id' => $openai->id,
            'model_identifier' => 'gpt-3.5-turbo',
            'display_name' => 'GPT-3.5 Turbo',
            'description' => 'Fast and cost-effective model for most tasks',
            'context_window' => 16385,
            'max_output_tokens' => 4096,
            'supports_functions' => true,
            'supports_vision' => false,
            'supports_streaming' => true,
            'is_active' => true,
            'pricing' => [
                'input' => 0.0005,
                'output' => 0.0015,
            ],
        ]);

        // Anthropic Claude Provider
        $claude = AiProvider::create([
            'name' => 'anthropic',
            'display_name' => 'Anthropic Claude',
            'provider_type' => 'cloud',
            'api_endpoint' => 'https://api.anthropic.com/v1',
            'api_version' => 'v1',
            'is_active' => true,
            'is_available' => true,
            'config' => [
                'api_key_required' => true,
                'anthropic_version' => '2023-06-01',
                'logo_url' => 'https://cdn.simpleicons.org/anthropic/191919',
            ],
            'pricing' => [
                'currency' => 'USD',
            ],
        ]);

        // Claude Models
        AiModel::create([
            'provider_id' => $claude->id,
            'model_identifier' => 'claude-3-opus-20240229',
            'display_name' => 'Claude 3 Opus',
            'description' => 'Most capable Claude model for complex tasks',
            'context_window' => 200000,
            'max_output_tokens' => 4096,
            'supports_functions' => true,
            'supports_vision' => true,
            'supports_streaming' => true,
            'is_active' => true,
            'pricing' => [
                'input' => 0.015,
                'output' => 0.075,
            ],
        ]);

        AiModel::create([
            'provider_id' => $claude->id,
            'model_identifier' => 'claude-3-sonnet-20240229',
            'display_name' => 'Claude 3 Sonnet',
            'description' => 'Balanced performance and speed',
            'context_window' => 200000,
            'max_output_tokens' => 4096,
            'supports_functions' => true,
            'supports_vision' => true,
            'supports_streaming' => true,
            'is_active' => true,
            'pricing' => [
                'input' => 0.003,
                'output' => 0.015,
            ],
        ]);

        AiModel::create([
            'provider_id' => $claude->id,
            'model_identifier' => 'claude-3-haiku-20240307',
            'display_name' => 'Claude 3 Haiku',
            'description' => 'Fastest and most compact Claude model',
            'context_window' => 200000,
            'max_output_tokens' => 4096,
            'supports_functions' => true,
            'supports_vision' => true,
            'supports_streaming' => true,
            'is_active' => true,
            'pricing' => [
                'input' => 0.00025,
                'output' => 0.00125,
            ],
        ]);

        // DeepSeek Provider
        $deepseek = AiProvider::create([
            'name' => 'deepseek',
            'display_name' => 'DeepSeek',
            'provider_type' => 'cloud',
            'api_endpoint' => 'https://api.deepseek.com/v1',
            'api_version' => 'v1',
            'is_active' => true,
            'is_available' => true,
            'config' => [
                'api_key_required' => true,
                'supports_self_hosted' => true,
                'logo_url' => 'https://avatars.githubusercontent.com/u/165193168',
            ],
            'pricing' => [
                'currency' => 'USD',
            ],
        ]);

        // DeepSeek Models
        AiModel::create([
            'provider_id' => $deepseek->id,
            'model_identifier' => 'deepseek-chat',
            'display_name' => 'DeepSeek Chat',
            'description' => 'General purpose chat model',
            'context_window' => 32768,
            'max_output_tokens' => 4096,
            'supports_functions' => true,
            'supports_vision' => false,
            'supports_streaming' => true,
            'is_active' => true,
            'pricing' => [
                'input' => 0.00014,
                'output' => 0.00028,
            ],
        ]);

        AiModel::create([
            'provider_id' => $deepseek->id,
            'model_identifier' => 'deepseek-coder',
            'display_name' => 'DeepSeek Coder',
            'description' => 'Specialized model for coding tasks',
            'context_window' => 32768,
            'max_output_tokens' => 4096,
            'supports_functions' => true,
            'supports_vision' => false,
            'supports_streaming' => true,
            'is_active' => true,
            'pricing' => [
                'input' => 0.00014,
                'output' => 0.00028,
            ],
        ]);

        // Google Gemini Provider
        $gemini = AiProvider::create([
            'name' => 'google',
            'display_name' => 'Google Gemini',
            'provider_type' => 'cloud',
            'api_endpoint' => 'https://generativelanguage.googleapis.com/v1',
            'api_version' => 'v1',
            'is_active' => true,
            'is_available' => true,
            'config' => [
                'api_key_required' => true,
                'logo_url' => 'https://www.gstatic.com/lamda/images/gemini_sparkle_v002_d4735304ff6292a690345.svg',
            ],
            'pricing' => [
                'currency' => 'USD',
            ],
        ]);

        // Gemini Models
        AiModel::create([
            'provider_id' => $gemini->id,
            'model_identifier' => 'gemini-pro',
            'display_name' => 'Gemini Pro',
            'description' => 'Best performing model for a wide range of tasks',
            'context_window' => 32768,
            'max_output_tokens' => 2048,
            'supports_functions' => true,
            'supports_vision' => false,
            'supports_streaming' => true,
            'is_active' => true,
            'pricing' => [
                'input' => 0.00025,
                'output' => 0.0005,
            ],
        ]);

        AiModel::create([
            'provider_id' => $gemini->id,
            'model_identifier' => 'gemini-pro-vision',
            'display_name' => 'Gemini Pro Vision',
            'description' => 'Multimodal model with vision capabilities',
            'context_window' => 16384,
            'max_output_tokens' => 2048,
            'supports_functions' => false,
            'supports_vision' => true,
            'supports_streaming' => true,
            'is_active' => true,
            'pricing' => [
                'input' => 0.00025,
                'output' => 0.0005,
            ],
        ]);

        // Self-Hosted DeepSeek Provider (for users who install locally)
        AiProvider::create([
            'name' => 'deepseek-local',
            'display_name' => 'DeepSeek (Self-Hosted)',
            'provider_type' => 'self-hosted',
            'api_endpoint' => 'http://localhost:8000/v1',
            'api_version' => 'v1',
            'is_active' => false,
            'is_available' => false,
            'config' => [
                'api_key_required' => false,
                'installation_required' => true,
                'logo_url' => 'https://cdn.simpleicons.org/ollama/000000',
            ],
            'pricing' => [
                'currency' => 'USD',
                'input' => 0,
                'output' => 0,
                'note' => 'Free - Self-hosted',
            ],
        ]);

        $this->command->info('AI Providers and Models seeded successfully!');
    }
}
