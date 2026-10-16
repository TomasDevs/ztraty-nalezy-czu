<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\LoginResult;
use App\Core\App;
use App\Forms\LoginForm;
use App\Forms\RegisterForm;

final class AuthController extends Controller
{
    public function registerForm(): void
    {
        $this->guestOnly();
        $this->renderRegister(new RegisterForm());
    }

    public function register(): void
    {
        $this->guestOnly();
        $form = RegisterForm::fromArray($_POST);
        $emailTaken = $form->email !== '' && App::users()->findByEmail($form->email) !== null;

        if (!$form->validate($emailTaken)) {
            http_response_code(422);
            $this->renderRegister($form);
            return;
        }

        $id = App::users()->create($form->name, $form->email, $form->password);
        auth()->login(App::users()->find($id));
        App::session()->flash('success', 'Účet je vytvořený.');

        redirect(safe_redirect_path($this->back()), 303);
    }

    public function loginForm(): void
    {
        $this->guestOnly();
        $this->renderLogin(new LoginForm());
    }

    public function login(): void
    {
        $this->guestOnly();
        $form = LoginForm::fromArray($_POST);

        if (!$form->validate()) {
            http_response_code(422);
            $this->renderLogin($form);
            return;
        }

        $error = match (auth()->attempt($form->email, $form->password)) {
            LoginResult::Success => null,
            LoginResult::Invalid => 'Nesprávný e-mail nebo heslo.',
            LoginResult::Blocked => 'Účet je zablokovaný. Ozvěte se správci.',
        };

        if ($error !== null) {
            http_response_code(422);
            $this->renderLogin($form, $error);
            return;
        }

        redirect(safe_redirect_path($this->back()), 303);
    }

    public function logout(): void
    {
        auth()->logout();
        App::session()->flash('success', 'Byli jste odhlášeni.');

        redirect('/', 303);
    }

    private function guestOnly(): void
    {
        if (auth()->check()) {
            redirect('/ucet');
        }
    }

    private function back(): ?string
    {
        $back = $_POST['zpet'] ?? $_GET['zpet'] ?? null;

        return is_string($back) && $back !== '' ? $back : null;
    }

    private function renderLogin(LoginForm $form, ?string $error = null): void
    {
        view('auth/login', [
            'title' => 'Přihlášení',
            'noindex' => true,
            'form' => $form,
            'error' => $error,
            'back' => $this->back(),
        ]);
    }

    private function renderRegister(RegisterForm $form): void
    {
        view('auth/register', [
            'title' => 'Registrace',
            'noindex' => true,
            'form' => $form,
            'back' => $this->back(),
        ]);
    }
}
