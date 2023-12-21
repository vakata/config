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
    protected bool $locked = false;

    /**
     * creates a config object
     * @param  array<string,mixed> $defaults    initial values to populate
     */
    public function __construct(array $defaults = []) {
        $this->data = [];
        $this->storage = new Storage($this->data);
        $this->fromArray($defaults);
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
    public function getString(string $key, string $default = '', string $separator = ''): string
    {
        $tmp = $this->get($key, null, $separator);
        if ($tmp === null) {
            $tmp = $default;
        }
        return (string)$tmp;
    }
    public function getInt(string $key, int $default = 0, string $separator = ''): int
    {
        $tmp = $this->get($key, null, $separator);
        if ($tmp === null) {
            $tmp = $default;
        }
        return (int)$tmp;
    }
    public function getFloat(string $key, float $default = 0, string $separator = ''): float
    {
        $tmp = $this->get($key, null, $separator);
        if ($tmp === null) {
            $tmp = $default;
        }
        return (float)$tmp;
    }
    public function getBool(string $key, bool $default = false, string $separator = ''): bool
    {
        $tmp = $this->get($key, null, $separator);
        if ($tmp === null) {
            $tmp = $default;
        }
        return (bool)$tmp;
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
        if ($this->locked) {
            throw new ConfigException('Locked');
        }
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
        if ($this->locked) {
            throw new ConfigException('Locked');
        }
        return $this->storage->del($key, $separator);
    }
    public function lock(): self
    {
        $this->locked = true;
        return $this;
    }
    public function unlock(): self
    {
        $this->locked = false;
        return $this;
    }
    /**
     * @param string $location
     * @return array<string,mixed>
     */
    public static function parseFile(string $location): array
    {
        switch (strtolower(pathinfo($location, PATHINFO_EXTENSION))) {
            case 'ini':
                return static::parseIniFile($location);
            case 'env':
                return static::parseEnvFile($location);
            case 'json':
                return static::parseJsonFile($location);
            default:
                throw new ConfigException('Unsupported file format');
        }
    }
    /**
     * Parse a .json file and import into config object
     * @param  string $location  the location of the file to parse
     * @return array<string,mixed>
     */
    public static function parseJsonFile(string $location): array
    {
        $parsed = json_decode(file_get_contents($location) ?: throw new RuntimeException(), true);
        if (!is_array($parsed)) {
            throw new ConfigException('Incorrect format');
        }
        $location = dirname(realpath($location) ?: throw new RuntimeException());
        foreach ($parsed as $k => $v) {
            if (is_string($v)) {
                $parsed[$k] = str_replace('${__DIR__}', $location, $v);
            }
        }
        return static::replaceExisting($parsed, $parsed);
    }
    /**
     * Parse an .ini file and import into config object
     * @param  string $location  the location of the file to parse
     * @return array<string,mixed>
     */
    public static function parseIniFile(string $location, bool $sections = false): array
    {
        $parsed = parse_ini_file($location, $sections, INI_SCANNER_RAW);
        if (!is_array($parsed)) {
            throw new ConfigException('Incorrect format');
        }
        $location = dirname(realpath($location) ?: throw new RuntimeException());
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
                $v = str_replace('${__DIR__}', $location, $v);
            }
            $parsed[$k] = $v;
        }
        return static::replaceExisting($parsed, $parsed);
    }
    /**
     * Parse an .env file and import into config object
     * @param  string $location  the location of the file to parse
     * @return array<string,mixed>
     */
    public static function parseEnvFile(string $location): array
    {
        $parsed = [];
        $dir = dirname(realpath($location) ?: throw new RuntimeException());
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
                $v[1] = str_replace('${__DIR__}', $dir, $v[1]);
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
        return static::replaceExisting($parsed, $parsed);
    }
    /**
     * Parse a supported file and import into config object
     * @param  string $location  the location of the file to parse
     * @return self
     */
    public function fromFile(string $location): self
    {
        return $this->fromArray(static::parseFile($location));
    }
    protected static function replaceExisting(mixed $data, array $current = []): mixed
    {
        if (is_array($data)) {
            return array_map(function ($v) use ($current) { return static::replaceExisting($v, $current); }, $data);
        }
        if (is_string($data)) {
            return preg_replace_callback(
                '(\${([a-zA-Z0-9_]+)})',
                function ($matches) use ($current) {
                    return $current[$matches[1]] ?? $matches[0];
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
        return $this->fromArray($this->parseJsonFile($location));
    }
    /**
     * Parse an .ini file and import into config object
     * @param  string $location  the location of the file to parse
     * @return self
     */
    public function fromIniFile(string $location, bool $sections = false): self
    {
        return $this->fromArray($this->parseIniFile($location, $sections));
    }
    /**
     * Parse an .env file and import into config object
     * @param  string $location  the location of the file to parse
     * @return self
     */
    public function fromEnvFile(string $location): self
    {
        return $this->fromArray($this->parseEnvFile($location));
    }
    /**
     * Parse all supported files in a directory and import into config object
     * @param  string $location  the location of the dir to scan & parse
     * @param  bool   $deep      should sub directories be parsed as well, defaults to `false`
     * @return  self
     */
    public function fromDir(string $location, bool $deep = false): self
    {
        if ($this->locked) {
            throw new ConfigException('Locked');
        }
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
        if ($this->locked) {
            throw new ConfigException('Locked');
        }
        foreach ($data as $k => $v) {
            if (is_string($v)) {
                $v = static::replaceExisting($v, $this->data);
            }
            $this->set($k, $v, '');
        }
        return $this;
    }
    public function fromEnv(bool $onlyExisting = true): self
    {
        if ($this->locked) {
            throw new ConfigException('Locked');
        }
        foreach ($_ENV as $k => $v) {
            if ($onlyExisting && !isset($this->data[$k])) {
                continue;
            }
            if (is_string($v)) {
                $v = static::replaceExisting($v, $this->data);
            }
            $this->set($k, $v, '');
        }
        return $this;
    }
}
