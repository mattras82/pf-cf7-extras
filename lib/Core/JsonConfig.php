<?php

namespace PublicFunction\Cf7Extras\Core;

use PublicFunction\Cf7Extras\Plugin;

class JsonConfig implements \ArrayAccess
{
    protected $_path;
    protected $_data;
    protected $_pointer;

    public function __construct($path)
    {
        $this->_path = $path;

        if(empty($this->_path) || !file_exists($this->_path))
            Plugin::stop($this->errorMessage(), $this->errorSubtitle(), $this->errorTitle());

        $this->_data = json_decode( file_get_contents($path), true );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Access
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function offsetSet($offset, $value) : void
    {
        $this->_data[$offset] = $value;
    }

    public function offsetGet($offset) : mixed
    {
        return $this->offsetExists($offset) ? $this->_data[$offset] : null;
    }

    public function offsetUnset($offset) : void
    {
        if ( $this->offsetExists( $offset ) )
            unset( $this->_data[$offset] );
    }

    public function offsetExists( $offset ) : bool
    {
        return is_string($offset) ? isset( $this->_data[$offset] ) : false;
    }

    public function __get( $property )
    {
        return $this->offsetGet( $property );
    }

    public function __set($offest, $value)
    {
        $this->offsetSet($offest, $value);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // API
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function has($offset)
    {
        return $this->offsetExists( $offset );
    }

    public function set($offset, $value)
    {
        $this->offsetSet($offset, $value);
    }

    public function get($offset = null)
    {
        if(empty($offset))
            return $this->_data;

        return $this->offsetGet($offset);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Error Messages
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    protected function errorTitle()
    {
        return 'Missing JSON config file:';
    }

    protected function errorSubtitle()
    {
        $p = $this->_path;
        if(strpos($p, 'wwwroot') !== false)
            $p = '\\wwwroot' . explode('wwwroot', $p)[1];
        else if(strpos($p, 'wp-content') !== false)
            $p = '\\wp-content' . explode('wp-content', $p)[1];

        $p = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $p);
        return "<code style=\"color:#F44336;font-weight:normal;font-size:.8em\">{$p}<small></small></code>";
    }

    protected function errorMessage()
    {
        $class = get_called_class();
        return "The <code><strong>{$class}</strong></code> class object is trying to access this file, but it cannot be found. Please create the file and try again.";
    }
}