/**
*  jQuery Selectbox filter
* author: bigo 
* date: aug/2008
*/
;(function($){
  
  var BigoFilter = function (src, where, settings) { 
    settings = jQuery.extend({
        property: 'text'        
        },settings);    
    $(src).bind('keyup',function(){
      var field = $(this)[0];
      var select = $(where)[0];      
      var found = false;
      for (var i = 0; i < select.options.length; i++) {
        if (select.options[i][settings.property].toUpperCase().indexOf(field.value.toUpperCase()) == 0) {          
          found=true; break;
        }
      }
      if (found) { select.selectedIndex = i; }
      else { select.selectedIndex = -1; }      
    }); // function  
  } // main func.
  
  $.fn.bigoFilter = function (where, opts) {    
    var bleh =  new BigoFilter(this, where, opts);    
  } // trigger
    
  
})(jQuery) // closure
