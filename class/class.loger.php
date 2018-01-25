<?php

/** * **********************************************************
 * class.loger.php Класс для логирования событий
 * @author С.А. Афанасьев
 */
class Loger
{
	
	const LEVEL_NULL = 0;
	const LEVEL_INFO = 1;
	const LEVEL_WARNING = 2;
	const LEVEL_ERROR = 3;
	const LEVEL_FATAL = 4;
	const LEVEL_DEBUG = 5;
	
	/**
	 * Общее количество сообщений
	 * @var int 
	 */
	static protected $count_message = 0;
	/**
	 * Количество сообщений уровня INFO
	 * @var int 
	 */
	static protected $count_info = 0;
	/**
	 * Количество сообщений уровня WARNING
	 * @var int 
	 */
	static protected $count_warning = 0;
	/**
	 * Количество сообщений уровня ERROR
	 * @var int 
	 */
	static protected $count_error = 0;
	/**
	 * Количество сообщений уровня FATAL
	 * @var int 
	 */
	static protected $count_fatal = 0;
	/**
	 * Количество сообщений уровня DEBUG
	 * @var int 
	 */
	static protected $count_debug = 0;
	
	/**
	 * Имя файла лога
	 * @var string 
	 */
	protected $name_log_file = "loger.log";
	/**
	 * Директория для сохранения файлов лога
	 * @var string 
	 */
	protected $dir_file = "";
	
	/**
	 * Максимальный размер файла лога (МБ)
	 * @var int 
	 */
	protected $max_file_zise_MB = 5;

	/**
	 * массив с данными логирований
	 * @var array 
	 */
	static public $data = array();
	
	const SIZE_KB = 1024;
	const SIZE_MB = 1048576;
	
	/**
	 * Время запуска скрипта
	 * @var float 
	 */
	static protected $time_start;
	/**
	 * Флаг. Нужно ли выводить лог в stdout
	 * @var boolean 
	 */
	protected $echo_to_html = true;
	
	/**
	 * Текущий уровень лога
	 * @var int 
	 */
	static protected $level;

	/**
	 * идентификатор
	 * @var string 
	 */
	private $id;

	
	/**
	 * конструктор класса
	 */	
	public function __construct()
	{
		$this->id = $this->get_id();
		$this->set_level( self::LEVEL_INFO );
		$this->set_string( "script $this->id start" );
		self::$time_start = microtime( true );
	}
	
	/**
	 * Устанавливает флаг. выводить лог в stdout
	 * @param boolean $tr
	 */
			
	public function set_echo_to_html( $tr = true )
	{
		$this->echo_to_html =$tr?true:false;
	}
	
	
	/**
	 * устанавливает уровень логирования
	 * @param int $level
	 */
	public function set_level( $level )
	{
		Loger::$level = $level;
	}

	/**
	 * Заносит строку в массив лога
	 * @param string $str 
	 */
	static public function set_string( $str, $level = self::LEVEL_INFO )
	{
		if ($level > Loger::$level)
			return false;
		Loger::$data[] = array('level' => $level, 'str' => $str, 'time' => time(), 'mtime' => microtime( true ));
	}

	/**
	 * возвращает записи лога
	 * @return array
	 */
	static public function get_data()
	{
		return Loger::$data;
	}
	
	/**
	 * возвращает время старта скрипта
	 * @return float
	 */
	static public function get_time_start()
	{
		return Loger::$time_start;
	}
	/**
	 * выводит лог в stdout
	 * @param string $data
	 */
	protected function echo_to_html( $data )
	{
		echo '<pre>' . $data . '</pre>';
	}
	/**
	 * возвращает время, прошедшее со старта скрипта
	 * @return float
	 */
	static public function get_time()
	{
		return microtime( true ) - self::$time_start;
	}

	/**
	 * подготовливает строку для записи в файл лога
	 * @param array $data массив с данными
	 * @return string 
	 */
	protected function prepear_data()
	{
		$this->set_string( sprintf("script %s stop time %.2f sec",$this->id,self::get_time()));
		$data = Loger::$data;

		$str = '';
		$level_title = array(
			'NULL',
			'INFO',
			'WARNING',
			'ERROR',
			'FATAL',
			'DEBUG'
		);
		if (is_array( $data ) && count( $data ) > 0)
		{
			foreach ($data as $item)
			{
				if ($item['level'] > Loger::$level)
					continue;
				$str .= sprintf( "%s\t%-7s\t%s", date( "d.m.Y H:i:s", $item['time'] ), $level_title[$item['level']], $item['str'] ) . PHP_EOL;
			}
		}
		if ($this->echo_to_html)
		{
			$this->echo_to_html( $str );
		}
		return $str;
	}

