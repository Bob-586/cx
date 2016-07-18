(function (window) {
  'use strict';
  /*
  Local storage is more secure, and large amounts of data can be stored locally, without affecting website performance.
  Unlike cookies, the storage limit is far larger (at least 5MB) and information is never transferred to the server.
  Local storage is per origin (per domain and protocol). All pages, from one origin, can store and access the same data.

  window.localStorage - stores data with no expiration date
  window.sessionStorage - stores data for one session (data is lost when the browser tab is closed)     
  */

  function store(temp) {
    if(typeof temp == "undefined") {
      this.temp = false;
    } else {
      this.temp = temp;
    }
    
    if(typeof(Storage) !== "undefined") {
      return true;
    } else {
      return false;
    }
  }

  store.prototype.set = function(name, data) {
    if(typeof(Storage) !== "undefined") {
      var d = JSON.stringify(data);
      if(this.temp === false) {
        localStorage.setItem(name, d);
      } else {
        sessionStorage.setItem(name, d);
      }
      return true;  
    } else {
      return false;
    }
  }

  store.prototype.get = function(name) {
    if(typeof(Storage) !== "undefined") {
      if(this.temp === false) {
        return JSON.parse(localStorage.getItem(name));
      } else {
        return JSON.parse(sessionStorage.getItem(name));
      }  
    } else {
      return false;
    }
  }

  store.prototype.remove = function(name) {
    if(typeof(Storage) !== "undefined") {
      if(this.temp === false) {
        localStorage.removeItem(name);
      } else {
        sessionStorage.removeItem(name);
      }
      return true;
    } else {
      return false;
    }
  }
  
  // Export to window
	window.app = window.app || {};
	window.app.store = store;
})(window);  
