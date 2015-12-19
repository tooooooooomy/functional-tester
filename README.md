# Test library

This is a library for testing PHP legacy product.
If you have difficulty with testing such products, this library can help you.

## Usage

First, create a new "FunctionalTester" instance.

You can set session or include paths used in your target product.

If you call `request` method like http request, you can get a parsed response instance of `Guzzle\Http\Message\Response`

```php
use Test/FunctionalTester;

$tester = new FunctionalTester();

//set session used in your target product.
$tester->setSession(['id' => 'hogehoge']);

//add includePath used in your target product.
$tester->addIncludePath(':/path/to/src');

//if you can get response like http request if you call get or post method
$response = $tester->get('index.php', ['username' => 'hogehoge']);

//you can assert request results like this
$this->assertEquals(200, $response->getStatusCode());
$this->assertEquals('OK', $response->getBody());

```


## Tests

To execute the test suite, you'll need phpunit.

```bash
$ phpunit
```

## License

The Slim Framework is licensed under the MIT license. See [License File](LICENSE.md) for more information.
