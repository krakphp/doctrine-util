<?php

namespace Krak\DoctrineUtil\DataFixture;

use Doctrine\Bundle\FixturesBundle\Loader\SymfonyFixturesLoader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;

final class DataFixtureTool
{
    private $fixturesLoader;
    private $managerRegistry;

    public function __construct(SymfonyFixturesLoader $fixturesLoader, ManagerRegistry $managerRegistry) {
        $this->fixturesLoader = $fixturesLoader;
        $this->managerRegistry = $managerRegistry;
    }

    public function loadFixturesByClasses(array $fixtureClasses): void {
        $this->loadFixtures(array_map(function(string $className) {
            return new $className();
        }, $fixtureClasses));
    }

    public function loadFixturesByGroups(array $fixtureGroups): void {
        $this->loadFixtures($this->fixturesLoader->getFixtures($fixtureGroups));
    }

    /** @param FixtureInterface[] $fixtures */
    public function loadFixtures(array $fixtures) {
        $fixturesByEm = $this->indexFixturesByEntityManagerName($fixtures);
        foreach ($fixturesByEm as $emName => $fixtures) {
            /** @var EntityManagerInterface $em */
            $em = $this->managerRegistry->getManager($emName);
            $purger = new ORMPurger($em);
            $executor = new ORMExecutor($em, $purger);
            $executor->execute($fixtures);
        }
    }

    /** @param FixtureInterface[] $fixtures */
    private function indexFixturesByEntityManagerName(array $fixtures): array {
        $acc = [];
        foreach ($fixtures as $fixture) {
            $emName = $fixture instanceof EntityManagerProvidingDataFixture ? $fixture->getEntityManagerName() : 'default';
            $acc[$emName][] = $fixture;
        }
        return $acc;
    }
}
