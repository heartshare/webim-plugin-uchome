<?php
//error_reporting(0);
include_once '..' . DIRECTORY_SEPARATOR . 'config.php';
define('IM_ROOT', dirname(__FILE__).DIRECTORY_SEPARATOR);
include_once(dirname(dirname(IM_ROOT)).DIRECTORY_SEPARATOR.'common.php');
include_once(IM_ROOT . "json.php");


function user_pic($uid) {
    if( !function_exists('avatar') )
        return UC_API.'/avatar.php?uid='.$uid.'&size=small';
    return avatar($uid,"small",true);
}

function id_to_name($id) {
    global $_SGLOBAL;
    $query=$_SGLOBAL['db']-> query("SELECT username FROM ".tname('space')." WHERE uid =$id");
    $value = $_SGLOBAL['db']->fetch_array($query);
    return $value['username'];
}

function name_to_id($name) {
    global $_SGLOBAL;
    $query=$_SGLOBAL['db']-> query("SELECT uid FROM ".tname('space')." WHERE username = '$name' ");
    $value = $_SGLOBAL['db']->fetch_array($query);
    return $value['uid'];
}

function names_to_ids($names) {
    $names_ary=ids_array($names);
    foreach($names_ary as $n) {
        $ids[]=name_to_id($n);
    }
    return join(",",$ids);
}
function ids_to_names($ids) {
    $ids_ary=ids_array($ids);
    foreach($ids_ary as $id) {
        $names[]=id_to_name($id);
    }
    return join(",",$names);
}


function _iconv($s,$t,$data) {
    if( function_exists('iconv') ) {
        return iconv($s,$t,$data);
    }else {
        require_once 'chinese.class.php';
        $chs = new Chinese($s,$t);
        return $chs->convert($data);
    }
}
if( !function_exists('json_encode') ) {
    function json_encode($data) {
        $json = new Services_JSON();
        return( $json->encode($data) );
    }
}

// Future-friendly json_decode
if( !function_exists('json_decode') ) {
    function json_decode($data) {
        $json = new Services_JSON();
        return( $json->decode($data) );
    }
}
function g($key = '') {
    return $key === '' ? $_GET : (isset($_GET[$key]) ? $_GET[$key] : null);
}

function p($key = '') {
    return $key === '' ? $_POST : (isset($_POST[$key]) ? $_POST[$key] : null);
}

function gp($key = '',$def = null) {
    $v = g($key);
    if(is_null($v)) {
        $v = p($key);
    }
    if(is_null($v)) {
        $v = $def;
    }
    return $v;
}

function nick($sp) {
    global $_IMC;
    //return $sp{$_IMC['buddy_name']};
    $_nick=(!$_IMC['show_realname']||empty($sp['name'])) ? $sp['username'] : $sp['name'];
    return to_unicode(to_utf8(($_nick)));
}

function to_utf8($s) {
    global $_SC;
    if($_SC['charset'] == 'utf-8') {
        return $s;
    } else {
        return  _iconv($_SC['charset'],'utf-8',$s);
    }
}

function from_utf8($s) {
    global $_SC;
    if($_SC['charset'] == 'utf-8') {
        return $s;
    } else {
        return  _iconv('utf-8',$_SC['charset'],$s);
    }
}

function to_unicode($s) {
    return preg_replace("/^\"(.*)\"$/","$1",json_encode($s));
}
function ids_array($ids) {
    return ($ids===NULL || $ids==="") ? array() : (is_array($ids) ? array_unique($ids) : array_unique(split(",", $ids)));
}
function ids_except($id, $ids) {
    if(in_array($id, $ids)) {
        array_splice($ids, array_search($id, $ids), 1);
    }
    return $ids;
}
function im_table($name) {
    return "`webim_".$name."`";
}

function im_tname($name) {
//     return "`webim_".$name."`";
    return UC_DBTABLEPRE."webim_".$name;
}

