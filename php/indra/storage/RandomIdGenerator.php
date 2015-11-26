<?php

namespace indra\storage;

/**
 * @author Patrick van Bergen
 */
class RandomIdGenerator implements IdGenerator
{
    public function generateId()
    {
        $id = '';
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        for ($i = 0; $i < 22; $i++) {
            $id .= $chars[mt_rand(0, 61)];
        }

        return $id;
    }
}