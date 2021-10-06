<?php

namespace Jcergolj\FormRequestAssertions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;
use Symfony\Component\HttpFoundation\ParameterBag;

class TestFormRequest
{
    private FormRequest $request;

    public function __construct(FormRequest $request)
    {
        $this->request = $request;
    }

    public function validate(array $data)
    {
        $this->request->request = new ParameterBag($data);

        /** @var Validator $validator */
        $validator = \Closure::fromCallable(function () {
            return $this->getValidatorInstance();
        })->call($this->request);

        try {
            $validator->validate();
        } catch (ValidationException $e) {
            return new TestValidationResult($validator, $e);
        }

        return new TestValidationResult($validator);
    }

    public function by(Authenticatable $user = null)
    {
        $this->request->setUserResolver(fn () => $user);

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

    private function bully(\Closure $elevatedFunction, object $targetObject)
    {
        return \Closure::fromCallable($elevatedFunction)->call($targetObject);
    }
}
