<?php

namespace Tests\Authentication;

use CodeIgniter\Config\Factories;
use CodeIgniter\Shield\Entities\AccessToken;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Filters\TokenAuth;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;
use Tests\Support\DatabaseTestCase;

/**
 * @internal
 */
final class TokenFilterTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    protected $namespace;

    protected function setUp(): void
    {
        Services::reset();

        parent::setUp();

        $_SESSION = [];

        // Register our filter
        $filterConfig                       = config('Filters');
        $filterConfig->aliases['tokenAuth'] = TokenAuth::class;
        Factories::injectMock('filters', 'filters', $filterConfig);

        // Add a test route that we can visit to trigger.
        $routes = service('routes');
        $routes->group('/', ['filter' => 'tokenAuth'], static function ($routes) {
            $routes->get('protected-route', static function () {
                echo 'Protected';
            });
        });
        $routes->get('open-route', static function () {
            echo 'Open';
        });
        $routes->get('login', 'AuthController::login', ['as' => 'login']);
        Services::injectMock('routes', $routes);
    }

    public function testFilterNotAuthorized()
    {
        $result = $this->call('get', 'protected-route');

        $result->assertRedirectTo('/login');

        $result = $this->get('open-route');
        $result->assertStatus(200);
        $result->assertSee('Open');
    }

    public function testFilterSuccess()
    {
        /** @var User $user */
        $user  = fake(UserModel::class);
        $token = $user->generateAccessToken('foo');

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token->raw_token])
            ->get('protected-route');

        $result->assertStatus(200);
        $result->assertSee('Protected');

        $this->assertSame($user->id, auth('tokens')->id());
        $this->assertSame($user->id, auth('tokens')->user()->getAuthId());

        // User should have the current token set.
        $this->assertInstanceOf(AccessToken::class, auth('tokens')->user()->currentAccessToken());
        $this->assertSame($token->id, auth('tokens')->user()->currentAccessToken()->id);
    }
}
