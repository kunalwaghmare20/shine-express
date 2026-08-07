<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect(Auth::homePath());
        }
        $this->view('auth/login', ['title' => 'Sign in'], 'layouts/auth');
    }

    public function login(): void
    {
        if (!verify_csrf(Request::input('_csrf'))) {
            flash_error('Invalid session token. Try again.');
            $this->redirect('/login');
        }

        $email = trim((string) Request::input('email'));
        $password = (string) Request::input('password');

        if ($email === '' || $password === '') {
            flash_error('Email and password are required.');
            $this->redirect('/login');
        }

        if (!Auth::attempt($email, $password)) {
            flash_error('Invalid credentials.');
            $this->redirect('/login');
        }

        flash_success('Welcome back!');
        $this->redirect(Auth::homePath());
    }

    public function showRegister(): void
    {
        if (Auth::check()) {
            $this->redirect(Auth::homePath());
        }
        $this->view('auth/register', ['title' => 'Create account'], 'layouts/auth');
    }

    public function register(): void
    {
        if (!verify_csrf(Request::input('_csrf'))) {
            flash_error('Invalid session token.');
            $this->redirect('/register');
        }

        $first = trim((string) Request::input('first_name'));
        $last = trim((string) Request::input('last_name'));
        $email = trim((string) Request::input('email'));
        $phone = trim((string) Request::input('phone'));
        $password = (string) Request::input('password');

        if ($first === '' || $last === '' || $email === '' || strlen($password) < 6) {
            flash_error('Please fill all fields (password min 6 chars).');
            Session::flash('_old', Request::all());
            $this->redirect('/register');
        }

        $db = Database::connection();
        $exists = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $exists->execute([$email]);
        if ($exists->fetch()) {
            flash_error('Email already registered.');
            $this->redirect('/register');
        }

        $userId = generate_id();
        $customerId = generate_id();

        $db->beginTransaction();
        try {
            $db->prepare(
                'INSERT INTO users (id, email, password_hash, phone, first_name, last_name, role, is_active)
                 VALUES (?,?,?,?,?,?,"CUSTOMER",1)'
            )->execute([$userId, $email, password_hash($password, PASSWORD_DEFAULT), $phone ?: null, $first, $last]);

            $db->prepare('INSERT INTO customers (id, user_id) VALUES (?,?)')->execute([$customerId, $userId]);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            flash_error('Registration failed.');
            $this->redirect('/register');
        }

        Auth::attempt($email, $password);
        flash_success('Account created. You can book a service now.');
        $this->redirect('/book');
    }

    public function logout(): void
    {
        Auth::logout();
        Session::regenerate();
        flash_success('Signed out.');
        $this->redirect('/login');
    }
}
