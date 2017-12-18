# vakata\config\Config  



## Implements:
vakata\kvstore\StorageInterface



## Methods

| Name | Description |
|------|-------------|
|[__construct](#config__construct)|creates a config object|
|[del](#configdel)|Delete an element from the storage.|
|[export](#configexport)|Export all config values into $_SERVER and $_ENV|
|[fromDir](#configfromdir)|Parse all supported files in a directory and import into config object|
|[fromEnvFile](#configfromenvfile)|Parse an .env file and import into config object|
|[fromFile](#configfromfile)|Parse a supported file and import into config object|
|[fromIniFile](#configfrominifile)|Parse an .ini file and import into config object|
|[fromJsonFile](#configfromjsonfile)|Parse a .json file and import into config object|
|[get](#configget)|Get a key from the config storage by using a string locator.|
|[set](#configset)|Set an element in the config storage to a specified value. Deep arrays will not work when exporting!|
|[toArray](#configtoarray)|Get all config items as an array|




### Config::__construct  

**Description**

```php
public __construct (array $defaults)
```

creates a config object 

 

**Parameters**

* `(array) $defaults`
: initial values to populate  

**Return Values**




### Config::del  

**Description**

```php
public del (string $key, string $separator)
```

Delete an element from the storage. 

 

**Parameters**

* `(string) $key`
: the element to delete (can be a deeply nested element of the data array)  
* `(string) $separator`
: the string used to separate levels of the array, defaults to "."  

**Return Values**

`mixed|null`

> the value that was just deleted or null  




### Config::export  

**Description**

```php
public export (bool $overwrite)
```

Export all config values into $_SERVER and $_ENV 

 

**Parameters**

* `(bool) $overwrite`
: should existing values be overwritten, defaults to `false`  

**Return Values**




### Config::fromDir  

**Description**

```php
public fromDir (string $location, bool $deep)
```

Parse all supported files in a directory and import into config object 

 

**Parameters**

* `(string) $location`
: the location of the dir to scan & parse  
* `(bool) $deep`
: should sub directories be parsed as well, defaults to `false`  

**Return Values**

`self`





### Config::fromEnvFile  

**Description**

```php
public fromEnvFile (string $location)
```

Parse an .env file and import into config object 

 

**Parameters**

* `(string) $location`
: the location of the file to parse  

**Return Values**

`self`





### Config::fromFile  

**Description**

```php
public fromFile (string $location)
```

Parse a supported file and import into config object 

 

**Parameters**

* `(string) $location`
: the location of the file to parse  

**Return Values**

`self`





### Config::fromIniFile  

**Description**

```php
public fromIniFile (string $location)
```

Parse an .ini file and import into config object 

 

**Parameters**

* `(string) $location`
: the location of the file to parse  

**Return Values**

`self`





### Config::fromJsonFile  

**Description**

```php
public fromJsonFile (string $location)
```

Parse a .json file and import into config object 

 

**Parameters**

* `(string) $location`
: the location of the file to parse  

**Return Values**

`self`





### Config::get  

**Description**

```php
public get (string $key, mixed $default, string $separator)
```

Get a key from the config storage by using a string locator. 

 

**Parameters**

* `(string) $key`
: the element to get (can be a deeply nested element of the data array)  
* `(mixed) $default`
: the default value to return if the key is not found in the data  
* `(string) $separator`
: the string used to separate levels of the array, defaults to "."  

**Return Values**

`mixed`

> the value of that element in the data array (or the default value)  




### Config::set  

**Description**

```php
public set (string $key, mixed $value, string $separator)
```

Set an element in the config storage to a specified value. Deep arrays will not work when exporting! 

 

**Parameters**

* `(string) $key`
: the element to set (can be a deeply nested element of the data array)  
* `(mixed) $value`
: the value to assign the selected element to  
* `(string) $separator`
: the string used to separate levels of the array, defaults to "."  

**Return Values**

`mixed`

> the stored value  




### Config::toArray  

**Description**

```php
public toArray (void)
```

Get all config items as an array 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`array`




