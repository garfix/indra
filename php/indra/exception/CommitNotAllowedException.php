<?php

namespace indra\exception;

use Exception;

/**
 * @author Patrick van Bergen
 */
class CommitNotAllowedException extends Exception
{
    public static function getOldCommit()
    {
        return new CommitNotAllowedException('This commit is not possible. You are working from an old version of this branch.');
    }
}