<?php

namespace App\Utilities;

class PasswordHasher
{
    private const DEFAULT_ROUNDS = 10;

    public static function hash(string $password, ?int $rounds = null): string
    {
        $cost = $rounds ?? (int) env('BCRYPT_ROUNDS', self::DEFAULT_ROUNDS);
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => $cost]);
    }

    public static function verify(string $password, string $hashedPassword): bool
    {
        if (strlen($hashedPassword) === 0) {
            return false;
        }

        return password_verify($password, $hashedPassword);
    }

    public static function needsRehash(string $hashedPassword, ?int $rounds = null): bool
    {
        $cost = $rounds ?? (int) env('BCRYPT_ROUNDS', self::DEFAULT_ROUNDS);
        return password_needs_rehash($hashedPassword, PASSWORD_BCRYPT, ['cost' => $cost]);
    }
}

