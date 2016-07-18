var rdebug = true;
var loaded_files = [];

function is_path_loaded(path) {
  for (var x = 0, l = loaded_files.length; x < l; ++x) {
    var xfile = loaded_files[x]; // This was already registed...
    var filename = xfile.split('/').pop();
    var pathname = path.split('/').pop();
    if (filename.indexOf(pathname) != -1) {
      return true;
    }
  }
  return false;
}

function is_file_loaded(path, isMethod) {
  /* Check if file was already loaded */
  if (is_path_loaded(path) === false) {
    if (rdebug) {
      console.log("Loading: " + path);
    }
    return false;
  } else if (!isMethod || isMethod.length === 0) {
    console.log("ALREADY Loaded: " + path);
    return true;
  } else {
    /* Double check method exists, if not reload! */
    fn = window[isMethod];
    fnExists = typeof fn === 'function';
    if (fnExists === false) {
      if (rdebug) {
        console.log("Re-loading, as method didn't exist: " + path);
      }
      return false;
    } else {
      if (rdebug) {
        console.log("ALREADY Loaded: " + path);
      }
      return true;
    }
  }
}

var load = function (path, isMethod) {
  rd = deferred();
  if (is_file_loaded(path, isMethod) === true) {
    setTimeout(function () {
      rd.resolve("files ready");
    }, 50);
    return rd.promise;
  }
  var head = document.head || document.getElementsByTagName('head')[0];
  var baseElement = document.getElementsByTagName('base')[0];
  if (baseElement) {
    head = baseElement.parentNode;
  }
  if (path.indexOf(".css") != -1) {
    var style = document.createElement('link');
    style.type = 'text/css';
    style.rel = 'stylesheet';
    style.href = path;
    style.media = 'all';
    style.async = true;
    if (style.attachEvent && !(style.attachEvent.toString && style.attachEvent.toString().indexOf('[native code') < 0) && !isOpera) {
      style.attachEvent('onreadystatechange', rdcontext.onScriptLoad);
    } else {
      style.addEventListener('load', rdcontext.onScriptLoad, false);
      style.addEventListener('error', rdcontext.onScriptError, false);
    }
    if (baseElement) {
      head.insertBefore(style, baseElement);
    } else {
      head.appendChild(style);
    }
  } else if (path.indexOf(".html") != -1) {
    rd.resolve($templateCache.get(path));
  } else {
    var script = document.createElement('script');
    script.type = "text/javascript";
    script.charset = "utf-8";
    script.async = true;
    if (rdebug) {
      console.log("init");
    }
    if (script.attachEvent && !(script.attachEvent.toString && script.attachEvent.toString().indexOf('[native code') < 0) && !isOpera) {
      script.attachEvent('onreadystatechange', rdcontext.onScriptLoad);
    } else {
      script.addEventListener('load', rdcontext.onScriptLoad, false);
      script.addEventListener('error', rdcontext.onScriptError, false);
    }
    script.src = path;
    if (baseElement) {
      head.insertBefore(script, baseElement);
    } else {
      head.appendChild(script);
    }
  }
  if (rdebug) {
    console.log("defer");
  }
  return rd.promise;
};

var ready = function (path, isMethod) {
  d = deferred();
  if (!path instanceof Array) {
    if (rdebug) {
      console.log("load array excepteded!");
    }
    d.reject("error");
    return false;
  } else {
    for (var i = 0, j = path.length; i < j; ++i) {
      load(path[i], isMethod).then(function () {
        if (rdebug) {
          console.log("Ready...");
          setTimeout(function () {
            d.resolve("ready");
          }, 50);
        }
      });
    }
    if (rdebug) {
      console.log("Foreach...looped.");
    }
  }
  if (rdebug) {
    console.log("promise");
  }
  return d.promise;
};

function removeListener(node, func, name, ieName) {
  if (node.detachEvent && !isOpera) {
    if (ieName) {
      node.detachEvent(ieName, func);
    }
  } else {
    node.removeEventListener(name, func, false);
  }
}

function getScriptData(evt) {
  var node = evt.currentTarget || evt.srcElement;
  removeListener(node, rdcontext.onScriptLoad, 'load', 'onreadystatechange');
  removeListener(node, rdcontext.onScriptError, 'error');
  return {
    node: node,
    id: node && node.getAttribute('data-requiremodule')
  };
}

rdcontext = {
  onScriptLoad: function (evt) {
    if (evt.type === 'load' || (readyRegExp.test((evt.currentTarget || evt.srcElement).readyState))) {
      var data = getScriptData(evt);
      if (data.node.src) {
        var file = data.node.src;
      } else {
        var file = data.node.href;
      }
      var filename = file.split('/').pop();

      //var css = evt.path[0].href;
      //var js = evt.path[0].src;
      if (rdebug) {
        console.log("Loaded file: " + filename);
      }
      loaded_files.push(filename);
      setTimeout(function () {
        rd.resolve("File ok");
      }, 90);
    }
  },
  onScriptError: function (evt) {
    var data = getScriptData(evt);
    if (rdebug) {
      console.log("Error loading JS/CSS");
    }
    rd.reject("error");
  }
};
