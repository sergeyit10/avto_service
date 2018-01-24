<?php
$base_decode = base64_decode( $argv[1] );
$arg = json_decode( $base_decode, true );
$wsdl = '';
$errors = array();
$params = array();
$data = false;
if (isset( $arg['wsdl'] ) && !empty( $arg['wsdl'] ))
{
	$wsdl = $arg['wsdl'];
}
else
{
	$errors[] = 'Неверный wsdl';
}
if (isset( $arg['param'] ))
{
	$params = $arg['param'];
}

if (!count( $errors ))
{
	try{
		$client = new SoapClient( $wsdl );
	
	
		if ($result = $client->SearchParts( $params ))
		{
			$data = json_encode( $result );
		}
		else
		{
			$errors[] = 'server not answed';
		}
	} catch (SoapFault $ex) {
		$errors[] = 'error wsdl valid';
	}
	
}

if (count($errors))
{
	$answ['Info']['Errors'] = $errors;
	$data = json_encode( $answ );
}

//echo ( $data );
echo base64_encode( $data );
?>
