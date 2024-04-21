<?php

namespace Jcergolj\FormRequestAssertions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Gate;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;
use Symfony\Component\HttpFoundation\ParameterBag;

class TestFormRequest
{
    private FormRequest $request;
    protected ?\Mockery\ClosureWrapper $userResolver = null;

    public function __construct(FormRequest $request)
    {
        $this->request = $request;
    }

    public function validator(array $data = [])
    {
        $this->request->request->replace($data);

        return \Closure::fromCallable(function () {
            return $this->getValidatorInstance();
        })->call($this->request);
    }

    public function validate(array $data)
    {
        $validator = $this->validator($data);

        try {
            $validator->validate();
        } catch (ValidationException $e) {
            return new TestValidationResult($validator, $e);
        }

        return new TestValidationResult($validator);
    }

    public function by(Authenticatable $user = null)
    {
        $this->userResolver = \Mockery::spy(fn() => $user);
        $this->request->setUserResolver(fn (...$args) => call_user_func_array($this->userResolver, $args));

        return $this;
    }

    public function actingAs(Authenticatable $user = null)
    {
        return $this->by($user);

        return $this;
    }

    public function withParams(array $params)
    {
        foreach ($params as $param => $value) {
            $this->withParam($param, $value);
        }

        return $this;
    }

    public function withParam(string $param, $value)
    {
        $this->request->route()->setParameter($param, $value);

        return $this;
    }

    public function assertAuthorized()
    {
        assertTrue(
            $this->bully(fn () => $this->passesAuthorization(), $this->request),
            'The provided user is not authorized by this request'
        );
    }

    public function assertNotAuthorized()
    {
        assertFalse(
            $this->bully(fn () => $this->passesAuthorization(), $this->request),
            'The provided user is authorized by this request'
        );
    }

    public function assertCallsGate($action, $params, $guard = null): void
    {
        Gate::shouldReceive('forUser')
            ->andReturnSelf();
        Gate::shouldReceive('check')
            ->once()
            ->with($action, $params)
            ->andReturn(true);
        if ($guard && !$this->userResolver) {
            $this->by(null);
        }

        $this->bully(fn () => $this->passesAuthorization(), $this->request);
        if ($guard) {
            $this->userResolver->shouldHaveBeenCalled()
                ->with($guard);
        }
    }

    private function bully(\Closure $elevatedFunction, object $targetObject)
    {
        return \Closure::fromCallable($elevatedFunction)->call($targetObject);
    }
}
