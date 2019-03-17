# Elastic Product

[ALPHA] This module is in alpha version and is not yet stable.

Fast search for your products with ElasticSearch !

![Demo](./demo.gif?raw=true "Demo")

## Requirements

* Thelia 
    * Version: >= 2.2   
* ElasticSearch
    * Version: >= 6.4

## Installation

```
composer require thelia/elastic-product-module:~0.0.4
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

You can index your products with the button in module configuration page.   
Or a more efficient way is to execute this command `php Thelia elasticproduct:index:build` this will create the index with the mapping and index the whole catalog.   
You can set a cron with this command to reindex the catalog every month, week or day. Don't execute this command more than once a day because it can take very long time on big catalog.

But if your version of Thelia is >= 2.4.0 you can execute this other command `php Thelia elasticproduct:index:queue`, this will only index modified products since last indexation.
So you can set a cron with this command every hour.




