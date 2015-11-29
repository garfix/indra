<?php

namespace indra\service;

use indra\definition\TypeDefinition;

/**
 * @author Patrick van Bergen
 */
class TypeModel
{
    public function addType($locatorClass, TypeDefinition $type)
    {
        $classCreator = new ClassCreator();
        $classCreator->createClasses($locatorClass, $type);

        preg_match('/^(.*)\\\\(.*)Picket$/', $locatorClass, $matches);

        $path = $matches[1];
        $typeName = $matches[2];
        $modelClass = $path . '\\' . $typeName . 'Model';
        $methodName = 'create' . $typeName;
        $model = new $modelClass;
        /** @var Object $object */
        $object = $model->$methodName();
        $type = $object->getType();

        $viewStore = Context::getViewStore();
        $viewStore->createView($type);
    }
}