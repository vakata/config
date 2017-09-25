<?php

namespace vakata\config;

use vakata\kvstore\StorageInterface;
use vakata\kvstore\Storage;
use Symfony\Component\Yaml\Yaml;

class Config implements StorageInterface
{
    protected $data;
    protected $storage;

    /**
     * creates a config object
     * @param  array $defaults    initial values to populate
     */
    public function __construct(array $defaults = []) {
        $this->data = $defaults;
        $this->storage = new Storage($this->data);
    }
    /**
     * Get a key from the config storage by using a string locator.
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
     * @param  string $key       the element to delete (can be a deeply nested element of the data array)
     * @param  string $separator the string used to separate levels of the array, defaults to "."
     * @return mixed|null        the value that was just deleted or null
     */
    public function del($key, $separator = '.')
    {
        return $this->storage->del($key, $separator);
    }
    /**
     * Parse a supported file and import into config object
     * @param  string $location  the location of the file to parse
     * @return self
     */
    public function fromFile($location)
    {
        switch (strtolower(pathinfo($location, PATHINFO_EXTENSION))) {
            case 'ini':
                return $this->fromIniFile($location);
            case 'env':
                return $this->fromEnvFile($location);
            case 'json':
                return $this->fromJsonFile($location);
            case 'yml':
            case 'yaml':
                return $this->fromYamlFile($location);
            default:
                break;
        }
    }
    protected function replaceExisting($data, $location = __DIR__) {
        if (is_array($data)) {
            return array_map(function ($v) { return $this->replaceExisting($v); }, $data);
        }
        if (is_string($data)) {
            return preg_replace_callback(
                '(\${([a-zA-Z0-9_]+)})',
                function ($matches) use ($location) {
                    if ($matches[1] === '__DIR__') {
                        return dirname(realpath($location));
                    }
                    return $this->get($matches[1], $matches[0]);
                },
                $data
            );
        }
        return $data;
    }
    /**
     * Parse an .yaml file and import into config object
     * @param  string $location  the location of the file to parse
     * @return self
     */
    public function fromYamlFile($location)
    {
        if (function_exists('yaml_parse_file')) {
            $parsed = yaml_parse_file($location);
        } else {
            $parsed = Yaml::parse(file_get_contents($location));
        }
        if (!is_array($parsed)) {
            throw new ConfigException('Incorrect format');
        }
        foreach ($parsed as $k => $v) {
            $this->set($k, $this->replaceExisting($v, $location));
        }
        return $this;
    }
    /**
     * Parse an .json file and import into config object
     * @param  string $location  the location of the file to parse
     * @return self
     */
    public function fromJsonFile($location)
    {
        $parsed = json_decode(file_get_contents($location), true);
        if (!is_array($parsed)) {
            throw new ConfigException('Incorrect format');
        }
        foreach ($parsed as $k => $v) {
            $this->set($k, $this->replaceExisting($v, $location));
        }
        return $this;
    }
    /**
     * Parse an .ini file and import into config object
     * @param  string $location  the location of the file to parse
     * @return self
     */
    public function fromIniFile($location, $sections = false)
    {
        $parsed = parse_ini_file($location, $sections, INI_SCANNER_RAW);
        if (!is_array($parsed)) {
            throw new ConfigException('Incorrect format');
        }
        foreach ($parsed as $k => $v) {
            if (preg_match('(^\d+$)', $v)) {
                $v = (int)$v;
            } else if (is_numeric($v)) {
                $v = (float)$v;
            } else if ($v === 'true') {
                $v = true;
            } else if ($v === 'false') {
                $v = false;
            } else if ($v === 'null') {
                $v = null;
            } else {
                $v = $this->replaceExisting($v, $location);
            }
            $this->set($k, $v);
        }
        return $this;
    }
    /**
     * Parse an .env file and import into config object
     * @param  string $location  the location of the file to parse
     * @return self
     */
    public function fromEnvFile($location)
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
            if (!preg_match('(^[a-zA-Z0-9_.]+$)', $v[0])) {
                continue;
            }
            $v[1] = trim($v[1], " \r\n\t");
            $quoted = false;
            if ($v[1][0] === '"' && $v[1][strlen($v[1]) - 1] === '"') {
                $quoted = true;
                $v[1] = trim($v[1], '"');
                $v[1] = $this->replaceExisting($v[1], $location);
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
                } else if ($v[1] === 'null') {
                    $v[1] = null;
                }
            }

            $this->set($v[0], $v[1]);
        }
        return $this;
    }
    /**
     * Parse all supported files in a directory and import into config object
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
                if (is_file($location . DIRECTORY_SEPARATOR . $item)) {
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
