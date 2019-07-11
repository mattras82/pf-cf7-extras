<?php

namespace PublicFunction\Cf7Extras\Core;

class Loader
{
    /**
     * Array of filters to be registered
     * @var array
     */
    protected $filters = [];

    /**
     * Array of actions to be registered
     * @var array
     */
    protected $actions = [];

    /**
     * Array of shortcodes to be registered
     * @var array
     */
    protected $shortcodes = [];

    /**
     * Adds an action
     * @param string   $hook          The name of the hook we want to register to
     * @param callable $callback      The callback function, either a closure, function string or object
     * @param integer  $priority      Priority of the action
     * @param integer  $accepted_args The number of accepted arguments for the callable
     * @return $this
     */
    public function addAction($hook, $callback, $priority = 10, $accepted_args = 1)
    {
        $this->add($this->actions, $hook, $callback, $priority, $accepted_args);
        return $this;
    }

    /**
     * Adds a filter
     * @param string   $hook          The name of the hook we want to register to
     * @param callable $callback      The callback function, either a closure, function string or object
     * @param integer  $priority      Priority of the action
     * @param integer  $accepted_args The number of accepted arguments for the callable
     * @return $this
     */
    public function addFilter($hook, $callback, $priority = 10, $accepted_args = 1)
    {
        $this->add($this->filters, $hook, $callback, $priority, $accepted_args);
        return $this;
    }

    /**
     * Adds a shortcode
     * @param string   $hook          The name of the hook we want to register to
     * @param callable $callback      The callback function, either a closure, function string or object
     * @return $this
     */
    public function addShortcode($hook, $callback)
    {
        $this->add($this->shortcodes, $hook, $callback, null, null);
        return $this;
    }

    /**
     * Takes the filters or arrays by reference and adds an item to it
     * @param array    $hooks          the name of the hook we want to register to
     * @param string    $hook          the name of the hook we want to register to
     * @param callable  $callback      The callback function, either a closure, function string or object
     * @param integer   $priority      Priority of the action
     * @param integer   $accepted_args The number of accepted arguments for the callable
     */
    private function add(&$hooks, $hook, $callback, $priority, $accepted_args)
    {
        $hooks[] = [
            'hook' => $hook,
            'callback' => $callback,
            'priority' => $priority,
            'accepted_args' => $accepted_args
        ];
    }

    /**
     * Registers all filters and actions
     * @return void
     */
    public function run()
    {
        if(count($this->filters) > 0)
            foreach ( $this->filters as $hook )
                add_filter( $hook['hook'], $hook['callback'], $hook['priority'], $hook['accepted_args'] );

        if(count($this->actions) > 0 )
            foreach ( $this->actions as $hook )
                add_action( $hook['hook'], $hook['callback'], $hook['priority'], $hook['accepted_args'] );

        if(count($this->shortcodes) > 0)
            foreach ( $this->shortcodes as $hook )
                add_shortcode( $hook['hook'], $hook['callback'] );
    }
}
