<?php
$configRoot = '..' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR ;
include_once($configRoot . 'http_client.php');
include_once($configRoot . 'uchome.php');

$ticket = gp('ticket');
$room_id = gp('id');
$nick = gp('nick');
//TODO: should get nick from database
if(!empty($ticket)) {
    $data = array('ticket'=>$ticket, 'domain'=>$_IMC['domain'],'nick'=>$nick, 'apikey'=>$_IMC['apikey'], 'room'=>$room_id, 'endpoint' => $space['uid']);
	$client = new HttpClient($_IMC['host'], $_IMC['port']);
	$client->post('/room/join', $data);
	$pageContents = $client->getContent();
        if($client->status !="200"||empty($pageContents)){

          echo '{"errorMsg":"'.$pageContents.'"}';
	}else{
		echo '{"count":"'.$pageContents.'"}';
	}
}else{
	echo '{"errorMsg":"no ticket"}';
}
?>
