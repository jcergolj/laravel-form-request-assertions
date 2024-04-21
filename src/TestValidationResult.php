<?php

namespace Jcergolj\FormRequestAssertions;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\Assert;

class TestValidationResult
{
    protected Validator $validator;

    protected ?ValidationException $failed;

    public function __construct(Validator $validator, ?ValidationException $failed = null)
    {
        $this->validator = $validator;
        $this->failed = $failed;
    }

    public function assertPasses()
    {
        Assert::assertTrue(
            $this->validator->passes(),
            sprintf(
                "Validation of the payload:\n%s\ndid not pass validation rules\n%s\n",
                json_encode($this->validator->getData(), JSON_PRETTY_PRINT),
                json_encode($this->getFailedRules(), JSON_PRETTY_PRINT)
            )
        );

        return $this;
    }

    public function assertFails($expectedFailedRules = [])
    {
        Assert::assertTrue($this->validator->fails());

        if (empty($expectedFailedRules)) {
            return $this;
        }

        $failedRules = $this->getFailedRules();

        foreach ($expectedFailedRules as $expectedFailedRule => $constraints) {
            $this->assertRules($constraints, $failedRules, $expectedFailedRule);
        }

        return $this;
    }

    public function ddFailedRules()
    {
        dd($this->getFailedRules());
    }

    public function assertHasMessage($message, $rule = null)
    {
        $validationMessages = $this->getValidationMessages($rule);
        Assert::assertContains(
            $message,
            $validationMessages,
            sprintf(
                "\"%s\" was not contained in the failed%s validation messages\n%s",
                $message,
                $rule ? ' '.$rule : '',
                json_encode($validationMessages, JSON_PRETTY_PRINT)
            )
        );

        return $this;
    }

    public function getFailedRules()
    {
        if (! $this->failed) {
            return [];
        }

        $failedRules = collect($this->validator->failed())
            ->map(function ($details) {
                return collect($details)->reduce(function ($aggregateRule, $constraints, $ruleName) {
                    $failedRule = Str::lower($ruleName);

                    if (count($constraints)) {
                        $failedRule .= ':'.implode(',', $constraints);
                    }

                    return $aggregateRule.$failedRule;
                });
            });

        return $failedRules;
    }

    private function getValidationMessages($rule = null)
    {
        $messages = $this->validator->messages()->getMessages();
        if ($rule) {
            return $messages[$rule] ?? [];
        }

        return Arr::flatten($messages);
    }

    /**
     *
     *
     * @param  mixed  name
     * @return mixed
     */
    protected function assertRules($constraints, $failedRules, $expectedFailedRule)
    {
        if (class_exists($constraints)) {
            return Assert::assertContains(strtolower($constraints), $failedRules);
        }

        Assert::assertArrayHasKey($expectedFailedRule, $failedRules);
        Assert::assertStringContainsString($constraints, $failedRules[$expectedFailedRule]);
    }
}
