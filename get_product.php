<?php

mb_internal_encoding("UTF-8");

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("HTTP/1.1 404 Not Found");
    echo '404 Not Found';
    exit();
}

function __autoload($class) {
    $class = strtolower($class);
    if (file_exists('class/class.' . $class . '.php')) {
        require_once 'class/class.' . $class . '.php';
    } else {
        throw new Exception('Class not found: ' . $class);
    }
}

$response = array();

//инициализация логера
$objLoger = new Loger();
$objLoger->set_dir_file('../log');
$objLoger->set_name_file('main.log');
$objLoger->set_level(Loger::LEVEL_DEBUG);
$objLoger->set_echo_to_html(false);

$time_start = microtime(true);
$params = array();
$errors = array();
if (isset($_POST['title'])) {
    $title = trim(strip_tags($_POST['title']));
    if(empty($title)){
        $errors[] = 'Поле название товара не заполнено ';
    }elseif(mb_strlen($title) <= 50){
        $errors[] = 'Поле название товара не должно содержать больше 50 символов ';
    }else{
        $params['title'] = $title;
    }
} else {
    $errors[] = 'Поле поиска не заполнено';
}


$service = new AvtoToService();
$service->set_max_time_response(10);
$service->set_php_path('php');
//устанавливаем флаг "целостности данных" (см. описание в классе)
$service->set_data_integrity(false);

//добавление воркеров
$wsdl_uri = 'http://www.avtoto.ru/?soap_server_test=get_wsdl&server_id=';
//параметры для запроса

for ($i = 0; $i < 10; $i++) {
    $service->add_soap_client_to_pool(new SoapWorker($i, $wsdl_uri . $i, $params));
}

//эмуляция ошибки
//$service->add_soap_client_to_pool( new SoapWorker( 'http://ddo.ddo/' . $i, $params ) );
//получение данных с avtoto.ru
$servers = $service->search_parts();

if (is_array($servers) && count($servers)) {
    //работа с полученными данными
    $template = new XTemplate('templates/server_result.xtpl');
    $error_info = true;
    foreach ($servers as $id => $server) {
        if (!count($server['errors']) && count($server['items'])) {
            $error_info = false;
            $template->assign('SERVER', array(
                'ID' => $id,
                'TIME' => $server['time_work'],
                'COUNT_ROW' => count($server['items']),
            ));
            //сортируем товары по цене по возрастанию
            usort($server['items'], function($a, $b) {
                return ($a['Price'] < $b['Price']) ? -1 : 1;
            });
            foreach ($server['items'] as $item) {
                $template->assign('PRODUCT', array(
                    'ID' => $item['Id'],
                    'NAME' => $item['Name'],
                    'PRICE' => number_format($item['Price'], 2, '.', ' '),
                    'DELIV' => $item['Deliv'],
                ));
                $template->parse('main.server.row');
            }

            $template->parse('main.server');
        } else {
            //запись ошибок в лог
            foreach ($server['errors'] as $error) {
                Loger::set_error($error);
            }
        }
    }
    if ($error_info) {
        $template->parse('main.error');
    }
    $template->parse('main');
    $response['body'] = $template->text();
}

if (!$response['body']) {
    $response['errors'][] = 'Товары не найдены';
}
header('Content-Type: application/json');
echo json_encode($response);
?>