	/**
	 * Возвращает количество сообщений
	 * @param int $level уровень лога
	 * @return int
	 */
	static public function get_count( $level = false )
	{
		$count = 0;
		switch ($level)
		{
			case self::LEVEL_DEBUG:
				$count = self::$count_debug;
				break;
			case self::LEVEL_ERROR:
				$count = self::$count_error;
				break;
			case self::LEVEL_FATAL:
				$count = self::$count_fatal;
				break;
			case self::LEVEL_INFO:
				$count = self::$count_info;
				break;
			case self::LEVEL_WARNING:
				$count = self::$count_warning;
				break;
			default :
				$count = count( self::$data );
		}
		return $count;
	}

	/**
	 * генерирует идентификатор 
	 * @return int 
	 */
	private function get_id()
	{
		return substr( md5( rand( 1, 100000 ) . "soll22" ), 4, 12 );
	}
	
	/**
	 * заносит строку в лог (уровень INFO)
	 * @param string $str
	 */
	static public function set_info( $str )
	{
		Loger::set_string( $str, Loger::LEVEL_INFO );
		self::$count_info++;
	}
	/**
	 * заносит строку в лог (уровень WARNING)
	 * @param string $str
	 */
	static public function set_warning( $str )
	{
		Loger::set_string( $str, Loger::LEVEL_WARNING );
		self::$count_warning++;
	}
	/**
	 * заносит строку в лог (уровень ERROR)
	 * @param string $str
	 */
	static public function set_error( $str )
	{
		Loger::set_string( $str, Loger::LEVEL_ERROR );
		self::$count_error++;
	}
	/**
	 * заносит строку в лог (уровень FATAL)
	 * @param string $str
	 */
	static public function set_fatal( $str )
	{
		Loger::set_string( $str, Loger::LEVEL_FATAL );
		self::$count_fatal++;
	}
	/**
	 * заносит строку в лог (уровень DEBUG)
	 * @param string $str
	 */
	static public function set_debug( $str )
	{
		Loger::set_string( $str, Loger::LEVEL_DEBUG );
		self::$count_debug++;
	}
	/**
	 * устанавливает максимальный размер файла лога
	 * @param int $value
	 */
	public function set_max_file_size_MB( $value )
	{
		if ((int) $value > 0)
			$this->max_file_zise_MB = (int) $value;
	}
	
	/**
	 * проверяет размер файла лога, если размер больше чем установленный лимит
	 * архиварует файл лога
	 * @param string $fileName
	 * @return boolean
	 */
	protected function check_size_log_file( $fileName )
	{
		if (filesize( $fileName ) >= self::SIZE_MB * $this->max_file_zise_MB)
		{
			$fileZip = $this->get_actual_zip_file();
			$zip = new ZipArchive();
			if ($zip->open( $fileZip, ZipArchive::CREATE ) !== TRUE)
			{
				return false;
			}
			$zip->addFromString( "loger.txt", file_get_contents( $fileName ) );
			//$res = $zip->addFile($fileName);
			$zip->close();
			unlink( $fileName );
		}
	}
	/**
	 * генерирует имя файла архива
	 * @return string
	 */
	protected function get_actual_zip_file()
	{
		$i = 0;
		do
		{
			$syfix = "";
			if ($i > 0)
			{
				$syfix = "-" . $i;
			}
			$fileZip = $this->dir_file . $this->name_log_file . '-' . date( 'Y-m-d' ) . $syfix . '.zip';
			$i++;
		} while (is_file( $fileZip ));
		return $fileZip;
	}
	
	/**
	 * Устанавливает директория для сохранения файлов лога
	 * @param string $value
	 */
	public function set_dir_file( $value )
	{
		if (!preg_match( '/\/$/iu', $value ))
			$value .= '/';
		$this->dir_file = __DIR__ . '/' . $value;
	}
	
	/**
	 * Устанавливает имя файла лога
	 * @param string $value
	 */
	public function set_name_file( $value )
	{
		$this->name_log_file = $value;
	}

	/**
	 * сохраняет файл лога
	 * @param string $data
	 */
	protected function save_file( $data )
	{
		if ($data)
		{
			$filename = $this->dir_file . $this->name_log_file;
			if (is_file( $filename ))
				$this->check_size_log_file( $filename );
			file_put_contents( $filename, $data, FILE_APPEND );
		}
	}

	/**
	 *  деструктор. Подгатавливает лог, записывает файл лога
	 */
	public function __destruct()
	{
		$data = $this->prepear_data();
		$this->save_file( $data );
	}

}

?>
