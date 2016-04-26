# Stretcher - PHP Library

[![](https://img.shields.io/packagist/v/assouan/stretcher.svg)](https://packagist.org/packages/assouan/stretcher)
[![](https://img.shields.io/packagist/dt/assouan/stretcher.svg)](https://packagist.org/packages/assouan/stretcher)
[![](https://img.shields.io/packagist/l/assouan/stretcher.svg)](https://packagist.org/packages/assouan/stretcher)

PSR-7 stretchable middleware dispatcher

![](https://i.imgsafe.org/6459ebd.png)

## Installation

Install using composer:

```bash
$ composer require assouan/stretcher
```

## Usage

Stretcher:

```php
<?php

$app = new Stretcher();
//   = new Stretcher($resolver);

$app->add($callable1);
$app->add($callable2);
$app->add($callable3);

$response = $app->dispatch($request, $response);
```

Add middlewares in the queue priority in runetime:

```php
<?php

function middleware($request, $response, $next)
{
    // Write response header
    ...

    // Call middleware 'render_html_nav' and 'MyBlog::lastNews' in priority and continue next
    $response = $next($request, $response, 'render_html_nav', 'MyBlog::lastNews')

    // Write response footer
    ...

    return $response;
}
```

Default resolver:

```php
<?php

// Middleware = class name
$app->add('@Middleware');

// Controller = class name
// action = method name
$app->add('@Controller:action');
```

Write a middleware:

```php
<?php

class MyMiddleware
{
    public function __invoke($request, $response, $next)
    {
        return $response;
    }
}
```

Write a controller:

```php
<?php

class MyController
{
    public function myActionA()
    {
        return new Response;
    }

    public function myActionB($request)
    {
        return new Response;
    }

    public function myActionC($request, $response, $next)
    {
        return new Response;
    }
}
```

## Example

Bootstrap file:

```php
<?php

$app = new Stretcher();

$app->add(IsHttpsMiddleware::class);
$app->add('@AppKernelMiddleware'); // middleware callable on default resolver
$app->add([$router, 'run']);
$app->add('display_error_404');

$response = $app->dispatch($request, $response);

echo $response->getBody();
```

Routing file:

```php
<?php

$router = new Router();

$router->map('/', '@HomeMiddleware');
$router->map('/blog', '@BlogMiddleware:showNews');
$router->map('/blog/edit', '@BlogMiddleware:addNews');
```

BlogMiddleware file:

```php
<?php

class BlogMiddleware
{
    public function showNews($request)
    {
        ...

        return $response;
    }

    public function addNews($request, $response, $next)
    {
        return $next($request, $response, '@BlogEdit:hasAccess', '@BlogEdit:adminNav', '@BlogEdit:adminEditor');
    }
}
```
