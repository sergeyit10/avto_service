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

$template = new XTemplate( 'templates/service.xtpl' );

$template->parse( 'main' );
$template->out( 'main' );
?>
