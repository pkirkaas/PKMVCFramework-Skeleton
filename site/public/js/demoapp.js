/**
 * PKMVC Framework 
 *
 * @author    Paul Kirkaas
 * @email     p.kirkaas@gmail.com
 * @link     
 * @copyright Copyright (c) 2012-2014 Paul Kirkaas. All rights Reserved
 * @license   http://opensource.org/licenses/BSD-3-Clause  
 */
/**
 * demoapp.js is part of the PKMVC Framework demo application, supporting 
 * JavaScript/AJAX operations, especially for forms, etc. Assumes jQuery
 */

$(function() {
  //On Load; jQuery
});

//Generic button attachments to add/delete items from form collections, based
//on standard button and collection div names...
/**
 * Delete the associated form item row/component
 */
$('.base-collection-set').on('click', '.delete-item-button', function() {
    $(this).closest('div.base-item-el').remove();
  });


$('.add-item-button').on('click', newItemFromTemplate);

function newItemFromTemplate() {
  if (typeof PKMVC_TPL_STR === "undefined") {
    PKMVC_TPL_STR = "__TEMPLATE_JS__";
  }
  //alert("Clicked add item button..");
  var template = $(this).closest('.base-collection-set').find('.form-template').data('item-template');
  console.log("And this is...",this,"; and the template is:",template);
  var idx = parseInt($(this).attr('data-idx'));
  var newstr = template.replace(/__template__/g, idx);
  idx++;
  $(this).attr('data-idx', idx);
  console.log("The New Template:", newstr);
  //$('.base-collection-set').append(newstr);
  $(this).before(newstr);

}










