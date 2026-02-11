<?php

declare(strict_types=1);

namespace App\Auth\Passwords;

use Illuminate\Auth\Passwords\DatabaseTokenRepository;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CompanyScopedDatabaseTokenRepository extends DatabaseTokenRepository
{
    /**
     * Create a new token record.
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @return string
     */
    public function create(CanResetPassword $user)
    {
        $email = $user->getEmailForPasswordReset();
        $companyId = $user->company_id ?? null;

        // Delete existing tokens for this email/company combination
        $this->deleteExisting($user);

        // Create a new token
        $token = $this->createNewToken();

        // Insert the new token record with company_id
        $this->getTable()->insert([
            'email' => $email,
            'company_id' => $companyId,
            'token' => $this->hasher->make($token),
            'created_at' => new Carbon,
        ]);

        return $token;
    }

    /**
     * Delete all existing reset tokens from the database for this user.
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @return int
     */
    protected function deleteExisting(CanResetPassword $user)
    {
        $companyId = $user->company_id ?? null;

        return $this->getTable()
            ->where('email', $user->getEmailForPasswordReset())
            ->where('company_id', $companyId)
            ->delete();
    }

    /**
     * Determine if a token record exists and is valid.
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @param  string  $token
     * @return bool
     */
    public function exists(CanResetPassword $user, $token)
    {
        $companyId = $user->company_id ?? null;

        $record = (array) $this->getTable()
            ->where('email', $user->getEmailForPasswordReset())
            ->where('company_id', $companyId)
            ->first();

        return $record &&
               ! $this->tokenExpired($record['created_at']) &&
               $this->hasher->check($token, $record['token']);
    }

    /**
     * Delete a token record by user.
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @return void
     */
    public function delete(CanResetPassword $user)
    {
        $this->deleteExisting($user);
    }

    /**
     * Delete expired tokens.
     *
     * @return void
     */
    public function deleteExpired()
    {
        $expiredAt = Carbon::now()->subSeconds($this->expires);

        $this->getTable()->where('created_at', '<', $expiredAt)->delete();
    }
}
