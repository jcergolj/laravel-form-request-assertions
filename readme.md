**Package for unit testing Laravel form request classes.**

# Why
Colin DeCarlo gave a talk on [Laracon online 21](https://laracon.net/) about unit testing Laravel form requests classes. If you haven't seen his talk, I recommend that you watch it.
He prefers testing form requests as a unit and not as feature tests.I like this approach too.

He asked Freek Van der Herten to convert his gist code to package. Granted, I am not Freek; however, I accepted the challenge, and I did it myself. So this package is just a wrapper for [Colin's gist](https://gist.github.com/colindecarlo/9ba9bd6524127fee7580ae66c6d4709d), and I added two methods from [Jason's package](https://github.com/jasonmccreary/laravel-test-assertions) for asserting that controller has the form request.

# Installation
`composer require --dev jcergolj/laravel-form-request-assertions`

# Usage
### Controller
```
<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePostRequest;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function store(CreatePostRequest $request)
    {
    }
}

```

### web.php routes
```
<?php

use App\Http\Controllers\PostController;

Route::post('posts', [PostController::class, 'store']);
```

### Request
```
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

### Add trait to unit test
After package installation add the `TestableFormRequest` trait
```
<?php

namespace Tests\Unit;

use Tests\TestCase;
use Jcergolj\FormRequestAssertions\TestableFormRequest;

class CreatePostRequestTest extends TestCase
{
    use TestableFormRequest;
}

```

### Does the controller have the form request test?
```
public function controller_has_form_request()
{
    $this->assertActionUsesFormRequest(PostController::class, 'store', CreatePostRequest::class);
}
```

### Test Validation Rules
```
public function email_is_required()
{
    $this->createFormRequest(CreatePostRequest::class)
        ->validate(['email' => ''])
        ->assertFails(['email' => 'required'])
	    ->assertHasMessage('Email is required', 'required')
}
```

### Test Form Request
```
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


# Available Methods
```
createFormRequest(string $requestClass, $headers = [])
```

```
assertRouteUsesFormRequest(string $routeName, string $formRequest)
```

```
assertActionUsesFormRequest(string $controller, string $method, string $form_request)
```

```
validate(array $data)
```

```
by(Authenticatable $user = null)
```

```
actingAs(Authenticatable $user = null)
```

```
withParams(array $params)
```

```
withParam(string $param, $value)
```

```
assertAuthorized()
```

```
assertNotAuthorized()
```

```
assertPasses()
```

```
assertFails($expectedFailedRules = [])
```

```
assertHasMessage($message, $rule = null)
```

```
getFailedRules()
```

# Contributors
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
