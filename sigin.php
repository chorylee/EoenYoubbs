<?php
define('IN_SAESPOT', 1);
define('CURRENT_DIR', pathinfo(__FILE__, PATHINFO_DIRNAME));

include(CURRENT_DIR . '/config.php');
include(CURRENT_DIR . '/common.php');
include(CURRENT_DIR . '/include/avatars/avatars.php');

if($cur_user){
    header('location: /');
    exit;
}else{
    if($options['close_register']){
        header('location: /login');
        exit;
    }
}

$errors = array();
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(empty($_SERVER['HTTP_REFERER']) || $_POST['formhash'] != formhash() || preg_replace("/https?:\/\/([^\:\/]+).*/i", "\\1", $_SERVER['HTTP_REFERER']) !== preg_replace("/([^\:]+).*/", "\\1", $_SERVER['HTTP_HOST'])) {
    	exit('403: unknown referer.');
    }
    
    $name = addslashes(strtolower(trim($_POST["name"])));
    $pw = addslashes(trim($_POST["pw"]));
    $pw2 = addslashes(trim($_POST["pw2"]));
    $seccode = intval(trim($_POST["seccode"]));
	
	$Avatar = new Md\MDAvatars($name, 100);
	
    if($name && $pw && $pw2 && $seccode){
        if($pw === $pw2){
            if(strlen($name)<21 && strlen($pw)<32){
                //检测字符
                if(preg_match('/^[a-zA-Z0-9\x80-\xff]{4,20}$/i', $name)){
                    if(preg_match('/^[0-9]{4,20}$/', $name)){
                        $errors[] = '名字不能全为数字';
                    }else{
                        error_reporting(0);
                        session_start();
                        if($seccode === intval($_SESSION['code'])){
                            $db_user = $DBS->fetch_one_array("SELECT id FROM yunbbs_users WHERE name='".$name."' LIMIT 1");
                            if(!$db_user){
                                //正常
                            }else{
                                $errors[] = '这名字太火了，已经被抢注了，换一个吧！';
                            }
                        }else{
                            $errors[] = '验证码输入不对';
                        }
                    }
                }else{
                    $errors[] = '名字 太长 或 太短 或 包含非法字符';
                }
            }else{
                $errors[] = '用户名 或 密码 太长了';
            }
        }else{
            $errors[] = '密码、重复密码 输入不一致'; 
        }
    }else{
       $errors[] = '用户名、密码、重复密码、验证码 必填'; 
    }
    ////
    if(!$errors){
        $pwmd5 = encode_password($pw, $timestamp);
        
        if($options['register_review']){
            $flag = 1;
        }else{
            $flag = 5;
        }
        $DBS->query("INSERT INTO yunbbs_users (id,name,flag,password,regtime,logintime) VALUES (null,'$name', '$flag', '$pwmd5', $timestamp, '$timestamp')");
        $new_uid = $DBS->insert_id();
        if($new_uid == 1){
            $DBS->unbuffered_query("UPDATE yunbbs_users SET flag = '99' WHERE id='1'");
        }
        
        $cache->clear('site_infos');
        //设置cookie
        $db_ucode = md5($new_uid.$pwmd5.$timestamp.'00');
        $cur_uid = $new_uid;
		
		//设置一个默认用户头像
		$Avatar->Save(CURRENT_DIR .'/avatar/large/'.$cur_uid.'.png', 73);
		$Avatar->Save(CURRENT_DIR .'/avatar/normal/'.$cur_uid.'.png', 48);
		$Avatar->Save(CURRENT_DIR .'/avatar/mini/'.$cur_uid.'.png', 24);
		$DBS->unbuffered_query("UPDATE yunbbs_users SET avatar = '$cur_uid' WHERE id='$cur_uid'");

        setcookie("cur_uid", $cur_uid, $timestamp+ 86400 * 365, '/');
        setcookie("cur_uname", $name, $timestamp+86400 * 365, '/');
        setcookie("cur_ucode", $db_ucode, $timestamp+86400 * 365, '/');
        header('location: /');
        exit;
    }
}

// 页面变量
$title = '注 册';

$pagefile = CURRENT_DIR . '/templates/default/'.$tpl.'sigin_login.php';

include(CURRENT_DIR . '/templates/default/'.$tpl.'layout.php');

?>
