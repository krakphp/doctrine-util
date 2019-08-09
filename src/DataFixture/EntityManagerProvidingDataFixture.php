<?php

namespace Krak\DoctrineUtil\DataFixture;

interface EntityManagerProvidingDataFixture
{
    public function getEntityManagerName(): string;
}
