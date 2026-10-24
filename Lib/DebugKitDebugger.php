<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         DebugKit 0.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * DebugKit Temporary Debugger Class
 *
 * Provides the future features that are planned. Yet not implemented in the 1.2 code base
 *
 * This file will not be needed in future version of CakePHP.
 *
 * @since         DebugKit 0.1
 */
class DebugKitDebugger extends Debugger
{
    /**
     * Start an benchmarking timer.
     *
     * @param string $name The name of the timer to start.
     * @param string $message A message for your timer
     * @return bool true
     * @deprecated use DebugTimer::start()
     */
    public static function startTimer($name = null, $message = null)
    {
        return DebugTimer::start($name, $message);
    }

    /**
     * Stop a benchmarking timer.
     *
     * $name should be the same as the $name used in startTimer().
     *
     * @param string $name The name of the timer to end.
     * @return bool true if timer was ended, false if timer was not started.
     * @deprecated use DebugTimer::stop()
     */
    public static function stopTimer($name = null)
    {
        return DebugTimer::stop($name);
    }

    /**
     * Get all timers that have been started and stopped.
     * Calculates elapsed time for each timer. If clear is true, will delete existing timers
     *
     * @param bool $clear false
     * @return array
     * @deprecated use DebugTimer::getAll()
     */
    public static function getTimers($clear = false)
    {
        return DebugTimer::getAll($clear);
    }

    /**
     * Clear all existing timers
     *
     * @return bool true
     * @deprecated use DebugTimer::clear()
     */
    public static function clearTimers()
    {
        return DebugTimer::clear();
    }

    /**
     * Get the difference in time between the timer start and timer end.
     *
     * @param string $name The name of the timer you want elapsed time for.
     * @param int $precision The number of decimal places to return, defaults to 5.
     * @return float number of seconds elapsed for timer name, 0 on missing key
     * @deprecated use DebugTimer::elapsedTime()
     */
    public static function elapsedTime($name = 'default', $precision = 5)
    {
        return DebugTimer::elapsedTime($name, $precision);
    }

    /**
     * Get the total execution time until this point
     *
     * @return float elapsed time in seconds since script start.
     * @deprecated use DebugTimer::requestTime()
     */
    public static function requestTime()
    {
        return DebugTimer::requestTime();
    }

    /**
     * get the time the current request started.
     *
     * @return float time of request start
     * @deprecated use DebugTimer::requestStartTime()
     */
    public static function requestStartTime()
    {
        return DebugTimer::requestStartTime();
    }

    /**
     * get current memory usage
     *
     * @return int number of bytes ram currently in use. 0 if memory_get_usage() is not available.
     * @deprecated Use DebugMemory::getCurrent() instead.
     */
    public static function getMemoryUse()
    {
        return DebugMemory::getCurrent();
    }

    /**
     * Get peak memory use
     *
     * @return int peak memory use (in bytes). Returns 0 if memory_get_peak_usage() is not available
     * @deprecated Use DebugMemory::getPeak() instead.
     */
    public static function getPeakMemoryUse()
    {
        return DebugMemory::getPeak();
    }

    /**
     * Stores a memory point in the internal tracker.
     * Takes a optional message name which can be used to identify the memory point.
     * If no message is supplied a debug_backtrace will be done to identifty the memory point.
     * If you don't have memory_get_xx methods this will not work.
     *
     * @param string $message Message to identify this memory point.
     * @return bool
     * @deprecated Use DebugMemory::getAll() instead.
     */
    public static function setMemoryPoint($message = null)
    {
        return DebugMemory::record($message);
    }

    /**
     * Get all the stored memory points
     *
     * @param bool $clear Whether you want to clear the memory points as well. Defaults to false.
     * @return array Array of memory marks stored so far.
     * @deprecated Use DebugMemory::getAll() instead.
     */
    public static function getMemoryPoints($clear = false)
    {
        return DebugMemory::getAll($clear);
    }

    /**
     * Clear out any existing memory points
     *
     * @return void
     * @deprecated Use DebugMemory::clear() instead.
     */
    public static function clearMemoryPoints()
    {
        DebugMemory::clear();
    }

    /**
     * Create a FirePHP error message
     *
     * @param array $data Data of the error.
     * @param array $links Links for the error (unused).
     * @return void
     */
    public static function fireError($data, $links)
    {
        $name = $data['error'] . ' - ' . $data['description'];
        $message = "{$data['error']} {$data['code']} {$data['description']} on line: {$data['line']} in file: {$data['file']}";
        FireCake::group($name);
        FireCake::error($message, $name);
        if (isset($data['context'])) {
            FireCake::log($data['context'], 'Context');
        }
        if (isset($data['trace'])) {
            FireCake::log(preg_split('/[\r\n]+/', $data['trace']), 'Trace');
        }
        FireCake::groupEnd();
    }
}

DebugKitDebugger::getInstance('DebugKitDebugger');
Debugger::addFormat('fb', ['callback' => 'DebugKitDebugger::fireError']);
