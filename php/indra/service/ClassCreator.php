<?php

namespace indra\service;

use indra\object\Type;
use ReflectionClass;

/**
 * @author Patrick van Bergen
 */
class ClassCreator
{
    /**
     * @param $locatorClass
     * @return Type
     */
    public function createClasses($locatorClass, Type $type)
    {
        $reflector = new ReflectionClass($locatorClass);
        $locatorClassPath = dirname($reflector->getFileName());

        $attributeTemplate = file_get_contents(__DIR__ . '/../template/Attribute.php.txt');

        $attributes = "";

        foreach ($type->getAttributes() as $attribute) {

            $replacements = [
                '{{ attribute }}' => $attribute->getName(),
                '{{ attributeName }}' => ucfirst($attribute->getName()),
            ];

            $attributes .= str_replace(array_keys($replacements), array_values($replacements), $attributeTemplate);
        }

        $attributeTemplate = file_get_contents(__DIR__ . '/../template/AttributeType.php.txt');

        $attributeTypes = "";

        foreach ($type->getAttributes() as $attribute) {

            $replacements = [
                '{{ attribute }}' => $attribute->getName(),
                '{{ attributeId }}' => $attribute->getId(),
            ];

            $attributeTypes .= str_replace(array_keys($replacements), array_values($replacements), $attributeTemplate);
        }

        if (preg_match('/^(.*)\\\\(.*)Picket$/', $locatorClass, $matches)) {

            $path = $matches[1];
            $classNameBase = $matches[2];

            foreach (['Type', 'Model', ''] as $item) {
                $fileName = $item == '' ? 'Instance' : $item;
                $template = file_get_contents(__DIR__ . '/../template/' . $fileName . '.php.txt');

                $className = $classNameBase . $item;

                $replacements = [
                    '{{ namespace }}' => $path,
                    '{{ className }}' => $className,
                    '{{ typeName }}' => $classNameBase,
                    '{{ attributes }}' => $attributes,
                    '{{ attributeTypes }}' => $attributeTypes,
                    '{{ typeClassName }}' => $classNameBase . "Type",
                ];
                $contents = str_replace(array_keys($replacements), array_values($replacements), $template);

                file_put_contents($locatorClassPath . '/' . $className . '.php', $contents);
            }

        } else {

        }


    }
}