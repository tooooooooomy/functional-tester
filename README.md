# FunctionalTester library

[![Build Status](https://travis-ci.org/kazu9su/functional-tester.svg?branch=master)](https://travis-ci.org/kazu9su/functional-tester)

This is a library for functional test of PHP legacy products.  
If you have difficulty with testing such products, this library can help you.

## Usage

First, create a new "FunctionalTester" instance.

You can set session, document root and include paths used in your target product.

If you call `request` method like http request, you can get a parsed response instance of `Guzzle\Http\Message\Response`

```php
use FunctionalTester/FunctionalTester;

class IndexTest extends PHPUnit_Framework_TestCase
{
    public function testIndex
    {
        $tester = new FunctionalTester();
        
        //set session used in your target product.
        $tester->setSession(['id' => 'hogehoge']);
        
        //set your document root. you can also set as constructer 1st argument.
        $tester->setDocumentRoot('/path/to/src');
        
        //set include path used in your target product. you can also set as constructer 2st argument.
        $tester->setIncludePath('.:/usr/bin/php');

        //you can also add
        $tester->addIncludePath(':/path/to/src');
        
        //you can call get or post method like http request
        $response = $tester->get('index.php', ['username' => 'hogehoge']);
        
        //you can assert request results like this.
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getBody());
    }
}
```
## Installation

You can install this library through  [Composer](https://getcomposer.org/) .

```bash
$ composer require kazu9su/functional-tester
```

This will install FunctionalTester and all required dependencies.

## Tests

To execute the test suite, you'll need phpunit.

```bash
$ phpunit
```

## License

The FunctionalTester is licensed under the MIT license. See [License File](LICENSE.md) for more information.
