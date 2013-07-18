# Sphinx for PHP 5.3 and above [![Build Status](https://travis-ci.org/nilopc/NilPortugues_PHP_Sphinx.png?branch=master)](https://travis-ci.org/nilopc/NilPortugues_PHP_Sphinx)

This class is a port of the original Sphinx API class by **Andrew Aksyonoff** and [Sphinx Technologies Inc][1] to PHP 5.3 and above.

## 1. Requirements
* Composer
* PHP 5.3 or above
* SphinxSearch

## 2. Added removeFilter
While all the existing methods are available and vastly documented in the [Sphinx Documentation][2], this version of the SphinxClient for PHP includes a new method.

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
## 3. Added chainable methods
While updating the code, chaining capability has been added. SphinxClient's setters can be chained resulting in a cleaner code.
```php
$sphinxSearch = new \NilPortugues\Sphinx\SphinxClient();

$result = $sphinxSearch
                ->setFilter('year',array(2014))
                ->query('Spiderman','movies')
        ;
```


## 5. Author
Nil Portugués Calderó
 - <contact@nilportugues.com>
 - http://nilportugues.com

[1]: [http://sphinxsearch.com/]
[2]: [http://sphinxsearch.com/docs/current.html]