/** Library of convenient JS/jQuery functions.
 * @author    Paul Kirkaas
 * @email     p.kirkaas@gmail.com
 * @copyright Copyright (c) 2012-2014 Paul Kirkaas. All rights Reserved
 * @license   http://opensource.org/licenses/BSD-3-Clause  
 */

$(function() {
  $('.formset').on('click', '.new-from-formset-tpl', function(event) {newSubForm(event, this);});
  $('.formset').on('click', '.delete-row-button', function(event)
     { $(this).closest('.multi-subform').remove();});

});

function newSubForm(myevent, me) {
  if (typeof PKMVC_TPL_STR === "undefined") {
    PKMVC_TPL_STR = "__TEMPLATE_JS__";
  }
  var container = $(me).closest('.formset');
  var template = container.attr('data-template');
  //console.log("In Subform: Event:", myevent, "ME:", me, "Data Template:", template, "PKMVC_TPL_STR", PKMVC_TPL_STR);
  //  data('cell-template');
  var fseqno = parseInt($(me).attr('data-count'));
  var regexstr = PKMVC_TPL_STR;
  var regexobj = new RegExp(regexstr,'g');
  console.log("In Subform: PKMVC_TPL_STR", PKMVC_TPL_STR, "RegExObj:", regexobj);
  var newstr = template.replace(regexobj, fseqno);
  fseqno++;
  $(me).attr('data-count', fseqno);
  console.log("The New Template:", newstr);
  container.append(newstr);
}


/**
 * Takes an array of GET key/value pairs, adds (or replaces) them, and redirects
 * @param {type} getArr
 */
function addGetsAndGo(getArr) {
  var gets = getGets(); //Array of existing GET params
  var outGets = $.extend({}, gets, getArr); 
  setGetsAndGo(outGets);
}

/** Takes an array of GET key/value pairs and sets them as the GET
 * parameters to the current path, totally removing any current GET
 * params not in this array.
 * @param {type} getArr
 */
function setGetsAndGo(getArr) {
  var queryStr = getArrToStr(getArr);
  var basePath = getBasePath();
  window.location = basePath + queryStr;
}

function getBasePath() {
  var basePath = window.location.protocol + '//' + window.location.hostname
    + window.location.pathname;
  return basePath;
}




/**
 * Refresh current page with new GET parameter value
 * Adds the parameter if it doesn't exist, or replaces the current value
 *
 * @param parmName: the name of the GET parameter
 * @param parmValue: the value of the GET parameter
 */
function refreshNewGet(parmName, parmValue) {
  var gets = getGets();
  //Kludge -- if changing perpage, reset page to 1
  if (parmName == 'perpage') {
    gets['page'] = '1';
  }
  gets[parmName] = parmValue;
  //Rebuild GET query string
  var getstr = '';
  for (var parname in gets) {
    if (gets[parname]) {
      getstr = getstr + '&' + parname + '=' + encodeURIComponent(gets[parname]);
    }
  }
  if (getstr) {
    getstr = '?' + getstr.substr(1);
  }
  window.location = window.location.pathname + getstr;
}


/**
 * Returns associative array of named "GET" parameters and values
 */
function getGets() {
  var queryStr = window.location.search.substr(1);
  var params = {};
  if (queryStr == '') return params;
  var prmarr = queryStr.split ("&");

  for ( var i = 0; i < prmarr.length; i++) {
      var tmparr = prmarr[i].split("=");
      params[tmparr[0]] = tmparr[1];
  }
  return params;
}


/**
 *Converts an array of key/values to a an '&' separated GET string of params
 *values. 
 * @param {type} getArr: Array of GET key/value pairs
 * @returns String: converted array of GET params to a query string
 */
function getArrToStr(getArr) {
  var retstr = '?';
  for (var paramName in getArr) {
    if (getArr.hasOwnProperty(paramName)) { // paramName is not inherited
      retstr += (paramName + '=' + getArr[paramName] + '&');
    }
  }
  retstr = retstr.substring(0, retstr.length - 1);
  return retstr;
}

/** 
 * Takes an associative array of key/value pairs and returns a GET param str
 * TODO: URL encode? But what if existing param values are already URLencoded?
 * @param Array getArr: array of key/value pairs
 * @returns String query get parameter string
 */
function setGets(getArr) {
}



/** Rounds a number to two decimal places. 
 * TODO: Make 2 the default, with additional optional parameter
 * @param {type} numberToRound
 * @returns {Number}
 */
function roundTo2Decimals(numberToRound) {
  return Math.round(numberToRound * 100) / 100
}

/**
 * Returns a "cousin" of the given object, as the first matched descendent
 * of the first matched ancestor
 * @param String parentSelector: the jQuery string for the parent to look for
 * @param String cousinSelector: the jQuery string for the cousin to look for
 * @param {type} me: the JS element to find the cousin of (if empty, this)
 * @returns {jQuery} -- the cousin, as a jQuery object
 */
function getCousin(parentSelector, cousinSelector, me) {
  return  getCousins(parentSelector, cousinSelector, me, true);
}

/** See definition of getCousin, above. This returns all cousins, unless the
 * "first" parameter is true, in which case it returns only the one, first
 * @param {type} parentSelector
 * @param {type} cousinSelector
 * @param {type} me
 * @param int first: Retrun only the first cousin?
 * @returns {getCousins.cousins}
 */
function getCousins(parentSelector, cousinSelector, me, first) {
  if (me == undefined) {
    me = this;
  }
  me = jQuerify(me);
  var cousins = me.closest(parentSelector).find(cousinSelector);
  if (first) {
      cousins = cousins.first();
  }
  return cousins;
}

/**
 * Given a JS DOM object, or a string, or a jQuery object, returns the 
 * corresponding jquery object. Used to normalize an argument to a function 
 * that might be any way of specifying and object
 * @param jQuery|string|obj: arg
 * @returns jQuery
 */
function jQuerify(arg) {
  if (arg instanceof jQuery) {
    return arg;
  }
  return jQuery(arg);
}

/**
 * Adds the given class to the object, and removes all the other classes in
 * the array.
 * @param string classToAdd
 * @param array classesToRemove
 * @param jQuerifyable obj
 * @returns the object
 */
function addClassAndClear(classToAdd, classesToRemove, obj) {
  //var possibleStates = array['pass', 'fail', 'unknown'];
  //Check for state in array of possibleStates
  var idx = classesToRemove.indexOf(classToAdd);
  if (idx == -1) { //Something is wrong, bail
    return;
  }
  /*
  classesToRemove.splice(idx, 1);
  if (obj == undefined) {
    obj = this;
  }
  */
  obj = jQuerify(obj);
  for (var idx in classesToRemove) {
    obj.removeClass(classesToRemove[idx]);
  }
  obj.addClass(classToAdd);
}

/**
 * Returns all the classes of the object
 * @param jQuerifyable el
 * @returns array of classes
 */
/* TODO: Debug at some point; getting "split of undefined" errors"
function getClasses(el) {
  if (el == undefined) {
    el = this;
  }
  el = jQuerify(el);
  if (!(el instanceof jQuery) ) {
    return false;
  }
  var classes = el.attr('class').split(' ');
  return classes;
}
*/
