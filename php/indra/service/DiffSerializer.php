<?php

namespace indra\service;

use indra\diff\AttributeValuesChanged;
use indra\diff\DiffItem;
use indra\diff\ObjectAdded;
use indra\exception\DiffItemClassNotRecognizedException;

/**
 * @author Patrick van Bergen
 */
class DiffSerializer
{
    /**
     * @param DiffItem[] $diffItems
     * @return string
     * @throws DiffItemClassNotRecognizedException
     */
    public function serializeDiffItems(array $diffItems)
    {
        $values = [];

        foreach ($diffItems as $diffItem) {

            $value = ['class' => get_class($diffItem)];

            if ($diffItem instanceof AttributeValuesChanged) {
                $value['object'] = $diffItem->getObjectId();
                $value['attributes'] = $diffItem->getAttributeValues();
            } elseif ($diffItem instanceof ObjectAdded) {
                $value['object'] = $diffItem->getObjectId();
                $value['attributes'] = $diffItem->getAttributeValues();
            } else {
                throw new DiffItemClassNotRecognizedException();
            }
        }

        return serialize($values);
    }

    public function deserializeDiffItems($string)
    {
        $diffItems = [];
        $values = unserialize($string);

        foreach ($values as $value) {
            if ($value == 'AttributeValuesChanged') {
                $diffItem = new AttributeValuesChanged($value['object'], $value['attributes']);
                $diffItems[] = $diffItem;
            } elseif ($value == 'ObjectAdded') {
                $diffItem = new ObjectAdded($value['object'], $value['attributes']);
                $diffItems[] = $diffItem;
            } else {
                throw new DiffItemClassNotRecognizedException();
            }
        }

        return $diffItems;
    }
}