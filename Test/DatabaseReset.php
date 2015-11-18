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
        $purger->purge();
    }
}