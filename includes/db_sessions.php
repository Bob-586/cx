<?php
function _cxs_open() {
  global $pdo_ses;

  $error_mode = cx_configure::a_get('database', 'PDO_Error');
  if ($error_mode === false) {
    $error_mode = PDO::ERRMODE_EXCEPTION;
  }

  $db_info = cx_configure::get('database');
  $db_socket = (isset($db_info['SOCKET'])) ? $db_info['SOCKET'] : false;
  
  if (! empty($db_socket)) {    
    $db_dsn = $db_info['TYPE'] . ':unix_socket=' . $db_socket . ';dbname=' . $db_info['NAME'] . ';charset=utf8';
  } else {
    $db_dsn = $db_info['TYPE'] . ':host=' . $db_info['HOST'] . ';port=' . $db_info['PORT'] . ';dbname=' . $db_info['NAME'] . ';charset=utf8';
  }

  $options = array(
    PDO::ATTR_PERSISTENT => cx_configure::a_get('database', 'PDO_PERSISTENT'),
    PDO::ATTR_ERRMODE => $error_mode,
    PDO::ATTR_TIMEOUT => cx_configure::a_get('database', 'PDO_TIMEOUT'),
  );

  try {
    $pdo_ses = new PDO($db_dsn, $db_info['USER'], $db_info['PASS'], $options);
  } catch (PDOException $e) {
    return false;
  } catch (Exception $e) {
    return false;
  }
  return true;
}

function _cxs_close() {
  global $pdo_ses;
  $pdo_ses = null;
  return true;
}

function _cxs_read($id) {
  global $pdo_ses;
  
  $session_table = \cx_configure::a_get('security','session_table');
  
  if ($session_table === false) {
    return false;
  }
  
  $sql = "SELECT `data` FROM `{$session_table}` WHERE `id` = :id";
  $stmt = $pdo_ses->prepare($sql);
  $stmt->bindValue(':id', $id, PDO::PARAM_STR);
  $stmt->execute();
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $data = (isset($row['data'])) ? $row['data'] : '';

  $security_level = cx_configure::a_get('security','session_security_level');
  
  if ($security_level === false || empty($data)) {
    return $data;
  } else {
    $enc_sess = new cx\common\crypt();
    $enc_sess->change_security($security_level);
    $enc_sess->set_ciphertext($data);
    $d = $enc_sess->decrypt();
    unset($enc_sess);
    return $d;
  }  
}

function _cxs_write($id, $data) {
  global $pdo_ses;

  $access = time();

  $session_table = \cx_configure::a_get('security','session_table');
  
  if ($session_table === false) {
    return false;
  }
  
  $security_level = cx_configure::a_get('security','session_security_level');
  
  if ($security_level !== false) {
    $enc_sess = new cx\common\crypt();
    $enc_sess->change_security($security_level);
    $enc_sess->set_cleartext($data);
    $data = $enc_sess->encrypt();
    unset($enc_sess);
  } 

  $sql = "REPLACE INTO `{$session_table}` SET `id`=:id, `access`=:access, `data`=:data";
  $stmt = $pdo_ses->prepare($sql);
  $stmt->bindValue(':id', $id, PDO::PARAM_STR);
  $stmt->bindValue(':access', $access, PDO::PARAM_INT);
  $stmt->bindValue(':data', $data, PDO::PARAM_STR);
  return $stmt->execute();
}

function _cxs_destroy($id) {
  global $pdo_ses;
  $session_table = $session_table = \cx_configure::a_get('security','session_table');
  
  if ($session_table === false) {
    return false;
  }
  
  $sql = "DELETE FROM `{$session_table}` WHERE `id`=:id LIMIT 1";
  $stmt = $pdo_ses->prepare($sql);
  $stmt->bindValue(':id', $id, PDO::PARAM_STR);
  return $stmt->execute();
}

function _cxs_clean($max) {
  global $pdo_ses;
  
  $old = time() - $max;
  
  $session_table = \cx_configure::a_get('security','session_table');
  
  if ($session_table === false) {
    return false;
  }
  
  $sql = "DELETE FROM `{$session_table}` WHERE `access` < :old";
  $stmt = $pdo_ses->prepare($sql);
  $stmt->bindValue(':old', $old, PDO::PARAM_INT);
  return $stmt->execute();  
}