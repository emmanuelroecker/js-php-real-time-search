# js-php-real-time-search (in progress)

A real-time full text search solution in javascript (client side) and php (server side)

Autocomplete, search and browsing as-you-type in real-time

![Sample Search](https://raw.githubusercontent.com/emmanuelroecker/js-php-search-as-you-type/master/doc/search.gif)

It's working with

*   [SQLite FTS4](https://sqlite.org/fts3.html)

## Import Data

```php
    use Symfony\Component\Console\Output\ConsoleOutput;
    use GlSearchEngine\GlServerEngine;

    $output    = new ConsoleOutput();

    $yamlFiles = [__DIR__ . "/data/web.yml", __DIR__ . "/data/web2.yml"];   //yaml files list to import
    $dbname    = __DIR__ . "/data/web.db";  //database name
    $table     = "web";     //table name
    $fields    = ['title', 'tags', 'description', 'address', 'city'];   //fields list to index

    $engine = new GlServerEngine($dbname, $output, true);
    $engine->importYaml(
               $table,
               $fields,
               $yamlFiles,
               function () use ($output) { //callback function each data inserted
                   $output->write(".");
               }
    );
```

Yaml file sample

```yaml
    cinema_comoedia:
        title : "Cinéma Comoedia"
        link: "http://www.cinema-comoedia.com"
        sociallink: "https://www.facebook.com/cinemacomoedia"
        feedlink: "http://www.cinema-comoedia.com/rss/"
        feedlimit: 5
        tags: "cinéma"
        gps: "45.7473766,4.8355764"
        description: |
                      Cinéma indépendant art et essai, lieu de rencontres dans lequel
                      projections de films, expositions, conférences et débats sont également possibles ...
        address: "13 Avenue Berthelot"
        city: "Lyon 7"
        phone: "04 26 99 45 00"
    jp_coureur_des_berges:
        title : "Jean-Pierre, le coureur des berges du Rhône"
        sociallink: "https://fr-fr.facebook.com/pages/Jean-Pierre-le-coureur-des-berges-du-Rh%C3%B4ne/101738059870725"
        rel: ""
        tags: "athlète papy joggeur short rouge"
        description: |
                      Courir, courir, et encore courir... sans jamais s'arrêter ...
```

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