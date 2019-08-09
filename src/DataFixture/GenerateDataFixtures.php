<?php

namespace Krak\DoctrineUtil\DataFixture;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\DisconnectedClassMetadataFactory;
use PhpParser;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class GenerateDataFixtures
{
    private static $typeAlias = [
        Type::DATETIMETZ    => '\DateTime',
        Type::DATETIME      => '\DateTime',
        Type::DATE          => '\DateTime',
        Type::TIME          => '\DateTime',
        Type::OBJECT        => '\stdClass',
        Type::INTEGER       => 'int',
        Type::BIGINT        => 'int',
        Type::SMALLINT      => 'int',
        Type::STRING        => 'string',
        Type::TEXT          => 'string',
        Type::BLOB          => 'string',
        Type::DECIMAL       => 'string',
        Type::GUID          => 'string',
        Type::JSON_ARRAY    => 'array',
        Type::SIMPLE_ARRAY  => 'array',
        Type::BOOLEAN       => 'bool',
    ];

    private static $typeDefaults = [
        Type::DATETIMETZ    => null,
        Type::DATETIME      => null,
        Type::DATE          => null,
        Type::TIME          => null,
        Type::OBJECT        => null,
        Type::INTEGER       => 0,
        Type::BIGINT        => 0,
        Type::SMALLINT      => 0,
        Type::DECIMAL       => '0',
        Type::STRING        => '',
        Type::TEXT          => '',
        Type::BLOB          => '',
        Type::GUID          => '',
        Type::JSON_ARRAY    => [],
        Type::SIMPLE_ARRAY  => [],
        Type::BOOLEAN       => false,
    ];

    private $logger;

    public function __construct(?LoggerInterface $logger = null) {
        $this->logger = $logger ?: new NullLogger();
    }

    public function withLogger(LoggerInterface $logger): self {
        $self = clone $this;
        $self->logger = $logger;
        return $self;
    }

    public function __invoke(GenerateDataFixturesArgs $args) {
        $cmf = new DisconnectedClassMetadataFactory();
        $cmf->setEntityManager($args->em());
        $metadataItems = $cmf->getAllMetadata();
        foreach ($metadataItems as $metadata) {
            $classContents = $this->createDataFixtureClass($metadata);
            $filename = (function() use ($metadata) {
                $parts = explode('\\', $metadata->getName());
                return end($parts) . '.php';
            })();
            $filePath = $args->outputPath() . DIRECTORY_SEPARATOR . $filename;
            $this->logger->info("Writing file: {$filePath}");
            file_put_contents($filePath, $classContents);
        }
    }

    private function createDataFixtureClass(ClassMetadata $metadata): string {
        $factory = new PhpParser\BuilderFactory();
        $prettyPrint = new PhpParser\PrettyPrinter\Standard(['shortArraySyntax' => true]);
        [$namespace, $className] = (function() use ($metadata) {
            $parts = explode('\\', $metadata->getName());
            return [
                implode('\\', array_slice($parts, 0, -1)),
                $parts[count($parts) - 1]
            ];
        })();

        $class = $factory->class($className);
        $class->addStmt($factory->useTrait('DataFixtureConstructors'));

        $dateTimeFields = array_values(array_filter($metadata->getFieldNames(), function(string $fieldName) use ($metadata) {
            return in_array($metadata->getTypeOfField($fieldName), [Type::DATE, Type::DATETIME, Type::DATETIMETZ, Type::TIME]);
        }));
        $class->addStmt($factory->property('dateTimeFields')
            ->makeStatic()
            ->makePrivate()
            ->setDefault($dateTimeFields)
        );

        foreach ($metadata->getFieldNames() as $fieldName) {
            $metaFieldType = $metadata->getTypeOfField($fieldName);
            $nullable = $metadata->fieldMappings[$fieldName]['nullable'] ?? false;
            $fieldType = self::$typeAlias[$metaFieldType] ?? 'string';
            $fieldDefault = array_key_exists($metaFieldType, self::$typeDefaults) ? self::$typeDefaults[$metaFieldType] : '';
            $property = $factory->property($fieldName)
                ->makePublic()
                ->setDocComment(sprintf("/** @var %s%s */", $nullable ? '?' : '', $fieldType));
            if ($fieldDefault !== null && $nullable === false) {
                $property->setDefault($fieldDefault);
            }
            $class->addStmt($property);
        }

        $node = $factory->namespace($namespace)
            ->addStmt($factory->use(DataFixtureConstructors::class)->as('DataFixtureConstructors'))
            ->addStmt($class)
            ->getNode();

        return $prettyPrint->prettyPrintFile([$node]);
    }
}
