<?php

namespace Statamic\Addons\Protect;

use Statamic\API\Request;
use Statamic\Extend\Listener;
use Statamic\Addons\Protect\Protectors\PasswordProtector;

class ProtectListener extends Listener
{
    public $events = [
        'Protect.password' => 'password',
    ];

    protected $scheme;
    protected $url;
    protected $password;

    /**
     * @var PasswordProtector
     */
    protected $protector;

    public function password()
    {
        if (! $token = $this->getTokenData()) {
            $this->flash->put('error', 'Invalid or expired token.');
            return back();
        }

        $this->url = $token['url'];

        $this->protector = new PasswordProtector($this->url, $token['scheme']);

        if (! $this->protector->isValidPassword($this->password = Request::get('password'))) {
            $this->flash->put('error', 'Incorrect password.');
            return back();
        }

        $this->storePassword();

        return redirect($this->url);
    }

    protected function getTokenData()
    {
        return session()->get('protect.scheme.'.Request::get('token'));
    }

    protected function storePassword()
    {
        $passwords = session()->get('protect.passwords', []);

        $passwords[md5($this->url)][] = $this->password;

        session()->put('protect.passwords', $passwords);
    }
}