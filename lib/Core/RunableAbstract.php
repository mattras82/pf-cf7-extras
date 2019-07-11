<?php

namespace PublicFunction\Cf7Extras\Core;

abstract class RunableAbstract
{
    /**
     * @var Container
     */
    protected $container;

    public function __construct(Container &$c)
    {
        $this->container = $c;
    }

    /**
     * @return Loader
     */
    public function loader()
    {
        return $this->container->get('loader');
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function get($name = '')
    {
        return $this->container->get($name);
    }

    abstract public function run();
}