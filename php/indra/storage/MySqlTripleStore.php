<?php

namespace indra\storage;

use indra\object\Instance;
use indra\service\Context;
use indra\service\IdGenerator;

/**
 * @author Patrick van Bergen
 */
class MySqlTripleStore implements TripleStore
{
    public function createBasicTables()
    {
        $mysqli = Context::getMySqli();

        $types = [
            'int' => [
                'type' => 'int',
                'encoding' => '',
                'key' => 'value',
            ],
            'date' => [
                'type' => 'date',
                'encoding' => '',
                'key' => 'value',
            ],
            'time' => [
                'type' => 'time',
                'encoding' => '',
                'key' => 'value',
            ],
            'datetime' => [
                'type' => 'datetime',
                'encoding' => '',
                'key' => 'value',
            ],
            'double' => [
                'type' => 'double',
                'encoding' => '',
                'key' => 'value',
            ],
            'varchar' => [
                'type' => 'varchar(255)',
                'encoding' => 'DEFAULT CHARSET = utf8mb4 COLLATE utf8mb4_unicode_ci',
                'key' => 'value(32)',
            ],
            'longtext' => [
                'type' => 'longtext',
                'encoding' => 'DEFAULT CHARSET = utf8mb4 COLLATE utf8mb4_unicode_ci',
                'key' => 'value(32)',
            ],
        ];

        foreach ($types as $name => $info) {
            foreach (['active', 'passive'] as $state) {

                $mysqli->query("
                    CREATE TABLE IF NOT EXISTS indra_{$state}_{$name} (
                        `triple_id` char(20) not null,
                        `object_id` char(20) not null,
                        `attribute_id` char(20) not null,
                        `value` {$info['type']} not null,
                        primary key object (`object_id`, `attribute_id`, {$info['key']}),
                        unique key attribute (`attribute_id`, {$info['key']}),
                        unique key triple (`triple_id`)
                    ) engine InnoDB {$info['encoding']}
                ");
            }
        }

        $mysqli->query("
            CREATE TABLE IF NOT EXISTS indra_revision (
					`revision_id`				char(20) not null,
					`revision_datetime` 		datetime not null,
					`revision_description` 		varchar(255),
					`username`				    varchar(255),
					primary key (`revision_id`),
					key (`revision_datetime`)
            ) engine InnoDB
        ");

        $mysqli->query("
            CREATE TABLE IF NOT EXISTS indra_revision_action (
					`revision_id`				char(20) not null,
					`triple_id`					char(20) not null,
					`action`					char not null,
					PRIMARY KEY  (`revision_id`, `triple_id`)
            ) engine InnoDB
        ");
    }

    public function save(Instance $instance)
    {
        $mysqli = Context::getMySqli();

        $type = $instance->getType();
        $attributeValues = $instance->getAttributeValues();
        $objectId = $instance->getId();

        foreach ($type->getAttributes() as $attribute) {

            if (isset($attributeValues[$attribute->getName()])) {

                $attributeValue = $attributeValues[$attribute->getName()];
                $attributeId = $attribute->getId();
                $dataType = $attribute->getDataType();
                $tripleId = IdGenerator::generateId();

                $mysqli->query("
                  INSERT INTO indra_active_" . $dataType . "
                  SET
                    `triple_id` = '" . $tripleId . "',
                    `object_id` = '" . $objectId . "',
                    `attribute_id` = '" . $attributeId . "',
                    `value` = '" . mysqli_real_escape_string($mysqli, $attributeValue) . "'
                ");
            }
        }
    }

    public function load(Instance $instance, $objectId)
    {
        $mysqli = Context::getMySqli();

        $instance->setId($objectId);

        $type = $instance->getType();
        $dataTypes = [];

        foreach ($type->getAttributes() as $attribute) {
            $dataTypes[$attribute->getDataType()] = $attribute->getDataType();
        }

        $attributeValues = [];

        foreach ($dataTypes as $dataType) {

            $resultSet = $mysqli->query("
              SELECT `attribute_id`, `value` FROM indra_active_" . $dataType . "
              WHERE
                `object_id` = '" . $objectId . "'
            ");

            if ($resultSet) {

                while ($result = $resultSet->fetch_assoc()) {

                    $attributeName = $type->getAttributeById($result['attribute_id'])->getName();
                    $attributeValues[$attributeName] = $result['value'];
                }
            }
        }

        $instance->setAttributeValues($attributeValues);
    }
}
