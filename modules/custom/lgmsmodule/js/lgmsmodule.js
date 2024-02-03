(function ($) {
  Drupal.behaviors.addInputElement = {
    attach: function (context, settings) {
      // Create the input element
      if ($('.lgms-table .lgms-search').length === 0) {
        // Create the input element
        var inputElement = $('<input>', {
          type: 'text',
          class: 'form-element lgms-search',
          style: 'width: 100%;',
          placeholder: 'Search by guide name, owner, or last modification date'
        });

        // Append the input element to the div with class 'lgms-table .view-filters'
        $('.lgms-table').prepend(inputElement);
      }
    }
  };
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
