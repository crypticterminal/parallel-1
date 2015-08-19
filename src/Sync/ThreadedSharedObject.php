<?php
namespace Icicle\Concurrent\Sync;

/**
 * A thread-safe container that shares a value between multiple threads.
 */
class ThreadedSharedObject implements MutexInterface
{
    private $mutex;
    private $threaded;

    /**
     * Creates a new shared object container.
     *
     * @param mixed $value The value to store in the container.
     */
    public function __construct($value)
    {
        $this->threaded = new \Threaded();
        $this->threaded->value = $value;

        $this->mutex = new ThreadedMutex();
    }

    /**
     * Gets the value from the shared container.
     *
     * @return mixed The shared value.
     */
    public function get()
    {
        return $this->threaded->value;
    }

    /**
     * Sets the value in the shared container.
     *
     * @param mixed $value The value to set to.
     */
    public function set($value)
    {
        $this->threaded->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function acquire()
    {
        return $this->mutex->acquire();
    }

    /**
     * Asynchronously invokes a callable while maintaining an exclusive lock on
     * the container.
     *
     * @param callable<mixed> $function The function to invoke. The value in the
     *                                  container will be passed as the first
     *                                  argument.
     *
     * @return \Generator
     */
    public function synchronized(callable $function)
    {
        $lock = (yield $this->mutex->acquire());
        $instance = $this->get();

        try {
            yield $function($instance);

            // If the value is an object, update the stored instance in case the
            // object was modified.
            if (is_object($instance)) {
                $this->set($instance);
            }
        } finally {
            $lock->release();
        }
    }
}
