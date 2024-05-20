# **Package for unit testing Laravel form request classes.**

## Why

Colin DeCarlo gave a talk on [Laracon online 21](https://laracon.net/) about unit testing Laravel form requests classes. If you haven't seen his talk, I recommend that you watch it.
He prefers testing form requests as a unit and not as feature tests.I like this approach too.

He asked Freek Van der Herten to convert his gist code to package. Granted, I am not Freek; however, I accepted the challenge, and I did it myself. So this package is just a wrapper for [Colin's gist](https://gist.github.com/colindecarlo/9ba9bd6524127fee7580ae66c6d4709d), and I added two methods from [Jason's package](https://github.com/jasonmccreary/laravel-test-assertions) for asserting that controller has the form request.

## Installation

Required PHP >=8.0

```bash
composer require --dev jcergolj/laravel-form-request-assertions
```

## Usage

### Controller

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePostRequest;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function store(CreatePostRequest $request)
    {
        // ...
    }
}
```

### web.php routes

```php
<?php

use App\Http\Controllers\PostController;

Route::post('posts', [PostController::class, 'store']);
```

### Request

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePostRequest extends FormRequest
{
    public function authorize()
    {
	    return $this->user()->id === 1 && $this->post->id === 1;
    }

    function rules()
    {
        return ['email' => ['required', 'email']];
    }
}
```

### Add the trait to a unit test

After package installation add the `TestableFormRequest` trait

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use Jcergolj\FormRequestAssertions\TestableFormRequest;

class CreatePostRequestTest extends TestCase
{
    use TestableFormRequest;

    // ...
}
```

### Does the controller have the form request test?

```php
public function controller_has_form_request()
{
    $this->assertActionUsesFormRequest(PostController::class, 'store', CreatePostRequest::class);
}
```

or

```php
public function controller_has_form_request()
{
    $this->post(route('users.store'));

    $this->assertContainsFormRequest(CreateUserRequest::class);
}
```

### Test Validation Rules

```php
public function email_is_required()
{
    $this->createFormRequest(CreatePostRequest::class)
        ->validate(['email' => ''])
        ->assertFails(['email' => 'required'])
	    ->assertHasMessage('Email is required', 'required');

    $this->createFormRequest(CreatePostRequest::class)
        ->validate(['password' => 'short'])
        ->assertFails(['password' => App\Rules\PasswordRule::class]); //custom password rule class
}
```

### Test attribute has the rule
When dealing with more complicated rules, you might extract logic to dedicated custom rule class. In that instance
you don't want to test the logic inside RequestTest class but rather in dedicated custom rule test class. Here you are only
interested if the give attribute has/contains the custom rule.

```php
public function email_has_custom_rule_applied()
{
    $this->createFormRequest(CreatePostRequest::class)
        ->validate()
        ->assertHasRule('email', CustomRule::class); // here we don't validate the rule, but just make sure rule is applied
}
```

### Test Form Request

```php
 /** @test */
function test_post_author_is_authorized()
{
    $author = User::factory()->make(['id' => 1]);
    $post = Post::factory()->make(['id' => 1]);

    $this->createFormRequest(CreatePostRequest::class)
        ->withParam('post', $post)
        ->actingAs($author)
        ->assertAuthorized();
}
```

### Test data preparation

Test how data is prepared within the `prepareForValidation` method of the `FormRequest`.

```php
 /** @test */
function test_transforms_email_to_lowercase_before_validation()
{
    $this->createFormRequest(CreatePostRequest::class)
        ->onPreparedData(['email' => 'TeSt@ExAmPlE.cOm'], function (array $preparedData) {
            $this->assertEquals('test@example.com', $preparedData['email']);
        });
}
```

## Extending

If you need additional/custom assertions, you can easily extend the `\Jcergolj\FormRequestAssertions\TestFormRequest` class.

1. Create a new class, for example: `\Tests\Support\TestFormRequest` extending the `\Jcergolj\FormRequestAssertions\TestFormRequest` class.
   ```php
   namespace Tests\Support;
   class TestFormRequest extends \Jcergolj\FormRequestAssertions\TestFormRequest
   {
     public function assertSomethingImportant()
     {
       // your assertions on `$this->request`
     }
   }
   ```
2. Create a new trait, for example: `\Tests\Traits\TestableFormRequest` using the `\Jcergolj\FormRequestAssertions\TestableFormRequest` trait.
3. Overwrite the `\Jcergolj\FormRequestAssertions\TestableFormRequest::createNewTestFormRequest` method to return an instance of the class created in (1).
   ```php
   namespace Tests\Support;
   trait TestableFormRequest {
     use \Jcergolj\FormRequestAssertions\TestableFormRequest;

     protected function createNewTestFormRequest(FormRequest $request): TestFormRequest
     {
       return new \Tests\Support\TestFormRequest($request);
     }
   }
   ```
4. Use your custom trait instead of `\Jcergolj\FormRequestAssertions\TestableFormRequest` on your test classes

# Available Methods

```php
createFormRequest(string $requestClass, $headers = [])
```

```php
assertRouteUsesFormRequest(string $routeName, string $formRequest)
```

```php
assertActionUsesFormRequest(string $controller, string $method, string $form_request)
```

```php
validate(array $data)
```

```php
by(Authenticatable $user = null)
```

```php
actingAs(Authenticatable $user = null)
```

```php
withParams(array $params)
```

```php
withParam(string $param, $value)
```

```php
assertAuthorized()
```

```php
assertNotAuthorized()
```

```php
assertPasses()
```

```php
assertFails($expectedFailedRules = [])
```

```php
assertHasMessage($message, $rule = null)
```

```php
getFailedRules()
```

## Contributors

A huge thanks go to Colin and Jason. I created a package from [Colin's gist](https://gist.github.com/colindecarlo/9ba9bd6524127fee7580ae66c6d4709d) and I copied two methods from [Jason's package](https://github.com/jasonmccreary/laravel-test-assertions).

<table>
<tr>
<td>
<a href="https://gist.github.com/colindecarlo">
<img src="https://avatars.githubusercontent.com/u/682860?v=4" width="100px">
<br />
<sub>
<b>Colin DeCarlo</b>
</sub>
</a>
</td>
<td>
<a href="https://github.com/jasonmccreary">
<img src="https://avatars.githubusercontent.com/u/161071?v=4" width="100px">
<br />
<sub>
<b>Jason McCreary</b>
</sub>
</a>
</td>
<td>
<a href="https://github.com/jcergolj">
<img src="https://avatars0.githubusercontent.com/u/6940394?s=460&amp;u=b4eaa035a3526a442d7d09dbf4d9d3ca63bfc1a5&amp;v=4" width="100px">
<br />
<sub>
<b>Janez Cergolj</b>
</sub>
</a>
</td>
</tr>
</table>
