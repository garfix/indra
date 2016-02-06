<?php

namespace indra\service;

use indra\diff\AttributeValueChanged;
use indra\diff\DiffItem;
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

            if ($diffItem instanceof AttributeValueChanged) {
                $value['object'] = $diffItem->getObjectId();
                $value['attribute'] = $diffItem->getAttributeTypeId();
                $value['old'] = $diffItem->getOldValue();
                $value['new'] = $diffItem->getNewValue();
            } else {
                throw new DiffItemClassNotRecognizedException();
            }
        }

        return serialize($values);
    }

    public function deserializeDiffItems($string)
    {
        $diffItems = [];
        $values = $this->deserializeDiffItems($string);

        foreach ($values as $value) {
            if ($value == 'AttributeValueChanged') {
                $diffItem = new AttributeValueChanged($value['object'], $value['attribute'], $value['old'], $value);
                $diffItems[] = $diffItem;
            } else {
                throw new DiffItemClassNotRecognizedException();
            }
        }

        return $diffItems;
    }
}