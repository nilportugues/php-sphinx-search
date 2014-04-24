[![Build Status](https://travis-ci.org/nilopc/NilPortugues_PHP_Sphinx.png?branch=master)](https://travis-ci.org/nilopc/NilPortugues_PHP_Sphinx) Sphinx for PHP 5.3 and above
=================

This class is a port of the original Sphinx API class by **Andrew Aksyonoff** and [Sphinx Technologies Inc](http://sphinxsearch.com/) to PHP 5.3 and above. All code has been rewritten to be PSR-2 compilant.

* [1.Installation](#block1)
* [2. Added removeFilter](#block2)
* [3. Added fluent interface](#block3)
* [4. Roadmap](#block4)
* [4. Author](#block5)

<a name="block1"></a>
## 1.Installation
Add the following to your `composer.json` file :

```js
{
    "require": {
        "nilportugues/sphinx-search":"dev-master"
    }
}
```
<a name="block2"></a>
## 2. Added removeFilter
While all the existing methods are available and vastly documented in the [Sphinx Documentation](http://sphinxsearch.com/docs/current.html), this version of the SphinxClient for PHP includes a new method.

* **removeFilter**: The original SphinxClient allows you to clear all filters. This method removes a specific filter previously set.

```php
<?php

$sphinxSearch = new \NilPortugues\Sphinx\SphinxClient();

//Do connection and set up search method...
$sphinxSearch->setServer('127.0.0.1',9312);


// Do search...
// Result would contain "The Amazing Spider-Man 2", to be in theatres in 2014.
$sphinxSearch->setFilter('year',array(2014));
$result = $sphinxSearch->query('Spiderman','movies');

// Unset the filter to stop filtering by year
// Now we'll get all the Spiderman movies.
$sphinxSearch->removeFilter('year');
$result = $sphinxSearch->query('Spiderman','movies');
```

<a name="block3"></a>
## 3. Added fluent interface
While updating the code, chaining capability has been added. SphinxClient's setters can be chained resulting in a cleaner code.
```php
$sphinxSearch = new \NilPortugues\Sphinx\SphinxClient();

$result = $sphinxSearch
                ->setFilter('year',array(2014))
                ->query('Spiderman','movies')
        ;
```

<a name="block4"></a>
## 4. Roadmap

- Refactor spaghetti methods.
- Replace `assert` functions for SphinxException.
- Make use of SplFixedArray were possible to gain speed.

<a name="block5"></a>
## 5. Author
Nil Portugués Calderó
 - <contact@nilportugues.com>
 - http://nilportugues.com