<?php

namespace Krak\DoctrineUtil;

use Doctrine\ORM;
use Krak\Fn;

class EntityRepository extends ORM\EntityRepository
{
    public function get($id) {
        $entity = $this->find($id);
        if (!$entity) {
            throw new EntityNotFoundException($this->getEntityName(), $id);
        }
        return $entity;
    }

    public function where(array $filters) {
        return $this->createFluentBuilder()->where($filters);
    }

    public function with($entities) {
        return $this->createFluentBuilder()->with($entities);
    }

    private function createFluentBuilder() {
        return new class($this->getEntityName(), $this->getEntityManager()) {
            private $className;
            private $em;
            private $qb;
            private $rootAlias;
            private $onlyOneResult;
            private $selectedEntityAliases;

            public function __construct($className, $em) {
                $this->className = $className;
                $this->em = $em;
                $this->qb = $em->createQueryBuilder();
                $this->rootAlias = $this->makeRootEntityAlias($className);
                $this->onlyOneResult = false;
                $this->selectedEntityAliases = [];

                $this->qb->select($this->rootAlias)->from($className, $this->rootAlias);
            }

            private function makeRootEntityAlias($className) {
                $parts = explode("\\", $className);
                $last = $parts[count($parts) - 1];
                return strtolower($last);
            }

            public function where(array $filters) {
                $this->qb->andWhere(
                    $this->qb->expr()->andX(...Fn\map(function($kv) {
                        [$k, $v] = $kv;

                        if (strpos($k, ".") === false) {
                            $k = $this->rootAlias . "." . $k;
                        }

                        if (\is_array($v)) {
                            return $this->qb->expr()->in($k, $v);
                        }

                        return $this->qb->expr()->eq($k, $v);
                    }, Fn\toPairs($filters)))
                );
                return $this;
            }

            public function with($entities) {
                if (!\is_array($entities)) {
                    $entities = [$entities];
                }

                foreach ($entities as $entity) {
                    [$entity, $alias] = $this->formatJoinEntity($entity);
                    $this->qb->leftJoin($entity, $alias);
                    $this->qb->addSelect($alias);
                }

                return $this;
            }

            private function formatJoinEntity($entity) {
                if (strpos($entity, ".") === false) {
                    return [$this->rootAlias . "." . $entity, $entity];
                }

                return [$entity, str_replace('.', '', $entity)];
            }

            public function one($val = true) {
                $this->onlyOneResult = $val;
                return $this;
            }

            public function find($id = null, $field = 'id') {
                if ($id) {
                    $this->one()->where([$field => $id]);
                }

                $query = $this->qb->getQuery();
                return $this->onlyOneResult ? $query->getOneOrNullResult() : $query->getResult();
            }

            public function get($id, $field = 'id') {
                $res = $this->find($id, $field);

                if (!$res) {
                    throw new EntityNotFoundException($this->className, $id);
                }

                return $res;
            }
        };
    }
}
