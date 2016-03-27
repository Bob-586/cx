/* 
 * copyright 2013
 * author Robert Strutts
 */

$("#autosaved-wrapper").fadeOut(15000);

function cx_random_number_between(min, max) { return Math.floor((Math.random()*max)+min) }

/* Set Cookie */
function cx_set_cookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    var expires = "expires="+d.toUTCString();
    document.cookie = cname + "=" + cvalue + "; " + expires;
}

/* Get Cookie */
function cx_get_cookie(cname) {
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for(var i=0; i<ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1);
        if (c.indexOf(name) != -1) return c.substring(name.length,c.length);
    }
    return "";
}

function cx_log(msg){
  if (window.console && console.info) {
      console.info(msg);
  } else {
    if (window.console && console.log) {
      console.log(msg); /* for Chorme Console is enabled */
    }
  }
}

function get_JSONP(response) {
  var fixedResponse = response.responseText.replace(/\\'/g, "'");
  return JSON.parse(fixedResponse);
}