# Sphinx and Sphinx Facet Search

## 1. Sphinx for PHP 5.3 or above
This class is a port of the original Sphinx API class by **Andrew Aksyonoff** and [Sphinx Technologies Inc][1] to PHP 5.3 and above.

### New methods for SphinxClient
While all the existing methods available and vastly documented in the [Sphinx Documentation][2], this version of the SphinxClient for PHP includes a new method.

* **removeFilter**: removes a filter previously set.

```php
<?php

$sphinxSearch = new \NilPortugues\Sphinx\SphinxClient();
//Do connection ...

// Do search...
// Result would contain "The Amazing Spider-Man 2", to be in theatres in 2014.
$sphinxSearch->setFilter('year',array(2014));
$result = $sphinxSearch->query('Spiderman','movies');

// Unset the filter to stop filtering by year
// Now we'll get all the Spiderman movies.
$sphinxSearch->removeFilter('year');
$sphinxSearch->query('Spiderman','movies');
```

## 2. Sphinx with Facet Search

## 3. Todo

## 4. Author
Nil Portugués Calderó
 - <contact@nilportugues.com>
 - http://nilportugues.com

[1]: http://sphinxsearch.com
[2]: http://sphinxsearch.com/docs/current.html
