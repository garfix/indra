<?php

namespace indra\service;

use Exception;
use indra\definition\TypeDefinition;
use indra\exception\ClassCreationException;
use ReflectionClass;

/**
 * @author Patrick van Bergen
 */
class ClassCreator
{
    /**
     * @param $locatorClass
     * @param TypeDefinition $typeDefinition
     * @throws Exception
     */
    public function createClasses($locatorClass, TypeDefinition $typeDefinition)
    {
        $reflector = new ReflectionClass($locatorClass);
        $locatorClassPath = dirname($reflector->getFileName());
        $typeId = Context::getIdGenerator()->generateId();
        $attributeTemplate = file_get_contents(__DIR__ . '/../template/Attribute.php.txt');

        $attributes = "";

        foreach ($typeDefinition->getAttributes() as $attribute) {

            $replacements = [
                '{{ attributeId }}' => $attribute->getId(),
                '{{ attributeName }}' => ucfirst($attribute->getName()),
            ];

            $attributes .= str_replace(array_keys($replacements), array_values($replacements), $attributeTemplate);
        }

        $attributeTemplate = file_get_contents(__DIR__ . '/../template/TypeAttribute.php.txt');

        $typeAttributes = "";

        foreach ($typeDefinition->getAttributes() as $attribute) {

            $replacements = [
                '{{ attribute }}' => $attribute->getName(),
                '{{ attributeId }}' => $attribute->getId(),
            ];

            $typeAttributes .= str_replace(array_keys($replacements), array_values($replacements), $attributeTemplate);
        }

        $columnTemplate = file_get_contents(__DIR__ . '/../template/TableColumn.php.txt');

        $tableViewColumns = "";
        foreach ($typeDefinition->getAttributes() as $attribute) {

            $replacements = [
                '{{ columnName }}' => strtoupper($attribute->getName()),
                '{{ columnId }}' => $attribute->getId(),
            ];

            $tableViewColumns .= str_replace(array_keys($replacements), array_values($replacements), $columnTemplate);
        }

        if (preg_match('/^(.*)\\\\(.*)Picket$/', $locatorClass, $matches)) {

            $path = $matches[1];
            $classNameBase = $matches[2];
            $classPath = $locatorClassPath . '/' . $classNameBase . 'Type.php';

            if (file_exists($classPath)) {

                // reuse existing type id
                $contents = file_get_contents($classPath);

                if (preg_match("/return '([a-zA-Z0-9]{22})'/", $contents, $matches)) {
                    $typeId = $matches[1];
                } else {
                    throw ClassCreationException::getTypeIdNotFound();
                }
            }

            foreach (['Type', 'Model', 'Table', ''] as $item) {
                $fileName = $item == '' ? 'Object' : $item;
                $template = file_get_contents(__DIR__ . '/../template/' . $fileName . '.php.txt');

                $className = $classNameBase . $item;
                $tableName = 'indra_view_' . $typeId;

                $replacements = [
                    '{{ namespace }}' => $path,
                    '{{ className }}' => $className,
                    '{{ classId }}' => $typeId,
                    '{{ typeName }}' => $classNameBase,
                    '{{ attributes }}' => $attributes,
                    '{{ typeAttributes }}' => $typeAttributes,
                    '{{ tableViewColumns }}' => $tableViewColumns,
                    '{{ tableName }}' => $tableName,
                    '{{ tableClassName }}' => $classNameBase . "Table",
                    '{{ typeClassName }}' => $classNameBase . "Type",
                ];
                $contents = str_replace(array_keys($replacements), array_values($replacements), $template);

                file_put_contents($locatorClassPath . '/' . $className . '.php', $contents);
            }

        } else {
            throw new Exception('Locator class name should end on "Picket"');
        }
    }
}