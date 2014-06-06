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