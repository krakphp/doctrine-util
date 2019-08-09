<?php

namespace Krak\DoctrineUtil\ORM;

use Doctrine\ORM\Decorator\EntityManagerDecorator;

final class PingReconnectEntityManager extends EntityManagerDecorator
{
    public function flush($entity = null) {
        $conn = $this->wrapped->getConnection();
        try {
            $res = $conn->ping();
        } catch (\Throwable $e) {
            $res = false;
        }
        if (!$res) {
            $conn->close();
            $conn->connect();
        }

        return parent::flush($entity);
    }
}
