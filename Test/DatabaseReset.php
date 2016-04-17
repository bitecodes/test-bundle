<?php

namespace BiteCodes\TestBundle\Test;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;

trait DatabaseReset
{
    /**
     * @before
     */
    public function resetDatabase()
    {
        if (!property_exists($this, 'em')) {
            throw new \Exception('No \'em\' property exists for this test case.');
        }

        if (!$this->em instanceof EntityManager) {
            throw new \Exception('The \'em\' property needs to be an entity manager');
        }

        $schema = new SchemaTool($this->em);
        $schema->dropDatabase();
        $schema->createSchema($this->em->getMetadataFactory()->getAllMetadata());
    }
}