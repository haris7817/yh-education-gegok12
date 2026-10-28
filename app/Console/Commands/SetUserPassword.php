<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Secure account activation for YH Education.
 *
 * New accounts are created with a random, unusable password (no more
 * predictable defaults). Until SMTP is configured for the email-based
 * "Forgot password" flow, an administrator with server access activates an
 * account by generating a one-time initial password with this command:
 *
 *   php artisan yh:set-password teacher@example.com
 *
 * The generated password is shown ONCE on the terminal (never logged or
 * stored in plaintext) and should be handed to the user with the advice to
 * change it after first login (every role has a self-service change-password
 * page). Any existing API tokens and device registrations are revoked.
 */
class SetUserPassword extends Command
{
    protected $signature = 'yh:set-password {login : The user\'s email address or mobile number}';

    protected $description = 'Generate and set a random initial password for a user (shown once, never stored in plaintext)';

    public function handle()
    {
        $login = trim($this->argument('login'));

        $user = User::where('email', $login)->orWhere('mobile_no', $login)->first();

        if (! $user) {
            $this->error("No user found with email or mobile number '{$login}'.");
            return self::FAILURE;
        }

        // Readable but strong: 14 chars from an unambiguous alphabet (~80 bits).
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        $password = '';
        for ($i = 0; $i < 14; $i++) {
            $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        $user->password = bcrypt($password);
        $user->save();

        // Revoke existing sessions on the API side.
        $user->tokens()->delete();
        $user->device_id = null;
        $user->platform_token = null;
        $user->save();

        $this->info('Password set for: ' . ($user->email ?: $user->mobile_no) . ' (usergroup ' . $user->usergroup_id . ')');
        $this->line('');
        $this->line('  Initial password (shown once, give it to the user securely):');
        $this->line('');
        $this->line('      ' . $password);
        $this->line('');
        $this->comment('Ask the user to change it after first login (Change Password page).');

        return self::SUCCESS;
    }
}