$is_login = false;
if(empty($_SGLOBAL['supe_uid'])) {
    $is_login = false;
} else {
    $is_login = true;
    $space = getspace($_SGLOBAL['supe_uid']);
}
$groups = getfriendgroup();

function build_buddies($buddies) {
    $_buddies = array();
    foreach($buddies as $name=>$show)
        $_buddies[]=array('id'=>$name,'show'=>$show,'need_reload'=>true,'presence'=>"online");
    return $_buddies;
}

//$ids="licangcai,qiukh"
function find_buddy($ids) {
    global $_SGLOBAL,$_IMC,$space, $groups;
    $ids = ids_array($ids);
    $ids = ids_except($space['username'], $ids);
    if(empty($ids))return array();
    $ids = join("','", $ids);
    $buddies = array();
    $q="SELECT main.uid, main.username, main.name, f.gid, f.fuid FROM "
            .tname('space')
            ." main LEFT OUTER JOIN "
            .tname('friend')
            ." f ON f.uid = '$space[uid]' AND main.uid = f.fuid WHERE main.username IN ('$ids')";
    $query = $_SGLOBAL['db']-> query($q);
    while ($value = $_SGLOBAL['db']->fetch_array($query)) {
//                realname_set($value['uid'], to_utf8($value['username']));
        $id = $value['username'];
        $nick = nick($value);
        if(empty($value['fuid'])) {
            $group = "stranger";
        }else {
            $gid = $value['gid'];
            $group = (empty($gid) || empty($groups[$gid])) ? "friend" : $groups[$gid];
        }
        $buddies[]=array('id'=>$id,
                'nick'=> to_utf8($nick),
                'pic_url' =>user_pic($value['uid']),
                'status'=>'' ,
                'status_time'=>'',
                'url'=>'space.php?uid='.$value['uid'],
                'group'=> $group,
                'default_pic_url' => UC_API.'/images/noavatar_small.gif');
    }
    return $buddies;
}

