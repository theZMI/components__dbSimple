<?php

/**
 * Класс инициализации баз данных
 *
 * @author Zmi
 */
class Db
{
    private $databases = null;
    public $config = [
        'logDbInfo'  => false, // Логгировать ли все запросы? (по умолчанию посмотреть можно в DebugPanel, внизу сайта)
        'logDbError' => true, // Логгировать запросы в которых произошла ошибка (по умолчанию в dbLogFile складываются)
        'dbLogFile'  => null,

        // Коннекты к базам данных
        'databases'  => [
            'db' => [
                'dsn'        => 'mysqli://root:@localhost/dbName?charset=UTF8', // Подключение к БД в формате DSN-строки
                // Кеш ф-я
                // Перед запросом который в кеш отправиться нужно написать "-- CACHE: 1h 5m 15" затем ENTER. Здесь h/m/цифра это часы/минут/секунды соотвественно
                'pCacheFunc' => ['MyDataBaseCache', 'Cache'],
            ],
        ],
    ];

    // Обработчик ошибок БД
    public static function DbSimpleError($message, $info)
    {
        if (!error_reporting()) {
            return;
        }

        if (stripos($info['query'], "mysql_connect(") !== false) // Не подсоединилась к БД
        {
            exit("Can not connect to database(s)"); // Не работаем дальше с кодом
        }

        if (stripos($info['query'], "mysql_select_db(") !== false) // Не удалось найти БД после подключения к серверу с базами
        {
            exit("Can not select database(s)"); // Не работаем дальше с кодом
        }

        static $fileLogger = null;

        $self = Db::GetInstance();
        $logPath = $self->config['dbLogFile'];

        if ($logPath) {
            if (is_null($fileLogger)) {
                $fileLogger = FileLogger::Create($logPath);
            }
            $fileLogger->Error(
                PHP_EOL .
                "\tquery: " . $info['query'] . PHP_EOL .
                "\tmessage: " . $info['message'] . PHP_EOL .
                "\tcode: " . $info['code'] . PHP_EOL .
                "\tcontext: " . $info['context'] . PHP_EOL .
                PHP_EOL
            );
        } else {
            throw new Exception($info['query']);
        }
    }

    private function __construct($config)
    {
        if (!empty($config)) {
            $this->config = array_merge($this->config, $config);
        }

        // Подключаем модули для работы с DbSimple (не по подгрузится автолоудером)
        $path = dirname(__FILE__) . '/DbSimple/';
        require_once $path . 'Generic.php';
        require_once $path . 'Mysql.php';
        require_once $path . 'Postgresql.php';
        require_once $path . 'my/MyDataBaseLog.php';
        require_once $path . 'my/MyDataBaseCache.php';

        // Собираем все объекты в $o
        $o = new stdClass();
        $dbs = $this->config['dbs'];
        foreach ($dbs as $db => $conn) {
            $dsn       = $conn['dsn'];
            $cacheFunc = isset($conn['pCacheFunc']) ? $conn['pCacheFunc'] : null;

            $o->$db = DbSimple_Generic::connect($dsn);

            if ($this->config['logDbError']) {
                MyDataBaseLog::SetFuncOnError([__CLASS__, 'DbSimpleError']);
                $o->$db->setErrorHandler(['MyDataBaseLog', 'Error']);
            }

            if ($this->config['logDbInfo']) {
                $o->$db->setLogger(['MyDataBaseLog', 'Log']);
            }

            if ($cacheFunc) {
                $o->$db->setCacher($cacheFunc);
            }
        }

        // Регистрируем все базы данных как объект
        $this->databases = $o;
    }

    public static function GetInstance($dbs = null) {
        static $instance = null;
        if (is_null($instance)) {
            $instance = new self($dbs);
        }

        return $instance;
    }

    public function GetDatabases() {
        return $this->databases;
    }
}
