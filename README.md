# js-php-search-as-you-type

A small [Algolia](https://www.algolia.com/) and [Elasticsearch](https://www.elastic.co/products/elasticsearch) alternative.

Autocomplete
Search and browsing as-you-type

![Sample Search](https://raw.githubusercontent.com/emmanuelroecker/js-php-search-as-you-type/master/doc/search.gif)


It's working with

*   [SQLite FTS4](https://sqlite.org/fts3.html)

## Configure Apache Server

Allow multiple requests to be sent over the same TCP connection

<IfModule mod_headers.c>
    Header set Connection Keep-Alive
 </IfModule>

## Running Client/Server Tests

Launch from command line :

```console
vendor\bin\phpunit
```

## Licence

GNU 2

## Contact

Authors : Emmanuel ROECKER & Rym BOUCHAGOUR

[Web Development Blog - http://dev.glicer.com](http://dev.glicer.com)