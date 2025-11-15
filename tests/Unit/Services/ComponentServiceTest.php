<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ComponentService;

/**
 * ComponentService Unit Tests
 *
 * ทดสอบ ComponentService functionality
 */
class ComponentServiceTest extends TestCase
{
    protected ComponentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ComponentService();
    }

    /** @test */
    public function it_can_check_component_existence(): void
    {
        $this->assertTrue($this->service->exists('arrow-x.button'));
        $this->assertTrue($this->service->exists('arrow-x.card.stat'));
        $this->assertFalse($this->service->exists('arrow-x.nonexistent'));
    }

    /** @test */
    public function it_can_get_available_components(): void
    {
        $components = $this->service->getAvailableComponents();

        $this->assertIsArray($components);
        $this->assertArrayHasKey('cards', $components);
        $this->assertArrayHasKey('buttons', $components);
        $this->assertArrayHasKey('forms', $components);
        $this->assertArrayHasKey('navigation', $components);
    }

    /** @test */
    public function it_lists_correct_card_components(): void
    {
        $components = $this->service->getAvailableComponents();

        $this->assertContains('arrow-x.card.stat', $components['cards']);
        $this->assertContains('arrow-x.card.info', $components['cards']);
        $this->assertContains('arrow-x.card.gradient', $components['cards']);
    }

    /** @test */
    public function it_lists_correct_form_components(): void
    {
        $components = $this->service->getAvailableComponents();

        $this->assertContains('arrow-x.form.input', $components['forms']);
        $this->assertContains('arrow-x.form.select', $components['forms']);
        $this->assertContains('arrow-x.form.checkbox', $components['forms']);
    }

    /** @test */
    public function it_lists_correct_navigation_components(): void
    {
        $components = $this->service->getAvailableComponents();

        $this->assertContains('arrow-x.sidebar', $components['navigation']);
        $this->assertContains('arrow-x.sidebar.item', $components['navigation']);
        $this->assertContains('arrow-x.navbar', $components['navigation']);
        $this->assertContains('arrow-x.navbar.notification', $components['navigation']);
        $this->assertContains('arrow-x.navbar.user-menu', $components['navigation']);
    }

    /** @test */
    public function it_can_generate_js_config(): void
    {
        $config = $this->service->generateJsConfig();

        $this->assertJson($config);

        $decoded = json_decode($config, true);
        $this->assertArrayHasKey('theme', $decoded);
        $this->assertArrayHasKey('colors', $decoded['theme']);
    }

    /** @test */
    public function it_includes_correct_gradient_colors_in_js_config(): void
    {
        $config = $this->service->generateJsConfig();
        $decoded = json_decode($config, true);

        $colors = $decoded['theme']['colors'];

        $this->assertArrayHasKey('purple', $colors);
        $this->assertArrayHasKey('blue', $colors);
        $this->assertArrayHasKey('green', $colors);

        $this->assertStringContainsString('from-purple-500', $colors['purple']);
        $this->assertStringContainsString('from-blue-500', $colors['blue']);
        $this->assertStringContainsString('from-green-500', $colors['green']);
    }

    /** @test */
    public function it_can_render_button_helper(): void
    {
        $html = $this->service->button('Click Me', [
            'variant' => 'primary',
            'size' => 'md',
        ]);

        $this->assertStringContainsString('Click Me', $html);
        $this->assertStringContainsString('button', $html);
    }

    /** @test */
    public function it_can_render_badge_helper(): void
    {
        $html = $this->service->badge('New', [
            'color' => 'success',
        ]);

        $this->assertStringContainsString('New', $html);
    }

    /** @test */
    public function it_can_render_alert_helper(): void
    {
        $html = $this->service->alert('Success message', 'success');

        $this->assertStringContainsString('Success message', $html);
    }

    /** @test */
    public function it_can_render_stat_card_helper(): void
    {
        $html = $this->service->statCard('Total Users', '1,234', [
            'icon' => 'fa-users',
            'color' => 'purple',
        ]);

        $this->assertStringContainsString('Total Users', $html);
        $this->assertStringContainsString('1,234', $html);
    }

    /** @test */
    public function it_can_render_input_helper(): void
    {
        $html = $this->service->input('username', [
            'label' => 'Username',
            'type' => 'text',
        ]);

        $this->assertStringContainsString('username', $html);
        $this->assertStringContainsString('Username', $html);
    }

    /** @test */
    public function it_can_generate_preview(): void
    {
        $html = $this->service->generatePreview('arrow-x.button', [
            'Primary' => ['variant' => 'primary'],
            'Secondary' => ['variant' => 'secondary'],
        ]);

        $this->assertStringContainsString('Primary', $html);
        $this->assertStringContainsString('Secondary', $html);
        $this->assertStringContainsString('arrow-x.button', $html);
    }
}
