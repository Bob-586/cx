<?php
function cx_send_email($options) {
  if (! is_array($options)) {
    return false;
  }

  $to = '';
  $from = '';
  $from_name = '';
  $reply = '';
  $reply_name = '';
  $content_type = '';
  $cc = '';
  $cc_name = '';
  $bcc = '';
  $bcc_name = ''; 	
  
  foreach($options as $key=>$value) {
    switch(safe_strtolower($key)) {
      case 'to':
        $to = $value;
        break;
      case 'cc':
        $cc = $value;
        break;
      case 'cc name':
        $cc_name = $value;
        break;        
      case 'bbc':
        $bbc = $value;
        break;
      case 'bbc name':
        $bbc_name = $value;
        break;
      case 'from':
        $from = $value;
        break;
      case 'from name':
        $from_name = $value;
        break;
      case 'subject':
        $subject = $value;
        break;
      case 'email':
      case 'message':
        $message = $value;
        break;
      case 'reply to':
      case 'reply':
        $reply = $value;
        break;
      case 'reply name':
        $reply_name = $value;
      case 'bounce':
      case 'bounce address':
      case 'bounce backs':
        $bounce = $value;
        break;
      case 'type':
      case 'content type':
        $content_type = $value;
    }
  }

  if (empty($to) || empty($from) || empty($subject) || empty($message)) {
    return false;
  }

  if (empty($from_name)) {
    $from_name = $from;
  }

  if (empty($reply)) {
    $reply = $from;
  }
  
  if (empty($reply_name)) {
    $reply_name = $reply;
  }
  
  if (empty($cc_name)) {
    $cc_name = $cc;
  }

  if (empty($bcc_name)) {
    $bcc_name = $bcc;
  }

  switch($content_type) {
    case 'html':
      $type = 'html';
      break;	
    case 'text':
    default:
      $type = 'plain';
      break;
  }

  $headers   = array();
  $headers[] = "MIME-Version: 1.0";
  $headers[] = "Content-type: text/{$type}}; charset=utf-8"; 
  $headers[] = "From: {$from_name} <{$from}>";
  if (! empty($cc)) {
    $headers[] = "Cc: {$cc_name} <{$cc}>";
  }
  if (! empty($bbc)) {
    $headers[] = "Bcc: {$bcc_name} <{$bcc}>";
  }
  $headers[] = "Reply-To: {$reply_name} <{$reply}>";
  if (! empty($bounce)) {
    $headers[] = "Return-Path: {$bounce}";
  }
  $headers[] = "Subject: {$subject}";
  $headers[] = "X-Mailer: PHP/" . phpversion();

  // In case any of our lines are larger than 70 characters, we should use wordwrap()
  $email = wordwrap($message, 70, "\r\n");

  $additional = (! empty($bounce)) ? "-f{$bounce}" : '';
  
  if (cx_configure::a_get('cx', 'send_emails') === false) {
    echo "To: {$to} Subject: {$subject} Message: {$email}";
    return true;
  } else {
    return mail($to, $subject, $email, implode("\r\n", $headers), $additional);
  }
  
}