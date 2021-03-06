<?php

namespace indra\storage;

/**
 * @author Patrick van Bergen
 */
interface IdGenerator
{
    /**
     * @return string A string of random upper and lower case letters, and numbers.
     */
    public function generateId();
}