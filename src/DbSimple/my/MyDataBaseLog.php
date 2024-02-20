<?php

/**
 * Для вывода лога запросов к БД (использовать совместно с DbSimple)
 *
 * @author Zmi
 */
class MyDataBaseLog
{
    private static $dbLog        = [];
    private static $pFuncOnError = null;

    /**
     * Устанавливает функцию на ошибку запроса
     */
    public static function setFuncOnError($func)
    {
        self::$pFuncOnError = $func;
    }

    /**
     * Определить функцию на получение ошибки в $info наиболее полезные параметры [query, message, code, context]
     */
    public static function error($message, $info)
    {
        if (!error_reporting()) {
            return;
        }

        if (!empty(self::$pFuncOnError)) {
            call_user_func(self::$pFuncOnError, $message, $info);
        }
    }

    /**
     * Функция логирования запроса
     */
    public static function log($db, $sql)
    {
        $caller          = $db->findLibraryCaller();
        $log             = [];
        $log['q']        = $sql;
        $log['file']     = $caller['file'];
        $log['line']     = $caller['line'];
        $log['time']     = $caller['object']->_statistics['time'];
        $log['count']    = $caller['object']->_statistics['count'];
        $log['error']    = $caller['object']->error;
        $log['errorMsg'] = $caller['object']->errmsg;

        self::$dbLog[] = $log;
    }

    /**
     * Показывает лог-табличку
     */
    public static function render()
    {
        $sameLog = [];
        foreach (self::$dbLog as $log) {
            $query = $log['q'];
            if (strpos($query, ' ms; returned') !== false) {
                continue;
            }

            $sameLog[$query] = isset($sameLog[$query]) ? intval($sameLog[$query] + 1) : 1;
        }
        arsort($sameLog);

        $maxLen = 125;

        ob_start();
        ?>
        <div id="iDbLogger">
            <table cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <th>№</th>
                    <th>Query</th>
                    <th>Time</th>
                </tr>
                <?php
                $totalTime = 0;
                $curElem   = 0;
                for ($i = 0, $j = count(self::$dbLog); $i < $j; $i += 2) {
                    $log1      = self::$dbLog[$i];
                    $defLog    = [
                        'q'        => '',
                        'file'     => '',
                        'line'     => 0,
                        'time'     => 0,
                        'count'    => 0,
                        'error'    => '',
                        'errorMsg' => ''
                    ];
                    $log2      = self::$dbLog[$i + 1] ?? $defLog;
                    $isError   = !empty($log2['error']);
                    $execTime  = $log2['time'] - $log1['time'];
                    $totalTime += $execTime;
                    $color     = $isError ? 'red' : 'green';
                    $error     = $log2['error'];
                    $query     = $log1['q'];
                    $time      = number_format($execTime, 5, '.', ' ');
                    $count     = $log1['count'];
                    ?>
                    <tr>
                        <td class="num" style="width: 50px;"><?= (int)(++$curElem); ?></td>
                        <td style='color: <?= $color ?>'>
                            <pre><?= wordwrap($query, $maxLen, PHP_EOL); ?></pre>
                            <?php if ($isError): ?>
                                <div id='iDataBaseLog_<?= $i ?>'>
                                    <pre><?= wordwrap(print_r($error, true), $maxLen, PHP_EOL); ?></pre>
                                </div>
                            <?php endif ?>
                        </td>
                        <td><?= $time ?></td>
                    </tr>
                    <?php
                    if ($isError) {
                        $i++;
                    }
                }
                ?>
                <tr class="total">
                    <td colspan='2' class="center"><?= $curElem ?> <span>queries</span></td>
                    <td><?= number_format($totalTime, 5, '.', ' '); ?></td>
                </tr>
            </table>
        </div>
        <div style="text-align: right;">
            <a href="javascript:$('#iDbQueryRepeats').toggle();">Query repeats ↓</a>
        </div>
        <table cellpadding="0" cellspacing="0" style="display: none;" id="iDbQueryRepeats">
            <tr>
                <th style="width: 50px;">Repeats</th>
                <th>Query</th>
            </tr>
            <?php foreach ($sameLog as $query => $count): ?>
                <tr>
                    <td class="num"><?= $count ?></td>
                    <td>
                        <pre><?= wordwrap($query, $maxLen, PHP_EOL); ?></pre>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php
        return ob_get_clean();
    }
}
