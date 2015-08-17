# js-php-real-time-search (in progress)

A real-time full text search solution with javascript (client side) and php (server side)

Autocomplete, search and browsing as-you-type in real-time

![Sample Search](https://raw.githubusercontent.com/emmanuelroecker/js-php-real-time-search/master/doc/search_real_time.gif)

It's working with

*   [SQLite FTS4](https://sqlite.org/fts3.html)

## Server Side

### Import Data

Data must be in yaml format,
some samples in [tests/server/data](https://github.com/emmanuelroecker/js-php-real-time-search/tree/master/tests/server/data)

```php
    <?php
    use Symfony\Component\Console\Output\ConsoleOutput;
    use GlSearchEngine\GlServerEngine;

    $output    = new ConsoleOutput();

    $yamlFiles      = [__DIR__ . "/data/web.yml", __DIR__ . "/data/web2.yml"];  //yaml files list to import in database
    $dbname         = __DIR__ . "/data/web.db"; //database path
    $table          = "web";    //prefix table name
    $fieldsFullText = ['title', 'tags', 'description', 'address', 'city'];  //fields list to fulltext search
    $fieldsFilter   = ['gps']; //fields list possibly used to filter

    $engine = new GlServerEngine($dbname, $output, true);
    $engine->importYaml(
           $table,
           $fieldsFilter,
           $fieldsFullText,
           $yamlFiles,
           function () use ($output) { //callback function to each import
               $output->write(".");
           }
    );
```

### Service

[*Example of php file called by javascript client.*](https://github.com/emmanuelroecker/js-php-real-time-search/blob/master/tests/site1/search.php)

### Configure Apache Server

Allow multiple requests to be sent over the same TCP connection

<IfModule mod_headers.c>
    Header set Connection Keep-Alive
 </IfModule>

## Client side

[*Example of use*](https://github.com/emmanuelroecker/js-php-real-time-search/blob/master/samples/sample.html)

## Running Client/Server Tests

Launch from command line :

```console
vendor\bin\phpunit
```

## To Do

Ranking / Sorting

## Licence

GNU 2

## Contact

Authors : Emmanuel ROECKER & Rym BOUCHAGOUR

[Web Development Blog - http://dev.glicer.com](http://dev.glicer.com)