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
  Drupal.behaviors.tabSwitchingBehavior = {
    attach: function (context, settings) {
      // Check if any tab is already marked as active
      var activeTabExists = $('.tabs-list .tab-link.active').length > 0;

      if (activeTabExists) {
        // If an active tab exists, show its content
        var activeTabId = $('.tabs-list .tab-link.active').attr('href');
        $(activeTabId).show();
      } else {

        $('.tab-content').hide();
        // Show the first tab content
        $('.tab-content').first().show();
        // Set the first tab link as active
        $('.tabs-list .tab-link').first().addClass('active');
      }

      $('.tab-link', context).click(function () {
        var tabId = $(this).attr('href');

        // Remove active class from all tabs and then add to the current tab
        $('.tabs-list .tab-link').removeClass('active');
        $(this).addClass('active');

        // Hide all tab content and show the selected tab's content
        $('.tab-content').hide();
        $(tabId).show();
        return false;
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

