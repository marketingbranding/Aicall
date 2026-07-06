<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateSuperAdmin extends Command
{
    protected $signature = 'app:create-super-admin
                            {--name= : Full name of the Super Admin}
                            {--email= : Email address of the Super Admin}
                            {--password= : Password for the Super Admin}
                            {--force : Create another Super Admin even if one already exists}';

    protected $description = 'Create the first Super Admin user securely';

    public function handle(): int
    {
        $name = $this->option('name');
        $email = $this->option('email');
        $password = $this->option('password');

        if (! $name || ! $email || ! $password) {
            $this->error('All options are required: --name, --email, --password');

            return self::FAILURE;
        }

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $existingUser = User::where('email', $email)->first();

        if ($existingUser) {
            $this->error("A user with email '{$email}' already exists.");

            return self::FAILURE;
        }

        $existingSuperAdmin = User::where('role', UserRole::SuperAdmin)->exists();

        if ($existingSuperAdmin && ! $this->option('force')) {
            $this->warn('A Super Admin user already exists. Use --force to create another.');

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Hash::make($password),
            'role' => UserRole::SuperAdmin,
            'status' => User::STATUS_ACTIVE,
            'branch_id' => null,
        ]);

        $this->info("Super Admin '{$user->name}' ({$user->email}) created successfully.");

        return self::SUCCESS;
    }
}
