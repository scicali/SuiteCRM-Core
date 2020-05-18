<?php namespace App\Tests;

use App\Service\RouteConverterInterface;
use \Codeception\Test\Unit;
use InvalidArgumentException;
use SuiteCRM\Core\Legacy\ActionNameMapperHandler;
use SuiteCRM\Core\Legacy\LegacyScopeState;
use SuiteCRM\Core\Legacy\ModuleNameMapperHandler;
use SuiteCRM\Core\Legacy\RouteConverterHandler;
use Symfony\Component\HttpFoundation\Request;

class RouteConverterTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;


    /**
     * @var RouteConverterInterface
     */
    private $routeConverter;

    protected function _before()
    {
        $projectDir = codecept_root_dir();
        $legacyDir = $projectDir . '/legacy';
        $legacySessionName = 'LEGACYSESSID';
        $defaultSessionName = 'PHPSESSID';

        $legacyScope = new LegacyScopeState();

        $moduleMapper = new ModuleNameMapperHandler(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScope
        );

        $actionMapper = new ActionNameMapperHandler(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScope
        );

        $this->routeConverter = new RouteConverterHandler(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScope,
            $moduleMapper,
            $actionMapper
        );
    }

    /**
     * Test check for to determine if is an API Request
     */
    public function testAPIRequestCheck(): void
    {
        $queryParams = [
        ];

        $serverParams = [
            'REDIRECT_BASE' => '/suiteinstance',
            'BASE' => '/suiteinstance',
            'HTTP_HOST' => 'localhost',
            'HTTP_ORIGIN' => 'http://localhost',
            'HTTP_REFERER' => 'http://localhost/suiteinstance/public/docs/graphql-playground/index.html',
            'SERVER_NAME' => 'localhost',
            'REDIRECT_URL' => '/suiteinstance/api/graphql',
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/suiteinstance/api/graphql',
            'SCRIPT_FILENAME' => '/var/www/html/suiteinstance/index.php',
            'SCRIPT_NAME' => '/suiteinstance/index.php',
            'PHP_SELF' => '/suiteinstance/index.php',
        ];

        $request = new Request($queryParams,[],[],[],[], $serverParams);

        $valid = $this->routeConverter->isLegacyViewRoute($request);

        static::assertFalse($valid);
    }

    /**
     * Test legacy call request on instance installed in a subpath
     */
    public function testLegacyValidSubPathRequestCheck(): void
    {
        $queryParams = [
            'module' => 'Contacts',
            'action' => 'ListView'
        ];

        $serverParams = [
            'BASE' => '/suiteinstance',
            'HTTP_HOST' => 'localhost',
            'SERVER_NAME' => 'localhost',
            'REDIRECT_URL' => '/suiteinstance/',
            'REDIRECT_QUERY_STRING' => 'module=Contacts&action=ListView',
            'REQUEST_METHOD' => 'GET',
            'QUERY_STRING' => 'module=Contacts&action=ListView',
            'SCRIPT_FILENAME' => '/var/www/html/suiteinstance/index.php',
            'REQUEST_URI' => '/suiteinstance/?module=Contacts&action=ListView',
            'SCRIPT_NAME' => '/suiteinstance/index.php',
            'PHP_SELF' => '/suiteinstance/index.php'
        ];

        $request = new Request($queryParams,[],[],[],[], $serverParams);

        $valid = $this->routeConverter->isLegacyViewRoute($request);

        static::assertTrue($valid);
    }

    /**
     * Test legacy call request check with valid request
     */
    public function testLegacyValidRequestCheck(): void
    {
        $queryParams = [
            'module' => 'Contacts',
            'action' => 'DetailView',
            'record' => '123',
        ];

        $request = new Request($queryParams);

        $valid = $this->routeConverter->isLegacyViewRoute($request);

        static::assertTrue($valid);
    }

    /**
     * Test legacy call request check with no module
     */
    public function testLegacyNoModuleRequestCheck(): void
    {
        $queryParams = [];

        $request = new Request($queryParams);

        $valid = $this->routeConverter->isLegacyViewRoute($request);

        static::assertFalse($valid);
    }

    /**
     * Test legacy call request check with invalid module
     */
    public function testLegacyInvalidModuleRequestCheck(): void
    {
        $queryParams = [
            'module' => 'FakeModule',
        ];

        $request = new Request($queryParams);

        $valid = $this->routeConverter->isLegacyViewRoute($request);

        static::assertFalse($valid);
    }

    /**
     * Test legacy call request check with invalid action
     */
    public function testLegacyInvalidActionRequestCheck(): void
    {
        $queryParams = [
            'module' => 'Contacts',
            'action' => 'FakeAction'
        ];

        $request = new Request($queryParams);

        $valid = $this->routeConverter->isLegacyViewRoute($request);

        static::assertFalse($valid);
    }

    /**
     * Test legacy call to valid module
     */
    public function testLegacyValidModuleIndexRRequest(): void
    {
        $resultingRoute = './#/contacts';
        $queryParams = [
            'module' => 'Contacts',
        ];
        $request = new Request($queryParams);

        $route = $this->routeConverter->convert($request);

        static::assertEquals($resultingRoute, $route);
    }

    /**
     * Test legacy call to valid view
     */
    public function testLegacyValidModuleViewRequest(): void
    {
        $resultingRoute = './#/contacts/list';
        $queryParams = [
            'module' => 'Contacts',
            'action' => 'ListView'
        ];

        $request = new Request($queryParams);

        $route = $this->routeConverter->convert($request);

        static::assertEquals($resultingRoute, $route);
    }

    /**
     * Test legacy call to module record
     */
    public function testLegacyValidModuleRecordRequest(): void
    {
        $resultingRoute = './#/contacts/record/123';
        $queryParams = [
            'module' => 'Contacts',
            'action' => 'DetailView',
            'record' => '123',
        ];

        $request = new Request($queryParams);

        $route = $this->routeConverter->convert($request);

        static::assertEquals($resultingRoute, $route);
    }

    /**
     * test legacy call to invalid module
     */
    public function testLegacyInvalidModuleIndexRequest(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $queryParams = [
            'module' => 'FakeModule',
        ];

        $request = new Request($queryParams);

        $this->routeConverter->convert($request);

    }

    /**
     * Test legacy call with invalid action
     */
    public function testLegacyModuleInvalidActionRequest(): void
    {
        $resultingRoute = './#/contacts/FakeAction';
        $queryParams = [
            'module' => 'Contacts',
            'action' => 'FakeAction'
        ];

        $request = new Request($queryParams);

        $route = $this->routeConverter->convert($request);

        static::assertEquals($resultingRoute, $route);
    }

    /**
     * Test legacy call without module
     */
    public function testLegacyNoModuleRequest(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $queryParams = [];

        $request = new Request($queryParams);

        $this->routeConverter->convert($request);
    }

}