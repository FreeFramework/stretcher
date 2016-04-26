<?php

class StretcherTest extends PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $stretcher = new Stretcher();
    }

    public function testConstructorWithResolver()
    {
        $resolver = function($callable)
        {
            return $callable;
        };

        $stretcher = new Stretcher();

        $this->assertEquals($resolver, $stretcher->getResolver());
    }

    public function testSetResolver()
    {
        $resolver = 'resolver';

        $stretcher = new Stretcher();
        $stretcher->setResolver($resolver);

        $this->assertEquals($resolver, $stretcher->getResolver());
    }

    public function testAddNotCallable()
    {
        $this->expectException(UnexpectedValueException::class);
        
        $request = \Zend\Diactoros\ServerRequestFactory::fromGlobals();
        $response = new \Zend\Diactoros\Response();

        $stretcher = new Stretcher();

        $stretcher->add(666);

        $result = $stretcher($request, $response);
    }

    public function testAddAnonymousFunction()
    {
        $request = \Zend\Diactoros\ServerRequestFactory::fromGlobals();
        $response = new \Zend\Diactoros\Response();

        $stretcher = new Stretcher();

        $stretcher->add(function ()
        {
            return 'AnonymousFunction';
        });

        $result = $stretcher($request, $response);

        $this->assertEquals('AnonymousFunction', $result);
    }

    public function testAddFunction()
    {
        $request = \Zend\Diactoros\ServerRequestFactory::fromGlobals();
        $response = new \Zend\Diactoros\Response();

        $stretcher = new Stretcher();

        $stretcher->add('sample_function');

        $result = $stretcher($request, $response);

        $this->assertEquals('SampleFunction', $result);
    }

    public function testAddFunctionStatic()
    {
        $request = \Zend\Diactoros\ServerRequestFactory::fromGlobals();
        $response = new \Zend\Diactoros\Response();

        $stretcher = new Stretcher();

        $stretcher->add('SampleStaticFunction::foo');

        $result = $stretcher($request, $response);

        $this->assertEquals('SampleStaticFunction', $result);
    }

    public function testAddObjectMethod()
    {
        $request = \Zend\Diactoros\ServerRequestFactory::fromGlobals();
        $response = new \Zend\Diactoros\Response();

        $stretcher = new Stretcher();

        $stretcher->add([new SampleController(), 'method']);

        $result = $stretcher($request, $response);

        $this->assertEquals('ObjectMethod', $result);
    }

    public function testAddResolverMiddleware()
    {
        $request = \Zend\Diactoros\ServerRequestFactory::fromGlobals();
        $response = new \Zend\Diactoros\Response();

        $stretcher = new Stretcher();

        $stretcher->add('@SampleMiddleware');

        $result = $stretcher($request, $response);

        $this->assertEquals('SampleMiddleware', $result);
    }

    public function testAddResolverController()
    {
        $request = \Zend\Diactoros\ServerRequestFactory::fromGlobals();
        $response = new \Zend\Diactoros\Response();

        $stretcher = new Stretcher();

        $stretcher->add('@SampleController.action');

        $result = $stretcher($request, $response);

        $this->assertEquals('SampleController', $result);
    }

    public function testDispatcher()
    {
        $request = \Zend\Diactoros\ServerRequestFactory::fromGlobals();
        $response = new \Zend\Diactoros\Response();

        $stretcher = new Stretcher();

        $stretcher->add('sample_function');

        $result = $stretcher->dispatch($request, $response);

        $this->assertEquals('SampleFunction', $result);
    }

    public function testNext()
    {
        $request = \Zend\Diactoros\ServerRequestFactory::fromGlobals();
        $response = new \Zend\Diactoros\Response();

        $stretcher = new Stretcher();

        $stretcher->add('fm1');
        $stretcher->add('fm2');

        $result = $stretcher->dispatch($request, $response);

        $this->assertEquals('1221', $result);
    }

    public function testNextStretch()
    {
        $request = \Zend\Diactoros\ServerRequestFactory::fromGlobals();
        $response = new \Zend\Diactoros\Response();

        $stretcher = new Stretcher();

        $stretcher->add('fm1');
        $stretcher->add('fm0');
        $stretcher->add('fm1');

        $result = $stretcher->dispatch($request, $response);

        $this->assertEquals('1012112101', $result);
    }

    public function testInvoke()
    {
        $request = \Zend\Diactoros\ServerRequestFactory::fromGlobals();
        $response = new \Zend\Diactoros\Response();

        $stretcher = new Stretcher();

        $stretcher->add('fm1');

        $result = $stretcher($request, $response, 'fm2');

        $this->assertEquals('2112', $result);
    }

    public function testRequestEquals()
    {
        $request = \Zend\Diactoros\ServerRequestFactory::fromGlobals();
        $response = new \Zend\Diactoros\Response();

        $stretcher = new Stretcher();

        $stretcher->add(function ($request_p, $response_p) use ($request, $response)
        {
            $this->assertEquals($request, $request_p);
        });

        $result = $stretcher->dispatch($request, $response);
    }

    public function testRequestNotEquals()
    {
        $request = \Zend\Diactoros\ServerRequestFactory::fromGlobals();
        $response = new \Zend\Diactoros\Response();

        $stretcher = new Stretcher();

        $stretcher->add(function ($request_p, $response_p) use ($request, $response)
        {
            $this->assertNotEquals($request, $request_p);
        });

        $result = $stretcher->dispatch($request->withMethod('POST'), $response);
    }

    public function testResponseEquals()
    {
        $request = \Zend\Diactoros\ServerRequestFactory::fromGlobals();
        $response = new \Zend\Diactoros\Response();

        $stretcher = new Stretcher();

        $stretcher->add(function ($request_p, $response_p) use ($request, $response)
        {
            $this->assertEquals($response, $response_p);
        });

        $result = $stretcher->dispatch($request, $response);
    }

    public function testResponseNotEquals()
    {
        $request = \Zend\Diactoros\ServerRequestFactory::fromGlobals();
        $response = new \Zend\Diactoros\Response();

        $stretcher = new Stretcher();

        $stretcher->add(function ($request_p, $response_p) use ($request, $response)
        {
            $this->assertNotEquals($response, $response_p);
        });

        $result = $stretcher->dispatch($request, $response->withStatus(500));
    }
}

// Resolver
function resolver($callable)
{
    return $callable;
}

// Callable function
function sample_function()
{
    return 'SampleFunction';
}

// Callable static function
class SampleStaticFunction
{
    public static function foo()
    {
        return 'SampleStaticFunction';
    }
}

// Callable middleware
class SampleMiddleware
{
    protected $result;

    public function __construct()
    {
        $this->result = 'SampleMiddleware';
    }

    public function __invoke()
    {
        return $this->result;
    }
}

// Callable controller
class SampleController
{
    protected $result;

    public function __construct()
    {
        $this->result = 'SampleController';
    }

    public function action()
    {
        return $this->result;
    }

    public function method()
    {
        return 'ObjectMethod';
    }
}

// Callable next
function fm1($request, $response, $next)
{
    $result = '1';
    $result.= (($tmp = $next($request, $response) AND is_string($tmp)) ? $tmp : '');
    $result.= '1';
    return $result;
}
function fm2($request, $response, $next)
{
    $result = '2';
    $result.= (($tmp = $next($request, $response) AND is_string($tmp)) ? $tmp : '');
    $result.= '2';
    return $result;
}
function fm0($request, $response, $next)
{
    $result = '0';
    $result.= (($tmp = $next($request, $response, 'fm1', 'fm2') AND is_string($tmp)) ? $tmp : '');
    $result.= '0';
    return $result;
}
