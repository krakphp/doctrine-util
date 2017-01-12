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

## API

```
/** Chunks the result into arrays. After each chunk the em is flushed, then cleared */
integer repoChunk(AbstractQuery $query, integer $chunk_size, callable $handler)
/** Works exactly like repoChunk, except that it will keep chunking results until the query returns an empty result set.
    This function expects that the handler will be modifying the entities so that they will no longer be in the query result */
integer repoUntilEmpty(AbstractQuery $query, integer $chunk_size, callable $handler, integer $max = INF)
```
