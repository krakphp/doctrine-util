<?php

namespace Krak\DoctrineUtil\ORM\Mapping;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\Id\AssignedGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Metadata mapper to prep metadata for entities that will be used as data fixture records which will ensure
 * field names match the column names and the primary key needs to be manually assigned
 */
final class DataFixtureMapMetadata implements MapMetadata
{
    private $allowedEntityNamespace;
    private $mapMetadata;

    public function __construct(string $allowedEntityNamespace, MapMetadata $mapMetadata) {
        $this->allowedEntityNamespace = $allowedEntityNamespace;
        $this->mapMetadata = $mapMetadata;
    }

    public function __invoke(Configuration $configuration, ClassMetadata $metadata): ClassMetadata {
        $isAllowedEntity = isset($configuration->getEntityNamespaces()[$this->allowedEntityNamespace]);
        if (!$isAllowedEntity) {
            return ($this->mapMetadata)($configuration, $metadata);
        }

        $metadata = clone $metadata;

        $this->setIdGenerator($metadata);
        $this->convertFieldNames($metadata);

        return ($this->mapMetadata)($configuration, $metadata);
    }

    private function convertFieldNames(ClassMetadata $metadata): void {
        $metadata->fieldNames = $this->mapKeyValue(function(array $tup) {
            [$columnName, $fieldName] = $tup;
            return [$columnName, $columnName];
        }, $metadata->fieldNames);

        $metadata->fieldMappings = $this->mapKeyValue(function(array $tup) {
            [$fieldName, $mapping] = $tup;
            $mapping['fieldName'] = $mapping['columnName'];
            return [$mapping['columnName'], $mapping];
        }, $metadata->fieldMappings);
    }

    private function setIdGenerator(ClassMetadata $metadata): void {
        $metadata->setIdGenerator(new AssignedGenerator());
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);
    }

    private function mapKeyValue(callable $fn, array $iter): array {
        $newArr = [];
        foreach ($iter as $key => $value) {
            [$key, $value] = $fn([$key, $value]);
            $newArr[$key] = $value;
        }
        return $newArr;
    }
}
