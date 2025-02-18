<?php

namespace Tests\Authentication;

use CodeIgniter\Shield\Authentication\AuthenticationException;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class AuthHelperTest extends TestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        helper(['auth']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setPrivateProperty(auth(), 'alias', null);
    }

    public function testAuthReturnsDefaultAuthenticator()
    {
        $authenticatorClassname = config('Auth')->authenticators[config('Auth')->defaultAuthenticator];

        $this->assertInstanceOf($authenticatorClassname, auth()->getAuthenticator());
    }

    public function testAuthReturnsSpecifiedAuthenticator()
    {
        $authenticatorClassname = config('Auth')->authenticators['tokens'];

        $this->assertInstanceOf($authenticatorClassname, auth('tokens')->getAuthenticator());
    }

    public function testAuthThrowsWithInvalidAuthenticator()
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage(lang('Auth.unknownAuthenticator', ['foo']));

        auth('foo')->user();
    }

    public function testUserIdReturnsNull()
    {
        $this->assertFalse(auth()->loggedIn());
        $this->assertNull(user_id());
    }

    public function testUserIdReturnsId()
    {
        $user = fake(UserModel::class, ['id' => 1], false);
        $this->setPrivateProperty(auth()->getAuthenticator(), 'user', $user);

        $this->assertTrue(auth()->loggedIn());
        $this->assertSame($user->id, user_id());
    }
}
