<?php

	require_once("core/Manager.php");
	
	$mng = new Manager();
	$response = $mng->digest();
	
	echo json_encode($response);


