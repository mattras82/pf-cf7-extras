<?php

namespace PublicFunction\Cf7Extras\Core;

trait SingletonTrait
{
    /**
     * Returns a static instance of the class inheriting this trait
     * @return null|static
     */
    public static function getInstance()
    {
        static $instance = null;
        if (null === $instance)
            $instance = new static();

        return $instance;
    }

    /**
     * Protected constructor to prevent creating a new instance of the
     * *SingletonInterface* via the `new` operator from outside of this class.
     */
    protected function __construct() { }

    /**
     * Private clone method to prevent cloning of the instance of the
     * *SingletonInterface* instance.
     *
     * @return void
     */
    private function __clone() { }

    /**
     * Pre-unserialize method to prevent unserializing of the *SingletonInterface*
     * instance.
     *
     * @return void
     */
    public function __wakeup() { }

    /**
     * Pre-serialize method to prevent serializing of the *SingletonInterface*
     *
     * @return void
     */
    public function __sleep() { }
}