<?php
/**
 *  Сервис поиска товаров на AvtoTo.ru
 *  @author С.А. Афанасьев
 */
class AvtoToService
{

	/**
	 * максимальное время ожидания запросов
	 * @var int 
	 */
	private $max_time_resopnse = 10;

	/**
	 * пул воркеров
	 * @var array 
	 */
	private $soap_worker_pool = array();

	/**
	 * временной интервал опроса воркеров 
	 * @var float 
	 */
	private $check_result_interval = 0.1;
	
	/**
	 * Путь к php
	 * @var string 
	 */
	protected $php_path = 'php';
	
	/**
	 * Ошибки
	 * @var array 
	 */
	private $errors = array();
	
	/**
	 * Флаг. целостность данных
	 * если установлен в true, при возникновении ошибки в одном из процессов
	 * завершает все процессы. не возвращает данные
	 * если false - возвращает все данные, которые смог получить из процессов, игнорирует
	 * ошибки в процессах
	 * @var boolean 
	 */
	private $data_integrity = false;

	/**
	 * Устанавливает время ожидания запросов
	 * @param int $second
	 */
	public function set_max_time_response( $second )
	{
		if ((int) $second > 0)
			$this->max_time_resopnse = (int) $second;
	}

	/**
	 * добавляет воркера в пул
	 * @param SoapWorker $worker объект, который будет запускать дочерний процесс и работать с ним
	 */
	public function add_soap_client_to_pool( SoapWorker $worker )
	{
		$this->soap_worker_pool[] = $worker;
	}

	/**
	 * возвращает ошибки
	 * @return array
	 */
	public function get_errors()
	{
		return $this->errors;
	}
	
	/**
	 * Задает путь к php
	 * @param string $value
	 */
	public function set_php_path($value){
		$this->php_path = $value;
	}
	
	/**
	 * Устанавливает флаг "Целостности данных"
	 * @param boolean $value
	 */
	public function set_data_integrity($value){
		$this->data_integrity = $value ? true : false;
	}

	/**
	 * ищет товары на сервере avtoto
	 * @return boolean|array массив товаров
	 */
	public function search_parts()
	{

		$servers = array();
		if(!count($this->soap_worker_pool)){
			$this->errors[] = 'Воркеры не найдены';
		}
		//запускаем воркеры
		foreach ($this->soap_worker_pool as $soap_worker)
		{
			$soap_worker->set_php_path($this->php_path);
			$soap_worker->start();
			$servers[$soap_worker->get_id()] = array(
				'time_start'=>microtime(1),
				'time_work'=>0,
				'errors'=>array(),
				'items'=>array(),
			);
		}
		$time_start = microtime( true );
        $termenate = false;
		//ожидаем выполнения задания. 
		try
		{
			while (true)
			{
				//проверяем timeout
				if (microtime( true ) - $time_start >= $this->max_time_resopnse)
				{
					$this->errors[] = 'timeout error';
					Loger::set_error( 'timeout error' );
                    $termenate = true;
					break;
				}
				$done = true;
				//опрашиваем воркеров.
				foreach ($this->soap_worker_pool as $soap_worker)
				{
					if ($soap_worker->is_done() && !$soap_worker->is_flush())
					{
						Loger::set_debug( 'worker is done ' . $soap_worker->get_wsdl() );
						$servers[$soap_worker->get_id()]['time_work']=microtime(1)-$time_start;
						if ($data = $soap_worker->get_result())
						{
							$servers[$soap_worker->get_id()]['items']=$data;
						}
						else
						{
							if ($err = $soap_worker->get_errors())
							{
								$servers[$soap_worker->get_id()]['errors']=$err;
								$this->errors = array_merge( $this->errors, $err );
								Loger::set_error( 'Worker error' );
								if($this->data_integrity)
									throw new Exception( 'Worker error' );
							}
						}
						$soap_worker->set_flush( true );
					}
					else
					{
						if (!$soap_worker->is_done())
							$done = false;
					}
				}
				if ($done)
				{
					break;
				}
				usleep( $this->check_result_interval * 1000000 );
			}
			//закрываем соединение 
			foreach ($this->soap_worker_pool as $soap_worker)
			{
				if($termenate)
                    $soap_worker->terminate();
                else
                    $soap_worker->close();
			}
		} 
		catch (Exception $err)
		{
			Loger::set_debug( 'принудительно завершаем работу всех процессов' );
			$this->errors[] = $err->getMessage();
			foreach ($this->soap_worker_pool as $soap_worker)
			{
				$soap_worker->terminate();
			}
		}
		
		return $servers;
	}

}

?>
