<?php

namespace indra\storage;

use indra\definition\TypeDefinition;
use indra\object\Object;
use indra\object\Type;
use indra\service\Context;

/**
 * @author Patrick van Bergen
 */
class MySqlViewStore implements ViewStore
{
    public function createView(Type $type)
    {
        $db = Context::getDB();
        $viewTableName = "indra_view_" . $type->getId();

        $columns = [];

        foreach ($type->getAttributes() as $attribute) {
            $columns[$attribute->getId()] = $this->getMySqlDataType($attribute->getDataType());
        }

        $fields = "";
        foreach ($columns as $id => $dataType) {
            $fields .= $id . ' ' . $this->getMySqlDataType($dataType) . ",\n";
        }

        $db->execute("
            CREATE TABLE IF NOT EXISTS " . $viewTableName . " (
                `id` binary(22) not null,
                {$fields}
                primary key id (`id`)
            ) engine InnoDB DEFAULT CHARSET = utf8mb4 COLLATE utf8mb4_unicode_ci
        ");
    }

    public function updateView(Object $object)
    {
        $db = Context::getDB();
        $type = $object->getType();
        $viewTableName = "indra_view_" . $type->getId();

        $id = $object->getId();

        $values = "";
        foreach ($object->getAttributeValues() as $attributeId => $attributeValue) {
            $values .= $values ? ", " : "";
            $values .= "`" . $attributeId . "` = '" . $db->esc($attributeValue) . "'";
        }

        $db->execute("
            INSERT INTO {$viewTableName}
            SET `id` = '{$id}', {$values}
            ON DUPLICATE KEY
            UPDATE {$values}
        ");
    }

    private function getMySqlDataType($dataType)
    {
        return ($dataType == 'varchar') ? 'varchar(255)' : $dataType;
    }
}