function find_room() {
    global $_SGLOBAL,$_IMC,$space;
    $rooms = array();
    //uchome_mtag table
    $query = $_SGLOBAL['db']->query("SELECT t.tagid, t.membernum, t.tagname, t.pic
		FROM ".tname('tagspace')." main
		LEFT JOIN ".tname('mtag')." t ON t.tagid = main.tagid
		WHERE main.uid = '$space[uid]'");
    while ($value = $_SGLOBAL['db']->fetch_array($query)) {
        $tagid = $value['tagid'];
        $id = (string)($_IMC['room_id_pre'] + $tagid);
        $eid = 'channel:'.$id.'@'.$_IMC['domain'];
        $tagname = $value['tagname'];
        $pic = empty($value['pic']) ? 'image/nologo.jpg' : $value['pic'];
        $rooms[$id]=array('id'=>$id,'nick'=> to_utf8($tagname), 'pic_url'=>$pic, 'status'=>'','status_time'=>'', 'all_count' => $value['membernum'], 'url'=>'space.php?do=mtag&tagid='.$tagid);
    }
    return $rooms;
}


function find_new_message() {
    global $_SGLOBAL,$_IMC,$space;
    $uname = $space['username'];
    $messages = array();
    $ids = array();
    $_SGLOBAL['db']->query("SET NAMES " . UC_DBCHARSET);
    $query = $_SGLOBAL['db']->query("SELECT * FROM "
                            .im_tname('histories')
                            ." WHERE `to`='$uname' and send = 0 ORDER BY timestamp DESC LIMIT 100");
    while ($value = $_SGLOBAL['db']->fetch_array($query)) {
        array_unshift($messages,array('to'=>$value['to'],
                                      'nick'=>$value['nick'],
                                      'from'=>$value['from'],
                                      'style'=>$value['style'],
                                      'body'=>to_utf8($value['body']),
                                      'timestamp'=>$value['timestamp'],
                                      'type' =>$value['type']));
    }
    return $messages;
}

function new_message_to_histroy() {
    global $_SGLOBAL,$_IMC,$space;
    $uname = $space['username'];
//    var_dump("UPDATE ".im_tname('histories')." SET send = 1 WHERE `to`='$uname' AND send = 0");
    $_SGLOBAL['db']->query("UPDATE ".im_tname('histories')." SET send = 1 WHERE `to`='$uname' AND send = 0");
}

function find_history($ids,$type="unicast") {
    global $_SGLOBAL,$_IMC,$space;
    $_SGLOBAL['db']->query("SET NAMES " . UC_DBCHARSET);
    $uname= $space['username'];
    $histories = array();
    $ids = ids_array($ids);
    if($ids===NULL)return array();

    for($i=0;$i<count($ids);$i++) {
        $id = $ids[$i];
        $list = array();
        if($type == 'broadcast') {
            $query = $_SGLOBAL['db']->query("SELECT * FROM ".im_tname('histories') .
                    " WHERE (`type`='broadcast') and send = 1 ORDER BY timestamp DESC LIMIT 30");
            while ($value = $_SGLOBAL['db']->fetch_array($query)) {
                array_unshift($list,
                        array('to'=>$value['to'],
                        'from'=>$value['from'],
                        'style'=>$value['style'],
                        'body'=>to_utf8($value['body']),
                        'timestamp'=>$value['timestamp'],
                        'type' =>$value['type'], 'new' => 0));
            }
        }
        //get group message
        else if($type=='multicast') {
            $q="SELECT * FROM ".im_tname('histories')
                    . " WHERE (`to`='$id') AND (`type`='multicast') AND send = 1 ORDER BY timestamp DESC LIMIT 30";
            $query = $_SGLOBAL['db']->query($q);
            while ($value = $_SGLOBAL['db']->fetch_array($query)) {
                array_unshift($list,
                        array('to'=>$value['to'],
                        'from'=>$value['from'],
                        'style'=>$value['style'],
                        'body'=>to_utf8($value['body']),
                        'timestamp'=>$value['timestamp'],
                        'type' =>$value['type'],
                        'new' => 0));
            }
        }
        //get personal message
        else {
            $q=  "SELECT main.* FROM "
            . im_tname('histories')
            . " main WHERE (`send`=1) AND ((`to`='$id' AND `from`='$uname' AND `fromdel` != 1) or (`from`='$id' AND `to`='$uname' AND `todel` != 1))  ORDER BY timestamp DESC LIMIT 30";
            $query = $_SGLOBAL['db']->query($q);
            while ($value = $_SGLOBAL['db']->fetch_array($query)) {
                array_unshift($list,
                        array('to'=>$value['to'],
                        'nick'=>$value['nick'],
                        'from'=>$value['from'],
                        'style'=>$value['style'],
                        'body'=>to_utf8($value['body']),
                        'type' => $value['type'],
                        'timestamp'=>$value['timestamp']));
            }
        }

    }
    return $list;
}

function setting() {
    global $_SGLOBAL,$_IMC,$space;
    if(!empty($_SGLOBAL['supe_uid'])) {
        $setting  = $_SGLOBAL['db']->fetch_array($_SGLOBAL['db']->query("SELECT * FROM ".im_tname('settings')." WHERE uid='$_SGLOBAL[supe_uid]'"));
        if(empty($setting)) {
            $setting = array('uid'=>$space['uid'],'web'=>"");
            $_SGLOBAL['db']->query("INSERT INTO ".im_tname('settings')." (uid,web) VALUES ($_SGLOBAL[supe_uid],'')");
        }
        $setting = $setting["web"];
    }
    return json_decode(empty($setting) ? "{}" : $setting);
}
if(!empty($_SCONFIG['uc_dir'])&& (substr($_SCONFIG['uc_dir'],0,2)=='./'||substr($_SCONFIG['uc_dir'],0,3)=='../'))
    $_SCONFIG['uc_dir']= '../'.$_SCONFIG['uc_dir'];
?>
