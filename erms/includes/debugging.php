<?php
$log = new KLogger ( "log.txt" , KLogger::DEBUG );

/**
 * Methods useful when debugging
 *
 * Set the DEBUGME global to true here and include this, set to false to turn off debugging messages
 *
 * @version     1.0
 * @category    Debugging
 * @example     include '../includes/debugging.php';
 */

/**
 * DebugPrint()
 *
 * @param mixed $szReason
 * @return void
 */
function debugPrint($szReason)
{
    //$DEBUGME = TRUE;
    $DEBUGME = FALSE;

    if ($DEBUGME)
    {
        $GLOBALS['log']->logInfo(sprintf("[DEBUG]: %s", $szReason));
        printf('<pre>DEBUG: %s</pre>', $szReason);
    }
}
?>
