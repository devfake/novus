![novus](http://80.240.132.120/novus/github_header.png)

Novus is a JSON-file database for PHP. Use this package for quick prototyping and to testing your application without to configure a mysql (or other) database.

The syntax is a little like more typical sql, and not like ORM.

**Warning:** This package is incomplete and it miss some important features.

* [Get Started](#get-started)
* [Quick Overview](#quick-overview)
* [First Steps](#first-steps)
* [Options](#options)
* [Parameter Values](#parameter-values)
* [Create Table](#create-table)
* [Add Fields](#add-fields)
* [Remove Fields](#remove-fields)
* [Insert Data](#insert-data)
* [Select Data](#select-data)
* [Order Data](#order-data)
* [Limit Data](#limit-data)
* [Where Conditions](#where-conditions)
* [Update Data](#update-data)
* [Delete And Remove](#delete-and-remove)
* [Last Primary Key](#last-primary-key)
* [Next Primary Key](#next-primary-key)
* [Last And First Data](#last-and-first-data)
* [Find And FindOrFail](#find-and-findorfail)
* [ToDo](#todo)

## Get Started

##### Requirements 

* PHP 5.4+
* Composer

##### Install

The easiest way to install Novus is via [Composer](https://getcomposer.org/). Add this to your `composer.json` file and run `$ composer update`:

```json
{
  "require": {
    "devfake/novus": "dev-master"
  }
}
```

Feature release will have a bootstrap file if you don't want to use composer.

## Quick Overview

Let's see a quick overview of the syntax:

```php
$novus = new \Devfake\Novus\Database();

// Create a new 'users' table.
$novus->table('users')->create('username, password, email');

// Add more fields.
$novus->table('users')->addFields('activated');

// Insert data.
$novus->table('users')->insert('username = Arya, email = a.stark@winterfell.com, password = n33dl3, activated = 1');

// Select all data.
$data = $novus->table('users')->select();

// Select only username and email.
$data = $novus->table('users')->select('username, email');

// Select all data from 'users' where id = 1.
$data = $novus->table('users')->where('id = 1')->select();

// Update username.
$novus->table('users')->update('username = Jon');

// Delete all data from 'users'.
$novus->table('users')->delete(true);

// Delete all data from 'users' with softdelete.
$novus->table('users')->delete();

// Remove complete 'users' file.
$novus->table('users')->remove();
```

## First Steps

Once you have created the object, you can normally work with them. 

However, there is a little rule: You must always specify which table you're working on at the beginning with `table()`:

```php
$novus = new \Devfake\Novus\Database();
$novus->table('myTable')->myMethods();
```

But you can also specify directly a table for the object:

```php
$myTable = new \Devfake\Novus\Database('myTable');
$myTable->myMethods();

$users = new \Devfake\Novus\Database('users');
$users->orderBy('username, email')->select('username, email, date');
```

This way is recommended if you are working only with a few tables, or want to make your code a bit more readable. And of course, you have less to write.

## Options

You can pass a few options when you instantiate your class.

```php
// Pass a single string to specify directly a table.
$novus = new \Devfake\Novus\Database('myTable');

// Or pass a array with conditions.
$novus = new \Devfake\Novus\Database([
  'table' => 'myTable',
  'path' => 'myPathForDatabaseFiles',
  'primaryKey' => 'Number'
]);
```

The `path` for your database files is relative to your root folder (or where your composers `vendor` folder is). 

The default folder is `database`. There are a `saves` folder to save your softdeletes.

## Parameter Values

```php
// First, pass a string in the parameter and separate the keywords with a comma.
$novus->table('users')->create('username, email, password');

// Or, pass a array in the parameter.
$novus->table('tablename')->create(['username', 'email', 'password']);
```
What is the difference? The first method is clear and fast to type.

Use the second method if you have commas in your keys. But please, i hope you have no commas (or other special chars) in your field names. The second method you will (or need) to use for insert or update data. 

## Create Table

Let‘s create a users table and work with them over the complete documentation. For the documentation we will work with `table()` to make it a little more detailed.

```php
// Create a new file without fields.
$novus->table('users')->create();

// Create a new file with fields.
$novus->table('users')->create('field1, field2');
// Or with the array spelling.
$novus->table('users')->create(['field1', 'field2']);
```

And that‘s it! You need the `create()` method only run once.

There is a new `database/users.json` file. Let‘s open it:

```json
{"table":"users","id":1,"fields":[["id"]],"data":[]}

// Format it a bit for the documentation
{
  "table": "users",
  "id": 1,

  "fields": [
    ["id"]
  ],

  "data": []
}
```

So, what we have in our table? First, we have `"table"`, this is our tablename, in this case `"users"`. Then comes our primary key, `"id"`. They will automatically increased.

Then we have our `"fields"`. The primary key will added by default.

And last, we have our `"data"`. Since we have no data entered, this field is empty.
As you can see, `"fields"` and `"data"` are arrays which contains other arrays.

## Add Fields

```php
$novus->table('users')->addFields('username, email');
$novus->table('users')->addFields(['username', 'email']);
```

## Remove Fields

The `removeFields()` method also deletes the associated data.

```php
$novus->table('users')->removeFields('username, email');
$novus->table('users')->removeFields(['username', 'email']);
```

## Insert Data

```php
$novus->table('users')->insert('username = devfake, email = devfakeplus@googlemail.com');
$novus->table('users')->insert(['username' => 'devfake', 'email' => 'devfakeplus@googlemail.com']);
```

## Select Data

```php
// Select all data from 'users'.
$data = $novus->table('users')->select();
$data = $novus->table('users')->select('*');

// Select only username and email.
$data = $novus->table('users')->select('username, email');
$data = $novus->table('users')->select(['username', 'email']);

// Select all data from where id = 1.
$data = $novus->table('users')->where('id = 1')->select();

// Iterate over $data
foreach($data as $content) {
  echo $content['username'];
}
```

## Order Data

Order your output with the `orderBy()` method.

```php
// Order by id ASC and username DESC.
$order = $novus->table('users')->orderBy('id asc, username desc')->select();
// Or with array spelling.
$order = $novus->table('users')->orderBy(['id' => 'asc', 'username' => 'desc'])->select();

// ASC is default passed.
$order = $novus->table('users')->orderBy('id, username desc')->select();
$order = $novus->table('users')->orderBy(['id', 'username' => 'desc')->select();

// ASC and DESC are case insensitive.
$order = $novus->table('users')->orderBy('id DESC, username ASC')->select();
```

## Limit Data

```php
1 => first_user, 2 => second_user, 3 => third_user, 4 => fourth_user, 5 => fifth_user, 6 => sixth_user, 7 => seventh_user 
```

These are pseudo data of the users table. With them we demonstrate the `limit()` method.

```php
// Select the first three data.
$limit = $novus->table('users')->limit(3)->select();
1 => first_user, 2 => second_user, 3 => third_user

// Select the next four data after the first two.
$limit = $novus->table('users')->limit(2, 4)->select();
3 => third_user, 4 => fourth_user, 5 => fifth_user, 6 => sixth_user

// Select the last three data in reverse.
$limit = $novus->table('users')->limit(3, true)->select();
5 => fifth_user, 6 => sixth_user, 7 => seventh_user

// Select the last four data in reverse before the last two.
$limit = $novus->table('users')->limit(2, 4, true)->select();
2 => second_user, 3 => third_user, 4 => fourth_user, 5 => fifth_user
```

The reverse mode return the data in ASC. Use case is for example a chat.
 
If you don't want this, order them, for example by id.

```php
// Select the last four data without reverse.
$limit = $novus->table('users')->limit(4, true)->orderBy('id DESC')->select();
7 => seventh_user, 6 => sixth_user, 5 => fifth_user, 4 => fourth_user
```

It does not matter whether the `orderBy()` method is written before or after `limit()`.

## Where Conditions

**Warning:** This method is currently very limited. It only works with `select()` and only one query. But i will fix it soon.

```php
$data = $novus->table('users')->where('id = 1')->select();
$data = $novus->table('users')->where('username = Arya')->select();
$data = $novus->table('users')->where('id > 10')->select();
```

Use `<=, >=, !=, =, >` and `<`. 

## Update Data

**Warning:** `update()` will currently update ALL data. I will fix it soon with `where()`.

```php
$novus->table('users')->update('username = newUsername, email = newEmail');
$novus->table('users')->update(['username' => 'newUsername', 'email' => 'newEmail']);
```

## Delete And Remove

If you use `delete()` or `remove()`, novus will make a backup file in the `saves` folder. This folder is created in the beginning and is inside of your database folder.

Pass `true` as parameter to avoid the softdelete.

##### Delete

With `delete()` you can remove a dataset.

**Warning:** This method is currently very limited and removed all data from a table. I will fix it soon with `where()`.

```php
$novus->table('users')->delete();
$novus->table('users')->delete(true);
```

##### Remove

With `remove()` you can remove a complete table file.

```php
$novus->table('users')->remove();
$novus->table('users')->remove(true);
```

## Last Primary Key

Return the primary key of last data. If data is empty, the method returns `null`.

```php
$key = $novus->table('users')->lastID();
```

## Next Primary Key

Return the primary key of next insert data.

```php
$key = $novus->table('users')->nextID();
```

## Last And First Data

```php
$first = $novus->table('users')->first();
echo $first['username'];

$last = $novus->table('users')->last();
echo $last['username'];
```

## Find And FindOrFail

Find data by primary key. If no data found, `find()` will return an empty array and `findOrFail()` will return an exception.

```php
$find = $novus->table('users')->find(1);
echo $find['username'];

$find = $novus->table('users')->findOrFail(2);
echo $find['username'];
```

## ToDo

* Finish `where()` conditions.
* Types for fields.
* `changePrimaryKey()` method.
* Write tests.