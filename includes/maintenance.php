<?php
function cx_down($hours_down) {
  $protocol = "HTTP/1.0";
  if ( "HTTP/1.1" == $_SERVER["SERVER_PROTOCOL"] ) {
    $protocol = "HTTP/1.1";
  }
  if (intval($hours_down) < 1) {
    $hours_down = 1;
  }
  $retry = 60 * 60 * $hours_down;
  header( "{$protocol} 503 Service Unavailable", true, 503 );
  header( "Retry-After: {$retry}" );
  header('Content-type: text/html; charset=utf-8');
?>
<html>
  <head>
    <meta charset="utf-8">
    <base href="<?php echo PROJECT_BASE_REF; ?>"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="language" content="english">
    <meta name="robots" content="no-follow">
    <link rel="shortcut icon" href="favicon.ico">
    <title>Site is down for maintenance</title>
    <link rel="stylesheet" href="<?= CX_BASE_REF ?>/cx/assets/uikit/css/uikit.gradient.min.css" type="text/css" media="all" />
  </head>
  <body id="my-page">
    <div id="wrap">
      <div class="uk-container uk-container-center">
        <div id="autosavemessage">
          <div class="page-header">
            <div class="uk-alert uk-alert-danger">
                <header>Site is down for maintenance.</header>  
            </div>
          </div>
        </div>
        Our apologies for the temporary inconvenience.
      </div>
    </div>    
  </body>
</html>
<?php
  exit;
}

function cx_daily_down($m_work_start, $m_work_end) {
  $ServerCurrentTime = date("Y-m-d H:i:s");
  $ServerTimeZone = new \DateTime($ServerCurrentTime, new \DateTimeZone('UTC'));
  $ServerTimeZone->setTimezone(new \DateTimeZone('America/Detroit'));
  $ServerTime = $ServerTimeZone->format('H:i a');
  $date_now = \DateTime::createFromFormat('H:i a', $ServerTime);
  $date_begin = \DateTime::createFromFormat('H:i a', $m_work_start);
  $date_end = \DateTime::createFromFormat('H:i a', $m_work_end);
  if ($date_now > $date_begin && $date_now < $date_end) {
     cx_down(1);
  }
}

/* Take down the site, now? */
// cx_down(1);

/* Take down the server every day */
// cx_daily_down("1:30 AM", "2:30 AM");