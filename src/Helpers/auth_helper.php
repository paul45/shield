<?php

use CodeIgniter\Shield\Auth;

if (! function_exists('auth')) {
    /**
     * Provides convenient access to the main Auth class
     * for CodeIgniter Shield.
     *
     * @param string|null $alias Authenticator alias
     */
    function auth(?string $alias = null)
    {
        /** @var Auth $auth */
        $auth = service('auth');

        return $auth->setAuthenticator($alias);
    }
}

if (! function_exists('user_id')) {
    /**
     * Returns the ID for the current logged in user.
     * Note: For \CodeIgniter\Shield\Entities\User this will always return an int.
     *
     * @return mixed|null
     */
    function user_id()
    {
        return service('auth')->id();
    }
}
