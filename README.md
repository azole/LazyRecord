LazyRecord
==========

![Logo](https://c9s.github.io/LazyRecord/assets/images/LazyRecord.svg)

[![Build Status](https://travis-ci.org/c9s/LazyRecord.svg?branch=master)](https://travis-ci.org/c9s/LazyRecord)
[![Coverage Status](https://img.shields.io/coveralls/c9s/LazyRecord.svg)](https://coveralls.io/r/c9s/LazyRecord)
[![Latest Stable Version](https://poser.pugx.org/corneltek/lazyrecord/v/stable.svg)](https://packagist.org/packages/corneltek/lazyrecord) 
[![Total Downloads](https://poser.pugx.org/corneltek/lazyrecord/downloads.svg)](https://packagist.org/packages/corneltek/lazyrecord) 
[![Monthly Downloads](https://poser.pugx.org/corneltek/lazyrecord/d/monthly)](https://packagist.org/packages/corneltek/lazyrecord)
[![Daily Downloads](https://poser.pugx.org/corneltek/lazyrecord/d/daily)](https://packagist.org/packages/corneltek/lazyrecord)
[![Latest Unstable Version](https://poser.pugx.org/corneltek/lazyrecord/v/unstable.svg)](https://packagist.org/packages/corneltek/lazyrecord) 
[![License](https://poser.pugx.org/corneltek/lazyrecord/license.svg)](https://packagist.org/packages/corneltek/lazyrecord)

LazyRecord is an open-source Object-Relational Mapping (ORM) for PHP5. 

It allows you to access your database very easily by using ActiveRecord
pattern API.

LazyRecord uses code generator to generate static code, which reduces runtime 
costs, therefore it's pretty lightweight and fast. 

LazyRecord is not like PropelORM, it doesn't use ugly XML as its schema or
config file, LazyRecord uses simpler YAML format config file and it compiles
YAML to pure PHP code to improve the performance of config loading.

LazyRecord also has a simpler schema design, you can define your model schema 
very easily and you can even embed closure in your schema classes.

<div style="width:425px" id="__ss_12638921"><strong style="display:block;margin:12px 0 4px"><a href="http://www.slideshare.net/c9s/lazyrecord-the-fast-orm-for-php" title="LazyRecord: The Fast ORM for PHP" target="_blank">LazyRecord: The Fast ORM for PHP</a></strong> <iframe src="http://www.slideshare.net/slideshow/embed_code/12638921" width="425" height="355" frameborder="0" marginwidth="0" marginheight="0" scrolling="no"></iframe> <div style="padding:5px 0 12px"> View more <a href="http://www.slideshare.net/" target="_blank">presentations</a> from <a href="http://www.slideshare.net/c9s" target="_blank">Yo-An Lin</a> </div> </div>


Concept
--------

- Function calls in PHP are very slow, so the model schema data
  will be built statically, LazyRecord converts all definitions (default value, validator, filter, valid
  value builder) into classes and static PHP array, this keeps these model
  classes very lightweight and fast.
  
- In the runtime, all the same model objects use the same 
  schema object, and we can reuse the prebuild data from the static schema class.

- We keep base model class constructor empty, so when you are querying data from
  database, these model objects can be created with zero effort.


Feature
-------

* Fast
* Simple, Lightweight Pure PHP Model Schema (No XML)
* PDO, MySQL, Pgsql, SQLite support.
* Multiple data source support.
* Mix-in model support.
* Migration support. upgrade, downgrade, upgrade from schema diff.
* Schema/Database diff

Requirement
-----------

Please see the details on [Wiki](https://github.com/c9s/LazyRecord/wiki)

Installation
------------

Please see the details on [Wiki](https://github.com/c9s/LazyRecord/wiki)

Getting Started
---------------

### Configuring Database

Change directory to your project, run `init` command to initialize 
your database settings.

```sh
$ mkdir myapp
$ cd myapp
$ lazy init 
Database driver [sqlite] [sqlite/pgsql/mysql/] sqlite
Database name [:memory:] test
```

Then edit your config file:

```sh
$ vim db/config/database.yml
```

Suppose your application code is located in `src/` directory, 
then you should provide your schema path in following format:

```yaml
---
bootstrap:
  - vendor/autoload.php   # load the classloader from composer.
schema:
  auto_id: 1
  paths:
    - src/    # where you store the schema class files.
data_sources:
  default:
    dsn: 'sqlite:test'
```

In the above config file, the `auto_id` means an id column with auto-increment
integer primary key is automatically inserted into every schema class, so you
don't need to declare an primary key id column in your every schema file.


### Writing Model Schema

Next, write your model schema file:

```sh
$ vim src/YourApp/Model/UserSchema.php
```

Put the content into your file:

```php
namespace YourApp\Model;
use LazyRecord\Schema;

class UserSchema extends Schema
{
    public function schema()
    {
        $this->column('account')
            ->varchar(16);
        $this->column('password')
            ->varchar(40)
            ->filter('sha1');
    }
}
```

### Building Static Schema Files

Then run `build-schema` command to build static schema files:

```sh
$ lazy build-schema
Finding schemas...
Found schema classes
Initializing schema generator...
    YourApp\Model\UserSchemaProxy    => src/YourApp/Model/UserSchemaProxy.php
    YourApp\Model\UserCollectionBase => src/YourApp/Model/UserCollectionBase.php
    YourApp\Model\UserCollection     => src/YourApp/Model/UserCollection.php
    YourApp\Model\UserBase           => src/YourApp/Model/UserBase.php
    YourApp\Model\User               => src/YourApp/Model/User.php
Done
```


### Creating Database

If you are using postgresql or mysql, you can create your database with
`create-db` command:

```sh
$ lazy create-db
```


### Building SQL From Model Schemas

Now you need to build SQL schema into your database, simply run `build-sql`,
`-d` is for debug mode, which prints all generated SQL statements:

```sh
$ lazy -d build-sql
Finding schema classes...
Initialize schema builder...
Building SQL for YourApp\Model\UserSchema
DROP TABLE IF EXISTS users;
CREATE TABLE users ( 
  account varchar(16),
  password varchar(40)
);

Setting migration timestamp to 1347439779
Done. 1 schema tables were generated into data source 'default'.
```

### Writing Application Code

Now you can write your application code,
But first you need to write your lazyrecord config loader code:

```
$ vim app.php
```

```php
require 'vendor/autoload.php';
$config = new LazyRecord\ConfigLoader;
$config->load( __DIR__ . '/db/config/database.yml');
$config->init();
```

The `init` method initializes data sources to ConnectionManager, but it won't
create connection unless you need to operate your models.


### Sample Code Of Operating The User Model Object

Now append your application code to the end of `app.php` file:

```php
$user = new YourApp\Model\User;
$ret = $user->create(array('account' => 'guest', 'password' => '123123' ));
if( ! $ret->success ) {
    echo $ret->message;  // get the error message
    if ($ret->exception) {
        echo $ret->exception;  // get the exception object
    }
    echo $ret; // __toString() is supported
}
```

Please check `doc/` directory for more details.



Basic Usage
-----------

### Model Accessor

LazyRecord's BaseModel class provides a simple way to retrieve result data from the `__get` magic method,
by using the magic method, you can retrieve the column value and objects from relationship.

```php
$record = new MyApp\Model\User( 1 );   // select * from users where id = 1;
$record->name;    // get "users.name" and inflate it.
```

The `__get` method is dispatching to `get` method, if you don't want to use the magic method,

```php
$record->get('name');
```


The magic method calls value inflator, which can help you inflate values like
DateTime objects, it might be slower, if you want performance, you can simply
do:

```php
$record->getValue('name');
```

BaseModel also supports iterating, so you can iterate the data values with foreach:

```php
foreach( $record as $column => $rawValue ) {

}
```





### Model Operation

To create a model record:

```php
$author = new Author;
$ret = $author->create(array(
    'name' => 'Foo'
));
if ( $ret->success ) {
    echo 'created';
}
```

To find record:
    
```php
$ret = $author->find(123);
$ret = $author->find(array( 'foo' => 'Name' ));
if ( $ret->success ) {

} else {
    // handle $ret->exception or $ret->message
}
```

To find record with (static):

```php
$record = Author::load(array( 'name' => 'Foo' ));
```

To find record with primary key:

```php
$record = Author::load( 1 );
```

To update record:

```php
$author->update(array(  
    'name' => 'Author',
));
```

To update record (static):

```php
$ret = Author::update( array( 'name' => 'Author' ) )
    ->where()
        ->equal('id',3)
        ->execute();

if( $ret->success ) {
    echo $ret->message;
}
else {
    // pretty print error message, exception and validation errors for console
    echo $ret;

    $e = $ret->exception; // get exception
    $validations = $ret->validations; // get validation results
}
```


### Collection

To create a collection object:

```php
$authors = new AuthorCollection;
```

To make a query (the Query syntax is powered by SQLBuilder):

```php
$authors->where()
    ->equal( 'id' , 'foo' )
    ->like( 'content' , '%foo%' )
    ;
```

Or you can do:

```php
$authors->where(array( 
    'name' => 'foo'
));
```


#### Iterating a Collection

```php
$authors = new AuthorCollection;
foreach( $authors as $author ) {
    echo $author->name;
}
```

Model Schema
------------

### Defining Schema Class

Simply extend class from `LazyRecord\Schema`, and define your model columns 
in the `schema` method, e.g.,

```php
<?php
namespace TestApp;
use LazyRecord\Schema;

class BookSchema extends Schema
{

    public function schema()
    {
        $this->column('title')
            ->unique()
            ->varchar(128);

        $this->column('subtitle')
            ->varchar(256);

        $this->column('isbn')
            ->varchar(128)
            ->immutable()
            ;

        $this->column('description')
            ->text();

        $this->column('view')
            ->default(0)
            ->integer();

        $this->column('publisher_id')
            ->isa('int')
            ->integer();

        $this->column('published_at')
            ->isa('DateTime')
            ->timestamp();

        $this->column('created_by')
            ->integer()
            ->refer('TestApp\UserSchema');


        // Defining trait for model class
        $this->addModelTrait('Uploader');
        $this->addModelTrait('Downloader')
            ->useInsteadOf('Downloader::a', 'Uploader');

        $this->belongsTo('created_by', 'TestApp\UserSchema','id', 'created_by');

        /** 
         * column: author => Author class 
         *
         * $book->publisher->name;
         *
         **/
        $this->belongsTo('publisher','\TestApp\PublisherSchema', 'id', 'publisher_id');

        /**
         * accessor , mapping self.id => BookAuthors.book_id
         *
         * link book => author_books
         */
        $this->many('book_authors', '\TestApp\AuthorBookSchema', 'book_id', 'id');


        /**
         * get BookAuthor.author 
         */
        $this->manyToMany( 'authors', 'book_authors', 'author' )
            ->filter(function($collection) { return $collection; });
    }
}
```

### Defining Column Types

```php
$this->column('foo')->integer();
$this->column('foo')->float();
$this->column('foo')->varchar(24);
$this->column('foo')->text();
$this->column('foo')->binary();
```

Text:

```php
$this->column('name')->text();
```

Boolean:

```php
$this->column('name') ->boolean();
```

Integer:

```php
$this->column('name')->integer();
```

Timestamp:

```php
$this->column('name')->timestamp();
```

Datetime:

```php
$this->column('name')->datetime();
```




#### Defining Mixin Method

```php
namespace LazyRecord\Schema\Mixin;
use LazyRecord\Schema\MixinDeclareSchema;

class MetadataMixinSchema extends MixinDeclareSchema
{
    public function schema()
    {
        // ... define your schema here
    }

    public function fooMethod($record, $arg1, $arg2, $arg3, $arg4)
    {
        // ...
        return ...;
    }
}
```

Then you can use the `fooMethod` on your model object:

```php
$record = new FooModal;
$result = $record->fooMethod(1,2,3,4);
```



### Defining Model Relationship

#### Belongs to

`belongsTo(accessor_name, foreign_schema_class_name, foreign_schema_column_name, self_column_name = 'id')`

```php
$this->belongsTo( 'author' , '\TestApp\AuthorSchema', 'id' , 'author_id' );
$this->belongsTo( 'address' , '\TestApp\AddressSchema', 'address_id' );
```

#### Has One

`one(accessor_name, self_column_name, foreign_schema_class_name, foreign_schema_column_name)`

```php
$this->one( 'author', 'author_id', '\TestApp\AuthorSchema' , 'id' );
```

#### Has Many

`many(accessor_name, foreign_schema_class_name, foreign_schema_column_name, self_column_name )`

```php
$this->many( 'addresses', '\TestApp\AddressSchema', 'author_id', 'id');
$this->many( 'author_books', '\TestApp\AuthorBookSchema', 'author_id', 'id');
```

To define many to many relationship:

```php
$this->manyToMany( 'books', 'author_books' , 'book' );
```


Usage:

```php
// has many
$address = $author->addresses->create(array( 
    'address' => 'farfaraway'
));

$address->delete();

// create related address
$author->addresses[] = array( 'address' => 'Harvard' );

$addresses = $author->addresses->items();
is( 'Harvard' , $addresses[0]->address );

foreach( $author->addresses as $address ) {
    echo $address->address , "\n";
}
```


### Do Some Preparation When Model Is Ready

If you want to do something after the schmea is created into a database, you can define a
`bootstrap` method in your schema class:

```php
namespace User;
class UserSchema extends LazyRecord\Schema { 
    public function schema() {
        // ...
    }
    public function bootstrap($model) {
        // do something you want
    }
}
```

The bootstrap method is triggerd when you run:

`lazy build-sql`

### Using Multiple Data Source

You can define specific data source for different model in the model schema:

```php
use LazyRecord\Schema;
class UserSchema extends Schema {
    public function schema() {
        $this->writeTo('master');
        $this->readFrom('slave');
    }
}
```

Or you can specify for both (read and write):

```php
use LazyRecord\Schema;
class UserSchema extends Schema {
    public function schema() {
        $this->using('master');
    }
}
```

### Defining BaseData Seed

The basedata seed script is executed after you run `build-sql`, which means
all of your tables are ready in the database.

To define a basedata seed script:

```php
namespace User;
class Seed { 
    public static function seed() {

    }
}
```

Then update your config file by adding the class name of the data
seed class:

```yaml
seeds:
  - User\Seed
  - System\Seed
  - System\TestingSeed
```

Migration
---------

If you need to modify schema code, like adding new columns to a table, you 
can use the amazing migration feature to migrate your database to the latest
change without pain.

Once you modified the schema code, you can execute `lazy diff` command to compare
current exisiting database table:

    $ lazy diff
    + table 'authors'            tests/tests/Author.php
    + table 'addresses'          tests/tests/Address.php
    + table 'author_books'       tests/tests/AuthorBook.php
    + table 'books'              tests/tests/Book.php
    + table 'users'              tests/tests/User.php
    + table 'publishers'         tests/tests/Publisher.php
    + table 'names'              tests/tests/Name.php
    + table 'wines'              tests/tests/Wine.php

As you can see, we added a lot of new tables (schemas), and LazyRecord parses
the database tables to show you the difference to let you know current
status.

> Currently LazyRecord supports SQLite, PostgreSQL, MySQL table parsing.

now you can generate the migration script or upgrade database schema directly.

to upgrade database schema directly, you can simply run:

    $ lazy migrate -U

to upgrade database schema through a customizable migration script, you can 
generate a new migration script like:

    $ lazy migrate --diff AddUserRoleColumn
    Loading schema objects...
    Creating migration script from diff
    Found 10 schemas to compare.
        Found schema 'TestApp\AuthorSchema' to be imported to 'authors'
        Found schema 'TestApp\AddressSchema' to be imported to 'addresses'
        Found schema 'TestApp\AuthorBookSchema' to be imported to 'author_books'
        Found schema 'TestApp\BookSchema' to be imported to 'books'
        Found schema 'TestApp\UserSchema' to be imported to 'users'
        Found schema 'TestApp\PublisherSchema' to be imported to 'publishers'
        Found schema 'TestApp\NameSchema' to be imported to 'names'
        Found schema 'TestApp\Wine' to be imported to 'wines'
    Migration script is generated: db/migrations/20120912_AddUserRoleColumn.php

now you can edit your migration script, which is auto-generated:

    vim db/migrations/20120912_AddUserRoleColumn.php

the migration script looks like:

```php
class AddUserColumn_1347451491  extends \LazyRecord\Migration\Migration {

    public function upgrade() { 
        $this->importSchema(new TestApp\AuthorSchema);
        $this->importSchema(new TestApp\AddressSchema);

        // To upgrade with new schema:
        $this->importSchema(new TestApp\AuthorBookSchema);
        
        // To rename table column:
        $this->renameColumn($table, $columnName, $newColumnName);
        
        // To create index:
        $this->createIndex($table,$indexName,$columnNames);
        
        // To drop index:
        $this->dropIndex($table,$indexName);
        
        // To add a foreign key:
        $this->addForeignKey($table,$columnName,$referenceTable,$referenceColumn = null) 
        
        // To drop table:
        $this->dropTable('authors');
    }

    public function downgrade() { 

        $this->dropTable('authors');
        $this->dropTable('addresses');
        
    }
}
```

The built-in migration generator not only generates the upgrade script,
but also generates the downgrade script, you can modify it to anything as you
want.

After the migration script is generated, you can check the status of 
current database and waiting migration scripts:

    $ lazy migrate --status
    Found 1 migration script to be executed.
    - AddUserColumn_1347451491

now you can run upgrade command to 
upgrade database schema through the migration script:

    $ lazy migrate --up

If you regret, you can run downgrade migrations through the command:

    $ lazy migrate --down

But please note that SQLite doesn't support column renaming and column dropping.

To see what migration script could do, please check the documentation of SQLBuilder package.

## Mix-In Schema

...


## Collection Filter

The Built-in Collection Filter provide a powerful feature that helps you connect the backend collection filtering with your front-end UI by 
defining filter types, valid values from backend:


```php
use LazyRecord\CollectionFilter\CollectionFilter;
$posts = new PostCollection;
$filter = new CollectionFilter($posts);

$filter->defineEqual('status', [ 'published', 'draft' ]); // valid values are 'published', 'draft'
$filter->defineContains('content');
$filter->defineRange('created_on', CollectionFilter::String );
$filter->defineInSet('created_by', CollectionFilter::Integer );

$collection = $filter->apply([ 
    'status'     => 'published',   // get published posts
    'content'    => ['foo', 'bar'],  // posts contains 'foo' and 'bar'
    'created_on' => [ '2011-01-01', '2011-12-30' ], // posts between '2011-01-01' and '2011-12-30'
    'created_by' => [1,2,3,4],  // created by member 1, 2, 3, 4
]);

$collection = $filter->applyFromRequest('_filter_prefix_');

// use '_filter_' as the parameter prefix by default.
$collection = $filter->applyFromRequest();
```

The generated SQL statement is like below:

```sql
SELECT m.title, m.content, m.status, m.created_on, m.created_by, m.id FROM posts m  WHERE  (status = published OR status = draft) AND (content like %foo% OR content like %bar%) AND (created_on BETWEEN '2011-01-01' AND '2011-12-30') AND created_by IN (1, 2, 3, 4)
```


## Basedata Seed


## Setting up QueryDriver for SQL syntax
 
```php
$driver = LazyRecord\QueryDriver::getInstance('data_source_id');
$driver->configure('driver','pgsql');
$driver->configure('quote_column',true);
$driver->configure('quote_table',true);
```

## A More Advanced Model Schema

```php
use LazyRecord\Schema;

class AuthorSchema extends Schema
{
    function schema()
    {
        $this->column('id')
            ->integer()
            ->primary()
            ->autoIncrement();

        $this->column('name')
            ->varchar(128)
            ->validator(function($val) { .... })
            ->filter( function($val) {  
                        return preg_replace('#word#','zz',$val);  
            })
            ->inflator(function($val) {
                return unserialize($val);
            })
            ->deflator(function($val) {
                return serialize($val);
            })
            ->validValues( 1,2,3,4,5 )
            ->default(function() { 
                return date('c');
            })
            ;

        $this->column('email')
            ->required()
            ->varchar(128);

        $this->column('confirmed')
            ->default(false)
            ->boolean();

        $this->seeds('User\\Seed')
    }
}
```

Documentation
=============

For the detailed content,  please take a look at the `doc/` directory.


Contribution
============

Everybody can contribute to LazyRecord. You can just fork it, and send Pull
Requests. 

You have to follow PSR Coding Standards and provides unit tests
as much as possible.


Hacking
=======


Setting Up Environment
----------------------

Use Composer to install the dependency:

    composer install --dev

To deploy a testing environment, you need to install dependent packages.

Run script and make sure everything is fine:

    php bin/lazy

Database configuration is written in `phpunit.xml` file, the 
following steps are based on the default configuration. you may also take a look at `.travis.yml` for example.

### Unit Testing with MySQL database

To test with mysql database:

    $ mysql -uroot -p
    > create database testing charset utf8;
    > grant all privileges on testing.* to 'testing'@'localhost' identified by 'testing';

### Unit Testing with PostgreSQL database

To test with pgsql database:

    $ sudo -u postgres createuser --no-createrole --no-superuser --pwprompt testing
    $ sudo -u postgres createdb -E=utf8 --owner=testing testing

### Run PHPUnit

    $ phpunit

### Command-line testing

To test sql builder from command-line, please copy the default testing config

    $ cp db/config/database.testing.yml db/config/database.yml

Build config

    $ php bin/lazy build-conf db/config/database.yml

Build Schema files

    php bin/lazy build-schema

We've already defined 3 data sources, they were named as 'mysql', 'pgsql', 'sqlite' , 
now you can insert schema sqls into these data sources:

    bin/lazy sql --rebuild -D=mysql
    bin/lazy sql --rebuild -D=pgsql
    bin/lazy sql --rebuild -D=sqlite


Using LazyRecord TableParser
============================

Every ORM implements a table parser in their own, and generalize the API to
support different SQL database, but I think this kind of stuff should be
re-usable.  If you're interested in this, the below part are the API tutorial
for you to work on:

First you need to define the use statement to use the table parser:

```php
use LazyRecord\TableParser\TableParser;
```

Then the second step is to create a parser object.

```php
$parser = TableParser::create( $this->driver, $this->connection );
```

Where the first parameter is the `SQLBuilder\Driver`, which defines the SQL generator behaviours. And the 
second parameter is the `PDO` connection object. you may just pass the PDO the the factory function.

To get the table names, you may call:

```php
$tables = $parser->getTables();
```

To parse the table schema, you may call `getTableSchemaMap` method, which retunrs the Schema object:

```php
foreach( $tables as $table ) {
    $schema = $parser->getTableSchemaMap( $table );
}
```


Manipulating Schema Objects
==============================

To get the model class name from a schema:

```php
$class = $schema->getModelClass();
```

To get the table name of a schema:

```php
$t = $schema->getTable();
```

To iterate the column objects, you may call `getColumns`, which returns the
column objects in an associative array:

```php
foreach( $schema->getColumns() as $n => $c ) {
    echo $c->name; // column name
}
```









PROFILING
==============


    $ scripts/run-xhprof

OR

    $ phpunit -c phpunit-xhprof.xml
    $ cd xhprof_html
    $ php -S localhost:8888


LICENSE
===============
BSD License



