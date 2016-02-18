<?php

namespace indra\service;

use indra\diff\AttributeValuesChanged;
use indra\diff\DiffItem;
use indra\diff\ObjectAdded;
use indra\diff\ObjectRemoved;
use indra\exception\DiffItemClassNotRecognizedException;

/**
 * @author Patrick van Bergen
 */
class DiffService
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

            if ($diffItem instanceof AttributeValuesChanged) {
                $value['class'] = 'AttributeValuesChanged';
                $value['object'] = $diffItem->getObjectId();
                $value['attributes'] = $diffItem->getAttributeValues();
            } elseif ($diffItem instanceof ObjectAdded) {
                $value['class'] = 'ObjectAdded';
                $value['object'] = $diffItem->getObjectId();
                $value['attributes'] = $diffItem->getAttributeValues();
            } else {
                throw new DiffItemClassNotRecognizedException();
            }

            $values[] = $value;
        }

        return serialize($values);
    }

    public function deserializeDiffItems($string)
    {
        $diffItems = [];
        $values = unserialize($string);

        foreach ($values as $value) {
            $class = $value['class'];
            if ($class == 'AttributeValuesChanged') {
                $diffItem = new AttributeValuesChanged($value['object'], $value['attributes']);
                $diffItems[] = $diffItem;
            } elseif ($class == 'ObjectAdded') {
                $diffItem = new ObjectAdded($value['object'], $value['attributes']);
                $diffItems[] = $diffItem;
            } else {
                throw new DiffItemClassNotRecognizedException();
            }
        }

        return $diffItems;
    }

    public function getReverseDiffItem(DiffItem $diffItem)
    {
        if ($diffItem instanceof AttributeValuesChanged) {

            $reversedAttributeValues = [];

            foreach ($diffItem->getAttributeValues() as $attributeId => list($oldValue, $newValue)) {
                $reversedAttributeValues[$attributeId] = [$newValue, $oldValue];
            }

            return new AttributeValuesChanged($diffItem->getObjectId(), $reversedAttributeValues);

        } elseif ($diffItem instanceof ObjectAdded) {

            return new ObjectRemoved($diffItem->getObjectId());

        } else {
            throw new DiffItemClassNotRecognizedException();
        }
    }
}