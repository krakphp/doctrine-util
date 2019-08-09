<?php

namespace Krak\DoctrineUtil\DataFixture;

use Doctrine\ORM\EntityManagerInterface;

final class GenerateDataFixturesArgs
{
    private $em;
    private $outputPath;

    public function __construct(EntityManagerInterface $em, string $outputPath) {
        $this->em = $em;
        $this->outputPath = $outputPath;
    }

    public function em(): EntityManagerInterface {
        return $this->em;
    }

    public function outputPath(): string {
        return $this->outputPath;
    }
}
