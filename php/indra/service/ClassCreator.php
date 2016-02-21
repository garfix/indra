<?php

namespace indra\service;

use Exception;
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

        if (preg_match('/^(.*)\\\\(.*)Picket$/', $locatorClass, $matches)) {

            $namespace = $matches[1];
            $classNameBase = $matches[2];
            $typeClassPath = $locatorClassPath . '/' . $classNameBase . 'Type.php';

        } else {
            throw new Exception('Locator class name should end on "Picket"');
        }

        if (file_exists($typeClassPath)) {
            $typeFileContents = file_get_contents($typeClassPath);
        } else {
            $typeFileContents = '';
        }

        $attributeTemplate = file_get_contents(__DIR__ . '/../template/Attribute.php.txt');
        $typeAttributeTemplate = file_get_contents(__DIR__ . '/../template/TypeAttribute.php.txt');
        $columnTemplate = file_get_contents(__DIR__ . '/../template/TableColumn.php.txt');

        if ($typeFileContents) {

            if (preg_match("/return '([a-zA-Z0-9]{22})'/", $typeFileContents, $matches)) {
                $typeId = $matches[1];
            } else {
                throw ClassCreationException::getTypeIdNotFound();
            }
        } else {
            $typeId = Context::getIdGenerator()->generateId();
        }

        $attributes = "";
        $typeAttributes = "";
        $tableViewColumns = "";

        foreach ($typeDefinition->getAttributes() as $attribute) {

            $attributeName = $attribute->getName();

            // if an id had been given to the attribute, reuse it
            if (preg_match("/new Attribute\('([a-zA-Z0-9]{22})', '{$attributeName}'\)/", $typeFileContents, $matches)) {
                $attributeId = $matches[1];
            } else {
                // otherwise, use the id of the type definition
                // note: we cannot use a random id, because the same attribute may be used multiple times;
                // this is the way we make sure these are the same
                $attributeId = $attribute->getId();
            }

            $replacements = [
                '{{ attributeId }}' => $attributeId,
                '{{ attribute }}' => $attributeName,
                '{{ attributeName }}' => ucfirst($attributeName),
                '{{ columnName }}' => strtoupper($attribute->getName()),
                '{{ columnId }}' => $attribute->getId(),
            ];

            $attributes .= str_replace(array_keys($replacements), array_values($replacements), $attributeTemplate);
            $typeAttributes .= str_replace(array_keys($replacements), array_values($replacements), $typeAttributeTemplate);
            $tableViewColumns .= str_replace(array_keys($replacements), array_values($replacements), $columnTemplate);
        }

        foreach (['Type', 'Model', 'Table', ''] as $item) {
            $fileName = $item == '' ? 'Object' : $item;
            $template = file_get_contents(__DIR__ . '/../template/' . $fileName . '.php.txt');

            $className = $classNameBase . $item;

            $replacements = [
                '{{ namespace }}' => $namespace,
                '{{ className }}' => $className,
                '{{ classId }}' => $typeId,
                '{{ typeName }}' => $classNameBase,
                '{{ attributes }}' => $attributes,
                '{{ typeAttributes }}' => $typeAttributes,
                '{{ tableViewColumns }}' => $tableViewColumns,
                '{{ tableClassName }}' => $classNameBase . "Table",
                '{{ typeClassName }}' => $classNameBase . "Type",
            ];
            $typeFileContents = str_replace(array_keys($replacements), array_values($replacements), $template);

            file_put_contents($locatorClassPath . '/' . $className . '.php', $typeFileContents);
        }
    }
}