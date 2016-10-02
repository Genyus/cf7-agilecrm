(function($) {

  $(function() {
    $('input:checkbox.toggle-form-table').click(function(event) {
      $(this).cf7_civi_toggel_form_table();
    }).cf7_civi_toggel_form_table();
  });

  $.fn.cf7_civi_toggel_form_table = function() {
    return this.each(function() {
      var formtable = $(this).closest('.contact-form-editor-box-civicrm').find('fieldset');

      if ($(this).is(':checked')) {
        formtable.removeClass('hidden');
      } else {
        formtable.addClass('hidden');
      }
    });
  };

})(jQuery);
