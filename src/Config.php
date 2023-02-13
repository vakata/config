<?php

namespace vakata\config;

use RuntimeException;
use vakata\kvstore\StorageInterface;
use vakata\kvstore\Storage;

class Config implements StorageInterface
{
    /**
     * @var array<string,mixed>
     */
    protected array $data;
    protected Storage $storage;

    /**
     * creates a config object
     * @param  array<string,mixed> $defaults    initial values to populate
     */
    public function __construct(array $defaults = []) {
        $this->data = $defaults;
        $this->storage = new Storage($this->data);
    }
    /**
     * Get a key from the config storage by using a string locator.
     * @param  string $key       the element to get (can be a deeply nested element of the data array)
     * @param  mixed  $default   the default value to return if the key is not found in the data
     * @param  string $separator the string used to separate levels of the array, defaults to ""
     * @return mixed             the value of that element in the data array (or the default value)
     */
    public function get(string $key, mixed $default = null, string $separator = ''): mixed
    {
        return $this->storage->get($key, $default, $separator);
    }
    /**
     * Set an element in the config storage to a specified value. Deep arrays will not work when exporting!
     * @param  string $key       the element to set (can be a deeply nested element of the data array)
     * @param  mixed  $value     the value to assign the selected element to
     * @param  string $separator the string used to separate levels of the array, defaults to ""
     * @return mixed             the stored value
     */
    public function set(string $key, mixed $value, string $separator = ''): mixed
    {
        return $this->storage->set($key, $value, $separator);
    }
    /**
     * Delete an element from the storage.
     * @param  string $key       the element to delete (can be a deeply nested element of the data array)
     * @param  string $separator the string used to separate levels of the array, defaults to ""
     * @return mixed|null        the value that was just deleted or null
     */
    public function del(string $key, string $separator = ''): mixed
    {
        return $this->storage->del($key, $separator);
    }
    /**
     * @param string $location
     * @return array<string,mixed>
     */
    public function parseFile(string $location): array
    {
        switch (strtolower(pathinfo($location, PATHINFO_EXTENSION))) {
            case 'ini':
                return $this->parseIniFile($location);
            case 'env':
                return $this->parseEnvFile($location);
            case 'json':
                return $this->parseJsonFile($location);
            default:
                throw new ConfigException('Unsupported file format');
        }
    }
    /**
     * Parse a .json file and import into config object
     * @param  string $location  the location of the file to parse
     * @return array<string,mixed>
     */
    public function parseJsonFile(string $location): array
    {
        $parsed = json_decode(file_get_contents($location) ?: throw new RuntimeException(), true);
        if (!is_array($parsed)) {
            throw new ConfigException('Incorrect format');
        }
        foreach ($parsed as $k => $v) {
            $parsed[$k] = $this->replaceExisting($v, $location, $parsed);
        }
        return $parsed;
    }
    /**
     * Parse an .ini file and import into config object
     * @param  string $location  the location of the file to parse
     * @return array<string,mixed>
     */
    public function parseIniFile(string $location, bool $sections = false): array
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
            $parsed[$k] = $v;
        }
        foreach ($parsed as $k => $v) {
            $parsed[$k] = $this->replaceExisting($v, $location, $parsed);
        }
        return $parsed;
    }
    /**
     * Parse an .env file and import into config object
     * @param  string $location  the location of the file to parse
     * @return array<string,mixed>
     */
    public function parseEnvFile(string $location): array
    {
        $parsed = [];
        foreach (file($location, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $k => $v) {
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
            $parsed[$v[0]] = $v[1];
        }
        foreach ($parsed as $k => $v) {
            $parsed[$k] = $this->replaceExisting($v, $location, $parsed);
        }
        return $parsed;
    }
    /**
     * Parse a supported file and import into config object
     * @param  string $location  the location of the file to parse
     * @return self
     */
    public function fromFile(string $location): self
    {
        $data = $this->parseFile($location);
        return $this->fromArray($data);
    }
    protected function replaceExisting(mixed $data, string $location = __DIR__, array $current = []): mixed
    {
        if (is_array($data)) {
            return array_map(function ($v) { return $this->replaceExisting($v); }, $data);
        }
        if (is_string($data)) {
            return preg_replace_callback(
                '(\${([a-zA-Z0-9_]+)})',
                function ($matches) use ($location, $current) {
                    if ($matches[1] === '__DIR__') {
                        return dirname(realpath($location) ?: throw new RuntimeException());
                    }
                    return $current[$matches[1]] ?? $this->get($matches[1], null) ?? $matches[0];
                },
                $data
            );
        }
        return $data;
    }
    /**
     * Parse a .json file and import into config object
     * @param  string $location  the location of the file to parse
     * @return self
     */
    public function fromJsonFile(string $location): self
    {
        $data = $this->parseJsonFile($location);
        return $this->fromArray($data);
    }
    /**
     * Parse an .ini file and import into config object
     * @param  string $location  the location of the file to parse
     * @return self
     */
    public function fromIniFile(string $location, bool $sections = false): self
    {
        $data = $this->parseIniFile($location, $sections);
        return $this->fromArray($data);
    }
    /**
     * Parse an .env file and import into config object
     * @param  string $location  the location of the file to parse
     * @return self
     */
    public function fromEnvFile(string $location): self
    {
        $data = $this->parseEnvFile($location);
        return $this->fromArray($data);
    }
    /**
     * Parse all supported files in a directory and import into config object
     * @param  string $location  the location of the dir to scan & parse
     * @param  bool   $deep      should sub directories be parsed as well, defaults to `false`
     * @return  self
     */
    public function fromDir(string $location, bool $deep = false): self
    {
        if (is_dir($location)) {
            foreach (scandir($location) ?: [] as $item) {
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
    public function export(bool $overwrite = false): void
    {
        foreach ($this->data as $k => $v) {
            if (!$overwrite &&
                (
                    defined($k) ||
                    getenv($k) !== false ||
                    isset($_ENV[$k]) ||
                    isset($_SERVER[$k])
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
    /**
     * Get all config items as an array
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }
    /**
     * @param array<string,mixed> $data
     * @return self
     */
    public function fromArray(array $data): self
    {
        foreach ($data as $k => $v) {
            $this->set($k, $v, '');
        }
        return $this;
    }
}
