<?php

use App\Models\User;

it('renders the landing page in pt_BR by default', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('Buscar a Deus juntos', false);
    $response->assertSee('Participe da campanha', false);
    $response->assertSee('REMA', false);
    $response->assertSee('REMNE', false);
});

it('switches to Spanish via the locale query parameter', function () {
    $response = $this->withSession([])->get('/?locale=es');

    $response->assertOk();
    $response->assertSee('Buscar a Dios juntos', false);
    $response->assertSee('Únete a la campaña', false);
    expect(session('locale'))->toBe('es');
});

it('switches to English via the locale query parameter', function () {
    $response = $this->withSession([])->get('/?locale=en');

    $response->assertOk();
    $response->assertSee('Seek God together', false);
    $response->assertSee('Join the campaign', false);
    expect(session('locale'))->toBe('en');
});

it('shows the sign-in CTA for guests and a Browse posts CTA for authed users', function () {
    $this->get('/')->assertSee('Entrar', false);

    $user = User::factory()->create(['locale' => 'pt_BR']);
    $response = $this->actingAs($user)->get('/');
    expect($response->getContent())->toContain(route('posts.index'));
});

it('exposes a Browse posts CTA that links to the public posts feed', function () {
    $response = $this->get('/');

    $response->assertOk();
    expect($response->getContent())->toContain(route('posts.index'));
});
