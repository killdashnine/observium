<?php

@ini_set("session.gc_maxlifetime","0");

session_start();

# Fallback to MySQL auth as default
if (!isset($config['auth_mechanism']))
{
  $config['auth_mechanism'] = "mysql";
}

if (file_exists('includes/authentication/' . $config['auth_mechanism'] . '.inc.php'))
{
  include('includes/authentication/' . $config['auth_mechanism'] . '.inc.php');
}
else
{
  print_error('ERROR: no valid auth_mechanism defined!');
  exit();
}

if ($vars['page'] == "logout" && $_SESSION['authenticated'])
{
  if (auth_can_logout())
  {
    dbInsert(array('user' => $_SESSION['username'], 'address' => $_SERVER["REMOTE_ADDR"], 'result' => 'Logged Out'), 'authlog');
    unset($_SESSION);
    session_destroy();
    setcookie("user_id", NULL, time()+60*60*24*14, "/");
    setcookie("ckey",    NULL, time()+60*60*24*14, "/");
    setcookie("dkey",    NULL, time()+60*60*24*14, "/");
    $auth_message = "Logged Out";
  }
  header('Location: /');
}

$user_unique_id = md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);

if (!$_SESSION['authenticated'] && isset($_GET['username']) && isset($_GET['password']))
{
  $_SESSION['username'] = $_GET['username'];
  $_SESSION['password'] = $_GET['password'];
}
else if (!$_SESSION['authenticated'] && isset($_POST['username']) && isset($_POST['password']))
{
  $_SESSION['username'] = $_POST['username'];
  $_SESSION['password'] = $_POST['password'];
}
else if (!$_SESSION['authenticated'] && isset($_COOKIE['ckey']) && function_exists('mcrypt_decrypt'))
{
  $ckey = dbFetchRow("SELECT * FROM `users_ckeys` WHERE `user_uniq` = ? AND `user_ckey` = ? LIMIT 1",
                          array($user_unique_id, $_COOKIE['ckey']));

  if(is_array($ckey))
  {
    if($ckey['expire'] > time())
    {
      $_SESSION['username']     = $ckey['username'];
      $_SESSION['password']     = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $_COOKIE['dkey'], base64_decode($ckey['user_encpass']), MCRYPT_MODE_ECB);
      $_SESSION['user_ckey_id'] = $ckey['user_ckey_id'];
      $_SESSION['cookie_test']  = TRUE;
    }
  }
}

if($_COOKIE['password']) { setcookie("password",  NULL, time()+60*60*24*14, "/"); }
if($_COOKIE['username']) { setcookie("username",  NULL, time()+60*60*24*14, "/"); }
if($_COOKIE['user_id'] ) { setcookie("user_id",   NULL, time()+60*60*24*14, "/"); }

$auth_success = 0;

if (isset($_SESSION['username']))
{
  if (authenticate($_SESSION['username'],$_SESSION['password']) || (auth_usermanagement() && auth_user_level($_SESSION['origusername']) >= 10))
  {
    $_SESSION['userlevel'] = auth_user_level($_SESSION['username']);
    $_SESSION['user_id'] = auth_user_id($_SESSION['username']);

    if ($_SESSION['cookie_test']) { $_SESSION['cookie_auth']; }
    if (!$_SESSION['authenticated'])
    {
      $_SESSION['authenticated'] = true;
      dbInsert(array('user' => $_SESSION['username'], 'address' => $_SERVER["REMOTE_ADDR"], 'result' => 'Logged In'), 'authlog');
      header("Location: ".$_SERVER['REQUEST_URI']);
    }
    if (isset($_SESSION['user_ckey_id']))
    {
      dbUpdate("UPDATE `users_ckeys` SET `expire` = ? WHERE `users_ckey_id` = ?", array(time()+60*60*24*14, $_SESSION['user_ckey_id']));
      unset($_SESSION['user_ckey_id']);
    } elseif (isset($_POST['remember']) && function_exists('mcrypt_encrypt')) {
      $ckey = md5(strgen());
      $dkey = md5(strgen());
      $encpass = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $dkey, $_SESSION['password'], MCRYPT_MODE_ECB));
      dbInsert(array('user_encpass' => $encpass, 'expire' => time()+60*60*24*14, 'username' => $_SESSION['username'], 'user_uniq' => $user_unique_id, 'user_ckey' => $ckey), 'users_ckeys');
      setcookie("ckey",    $ckey, time()+60*60*24*14, "/");
      setcookie("dkey",    $dkey, time()+60*60*24*14, "/");
      unset($_SESSION['user_ckey_id']);
    }
     $permissions = permissions_cache($_SESSION['user_id']);

     // If we're authing from a cookie, we can unset the password.
     if($_SESSION['cookie_auth'])
     {
       unset($_SESSION['password']);
     }
  }

  elseif (isset($_SESSION['username']))
  {
    $auth_message = "Authentication Failed";
    unset ($_SESSION['authenticated']);
    dbInsert(array('user' => $_SESSION['username'], 'address' => $_SERVER["REMOTE_ADDR"], 'result' => 'Authentication Failure'), 'authlog');
  }
}

?>
