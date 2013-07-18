# Sphinx and Sphinx Facet Search [![Build Status](https://travis-ci.org/nilopc/NilPortugues_PHP_Sphinx.png?branch=master)](https://travis-ci.org/nilopc/NilPortugues_PHP_Sphinx)

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

### What is a faceted search?
Faceted search, also called faceted navigation or faceted browsing, is a technique for accessing information organized according to a faceted classification system, allowing users to explore a collection of information by applying multiple filters.

A faceted classification system classifies each information element along multiple explicit dimensions, enabling the classifications to be accessed and ordered in multiple ways rather than in a single, pre-determined, taxonomic order.

### What is a facet?
Facets correspond to properties of the information elements. They are often derived by analysis of the text of an item using entity extraction techniques or from pre-existing fields in a database such as author, descriptor, language, and format. Thus, existing web-pages, product descriptions or online collections of articles can be augmented with navigational facets.

*Source: [Wikipedia][http://en.wikipedia.org/wiki/Faceted_search]*


## 3. Todo
* Implement Facet
* Implement SphinxFacetClient
* Implement cache techniques.

## 4. Author
Nil Portugués Calderó
 - <contact@nilportugues.com>
 - http://nilportugues.com

## 5. Based on the work
 * [fSphinx][3], Sphinx Facet Search for Python.
 * [fSphinxPHP][4], port to PHP of [fSphinx][3].

[1]: http://sphinxsearch.com
[2]: http://sphinxsearch.com/docs/current.html
[3]: https://github.com/alexksikes/fSphinx
[4]: https://github.com/gigablah/fsphinxphp