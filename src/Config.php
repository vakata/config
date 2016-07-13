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
        return $this->storage->del($key, $separator);
    }
    /**
     * Parse an .env file and import into config object
     * @method fromFile
     * @param  string $location  the location of the file to parse
     * @return self
     */
    public function fromFile($location)
    {
        foreach (file($location, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $k => $v) {
            $v = trim($v, " \r\n\t");
            if ($v[0] === '#') {
                continue;
            }
            $v = explode('=', $v, 2);
            if (count($v) !== 2) {
                continue;
            }
            $v[0] = trim($v[0], " \r\n\t");
            if (!preg_match('(^[a-zA-Z0-9_]+$)', $v[0])) {
                continue;
            }
            $v[1] = trim($v[1], " \r\n\t");
            $quoted = false;
            if ($v[1][0] === '"' && $v[1][strlen($v[1]) - 1] === '"') {
                $quoted = true;
                $v[1] = trim($v[1], '"');
                $v[1] = preg_replace_callback(
                    '(\${([a-zA-Z0-9_]+)})',
                    function ($matches) use ($location) {
                        if ($matches[1] === '__DIR__') {
                            return dirname(realpath($location));
                        }
                        return $this->get($matches[1], $matches[0]);
                    },
                    $v[1]
                );
            }

            if (!$quoted) {
                if (preg_match('(^\d+$)', $v[1])) {
                    $v[1] = (int)$v[1];
                } else if (is_numeric($v[1])) {
                    $v[1] = (float)$v[1];
                } else if ($v[1] === 'true') {
                    $v[1] = true;
                } else if ($v[1] === 'false') {
                    $v[1] = false;
                }
            }

            $this->set($v[0], $v[1]);
        }
        return $this;
    }
    /**
     * Parse all .env files in a directory and import into config object
     * @method fromDir
     * @param  string $location  the location of the dir to scan & parse
     * @param  bool   $deep      should sub directories be parsed as well, defaults to `false`
     * @return  self
     */
    public function fromDir($location, $deep = false)
    {
        if (is_dir($location)) {
            foreach (scandir($location) as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                if (is_file($location . DIRECTORY_SEPARATOR . $item) && strtolower(substr($item, -4)) === '.env') {
                    $this->fromFile($location . DIRECTORY_SEPARATOR . $item);
                }
                if ($deep && is_dir($location . DIRECTORY_SEPARATOR . $item)) {
                    $this->fromDir($location . DIRECTORY_SEPARATOR . $item, $deep);
                }
            }
        }
        return $this;
    }
    /**
     * Export all config values into $_SERVER and $_ENV
     * @method export
     * @param  bool $overwrite  should existing values be overwritten, defaults to `false`
     */
    public function export($overwrite = false)
    {
        foreach ($this->data as $k => $v) {
            if (!$overwrite &&
                (
                    defined($k) ||
                    getenv($k) !== false ||
                    (isset($_ENV) && isset($_ENV[$k])) ||
                    (isset($_SERVER) && isset($_SERVER[$k]))
                )
            ) {
                continue;
            }
            
            putenv((string)($k . "=" . $v));
            $_ENV[$k] = (string)$v;
            $_SERVER[$k] = (string)$v;
            if (!defined($k)) {
                define($k, $v);
            }
        }
    }
}
