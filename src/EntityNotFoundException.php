<?php

namespace Krak\DoctrineUtil;

use Doctrine\ORM;

class EntityNotFoundException extends ORM\ORMException
{
    public $className;
    public $id;

    public function __construct(string $className, $id) {
        $this->className = $className;
        $this->id = $id;

        parent::__construct("Entity $className was not found with id {$id}.");
    }
}

