<?php
mb_internal_encoding( "UTF-8" );
require_once 'class.avtotoservice.php';
require_once 'class.soapworker.php';
require_once 'class.loger.php';

//инициализация логера
$objLoger = new Loger();
$objLoger->set_dir_file( 'log' );
$objLoger->set_name_file( 'main.log' );
$objLoger->set_level( Loger::LEVEL_DEBUG );
$objLoger->set_echo_to_html( true );

$time_start = microtime( true );

$service = new AvtoToService();
$service->set_max_time_response( 5 );
$service->set_php_path('php');
//устанавливаем флаг "целостности данных" (см. описание в классе)
$service->set_data_integrity( false );

//добавление воркеров
$wsdl_uri = 'http://www.avtoto.ru/?soap_server_test=get_wsdl&server_id=';
//параметры для запроса
$params = array();
for ($i = 0; $i < 10; $i++)
{
	$service->add_soap_client_to_pool( new SoapWorker( $wsdl_uri . $i, $params ) );
}

//эмуляция ошибки
//$service->add_soap_client_to_pool( new SoapWorker( 'http://ddo.ddo/' . $i, $params ) );
//получение данных с avtoto.ru
$data = $service->search_parts();

if ($service->get_errors())
{
	print_r( $service->get_errors() );
}
if (is_array( $data ) && count( $data ))
{
	//работа с полученными данными
	print_r( $data );
}
printf( "Done for %.2f seconds" . PHP_EOL, microtime( true ) - $time_start );
?>
