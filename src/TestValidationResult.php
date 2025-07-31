<?php

namespace Jcergolj\FormRequestAssertions;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\Assert;
use ReflectionClass;

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

    foreach ($expectedFailedRules as $attribute => $expectedRule) {
        // Get the actual rule instances used
        $rules = collect($this->validator->getRules()[$attribute] ?? []);

        if ($expectedRule instanceof \Illuminate\Validation\Rules\Enum) {
            // Extract expected enum class via reflection
            $ref = new \ReflectionClass($expectedRule);
            $prop = $ref->getProperty('type');
            $prop->setAccessible(true);
            $expectedEnumClass = $prop->getValue($expectedRule);

            // Find Enum rule for this attribute
            $enumRule = $rules->first(function ($rule) {
                return $rule instanceof \Illuminate\Validation\Rules\Enum;
            });

            Assert::assertNotNull(
                $enumRule,
                "No Enum rule found on field [{$attribute}]."
            );

            // Compare enum class used in rule
            $enumRef = new \ReflectionClass($enumRule);
            $typeProp = $enumRef->getProperty('type');
            $typeProp->setAccessible(true);
            $actualEnumClass = $typeProp->getValue($enumRule);

            Assert::assertSame(
                $expectedEnumClass,
                $actualEnumClass,
                "Enum class mismatch on field [{$attribute}]. Expected {$expectedEnumClass}, got {$actualEnumClass}."
            );
        } else {
            // For regular rule names (e.g. 'required')
            $failedRules = $this->getFailedRules();

            Assert::assertTrue(
                isset($failedRules[$attribute]),
                "Field [{$attribute}] did not fail as expected."
            );

            $this->assertRules($expectedRule, $failedRules, $attribute);
        }
    }

    return $this;
}


    public function ddFailedRules()
    {
        dd($this->getFailedRules());
    }

    public function assertHasMessage($message, $field = null)
    {
        $validationMessages = $this->getValidationMessages($field);
        Assert::assertContains(
            $message,
            $validationMessages,
            sprintf(
                "\"%s\" was not contained in the failed%s validation messages\n%s",
                $message,
                $field ? ' '.$field : '',
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

    public function assertHasRule($attribute, $rule)
    {
        $reflection = new ReflectionClass($this->validator);
        $reflectedValidation = $reflection->getProperty('initialRules');
        $reflectedValidation->setAccessible(true);
        $initialRules = $reflectedValidation->getValue($this->validator);

        Assert::assertTrue(in_array($rule, $initialRules[$attribute]));

        return $this;
    }

    private function getValidationMessages($field = null)
    {
        $messages = $this->validator->messages()->getMessages();
        if ($field) {
            return $messages[$field] ?? [];
        }

        return Arr::flatten($messages);
    }

    /**
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

    public function assertRulesWithoutFailures($expectedPassedRules = []): static
    {
        if ($this->validator->passes()) {
            Assert::assertTrue(true); // Prevent assertion-count is 0

            return $this;
        }

        $failedRules = $this->getFailedRules();

        foreach ($expectedPassedRules as $expectedPassedRule => $constraints) {
            $this->assertPassedRule($constraints, $failedRules, $expectedPassedRule);
        }

        return $this;
    }

    protected function assertPassedRule($constraints, $failedRules, $expectedFailedRule): void
    {
        if (class_exists($constraints)) {
            Assert::assertNotContains(strtolower($constraints), $failedRules);

            return;
        }

        if ($failedRules->has($expectedFailedRule)) {
            Assert::assertStringNotContainsString($constraints, $failedRules[$expectedFailedRule]);
        } else {
            Assert::assertTrue(true); // Prevent assertion-count is 0
        }
    }
}
