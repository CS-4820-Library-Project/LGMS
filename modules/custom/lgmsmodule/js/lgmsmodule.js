(function ($) {
  Drupal.behaviors.lgmsSearch = {
    attach: function (context, settings) {
      $('.lgms-search', context).on('input', function () {
        var searchValue = $(this).val().toLowerCase();
        $('.lgms-table .view-content table tbody tr').each(function () {
          var rowText = $(this).text().toLowerCase();
          if (rowText.indexOf(searchValue) === -1) {
            $(this).hide();
          } else {
            $(this).show();
          }
        });
      });
    }
  };
})(jQuery);
