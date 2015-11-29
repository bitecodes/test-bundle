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
        self::runCmd('doctrine:database:drop --force --env=test');
        self::runCmd('doctrine:database:create --env=test');
        self::runCmd('doctrine:schema:create --env=test');
    }
}