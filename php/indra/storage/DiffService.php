<?php

namespace indra\storage;

use indra\diff\AttributeValuesChanged;
use indra\diff\BranchMerged;
use indra\diff\BranchSplit;
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
            } elseif ($diffItem instanceof ObjectRemoved) {
                $value['class'] = 'ObjectRemoved';
                $value['object'] = $diffItem->getObjectId();
                $value['attributes'] = $diffItem->getAttributeValues();
            } elseif ($diffItem instanceof BranchMerged) {
                $value['class'] = 'BranchMerged';
                $value['commits'] = $diffItem->getCommitIds();
            } elseif ($diffItem instanceof BranchSplit) {
                $value['class'] = 'BranchSplit';
                $value['commits'] = $diffItem->getCommitIds();
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
            } elseif ($class == 'ObjectRemoved') {
                $diffItem = new ObjectRemoved($value['object'], $value['attributes']);
                $diffItems[] = $diffItem;
            } elseif ($class == 'BranchMerged') {
                $diffItem = new BranchMerged($value['commits']);
                $diffItems[] = $diffItem;
            } elseif ($class == 'BranchSplit') {
                $diffItem = new BranchSplit($value['commits']);
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

            $reversedAttributeValues = $this->reverseAttributes($diffItem->getAttributeValues());
            return new AttributeValuesChanged($diffItem->getObjectId(), $reversedAttributeValues);

        } elseif ($diffItem instanceof ObjectAdded) {

            $reversedAttributeValues = $this->reverseAttributes($diffItem->getAttributeValues());
            return new ObjectRemoved($diffItem->getObjectId(), $reversedAttributeValues);

        } elseif ($diffItem instanceof ObjectRemoved) {

            $reversedAttributeValues = $this->reverseAttributes($diffItem->getAttributeValues());
            return new ObjectAdded($diffItem->getObjectId(), $reversedAttributeValues);

        } elseif ($diffItem instanceof BranchMerged) {

            return new BranchSplit($diffItem->getCommitIds());

        } elseif ($diffItem instanceof BranchSplit) {

            return new BranchMerged($diffItem->getCommitIds());

        } else {
            throw new DiffItemClassNotRecognizedException();
        }
    }

    private function reverseAttributes($attributeValues)
    {
        $reversedAttributeValues = [];

        foreach ($attributeValues as $attributeId => list($oldValue, $newValue)) {
            $reversedAttributeValues[$attributeId] = [$newValue, $oldValue];
        }

        return $reversedAttributeValues;
    }
}