<?php

namespace vakata\config;

use \vakata\kvstore\StorageInterface;
use \vakata\kvstore\Storage;

class Config implements StorageInterface
{
    protected $data;
    protected $storage;

    /**
     * creates a config object
     * @method __construct
     * @param  array $defaults    initial values to populate
     */
    public function __construct(array $defaults = []) {
        $this->data = $defaults;
        $this->storage = new Storage($this->data);
    }
    /**
     * Get a key from the config storage by using a string locator.
     * @method get
     * @param  string $key       the element to get (can be a deeply nested element of the data array)
     * @param  mixed  $default   the default value to return if the key is not found in the data
     * @param  string $separator the string used to separate levels of the array, defaults to "."
     * @return mixed             the value of that element in the data array (or the default value)
     */
    public function get($key, $default = null, $separator = '.')
    {
        return $this->storage->get($key, $default, $separator);
    }
    /**
     * Set an element in the config storage to a specified value. Deep arrays will not work when exporting!
     * @method set
     * @param  string $key       the element to set (can be a deeply nested element of the data array)
     * @param  mixed  $value     the value to assign the selected element to
     * @param  string $separator the string used to separate levels of the array, defaults to "."
     * @return mixed             the stored value
     */
    public function set($key, $value, $separator = '.')
    {
        return $this->storage->set($key, $value, $separator);
    }
    /**
     * Delete an element from the storage.
     * @method set
     * @param  string $key       the element to delete (can be a deeply nested element of the data array)
     * @param  string $separator the string used to separate levels of the array, defaults to "."
     * @return mixed|null        the value that was just deleted or null
     */
    public function del($key, $separator = '.')
    {
        return $this->storage->set($key, $value, $separator);
    }
    /**
     * Parse an .env file and import into config object
     * @method fromFile
     * @param  string $location  the location of the file to parse
     */
    public function fromFile($location)
    {
        
    }
    /**
     * Parse all .env files in a directory and import into config object
     * @method fromDir
     * @param  string $location  the location of the dir to scan & parse
     * @param  bool   $deep      should sub directories be parsed as well, defaults to `false`
     */
    public function fromDir($location, $deep = false)
    {
        
    }
    /**
     * Export all config values into $_SERVER and $_ENV
     * @method export
     * @param  bool $overwrite  should existing values be overwritten, defaults to `false`
     */
    public function export($overwrite = false)
    {
        
    }
}
