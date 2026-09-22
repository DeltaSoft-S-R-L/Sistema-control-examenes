<?php

namespace Tests\Unit;

use App\Utilities\PasswordHasher;
use PHPUnit\Framework\TestCase;

class PasswordHasherTest extends TestCase
{
    public function test_hash_returns_bcrypt_string(): void
    {
        $hash = PasswordHasher::hash('SecureP@ss123');

        $this->assertNotEmpty($hash);
        $this->assertNotEquals('SecureP@ss123', $hash);
        $this->assertTrue(str_starts_with($hash, '$2y$'));
    }

    public function test_hash_produces_unique_hashes_for_same_password(): void
    {
        $hash1 = PasswordHasher::hash('RepeatPassword');
        $hash2 = PasswordHasher::hash('RepeatPassword');

        $this->assertNotEquals($hash1, $hash2);
    }

    public function test_verify_returns_true_for_correct_password(): void
    {
        $password = 'MyTestP@ssword!2024';
        $hash = PasswordHasher::hash($password);

        $this->assertTrue(PasswordHasher::verify($password, $hash));
    }

    public function test_verify_returns_false_for_incorrect_password(): void
    {
        $hash = PasswordHasher::hash('CorrectPassword');

        $this->assertFalse(PasswordHasher::verify('WrongPassword', $hash));
    }

    public function test_verify_returns_false_for_empty_password_against_hash(): void
    {
        $hash = PasswordHasher::hash('NonEmptyPassword');

        $this->assertFalse(PasswordHasher::verify('', $hash));
    }

    public function test_hash_handles_special_characters(): void
    {
        $password = '¡Hólá!@#$%^&*()_+{}|:"<>?ñüé';
        $hash = PasswordHasher::hash($password);

        $this->assertTrue(PasswordHasher::verify($password, $hash));
    }

    public function test_hash_handles_long_passwords(): void
    {
        $password = str_repeat('A', 500);
        $hash = PasswordHasher::hash($password);

        $this->assertTrue(PasswordHasher::verify($password, $hash));
    }

    public function test_needsRehash_returns_false_for_fresh_hash(): void
    {
        $hash = PasswordHasher::hash('FreshPassword');

        $this->assertFalse(PasswordHasher::needsRehash($hash));
    }

    public function test_verify_returns_false_for_invalid_hash(): void
    {
        $this->assertFalse(PasswordHasher::verify('password', 'invalid_hash_string'));
    }

    public function test_needsRehash_returns_true_for_different_cost(): void
    {
        $hash = PasswordHasher::hash('MyPassword', 10);

        $this->assertTrue(PasswordHasher::needsRehash($hash, 12));
    }
}
