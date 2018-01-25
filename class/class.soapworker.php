<?php
/**
 *  Воркер. Создает дочерний процесс для асинхронных запросов к серверу avtoto
 *	@author С.А. Афанасьев
 */
class SoapWorker
{

	/**
	 * потоки ввода\вывода процесса
	 * pipes[0] - stdin
	 * pipes[1] - stdout
	 * pipes[2] - stderr
	 * @var array 
	 */
	protected $pipes;
	/**
	 * идентификатор сервера
	 * @var int 
	 */
	protected $id;
	
	/**
	 * дочерний процесс
	 * @var resource  
	 */
	protected $process;

	/**
	 * URI wsdl
	 * @var string 
	 */
	protected $wsdl;

	/**
	 * Флаг. данные получены
	 * @var boolean 
	 */
	protected $flush = false;

	/**
	 * Флаг. завершена работа
	 * @var boolean 
	 */
	protected $done = false;
	/**
	 * Путь к php
	 * @var string 
	 */
	protected $php_path = 'php';
	/**
	 * Параметры запроса SoapClient
	 * @var array 
	 */
	protected $params;

	/**
	 * Ошибки работы воркера
	 * @var array 
	 */
	protected $errors = array();

	/**
	 * Конструктор класса
	 * @param int $id идентификатор сервера
	 * @param string $wsdl uri wsdl
	 * @param array $params параметры запроса
	 */
	public function __construct($id, $wsdl, array $params = array() )
	{
		$this->id = $id;
		$this->wsdl = $wsdl;
		$this->params = $params;
	}
	
	/**
	 * Задает путь к php
	 * @param string $value
	 */
	public function set_php_path($value){
		$this->php_path = $value;
	}
	/**
	 * Возвращает флаг "работа завершена"
	 * @return boolean $value
	 */
	public function is_done()
	{
		if (!$this->done)
		{
			$meta_info = proc_get_status( $this->process );
			if (!$meta_info['running'])
			{
				$this->done = true;
			}
		}
		return $this->done ? true : false;
	}
	/**
	 * Возвращает идентификатор 
	 * @return int
	 */
	public function get_id(){
		return $this->id;
	}
	/**
	 * Возвращает флаг "данные получены"
	 * @return boolean $value
	 */
	public function is_flush()
	{
		return $this->flush ? true : false;
	}

	/**
	 * Устанавливает флаг "данные получены"
	 * @param boolean $value
	 */
	public function set_flush( $value )
	{
		$this->flush = $value ? true : false;
	}

	/**
	 * Возвращает результат
	 * @return boolean|array
	 */
	public function get_result()
	{
		$data = false;
		if ($contents = $this->stream_read( $this->pipes[1] ))
		{
			$result_arr = json_decode( base64_decode( $contents ), true );
			if (isset( $result_arr['Parts'] ))
			{
				$data = $result_arr['Parts'];
			}
			if (isset( $result_arr['Info']['Logs'] ))
			{
				Loger::set_debug($result_arr['Info']['Logs']);
			}
			if (isset( $result_arr['Info']['Errors'] ) && is_array( $result_arr['Info']['Errors'] ) && count($result_arr['Info']['Errors']))
			{
				foreach($result_arr['Info']['Errors'] as $err){
					Loger::set_error($err);
				}
				$this->errors = array_merge( $this->errors, $result_arr['Info']['Errors'] );
			}
		}else{
			$this->errors[] ="Stream read error";
		}
		return $data;
	}

	/**
	 * Возвращает ошибки
	 * @return array
	 */
	public function get_errors()
	{
		if ($stderr_content = $this->stream_read( $this->pipes[2] ))
		{
			$this->errors[] = $contents;
		}
		return $this->errors;
	}

	/**
	 *  запускает процесс
	 */
	public function start()
	{
		$descriptorspec = array(
			0 => array('pipe', 'r'),
			1 => array('pipe', 'w'),
			2 => array('pipe', 'w'),
		);
		$this->params['wsdl'] = $this->wsdl;
		$args_str = json_encode( $this->params );
		Loger::set_debug('Start process '.$this->wsdl);
		
		$this->process = proc_open( $this->php_path.' soapclient.php ' . base64_encode( $args_str ), $descriptorspec, $this->pipes );
	}

	/**
	 * Закрывает процесс. ожидает его завершение
	 * @return int код завершения процесса
	 */
	public function close()
	{
		foreach ($this->pipes as $pipe)
		{
			if (is_resource( $pipe ))
			{
				fclose( $pipe );
			}
		}
		return proc_close( $this->process );
	}
	/**
	 * Принудительно закрывает процесс
	 * @return boolean 
	 */
	public function terminate()
	{
		foreach ($this->pipes as $pipe)
		{
			if (is_resource( $pipe ))
			{
				fclose( $pipe );
			}
		}
		return proc_terminate( $this->process );
	}
	/**
	 * Возвращает WSDL URI
	 * @return string
	 */
	public function get_wsdl(){
		return $this->wsdl;
	}
	/**
	 * Читает из потока
	 * @param resource $stdout
	 * @return string
	 */
	protected function stream_read( $stdout )
	{
		$contents = '';
		while (!feof( $stdout ))
		{
			$contents .= fread( $stdout, 8192 );
		}
		return $contents;
	}

}

?>
