<?php

namespace Krak\DoctrineUtil;

use Doctrine\Common\Persistence\ObjectManager,
    Doctrine\ORM\AbstractQuery;

const FETCH_ITER = 1;
const FETCH_CHUNKED = 2;

function _queryChunk($query, $page, $chunk_size) {
    $query->setFirstResult($page * $chunk_size);
    $query->setMaxResults($chunk_size);
    return $query->getResult();
}

/** Paginate a query and return a Paginator instance */
function repoPaginate(AbstractQuery $query, $page, $per_page, $fetch_join_collection = false) {
    $query->setFirstResult(($page - 1) * $per_page);
    $query->setMaxResults($per_page);
    return new \Doctrine\ORM\Tools\Pagination\Paginator($query, $fetch_join_collection);
}


/** execute query in chunks until data is empty. This is useful if your processing
    changes the size of the result set so that it's changing. After every chunk, the system will
    be cleared and flushed.

    usage:

    ```php
    $query = $em->createQuery("SELECT user FROM User WHERE user.is_processed = false");
    repoUntilEmpty($query, 50, function($users) {
        foreach ($users as $user) {
            $user->is_processed = true;
        }

    });
*/
function repoUntilEmpty(AbstractQuery $query, $chunk_size, $handler, $max = INF) {
    $page = 0;
    $total = 0;

    $query->setMaxResults($chunk_size);
    $em = $query->getEntityManager();

    while ($res = $query->getResult()) {
        $total += count($res);
        $handler($res);
        $em->flush();
        $em->clear();
        if ($total >= $max) {
            break;
        }
    }

    return $total;
}

/** chunks results and pass them to the handler */
function repoChunk(AbstractQuery $query, $chunk_size, $handler) {
    $page = 0;
    $total = 0;

    $em = $query->getEntityManager();

    $res = _queryChunk($query, $page, $chunk_size);

    while ($res && count($res)) {
        $total += count($res);
        $handler($res);

        $em->flush();
        $em->clear();

        $page += 1;
        $res = _queryChunk($query, $page, $chunk_size);
    }

    $em->flush();
    $em->clear();

    return $total;
}

function repoIter(AbstractQuery $query, $chunk_size, $fetch_all = true) {
    $em = $query->getEntityManager();

    if ($fetch_all) {
        $i = 0;
        foreach ($query->iterate() as $row) {
            yield $row[0];

            if ($i % $chunk_size === 0) {
                $em->flush();
                $em->clear();
            }

            $i += 1;
        }
    } else {
        $page = 0;

        $res = _queryChunk($query, $page, $chunk_size);
        while ($res && count($res)) {
            foreach ($res as $item) {
                yield $item;
            }

            $em->flush();
            $em->clear();

            $page += 1;
            $res = _queryChunk($query, $page, $chunk_size);
        }
    }

    $em->flush();
    $em->clear();
}

function repoIterAll(ObjectManager $om, $fetch) {
    return function($batch_size) use ($om, $fetch) {
        $page = 0;
        $entities = $fetch($page, $batch_size);

        while ($entities) {
            $empty = 1;
            foreach ($entities as $e) { $empty = 0; yield $e[0]; }

            if ($empty) {
                return;
            }

            $om->flush();
            $om->clear();

            $page += 1;
            $entities = $fetch($page, $batch_size);
        }
    };
}

function repoIterSqlQuery($fetch) {
    return function($batch_size) use ($fetch) {
        $page = 0;
        $stmt = $fetch($page, $batch_size);

        while ($stmt->rowCount()) {
            foreach ($stmt as $row) { yield $row; }

            $page += 1;
            $stmt = $fetch($page, $batch_size);
        }
    };
}
