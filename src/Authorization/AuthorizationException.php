<?php

namespace CodeIgniter\Shield\Authorization;

use Exception;

class AuthorizationException extends Exception
{
    protected $code = 401;

    public static function forUnknownGroup(string $group)
    {
        return new self(lang('Auth.unknownGroup', [$group]));
    }

    public static function forUnknownPermission(string $permission)
    {
        return new self(lang('Auth.unknownPermission', [$permission]));
    }
}
