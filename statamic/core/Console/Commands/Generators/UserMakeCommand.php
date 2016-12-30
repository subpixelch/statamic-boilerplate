<?php

namespace Statamic\Console\Commands\Generators;

use Illuminate\Console\Command;

use Statamic\API\User;

class UserMakeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a user. {username}';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $user = User::create();

        $user->username($this->promptForUsername());

        $password = $this->secret('Password (Your input will be hidden)');
        $email = $this->ask('Email address', false);
        $first_name = $this->ask('First Name', false);
        $last_name = $this->ask('Last Name', false);
        $super = $this->confirm('Super user');

        $user->with(
            array_filter(compact('first_name', 'last_name', 'email', 'password', 'super'))
        );

        $user->get()->save();

        $this->info('User created.');
    }

    /**
     * Prompt for an available username
     *
     * @return string
     */
    private function promptForUsername()
    {
        $username = $this->ask('Username');

        if ($this->usernameExists($username)) {
            $this->warn('Username exists.');
            return $this->promptForUsername();
        }

        return $username;
    }

    /**
     * Check if a user exists
     *
     * @param  string $username
     * @return boolean
     */
    private function usernameExists($username)
    {
        return User::whereUsername($username) !== null;
    }
}
