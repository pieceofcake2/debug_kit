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
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Log Panel - Reads log entries made this request.
 */
class LogPanel extends DebugPanel
{
    /**
     * The log engine collecting the log entries of this request, set by DebugKitLog.
     *
     * @var DebugKitLog|null
     */
    public $logger = null;

    /**
     * Constructor - sets up the log listener.
     *
     * @return \LogPanel
     */
    public function __construct()
    {
        parent::__construct();
        $existing = CakeLog::configured();
        if (empty($existing)) {
            CakeLog::config('default', [
                'engine' => 'FileLog',
            ]);
        }
        CakeLog::config('debug_kit_log_panel', [
            'engine' => 'DebugKit.DebugKitLog',
            'panel' => $this,
        ]);
    }

    /**
     * beforeRender Callback
     *
     * @param Controller $controller The controller.
     * @return DebugKitLog|null
     */
    public function beforeRender(Controller $controller)
    {
        $logger = $this->logger;

        return $logger;
    }

    /**
     * Get the summary: number of log entries.
     *
     * @param mixed $content The panel content.
     * @return string
     */
    public function summary($content = null)
    {
        if (!$content instanceof DebugKitLog) {
            return '0';
        }
        $count = 0;
        foreach ($content->logs as $logs) {
            $count += count($logs);
        }

        return (string)$count;
    }
}
