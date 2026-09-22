<?php

namespace App\Utilities;

class PasswordHasher
{
    private const DEFAULT_ROUNDS = 12;

    public static function hash(string $password, int $rounds = self::DEFAULT_ROUNDS): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => $rounds]);
    }

    public static function verify(string $password, string $hashedPassword): bool
    {
        if (strlen($hashedPassword) === 0) {
            return false;
        }

        return password_verify($password, $hashedPassword);
    }

    public static function needsRehash(string $hashedPassword, int $rounds = self::DEFAULT_ROUNDS): bool
    {
        return password_needs_rehash($hashedPassword, PASSWORD_BCRYPT, ['cost' => $rounds]);
    }
}

