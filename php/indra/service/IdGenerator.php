<?php

namespace indra\service;

/**
 * @author Patrick van Bergen
 */
class IdGenerator
{
    /**
     * @return string A string of 20 random upper and lower case letters, and numbers.
     */
    public static function generateId()
    {
        $id = '';
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        for ($i = 0; $i < 20; $i++) {
            $id .= $chars[mt_rand(0, 61)];
        }

        return $id;
    }
}