# Auto upload handling with Eloquent models trait

[![Latest Version on Packagist](https://img.shields.io/packagist/v/padosoft/laravel-uploadable.svg?style=flat-square)](https://packagist.org/packages/padosoft/laravel-uploadable)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/padosoft/laravel-uploadable/master.svg?style=flat-square)](https://travis-ci.org/padosoft/laravel-uploadable)
[![Quality Score](https://img.shields.io/scrutinizer/g/padosoft/laravel-uploadable.svg?style=flat-square)](https://scrutinizer-ci.com/g/padosoft/laravel-uploadable)
[![Total Downloads](https://img.shields.io/packagist/dt/padosoft/laravel-uploadable.svg?style=flat-square)](https://packagist.org/packages/padosoft/laravel-uploadable)
[![SensioLabsInsight](https://img.shields.io/sensiolabs/i/4570b2c7-71c6-4b11-9214-da078fb51a98.svg?style=flat-square)](https://insight.sensiolabs.com/projects/4570b2c7-71c6-4b11-9214-da078fb51a98)

This package provides a trait that will automatic handlind upload when saving/updating/deleting any Eloquent model with upload form request.

##Requires
  
- php: >=7.0.0
- illuminate/database: ^5.0
- illuminate/support: ^5.0
- illuminate/http: ^5.0
- padosoft/laravel-request: ^1.0
- padosoft/io: 1.*
  
## Installation

You can install the package via composer:
``` bash
$ composer require padosoft/laravel-uploadable
```

## Usage

Your Eloquent models should use the `Padosoft\Uploadable\Uploadable` trait and the `Padosoft\Uploadable\UploadOptions` class.

You can define `getUploadOptions()`  method  in your model. 

Here's an example of how to implement the trait with implementation of getUploadOptions():

```php
<?php

namespace App;

use Padosoft\Uploadable\Uploadable;
use Padosoft\Uploadable\UploadOptions;
use Illuminate\Database\Eloquent\Model;

class YourEloquentModel extends Model
{
    use Uploadable;
    
 /**
     * Retrive a specifice UploadOptions for this model, or return default UploadOptions
     * @return UploadOptions
     */
    public function getUploadOptions() : UploadOptions
    {
        if($this->uploadOptions){
            return $this->uploadOptions;
        }

        $this->uploadOptions = UploadOptions::create()->getUploadOptionsDefault()
            ->setUploadBasePath(public_path('upload/' . $this->getTable()))
            ->setUploadsAttributes(['image', 'image_mobile']);

        return $this->uploadOptions;
    }
}
```

You can specified uploads attributes with:

```php
public function getUploadOptions() : UploadOptions
{
    return UploadOptions::create()
        ->setUploadsAttributes(['image', 'image_mobile']);
}
```
You can set the base upload path for your model:

```php
public function getUploadOptions() : UploadOptions
{
    return UploadOptions::create()
        ->setUploadBasePath(public_path('upload/news'));
}
```
You can set different path for each (or for some) upload attributes in your model:
```php
public function getUploadOptions() : UploadOptions
{
    return UploadOptions::create()
        ->setUploadPaths(['image_mobile' => '/mobile' ]);
}
```

It support validation to accept files by specified list of Mime Type:
```php
public function getUploadOptions() : UploadOptions
{
    return UploadOptions::create()
        ->setMimeType([
          'image/gif',
          'image/jpeg',
          'image/png',
            ]);
}
```

By default every uploaded file will rename with `'original_name_'.$model->id.'.original_ext'` 
but you can redefine a custom function for renaming file:

```php
/**
 * Generate a new file name for uploaded file.
 * Return empty string if $uploadedFile is null.
 * @param \Illuminate\Http\UploadedFile $uploadedFile
 * @param String $uploadField
 * @return string
 */
public function generateNewUploadFileName(\Illuminate\Http\UploadedFile $uploadedFile, string $uploadField) : string 
{
    if($uploadField=='image'){
        return 'pippo.jpg';
    }else{
        return 'pippo_mobile.jpg';
    }
}
```

For all options see UploadOptions class.

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email instead of using the issue tracker.

## Credits
- [Lorenzo Padovani](https://github.com/lopadova)
- [All Contributors](../../contributors)

## About Padosoft
Padosoft (https://www.padosoft.com) is a software house based in Florence, Italy. Specialized in E-commerce and web sites.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
