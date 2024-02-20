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
  Drupal.behaviors.customLayoutRemover = {
    attach: function (context, settings) {
      // Remove the class "layout-container" from all elements.
      $('.layout-container', context).removeClass('layout-container');
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

(function ($, Drupal) {
  Drupal.behaviors.myPopupBehavior = {
    attach: function (context, settings) {
      $('.add-guide-box-link', context).once('myPopupBehavior').click(function (e) {
        e.preventDefault(); // Prevent default link behavior
        var url = $(this).attr('href');
        // Open popup window
        window.open(url, 'GuideBoxPopup', 'innerWidth=800,height=600');
      });
    }
  };
})(jQuery, Drupal);
