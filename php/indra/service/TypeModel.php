<?php

namespace indra\service;

use indra\definition\TypeDefinition;

/**
 * @author Patrick van Bergen
 */
class TypeModel
{
    public function addType($locatorClass, TypeDefinition $typeDefinition, Domain $domain)
    {
        $classCreator = new ClassCreator();
        $classCreator->createClasses($locatorClass, $typeDefinition);

        preg_match('/^(.*)\\\\(.*)Picket$/', $locatorClass, $matches);

        $path = $matches[1];
        $typeName = $matches[2];
        $modelClass = $path . '\\' . $typeName . 'Model';
        $methodName = 'create' . $typeName;
        $model = new $modelClass($domain);
        /** @var Object $object */
        $object = $model->$methodName();
        $type = $object->getType();

        $viewStore = $domain->getViewStore();
        $viewStore->createView($type);
    }
}