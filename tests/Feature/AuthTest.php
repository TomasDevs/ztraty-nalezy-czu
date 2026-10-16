<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\App;
use Tests\Support\FeatureTestCase;

final class AuthTest extends FeatureTestCase
{
    private function validRegistration(array $overrides = []): array
    {
        return [
            'name' => 'Jan Novák',
            'email' => 'jan' . uniqid() . '@example.com',
            'password' => 'tajne-heslo',
            'password_confirmation' => 'tajne-heslo',
            ...$overrides,
        ];
    }

    public function testFormsAreShown(): void
    {
        $this->get('/prihlaseni')->assertOk()->assertSee('name="email"')->assertSee('name="_token"');
        $this->get('/registrace')->assertOk()->assertSee('name="password_confirmation"');
    }

    public function testRegistrationCreatesUserAndLogsIn(): void
    {
        $data = $this->validRegistration();

        $this->post('/registrace', $data)->assertRedirect('/');

        $user = App::users()->findByEmail($data['email']);
        $this->assertNotNull($user);
        $this->assertSame($user['id'], $this->session['user_id']);
    }

    public function testRegistrationRedirectsBackToRequestedPage(): void
    {
        $this->post('/registrace', $this->validRegistration(['zpet' => '/nahlasit?type=found']))->assertRedirect('/nahlasit?type=found');
    }

    public function testRegistrationWithTakenEmailShowsError(): void
    {
        $user = $this->createUser(['email' => 'taken@example.com']);

        $response = $this->post('/registrace', $this->validRegistration(['email' => 'taken@example.com']));

        $response->assertStatus(422)->assertSee('Účet s tímto e-mailem už existuje.')->assertDontSee('value="tajne-heslo"');
        $this->assertArrayNotHasKey('user_id', $this->session);
    }

    public function testLoginRedirectsToRequestedPage(): void
    {
        $user = $this->createUser();

        $this->post('/prihlaseni', ['email' => $user['email'], 'password' => 'password123', 'zpet' => '/nahlasit'])
            ->assertRedirect('/nahlasit');
        $this->assertSame($user['id'], $this->session['user_id']);
    }

    public function testLoginIgnoresExternalRedirectTarget(): void
    {
        $user = $this->createUser();

        $this->post('/prihlaseni', ['email' => $user['email'], 'password' => 'password123', 'zpet' => '//evil.com'])
            ->assertRedirect('/');
    }

    public function testWrongPasswordShowsGenericError(): void
    {
        $user = $this->createUser();

        $this->post('/prihlaseni', ['email' => $user['email'], 'password' => 'wrong'])
            ->assertStatus(422)
            ->assertSee('Nesprávný e-mail nebo heslo.');
        $this->assertArrayNotHasKey('user_id', $this->session);
    }

    public function testBlockedUserSeesBlockedMessage(): void
    {
        $user = $this->createUser(['is_blocked' => 1]);

        $this->post('/prihlaseni', ['email' => $user['email'], 'password' => 'password123'])
            ->assertStatus(422)
            ->assertSee('Účet je zablokovaný.');
    }

    public function testLogoutClearsSession(): void
    {
        $this->actingAs($this->createUser());

        $this->post('/odhlaseni')->assertRedirect('/');

        $this->assertArrayNotHasKey('user_id', $this->session);
        $this->get('/')->assertSee('Byli jste odhlášeni.');
    }

    public function testLogoutRequiresCsrfToken(): void
    {
        $this->actingAs($this->createUser());

        $this->post('/odhlaseni', withToken: false)->assertStatus(419);
    }

    public function testLoggedInUserIsSentAwayFromAuthPages(): void
    {
        $this->actingAs($this->createUser());

        $this->get('/prihlaseni')->assertRedirect('/ucet');
        $this->get('/registrace')->assertRedirect('/ucet');
    }

    public function testGuestHeaderOffersLogin(): void
    {
        $this->get('/')->assertSee('Přihlásit se')->assertDontSee('Můj účet');
    }

    public function testUserHeaderShowsAccountAndLogout(): void
    {
        $this->actingAs($this->createUser());

        $this->get('/')->assertSee('Můj účet')->assertSee('Odhlásit')->assertDontSee('Administrace');
    }

    public function testAdminHeaderShowsAdministration(): void
    {
        $this->actingAs($this->createUser(['role' => 'admin']));

        $this->get('/')->assertSee('Administrace');
    }
}
