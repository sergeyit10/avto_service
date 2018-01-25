<?php

$base_decode = base64_decode($argv[1]);
$arg = json_decode($base_decode, true);
$wsdl = '';
$errors = array();
$params = array();
$data = false;
$test = false;
if ($test)
    $arg['wsdl'] = 'http://www.avtoto.ru/?soap_server_test=get_wsdl&server_id=1';
if (isset($arg['wsdl']) && !empty($arg['wsdl'])) {
    $wsdl = $arg['wsdl'];
} else {
    $errors[] = 'Неверный wsdl';
}
if (isset($arg['param'])) {
    $params = $arg['param'];
}

if (!count($errors)) {
    //генерация данных для отладки, что бы не дергать сервера autoto
    if ($test) {
        $sleap_second = rand(1, 3);
        sleep($sleap_second);
        $result = array();
        $result['Parts'] = array();
        for ($i = 0; $i < rand(50, 79); $i++) {
            $result['Parts'][] = array(
                'Id' => $i,
                'Name' => 'Part_' . $i . '_from_server_1',
                'Price' => rand(500, 1500),
                'Deliv' => sprintf('%d-%d', rand(1, 8), rand(8, 16))
            );
        }
        $result['Info'] = array(
            'SearchId' => rand(9999, 99999),
            'Errors' => array(),
            'Logs' => 'Time = ' . rand(2, 12) . ' sec'
        );
        $data = json_encode($result);
    } else {
        //запрос данных с сервера autoto
        try {
            $client = new SoapClient($wsdl);
            if ($result = $client->SearchParts($params)) {
                //print_r ( $result );
                $data = json_encode($result);
            } else {
                $errors[] = 'server not answed';
            }
        } catch (SoapFault $ex) {
            $errors[] = 'error wsdl valid';
        }
    }
}

if (count($errors)) {
    $answ['Info']['Errors'] = $errors;
    $data = json_encode($answ);
}


echo base64_encode($data);
?>
