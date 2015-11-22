<?php

namespace Fludio\TestBundle\Test;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;

trait DatabaseReset
{
    /**
     * @before
     */
    public function resetDatabase()
    {
        $purger = new ORMPurger($this->em);
        $connection = $this->em->getConnection();

        try {
            $connection->executeQuery('SET FOREIGN_KEY_CHECKS = 0');
            $purger->purge();
        } finally {
            $connection->executeQuery('SET FOREIGN_KEY_CHECKS = 1');
        }
    }
}