# Constant Contact PHP API Library

Version 0.0.1 by Evan Coury

[![Build Status](https://travis-ci.org/EvanDotPro/ctct_php_library.png)](https://travis-ci.org/EvanDotPro/ctct_php_library)

## Introduction

This is a complete re-write of the Constant Contact PHP API library.

## Requirements

0. PHP 5.1.3+ w/ cURL
1. A valid Constant Contact account. You can register for a free trial with no credit card at [http://www.constantcontact.com/](http://www.constantcontact.com/).
2. A valid API Key for your Constant Contact account, obtained from [http://developer.constantcontact.com/](http://developer.constantcontact.com/).

## Improvements over the old PHP library

* 100% PHPUnit test coverage
* Proper PSR-0 compliance
* Code style checking
* Travis-CI integration

## Legacy support

If you are using an older version of this library in your application, the old code is still available under the `./legacy/` directory.

## Usage

### Authentication (OAuth2)

Authentication with Constant Contact is supported via OAuth2.

If you haven't already, you can register your client application for OAuth / API access [here](http://community.constantcontact.com/t5/Documentation/API-Keys/ba-p/25015).

```php
<?php
require_once 'ctct_php_library/init_autoloader.php';

$consumerKey    = 'YOURCONSUMERKEY';           // Referred to by Constant Contact as "client_id" or "API key"
$consumerSecret = 'YOURCONSUMERSECRET';        // Referred to by Constant Contact as "client secret"
$returnUrl      = 'https://yoururl/returnurl'; // Must match the URL you registered with Constant Contact

$client = new Ctct_ApiClient($consumerKey, $consumerSecret);

if (empty($_COOKIE['ctctAccessToken'])) {
    if (empty($_GET['code'] || empty($_GET['username'])) {
        $redirectUrl = $client->getAuthorizeUrl($returnUrl);
        header('Location: ' . $redirectUrl);
        exit();
    }

    try {
       $accessToken = $client->getAccessToken($_GET['code'], $returnUrl);
    } catch (Ctct_Exception $e) {
        echo 'Error: ' . $e->getMessage();
        exit();
    }

    setcookie('ctctAccessToken', $accessToken);
    setcookie('ctctUsername', $_GET['username']);
}
?>

You are now authenticated with Constant Contact as <?php echo $_COOKIE['ctctUsername']; ?>!
```
