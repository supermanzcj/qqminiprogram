# QQMiniProgram Extension Pack

[![Latest Version on Packagist](https://img.shields.io/packagist/v/superzc/qqminiprogram.svg?style=flat-square)](https://packagist.org/packages/superzc/qqminiprogram)
[![Release Version](https://img.shields.io/badge/release-1.0.0-red.svg)](https://github.com/supermanzcj/qqminiprogram/releases)

This package provides additional features to the Laravel framework.


## Installation

You can install the package via composer:

```bash
composer require superzc/qqminiprogram
```

## Usage

调用类方法
```php
use Superzc\QQMiniprogram\QQMiniprogram;
use Superzc\QQMiniprogram\Exceptions\DefaultException as QQMPDefaultException;

try {
    $qqminiprogram = new QQMiniprogram();

    // 如果需要登录调用凭据，先获取access_token（有效期2小时，建议缓存，避免重复刷新），再设置access_token
    $access_token = $qqminiprogram->getAccessToken(); 
    $qqminiprogram->setAccessToken($access_token);

    $result = $qqminiprogram->doSomething();
} catch (QQMPDefaultException $e) {
    return response()->json([
        'ret' => $e->getCode(),
        'msg' => $e->getMessage(),
    ]);
}
```

使用门面
```php
use Superzc\QQMiniprogram\Facades\QQMiniprogram;

try {
    // 如果需要登录调用凭据，先获取access_token（有效期2小时，建议缓存，避免重复刷新），再设置access_token
    $access_token = QQMiniprogram::getAccessToken(); 
    QQMiniprogram::setAccessToken($access_token);

    $result = QQMiniprogram::doSomething();
} catch (QQMPDefaultException $e) {
    return response()->json([
        'ret' => $e->getCode(),
        'msg' => $e->getMessage(),
    ]);
}
```

## Change log
暂无