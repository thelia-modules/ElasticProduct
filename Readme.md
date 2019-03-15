# Elastic Product

[ALPHA] This module is in alpha version and is not yet stable.

Fast search for your products with ElasticSearch !

## Requirements

* Thelia 
    * Version: >= 2.4   
* ElasticSearch
    * Version: >= 6.4

## Installation

```
composer require thelia/elastic-product-module:~0.0.1
```

### Usage

#### Configuration

Configure the module with your server informations.  
The "Index prefix" config will be used to prefix the index name, the full index name will be `{YOUR_PREFIX}_products`.

In configuration page you can disable or enable features and attributes filters.

#### Hooks

This module use 2 Thelia native hooks :
* `main.head-bottom` : For add the CSS
* `main.javascript-initialization` : For add the JS

Be sure theses hooks are presents in your templated

And 1 own hook :
* `elastic_product.container` : For add the search field

Put this hook where you want add the search field.


#### Indexation

To index your products there are 2 commands :

* For first indexation use `php Thelia elasticproduct:index:build` this will create the index with the mapping and index the whole catalog.
* Then use `php Thelia elasticproduct:index:queue` this will only index modified products since last indexation.





