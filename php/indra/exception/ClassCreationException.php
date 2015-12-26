<?php

namespace indra\exception;


/**
 * @author Patrick van Bergen
 */
class ClassCreationException extends \Exception
{
    public static function getTypeIdNotFound()
    {
        return new ClassCreationException('Type id not found in type class');
    }
}