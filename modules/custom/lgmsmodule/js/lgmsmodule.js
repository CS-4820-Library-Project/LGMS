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
document.querySelectorAll('.js-form-item').forEach(function(item) {
  if (item.querySelector('.lgms-search')) {
    item.className = 'has-lgms-search ' + item.className;
  }

  if (item.querySelector('.lgms-dashboard-search')) {
    item.className = 'has-lgms-dashboard-search ' + item.className;
  }

  if (item.querySelector('.lgms-all_guides-search')) {
    item.className = 'has-lgms-all-guides-search ' + item.className;
  }
});
