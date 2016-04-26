<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class Stretcher
{
    /** @var callable */
    protected $resolver;

    /** @var \SplQueue */
    protected $queue;

    /**
     * Stretcher constructor.
     * @param callable|null $resolver
     */
    public function __construct(callable $resolver = null)
    {
        // Queue
        $this->queue = new \SplQueue();
        // Default resolver
        $this->resolver = $resolver ? $resolver : function ($callable)
        {
            // If custom callable
            if (is_string($callable) AND
                substr($callable, 0, 1) == '@' AND
                $str = substr($callable, 1) AND
                $cc = explode(':', $str))
                // @Middleware
                if (count($cc) == 1)
                    return [new $cc[0], '__invoke'];
                // @Controller:action
                elseif (count($cc) == 2)
                    return [new $cc[0], $cc[1]];
            // Else change nothing
            return $callable;
        };
    }

    /**
     * @return callable|null
     */
    public function getResolver()
    {
        return $this->resolver;
    }

    /**
     * @param callable|null $resolver
     * @return $this
     */
    public function setResolver(callable $resolver = null)
    {
        $this->resolver = $resolver;
        return $this;
    }

    /**
     * @param callable $callable
     * @return callable
     */
    protected function resolve($callable) : callable
    {
        return ($resolver = $this->resolver) ? $resolver($callable) : $callable;
    }

    /**
     * @param callable $callable
     */
    public function add($callable)
    {
        $this->queue->enqueue($callable);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param callable[] ...$callables unshift callables (top priority)
     * @return Response
     */
    public function __invoke(Request $request, Response $response, ...$callables)
    {
        foreach ($callables as $callable)
            $this->queue->unshift($callable);

        if ($this->queue->isEmpty())
            return $response;

        $callable = $this->resolve($this->queue->dequeue());

        if (is_callable($callable))
            return call_user_func($callable, $request, $response, $this);
        else
            throw new \UnexpectedValueException();
    }
}
