<?php

namespace PublicFunction\Cf7Extras\Core;
use \ArrayAccess;

class Container implements ArrayAccess
{
    /**
     * Main storage
     * @var array
     */
    protected $items = [];

    /**
     * Instantiated
     * @var array
     */
    protected $cache = [];

    /**
     * Container constructor.
     * @param array $items
     */
    public function __construct( array $items = [] )
    {
        foreach ( $items as $key => $item )
            $this->offsetSet( $key, $item );
    }

    /**
     * Add an item to the container
     * @param string $offset
     * @param mixed $value
     */
    public function offsetSet( $offset, $value )
    {
        $this->items[$offset] = $value;
    }

    /**
     * @param string $offset
     * @return mixed|null
     */
    public function offsetGet( $offset )
    {
        if ( !$this->offsetExists( $offset ) )
            return null;

        if ( isset( $this->cache[$offset] ) && $this->cache[$offset] !== null)
            return $this->cache[$offset];

        $item = DotNotation::parse($offset, $this->items);

        if( $item instanceof \Closure )
            $item = call_user_func_array( $this->items[$offset], [ &$this ] );

        $this->cache[$offset] = $item;

        return $item;
    }

    /**
     * Removes an item from the container
     * @param string $offset
     */
    public function offsetUnset( $offset )
    {
        if ( $this->has( $offset ) )
            unset( $this->items[$offset] );
    }

    /**
     * Check to see if an item exists
     * @param string $offset
     * @return bool
     */
    public function offsetExists( $offset )
    {
        return DotNotation::exists( $offset, $this->items );
    }

    /**
     * Wrapper for offsetGet() Magic method to retrieve an item as a property
     * @param string $property
     * @return mixed|null
     */
    public function __get( $property )
    {
        return $this->offsetGet( $property );
    }

    ////////////////////////////////////////////////////////////////////////
    //  API Wrappers
    ////////////////////////////////////////////////////////////////////////

	/**
	 * API for __construct functionality
	 * @param array $items
	 * @return $this
	 */
	public function bulkSet(array $items = [])
	{
		foreach ( $items as $key => $item )
			$this->offsetSet( $key, $item );
		return $this;
	}

    /**
     * Wrapper for offsetSet
     * @param $key
     * @param $item
     * @return $this
     */
    public function set($key, $item)
    {
        $this->offsetSet($key, $item);
        return $this;
    }

    /**
     * Wrapper for offsetExists, Check to see if an item exists
     * @param string $offset
     * @return bool
     */
    public function has( $offset )
    {
        return $this->offsetExists( $offset );
    }

    /**
     * Wrapper for offsetGet
     * @param $item
     * @return mixed|null
     */
    public function get( $item )
    {
        return $this->offsetGet( $item );
    }

    /**
     * Returns runnable objects
     * @return array|RunableAbstract[]
     */
    public function getRunables()
    {
        $runables = [];

        foreach($this->items as $key => $item)
            $this->offsetGet($key);

        foreach($this->cache as $offset => $item)
            if(is_object($item) && method_exists($item, 'run'))
                $runables[$offset] = $item;

        return $runables;
    }
}