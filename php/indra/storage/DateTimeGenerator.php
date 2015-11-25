<?php

namespace indra\storage;

use DateTime;

/**
 * @author Patrick van Bergen
 */
class DateTimeGenerator
{
    /**
     * Returns current time.
     *
     * @return DateTime
     */
    public function getDateTime()
    {
        return new DateTime();
    }
}