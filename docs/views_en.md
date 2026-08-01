# Views

HeartPhrame uses plain PHP files for its view templates, which means you can
use all the power of PHP directly in your views without needing to learn
a new templating language.

---

## Creating a View

Views are stored in the `views/` directory by default (this can be configured
in `config/app.php`). A view is simply a
PHP file that contains the HTML markup for a part of your page.

For example, you could create a file at `views/greeting.php`:

```php
<?php

declare(strict_types=1);

/**
 * @var \HeartPhrame\View\View $this
 * @var string $name 
 */
?>

<h1>Hello, <?= $this->escape($name) ?>!</h1>
```

Each view is rendered with a `HeartPhrame\View\View` (`$this`). The `View`
instance provides a number of useful methods, like `escape()` that we
have used in the example above.

> **Note:** It's a good practice to escape any dynamic data you output
to prevent XSS attacks.

## Generating View Response From a Controller

In your controller action, you can render a view and return it as a
`Psr\Http\Message\ResponseInterface` response. For
this purpose, you can use the `HeartPhrame\Http\ResponseFactory` class,
like in the example below:

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use HeartPhrame\Http\ResponseFactory;
use Psr\Http\Message\ResponseInterface;

class HomeController
{
    public function __construct(
        protected readonly ResponseFactory $responseFactory,
    ) {
    }
    
    public function hello(): ResponseInterface
    {
        return $this->responseFactory->view('greeting', ['name' => 'World']);
    }
}
```

## Passing Data to Views

Data is passed to a view as an associative array as the second argument to the
`view` method. The keys of the array are extracted into variables within
the view's scope.

From the example above, the key `name` becomes the variable `$name` inside
the `greeting.php` template.

## Using Layouts

Layouts allow you to define a common structure (like headers, footers, and
sidebars) that can be shared across multiple pages. The default layout file
is `views/layouts/main.php`, as configured in `config/app.php`.

A layout file typically contains the main HTML structure and a placeholder
where the content of the specific view will be injected.

Here is an example of `views/layouts/main.php`:

```php
<?php

declare(strict_types=1);

/**
 * @var \HeartPhrame\View\View $this
 * @var string|null $content
 */
 ?>
 

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Application</title>
</head>
<body>
    <header>
        <h1>My App</h1>
    </header>

    <main>
        <?= $content ?? '' ?>
    </main>

    <footer>
        <p>&copy; <?= date('Y') ?> My Application</p>
    </footer>
</body>
</html>
```

When you render a view, its generated content is passed to the layout in
the `$content` variable.

### Select a Different Layout

If you need to render a view using a different layout than the one set as
default, pass the layout name as the third argument to the `view` method:

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use HeartPhrame\Http\ResponseFactory;
use Psr\Http\Message\ResponseInterface;

class HomeController
{
    public function __construct(
        protected readonly ResponseFactory $responseFactory,
    ) {
    }
    
    public function hello(): ResponseInterface
    {
        return $this->responseFactory->view(
            'greeting',
            ['name' => 'World'],
            'layouts/alternative'
         );
    }
}
```

### Disable Layout

To get a view without any layout (for example, for an AJAX request that
returns an HTML fragment), set the layout
name to `null`:

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use HeartPhrame\Http\ResponseFactory;
use Psr\Http\Message\ResponseInterface;

class HomeController
{
    public function __construct(
        protected readonly ResponseFactory $responseFactory,
    ) {
    }
    
    public function hello(): ResponseInterface
    {
        return $this->responseFactory->view(
            'greeting',
            ['name' => 'World'],
            null,
         );
    }
}
```

## Rendering View String Content

To render a view to get its string content, you can use the `for` method
on the `View` instance:

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use HeartPhrame\Http\ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use HeartPhrame\View\View;

class HomeController
{
    public function __construct(
        protected readonly View $view,
        protected readonly ResponseFactory $responseFactory,
    ) {
    }
    
    public function hello(): ResponseInterface
    {
        // Get the view content as a string, using the default layout.
        $viewContent = $this->view->for('greeting', ['name' => 'World']);
        
        // Get the view content as a string, using a different layout.
        $viewContentAlternative = $this->view->for('greeting', ['name' => 'World'], 'layouts/alternative');
        
        // Get the view content as a string, without any layout.
        $viewContentWithoutLayout = $this->view->for('greeting', ['name' => 'World'], null);
                
        return $this->responseFactory->html($viewContent);
    }
}
```

## Rendering View Partials

You can also render a view as a partial, which is useful when you want to
reuse a view in multiple places.
For example, you could create a `views/partials/flash.php` file:

```php
<?php

declare(strict_types=1);

/**
 * @var \HeartPhrame\View\View $this
 * @var string $message 
 */
 ?>
  
 <p><?= $this->escape($message) ?></p>
```

Then, you can render the partial in a view:

```php
<?php

declare(strict_types=1);

/**
 * @var \HeartPhrame\View\View $this
 * @var string $message 
 */
 ?>

<?= $this->forPartial('partials/flash', ['message' => $message]) ?>

```

## Rendering Module Views

`HeartPhrame\Http\ResponseFactory` and `HeartPhrame\View\View` can be
used to render views from modules.
Here is a short overview of the methods that you can use

```php
<?php

declare(strict_types=1);

/** @var \HeartPhrame\Http\ResponseFactory $responseFactory */
$responseInstance = $responseFactory->viewForModule(
    'moduleName',
    'viewName',
    ['name' => 'World'],
    layout: 'layouts/alternative', // Or set to `null` to disable the layout, or `true` to use the default layout.
    useModuleLayout: true, // Or set to `false` to use the default app layout.
);

/** @var \HeartPhrame\View\View $view */
$viewString = $view->forModule(
    'moduleName',
    'viewName',
    ['name' => 'World'],
    layout: 'layouts/alternative', // Or set to `null` to disable the layout, or `true` to use the default layout.
    useModuleLayout: true, // Or set to `false` to use the default app layout.
);
```
