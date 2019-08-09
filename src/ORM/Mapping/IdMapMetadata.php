<?php

namespace Krak\DoctrineUtil\ORM\Mapping;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\ClassMetadata;

final class IdMapMetadata implements MapMetadata
{
    public function __invoke(Configuration $configuration, ClassMetadata $metadata): ClassMetadata {
        return $metadata;
    }
}
