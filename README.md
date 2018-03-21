# Doctrine Util

Holds a common set of functions/classes that are generally useful when working with the Doctrine ORM.

## Installation

Install with composer at `krak/doctrine-util`.

## Usage

```php
<?php

use function Krak\DoctrineUtil\{repoChunk, repoUntilEmpty};

$q = $em->createQuery('SELECT u FROM User u');
repoChunk($q, 100, function($users) {
    foreach ($users as $user) {
        // do something with user
    }
});

$q = $em->createQuery('SELECT u FROM User u WHERE u.email_sent = false');
repoUntilEmpty($q, 100, function($users) {
    foreach ($users as $user) {
        sendEmail($user);
        $user->setEmailSent(true);
    }
}, 1000); // run 1000 max
```

## EntityRepository

The `Krak\DoctrineUtil\EntityRepository` comes with a few awesome features that making working with the doctrine entity repositories even easier.



```php

// extend the DoctrineUtil Entity Repository
class UserRepository extends Krak\DoctrineUtil\EntityRepository
{

}

// or set the default in the entity manager config
$em->getConfiguration()->setDefaultRepositoryClassName(Krak\DoctrineUtil\EntityRepository::class);
```

You can then do the following with your repositories:

```php
$userRepo = $em->getRepository(User::class);


// `get` alias for finding the entity or throw if not found
try {
    $user = $userRepo->get($id);
} catch (Doctrine\ORM\EntityNotFoundException $e) {

}


// Simple fluent interface

// find users where ids are in 1,2, and 3
$users = $userRepo->where(['id' => [1,2,3]])->find();

// retrieve a single user with the state and address entities loaded.
$user = $userRepo->with(['state', 'address'])->get(1);
```

## API

```
/** Chunks the result into arrays. After each chunk the em is flushed, then cleared */
integer repoChunk(AbstractQuery $query, integer $chunk_size, callable $handler)
/** Works exactly like repoChunk, except that it will keep chunking results until the query returns an empty result set.
    This function expects that the handler will be modifying the entities so that they will no longer be in the query result */
integer repoUntilEmpty(AbstractQuery $query, integer $chunk_size, callable $handler, integer $max = INF)
```
