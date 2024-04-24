<?php

namespace Jcergolj\FormRequestAssertions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use PHPUnit\Framework\Assert as PHPUnitAssert;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

trait TestableFormRequest
{
    public function assertRouteUsesFormRequest(string $routeName, string $formRequest)
    {
        $controllerAction = collect(RouteFacade::getRoutes())->filter(function (Route $route) use ($routeName) {
            return $route->getName() == $routeName;
        })->pluck('action.controller');

        PHPUnitAssert::assertNotEmpty($controllerAction, 'Route "'.$routeName.'" is not defined.');
        PHPUnitAssert::assertCount(1, $controllerAction, 'Route "'.$routeName.'" is defined multiple times, route names should be unique.');

        $controller = $controllerAction->first();
        $method = '__invoke';
        if (strstr($controllerAction->first(), '@')) {
            [$controller, $method] = explode('@', $controllerAction->first());
        }

        $this->assertActionUsesFormRequest($controller, $method, $formRequest);
    }

    public function assertContainsFormRequest(string $form_request)
    {
        $controller = RouteFacade::getRoutes()->getByName(RouteFacade::currentRouteName())->getController();
        $method = RouteFacade::getRoutes()->getByName(RouteFacade::currentRouteName())->getActionMethod();

        $reflectionMethod = new ReflectionMethod($controller, $method);
        $reflectionParams = collect($reflectionMethod->getParameters());

        PHPUnitAssert::assertTrue($reflectionParams->contains(function ($reflectionParam) use ($form_request) {
            return $reflectionParam->getType()->getName() === $form_request;
        }), 'Action "'.$method.'" does not have validation using the "'.$form_request.'" Form Request.');

        return $this;
    }

    public function assertActionUsesFormRequest(string $controller, string $method, string $form_request)
    {
        PHPUnitAssert::assertTrue(is_subclass_of($form_request, 'Illuminate\\Foundation\\Http\\FormRequest'), $form_request.' is not a type of Form Request');

        try {
            $reflector = new \ReflectionClass($controller);
            $action = $reflector->getMethod($method);
        } catch (\ReflectionException $exception) {
            PHPUnitAssert::fail('Controller action could not be found: '.$controller.'@'.$method);
        }

        PHPUnitAssert::assertTrue($action->isPublic(), 'Action "'.$method.'" is not public, controller actions must be public.');

        $actual = collect($action->getParameters())->contains(function ($parameter) use ($form_request) {
            return $parameter->getType() instanceof \ReflectionNamedType && $parameter->getType()->getName() === $form_request;
        });

        PHPUnitAssert::assertTrue($actual, 'Action "'.$method.'" does not have validation using the "'.$form_request.'" Form Request.');
    }

    protected function createFormRequest(string $requestClass, $headers = [])
    {
        $symfonyRequest = SymfonyRequest::create(
            $this->prepareUrlForRequest('/test/route'),
            'POST',
            [],
            $this->prepareCookiesForRequest(),
            [],
            array_replace($this->serverVariables, $this->transformHeadersToServerVars($headers))
        );

        $formRequest = FormRequest::createFrom(
            Request::createFromBase($symfonyRequest),
            new $requestClass
        )->setContainer($this->app);

        $route = new Route('POST', '/test/route', fn () => null);
        $route->parameters = [];
        $formRequest->setRouteResolver(fn () => $route);

        return $this->createNewTestFormRequest($formRequest);
    }

    protected function createNewTestFormRequest(FormRequest $request): TestFormRequest
    {
        return new TestFormRequest($request);
    }
}
