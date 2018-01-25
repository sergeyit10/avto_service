<?php

mb_internal_encoding( "UTF-8" );

function __autoload( $class )
{
	$class = strtolower( $class );
	if (file_exists( 'class/class.' . $class . '.php' ))
	{
		require_once 'class/class.' . $class . '.php';
	}
	else
	{
		throw new Exception( 'Class not found: ' . $class );
	}
}

//инициализация логера
$objLoger = new Loger();
$objLoger->set_dir_file( '../log' );
$objLoger->set_name_file( 'main.log' );
$objLoger->set_level( Loger::LEVEL_DEBUG );
$objLoger->set_echo_to_html( false );

$time_start = microtime( true );

$service = new AvtoToService();
$service->set_max_time_response( 2 );
$service->set_php_path( 'php' );
//устанавливаем флаг "целостности данных" (см. описание в классе)
$service->set_data_integrity( false );

//добавление воркеров
$wsdl_uri = 'http://www.avtoto.ru/?soap_server_test=get_wsdl&server_id=';
//параметры для запроса
$params = array();
for ($i = 0; $i < 2; $i++)
{
	$service->add_soap_client_to_pool( new SoapWorker( $i, $wsdl_uri . $i, $params ) );
}

//эмуляция ошибки
//$service->add_soap_client_to_pool( new SoapWorker( 'http://ddo.ddo/' . $i, $params ) );
//получение данных с avtoto.ru
$servers = $service->search_parts();

if ($service->get_errors())
{
	//print_r( $service->get_errors() );
}
if (is_array( $service_data ) && count( $service_data ))
{
	//работа с полученными данными
	//print_r( $service_data );
}
$template = new XTemplate( 'templates/service.xtpl' );
$error_info = true;
foreach ($servers as $id => $server)
{
	if (!count( $server['errors'] ) && count( $server['items'] ))
	{
		$error_info = false;
		$template->assign( 'SERVER', array(
			'ID' => $id,
			'TIME' => $server['time_work'],
			'COUNT_ROW' => count( $server['items'] ),
		) );

		usort( $server['items'], function($a, $b)
		{
			return ($a['Price'] < $b['Price']) ? -1 : 1;
		} );
		foreach ($server['items'] as $item)
		{
			$template->assign( 'PRODUCT', array(
				'ID' => $item['Id'],
				'NAME' => $item['Name'],
				'PRICE' => number_format( $item['Price'], 2, '.', ' ' ),
				'DELIV' => $item['Deliv'],
			) );
			$template->parse( 'main.server.row' );
		}

		$template->parse( 'main.server' );
	}
	else
	{
		//TODO: сделать запись ошибок в лог
	}
}
if($error_info){
	$template->parse( 'main.error' );
}
$template->parse( 'main' );
$template->out( 'main' );
?>
