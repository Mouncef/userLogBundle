<?php

namespace Orca\UserLogBundle\DependencyInjection;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\FileLocator;
use Doctrine\DBAL\Schema\Comparator;

/**
 * Loads parsers to extract information from different libraries.
 *
 * They are only loaded when the corresponding library is installed and enabled.
 */
class UpdateDataBasePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $em = $container->get('doctrine.orm.default_entity_manager');
        $toSchema = $this->getSchemaProvider($em, $container->getParameter('orca_user_log.userlog_entity'));
        $fromSchema = $em->getConnection()->getSchemaManager()->createSchema();
        foreach ($fromSchema->getTables() as $table){
            $tableName = $table->getName();
            if ($tableName!==$container->getParameter('orca_user_log.table_name')) {
                $fromSchema->dropTable($tableName);
            }
        }
        $comparator = new Comparator();
        $schemaDiff = $comparator->compare($fromSchema, $toSchema);
        $platform = $em->getConnection()->getDatabasePlatform();

        $sqls = $schemaDiff->toSql($platform);
        if(count($sqls)>0) {
            $em->getConnection()
               ->executeQuery(implode('; ', $sqls))
            ;
        }

    }

    private function getSchemaProvider(EntityManagerInterface $em,$className)
    {
        $metadata = $em->getMetadataFactory()->getMetadataFor($className);

        $tool = new SchemaTool($em);

        return $tool->getSchemaFromMetadata([$metadata]);
    }


}
