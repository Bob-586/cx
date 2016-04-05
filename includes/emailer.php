<?php
function cx_send_email($options) {
  
  if (! is_array($options)) {
    return false;
  }

  require_once CX_BASE_DIR . 'classes'.DS.'phpmailer'.DS.'class.phpmailer.php';
  require_once CX_BASE_DIR . 'classes'.DS.'phpmailer'.DS.'class.smtp.php';
  
  $mail = new PHPMailer();  
  
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
        if (is_array($value)) {
          $to = (isset($value['address'])) ? $value['address'] : $value[0];
          $to_name = (isset($value['name'])) ? $value['name'] : false;
          if ($to_name === false) {
            $mail->addAddress($to);
          } else {
            $mail->addAddress($to, $to_name);
          }          
        } else {
          $to = $value;
          $mail->addAddress($to);     // Add a recipient
        }
        break;
      case 'cc':
        if (is_array($value)) {
          $cc = (isset($value['address'])) ? $value['address'] : $value[0];
          $cc_name = (isset($value['name'])) ? $value['name'] : false;
          if ($cc_name === false) {
            $mail->addCC($cc);
          } else {
            $mail->addCC($cc, $cc_name);
          }          
        } else {
          $mail->addCC($value);
        }
        break;
      case 'bcc':
        if (is_array($value)) {
          $bcc = (isset($value['address'])) ? $value['address'] : $value[0];
          $bcc_name = (isset($value['name'])) ? $value['name'] : false;
          if ($bbc_name === false) {
            $mail->addBCC($bcc);
          } else {
            $mail->addBCC($bcc, $bcc_name);
          }                    
        } else {
          $mail->addBCC($value);
        }
        break;
      case 'from':
        if (is_array($value)) {
          $from = (isset($value['address'])) ? $value['address'] : $value[0];
          $from_name = (isset($value['name'])) ? $value['name'] : false;
          if ($from_name === false) {
            $mail->setFrom($from);
          } else {
            $mail->setFrom($from, $from_name);
          }
        } else {
          $from = $value;
          $mail->setFrom($from);
        }
        break;
      case 'subject':
        $subject = $value;
        $mail->Subject = $subject;
        break;
      case 'email':
      case 'message':
        $message = $value;
        $mail->Body = $message;
        $mail->AltBody = strip_tags($message);        
        break;
      case 'reply to':
      case 'reply':
        if (is_array($value)) {
          $reply = (isset($value['address'])) ? $value['address'] : $value[0];
          $reply_name = (isset($value['name'])) ? $value['name'] : false;
          if ($reply_name === false) {
            $mail->addReplyTo($reply);
          } else {
            $mail->addReplyTo($reply, $reply_name);
          }
        } else {        
          $mail->addReplyTo($value);
        }
        break;
      case 'bounce':
      case 'bounce address':
      case 'bounce backs':
        $bounce = $value;
        break;
      case 'attachment':
      case 'attach':
        if (is_array($value)) {
          $attachment = (isset($value['file'])) ? $value['file'] : value[0];
          $file_name = (isset($value['name'])) ? $value['name'] : false;
          if ($file_name === false) {
            $mail->addAttachment($attachment);
          } else {
            $mail->addAttachment($attachment, $file_name);
          }
        } else { 
          $mail->addAttachment($value);
        }
        break;
      case 'type':
      case 'content type':
        $content_type = $value;
        break;
    }
  }

  if (empty($to) || empty($from) || empty($subject) || empty($message)) {
    return false;
  }

  switch($content_type) {
    case 'html':
      $mail->isHTML(true); // Set email format to HTML
      break;	
    case 'text':
    default:
      $mail->isHTML(false); // Set email format to Plain Text
      break;
  }

  $settings = cx_configure::get('email');
  
  if (isset($settings['send_emails']) && $settings['send_emails'] === false) {
//    echo "To: {$to} Subject: {$subject} Message: {$email}";
    return false;
  }
    
  if (isset($settings['host']) && ! empty($settings['host'])) {
    if (isset($settings['smtp_debug'])) {
      $mail->SMTPDebug = $settings['smtp_debug'];         // Enable verbose debug output
    }
 
    $mail->isSMTP(); // Set mailer to use SMTP      
    $mail->Host = $settings['host'];  // Specify main and backup SMTP servers
    $auth = ( isset($settings['username']) && ! empty($settings['username']) &&
      isset($settings['password']) && ! empty($settings['password']) &&
      (! isset($settings['auth']) || $settings['auth'] === true)
      ) ? true : false; // Enable SMTP authentication
    if ($auth === true) {
      $mail->SMTPAuth = true;
      $mail->Username = $settings['username']; // SMTP username
      $mail->Password = $settings['password']; // SMTP password
      $mail->SMTPSecure = (isset($settings['secure'])) ? $settings['secure'] : 'tls'; // Enable TLS encryption, `ssl` also accepted
    }
    $mail->Port = (isset($settings['port'])) ? $settings['port'] : 587; // TCP port to connect to
  } else {
    $mail->isMail(); // Use SendMail
  }

  if($mail->send()) {
    return true; // Yes, it worked!
  } else {
    error_log('Mailer Error: ' . $mail->ErrorInfo);
    return false;
  }    
}