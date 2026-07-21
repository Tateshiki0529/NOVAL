<?php

namespace Tests\Feature;

use App\Models\ProtocolVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_register_and_reach_private_dashboard(): void
    {
        $this->get('/')->assertOk()->assertSee('Notation for Open and Versatile Archives Layer');
        $response = $this->post('/register', [
            'name' => 'Toshiki',
            'email' => 'toshiki@example.test',
            'password' => 'a-secure-password',
            'password_confirmation' => 'a-secure-password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->get('/dashboard')->assertOk()->assertSee('Core MVP workspace');
    }

    public function test_web_form_uses_protocol_publish_use_case(): void
    {
        $user = User::factory()->create();
        $schema = [
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            'type' => 'object',
            'properties' => ['note' => ['type' => 'string']],
            'required' => ['note'],
            'additionalProperties' => false,
        ];

        $this->actingAs($user)->post('/protocols', [
            'slug' => 'notes',
            'version' => '1.0.0',
            'schema' => json_encode($schema, JSON_THROW_ON_ERROR),
            'metadata' => json_encode(['order' => ['/note'], 'fields' => ['/note' => ['kind' => 'text', 'label' => 'Note']]], JSON_THROW_ON_ERROR),
        ])->assertSessionHasNoErrors();

        $version = ProtocolVersion::firstOrFail();
        $this->actingAs($user)->post('/protocol-versions/'.$version->id.'/publish')
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');
        self::assertSame('published', $version->fresh()->state);
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
