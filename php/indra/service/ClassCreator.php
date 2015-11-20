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
     * @param Type $type
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

        $attributeTemplate = file_get_contents(__DIR__ . '/../template/TypeAttribute.php.txt');

        $typeAttributes = "";

        foreach ($type->getAttributes() as $attribute) {

            $replacements = [
                '{{ attribute }}' => $attribute->getName(),
                '{{ attributeId }}' => $attribute->getId(),
            ];

            $typeAttributes .= str_replace(array_keys($replacements), array_values($replacements), $attributeTemplate);
        }

        if (preg_match('/^(.*)\\\\(.*)Picket$/', $locatorClass, $matches)) {

            $path = $matches[1];
            $classNameBase = $matches[2];

            if (file_exists($locatorClassPath . '/' . $classNameBase . 'Type.php')) {

                # do not overwrite the type file, because it contains generated identifiers

                return;

            }

            foreach (['Type', 'Model', ''] as $item) {
                $fileName = $item == '' ? 'Object' : $item;
                $template = file_get_contents(__DIR__ . '/../template/' . $fileName . '.php.txt');

                $className = $classNameBase . $item;

                $replacements = [
                    '{{ namespace }}' => $path,
                    '{{ className }}' => $className,
                    '{{ typeName }}' => $classNameBase,
                    '{{ attributes }}' => $attributes,
                    '{{ typeAttributes }}' => $typeAttributes,
                    '{{ typeClassName }}' => $classNameBase . "Type",
                ];
                $contents = str_replace(array_keys($replacements), array_values($replacements), $template);

                file_put_contents($locatorClassPath . '/' . $className . '.php', $contents);
            }

        } else {

        }


    }
}