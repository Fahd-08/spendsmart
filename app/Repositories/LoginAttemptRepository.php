<?php

declare(strict_types=1);

namespace App\Repositories;

final class LoginAttemptRepository extends Repository
{
    public function countRecentFailures(string $email, string $ipAddress, int $minutes): int
    {
        return (int) $this->fetchValue(
            'SELECT COUNT(*) FROM login_attempts
             WHERE email = :email AND ip_address = :ip_address
               AND attempted_at >= NOW() - INTERVAL :minutes MINUTE',
            ['email' => $email, 'ip_address' => $ipAddress, 'minutes' => $minutes]
        );
    }

    public function record(string $email, string $ipAddress): void
    {
        $this->execute(
            'INSERT INTO login_attempts (email, ip_address) VALUES (:email, :ip_address)',
            ['email' => $email, 'ip_address' => $ipAddress]
        );
    }

    public function clear(string $email, string $ipAddress): void
    {
        $this->execute(
            'DELETE FROM login_attempts WHERE email = :email AND ip_address = :ip_address',
            ['email' => $email, 'ip_address' => $ipAddress]
        );
    }
}
