<?php

namespace CodeIgniter\Shield\Authentication\Passwords;

use CodeIgniter\Shield\Authentication\AuthenticationException;
use CodeIgniter\Shield\Result;

/**
 * Class CompositionValidator
 *
 * Checks the general makeup of the password.
 *
 * While older composition checks might have included different character
 * groups that you had to include, current NIST standards prefer to simply
 * set a minimum length and a long maximum (128+ chars).
 *
 * @see https://pages.nist.gov/800-63-3/sp800-63b.html#sec5
 */
class CompositionValidator extends BaseValidator implements ValidatorInterface
{
    /**
     * Returns true when the password passes this test.
     * The password will be passed to any remaining validators.
     * False will immediately stop validation process
     *
     * @param mixed|null $user
     */
    public function check(string $password, $user = null): Result
    {
        if (empty($this->config->minimumPasswordLength)) {
            throw AuthenticationException::forUnsetPasswordLength();
        }

        $passed = strlen($password) >= $this->config->minimumPasswordLength;

        if (! $passed) {
            return new Result([
                'success'   => false,
                'reason'    => lang('Auth.errorPasswordLength', [$this->config->minimumPasswordLength]),
                'extraInfo' => lang('Auth.suggestPasswordLength'),
            ]);
        }

        return new Result([
            'success' => true,
        ]);
    }
}
