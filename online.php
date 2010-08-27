<?php
include_once('common.php');
$friend_ids = ids_array($space['friends']);
$buddy_ids = ids_array(gp("buddy_ids"));//正在聊天的联系人

$new_messages = find_new_message();//查找离线消息
for($i=0;$i<count($new_messages);$i++) {
    $msg_uid = $new_messages[$i]["from"];
    array_push($buddy_ids, $msg_uid);//有离线消息的人
}

//查找群组
$setting = setting();
$block_list = is_array($setting->block_list) ? $setting->block_list : array();
$rooms=rooms();
foreach($rooms as $key => $value) {
    if(in_array($key, $block_list)) {
        $rooms[$key]['blocked'] = true;
    }else
        $room_ids[]=$key;
}

if(!empty($friend_ids)) {
    $ids=join(",",$friend_ids);
    $query = $_SGLOBAL['db']-> query("SELECT username FROM ".tname('space')." WHERE uid IN ($ids)");
    while ($value = $_SGLOBAL['db']->fetch_array($query)) {
        $buddie_ids[] = $value['username'];

    }
}
$buddie_ids=array_unique($buddie_ids);

$im = new WebIM($user, null, $_IMC['domain'], $_IMC['apikey'], $_IMC['host'], $_IMC['port']);
$data = $im->online(implode(",",$buddie_ids), implode(",", $room_ids));
if($data->success) {
    $_rooms=array();
    //Add room online member count.
    foreach($data->rooms as $k => $v) {
        $id = $v->id;
        $rooms[$id]['count'] = $v->count;
        $_rooms[]=$rooms[$id];
    }
  
    $data->rooms = $_rooms;
    $online_buddies=build_buddies($data->buddies);
    $data->buddies=array_merge($online_buddies,buddy($buddy_ids));
    $data->histories=find_history($buddy_ids);
    $data->new_messages=$new_messages;
    echo json_encode($data);
    new_message_to_histroy();
}else {
    header("HTTP/1.0 404 Not Found");
    echo json_encode($data->error_msg);
}
?>
