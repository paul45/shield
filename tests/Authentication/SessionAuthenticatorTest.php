<?php

namespace Tests\Authentication;

use CodeIgniter\Shield\Authentication\Authentication;
use CodeIgniter\Shield\Authentication\AuthenticationException;
use CodeIgniter\Shield\Authentication\Authenticators\Session;
use CodeIgniter\Shield\Config\Auth;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\RememberModel;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Shield\Result;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\Mock\MockEvents;
use Config\Services;
use Tests\Support\FakeUser;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class SessionAuthenticatorTest extends TestCase
{
    use DatabaseTestTrait;
    use FakeUser;

    protected Session $auth;
    protected $namespace;
    protected $events;

    protected function setUp(): void
    {
        parent::setUp();

        $config = new Auth();
        $auth   = new Authentication($config);
        $auth->setProvider(model(UserModel::class)); // @phpstan-ignore-line

        /** @var Session $authenticator */
        $authenticator = $auth->factory('session');
        $this->auth    = $authenticator;

        $this->events = new MockEvents();
        Services::injectMock('events', $this->events);

        $this->db->table('auth_identities')->truncate();
    }

    public function testLoggedIn()
    {
        $this->assertFalse($this->auth->loggedIn());

        $_SESSION['logged_in'] = $this->user->id;

        $this->assertTrue($this->auth->loggedIn());

        $authUser = $this->auth->getUser();
        $this->assertInstanceOf(User::class, $authUser);
        $this->assertSame($this->user->id, $authUser->getAuthId());
    }

    public function testLoginNoRemember()
    {
        $this->user->createEmailIdentity(['email' => 'foo@example.com', 'password' => 'secret']);

        $this->assertTrue($this->auth->login($this->user));

        $this->assertSame($this->user->id, $_SESSION['logged_in']);

        $this->dontSeeInDatabase('auth_remember_tokens', [
            'user_id' => $this->user->id,
        ]);
    }

    public function testLoginWithRemember()
    {
        $this->user->createEmailIdentity(['email' => 'foo@example.com', 'password' => 'secret']);

        $this->assertTrue($this->auth->remember()->login($this->user));

        $this->assertSame($this->user->id, $_SESSION['logged_in']);

        $this->seeInDatabase('auth_remember_tokens', [
            'user_id' => $this->user->id,
        ]);

        // Cookie should have been set
        $response = service('response');
        $this->assertNotNull($response->getCookie('remember'));
    }

    public function testLogout()
    {
        $this->user->createEmailIdentity(['email' => 'foo@example.com', 'password' => 'secret']);
        $this->auth->remember()->login($this->user);

        $this->seeInDatabase('auth_remember_tokens', ['user_id' => $this->user->id]);

        $this->auth->logout();

        $this->assertArrayNotHasKey('logged_in', $_SESSION);
        $this->dontSeeInDatabase('auth_remember_tokens', ['user_id' => $this->user->id]);
    }

    public function testLoginByIdBadUser()
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage(lang('Auth.invalidUser'));

        $this->auth->loginById(123);
    }

    public function testLoginById()
    {
        $this->user->createEmailIdentity(['email' => 'foo@example.com', 'password' => 'secret']);

        $this->auth->loginById($this->user->id);

        $this->assertSame($this->user->id, $_SESSION['logged_in']);

        $this->dontSeeInDatabase('auth_remember_tokens', ['user_id' => $this->user->id]);
    }

    public function testLoginByIdRemember()
    {
        $this->user->createEmailIdentity(['email' => 'foo@example.com', 'password' => 'secret']);

        $this->auth->remember()->loginById($this->user->id);

        $this->assertSame($this->user->id, $_SESSION['logged_in']);

        $this->seeInDatabase('auth_remember_tokens', ['user_id' => $this->user->id]);
    }

    public function testForgetCurrentUser()
    {
        $this->user->createEmailIdentity(['email' => 'foo@example.com', 'password' => 'secret']);
        $this->auth->remember()->loginById($this->user->id);
        $this->assertSame($this->user->id, $_SESSION['logged_in']);

        $this->seeInDatabase('auth_remember_tokens', ['user_id' => $this->user->id]);

        $this->auth->forget();

        $this->dontSeeInDatabase('auth_remember_tokens', ['user_id' => $this->user->id]);
    }

    public function testForgetAnotherUser()
    {
        fake(RememberModel::class, ['user_id' => $this->user->id]);

        $this->seeInDatabase('auth_remember_tokens', ['user_id' => $this->user->id]);

        $this->auth->forget($this->user->id);

        $this->dontSeeInDatabase('auth_remember_tokens', ['user_id' => $this->user->id]);
    }

    public function testCheckNoPassword()
    {
        $result = $this->auth->check([
            'email' => 'johnsmith@example.com',
        ]);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertFalse($result->isOK());
        $this->assertSame(lang('Auth.badAttempt'), $result->reason());
    }

    public function testCheckCannotFindUser()
    {
        $result = $this->auth->check([
            'email'    => 'johnsmith@example.com',
            'password' => 'secret',
        ]);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertFalse($result->isOK());
        $this->assertSame(lang('Auth.badAttempt'), $result->reason());
    }

    public function testCheckBadPassword()
    {
        $this->user->createEmailIdentity([
            'email'    => 'foo@example.com',
            'password' => 'secret123',
        ]);

        $result = $this->auth->check([
            'email'    => $this->user->email,
            'password' => 'secret',
        ]);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertFalse($result->isOK());
        $this->assertSame(lang('Auth.invalidPassword'), $result->reason());
    }

    public function testCheckSuccess()
    {
        $this->user->createEmailIdentity([
            'email'    => 'foo@example.com',
            'password' => 'secret123',
        ]);

        $result = $this->auth->check([
            'email'    => $this->user->email,
            'password' => 'secret123',
        ]);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isOK());

        $foundUser = $result->extraInfo();
        $this->assertSame($this->user->id, $foundUser->id);
    }

    public function testAttemptCannotFindUser()
    {
        $result = $this->auth->attempt([
            'email'    => 'johnsmith@example.com',
            'password' => 'secret',
        ]);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertFalse($result->isOK());
        $this->assertSame(lang('Auth.badAttempt'), $result->reason());

        // A login attempt should have always been recorded
        $this->seeInDatabase('auth_logins', [
            'email'   => 'johnsmith@example.com',
            'success' => 0,
        ]);
    }

    public function testAttemptSuccess()
    {
        $this->user->createEmailIdentity([
            'email'    => 'foo@example.com',
            'password' => 'secret123',
        ]);

        $this->assertArrayNotHasKey('logged_in', $_SESSION);

        $result = $this->auth->attempt([
            'email'    => $this->user->email,
            'password' => 'secret123',
        ]);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isOK());

        $foundUser = $result->extraInfo();
        $this->assertSame($this->user->id, $foundUser->id);

        $this->assertArrayHasKey('logged_in', $_SESSION);
        $this->assertSame($this->user->id, $_SESSION['logged_in']);

        // A login attempt should have been recorded
        $this->seeInDatabase('auth_logins', [
            'email'   => $this->user->email,
            'success' => 1,
        ]);
    }

    public function testAttemptCaseInsensitive()
    {
        $this->user->createEmailIdentity([
            'email'    => 'FOO@example.com',
            'password' => 'secret123',
        ]);

        $this->assertArrayNotHasKey('logged_in', $_SESSION);

        $result = $this->auth->attempt([
            'email'    => 'foo@example.COM',
            'password' => 'secret123',
        ]);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isOK());

        $foundUser = $result->extraInfo();
        $this->assertSame($this->user->id, $foundUser->id);

        $this->assertArrayHasKey('logged_in', $_SESSION);
        $this->assertSame($this->user->id, $_SESSION['logged_in']);

        // A login attempt should have been recorded
        $this->seeInDatabase('auth_logins', [
            'email'   => 'foo@example.COM',
            'success' => 1,
        ]);
    }

    public function testAttemptUsernameOnly()
    {
        /** @var User $user */
        $user = fake(UserModel::class, ['username' => 'foorog']);
        $user->createEmailIdentity([
            'email'    => 'FOO@example.com',
            'password' => 'secret123',
        ]);

        $this->assertArrayNotHasKey('logged_in', $_SESSION);

        $result = $this->auth->attempt([
            'username' => 'fooROG',
            'password' => 'secret123',
        ]);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isOK());

        $foundUser = $result->extraInfo();
        $this->assertSame($user->id, $foundUser->id);

        $this->assertArrayHasKey('logged_in', $_SESSION);
        $this->assertSame($user->id, $_SESSION['logged_in']);

        // A login attempt should have been recorded
        $this->seeInDatabase('auth_logins', [
            'email'   => 'fooROG',
            'success' => 1,
        ]);
    }
}
