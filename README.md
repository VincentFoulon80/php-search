# Installation

install this library via Composer :

```
composer require vfou/php-search
```

# Quick start

The search engine is packaged with an example schema that allow you to take hand quickly on the library.

at first you need to load the search engine.

```php
use VFou\Search\Engine;

$engine = new Engine();
```

You can give an array in parameter of the class constructor, see [the wiki's configuration page](https://github.com/VincentFoulon80/php-search/wiki/Configuration) for more informations.

By constructing the engine, there'll be some directory that appeared next to your index file :
- var/engine/index
- var/engine/documents
- var/engine/cache

(All these directories can be changed with the configuration array)

At first, you have to give to the engine something to search for. We'll create some documents and ask the engine to index them.

```php
$doc = [
    "id" => 1,
    "type" => "example-post",
    "title" => "this is my first blog post !",
    "content" => "I am very happy to post this first post in my blog !",
    "categories" => [
        "party",
        "misc."
    ],
    "date" => "2018/01/01",
    "comments" => [
        [
            "author" => "vincent",
            "date" => "2018/01/01",
            "message" => "Hello world!"
        ],
        [
            "author" => "someone",
            "date" => "2018/01/02",
            "message" => "Welcome !"
        ]
    ]
];
$engine->update($doc);
$doc = [
    "id" => 2,
    "type" => "example-post",
    "title" => "This is the second blog post",
    "content" => "a second one for fun",
    "date" => "2018/01/05",
    "categories" => [
        "misc."
    ],
    "comments" => [
        [
            "author" => "someone",
            "date" => "2018/01/05",
            "message" => "Another one ?!"
        ]
    ]
];
$engengine->update($doc);
```

Note : you can also put these two documents in one array and use the updateMultiple() function for indexing multiple documents at once.

Now that you documents are indexed, you can use the search function and fetch results :

```php
$response = $engine->search('second post');
var_dump($response);

$response = $engine->search('post');
var_dump($response);
```

For more informations about this library, go to [the wiki page of this repository](https://github.com/VincentFoulon80/php-search/wiki)

# License

This library is under the MIT license. See the complete license [in the bundle](LICENSE)


