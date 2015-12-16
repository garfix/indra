<?php

namespace indra\service;

use indra\definition\TypeDefinition;
use indra\object\Model;

/**
 * @author Patrick van Bergen
 */
class TypeModel extends Model
{
#todo should not extend model

    public function addType($locatorClass, TypeDefinition $typeDefinition)
    {
        $classCreator = new ClassCreator();
        $classCreator->createClasses($locatorClass, $typeDefinition);

        preg_match('/^(.*)\\\\(.*)Picket$/', $locatorClass, $matches);

        $path = $matches[1];
        $typeName = $matches[2];
        $modelClass = $path . '\\' . $typeName . 'Model';
        $methodName = 'create' . $typeName;
        $model = new $modelClass($this->domain);
        /** @var Object $object */
        $object = $model->$methodName();
        $type = $object->getType();

        $viewStore = $this->domain->getViewStore();
        $viewStore->createView($type);
    }
}