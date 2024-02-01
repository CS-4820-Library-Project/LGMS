/* Load jQuery.
--------------------------*/
jQuery(document).ready(function ($) {

$(document).foundation();
  // Homepage blocks
$('.p-navigation__nav > ul').superfish();


  //askus
  $(".page-node-627 .page-title").prepend('<i class="fa-brands fa-weixin"></i>');
  //computers
  $(".page-node-2411 .page-title").prepend('<i class="fa-solid fa-laptop"></i>');
  //search
  $(".path-databases-all .page-title").prepend('<i class="fa-solid fa-magnifying-glass"></i>');
  //print scan
  $(".page-node-2415 .page-title").prepend('<i class="fa-solid fa-print"></i>');
  //collections
  $(".page-node-371 .page-title").prepend('<i class="fa-solid fa-image"></i>');
  // tours
  $(".page-node-99 .page-title").prepend('<i class="fa-solid fa-info-circle"></i>');
  //booking
  //$(".page-node-2409 .page-title").prepend('<i class="fa-solid fa-users"></i>');
  // subject guides
  $(".page-node-1291 .page-title").prepend('<i class="fa-solid fa-book"></i>');
  // faq
  $(".page-node-889 .page-title").prepend('<i class="fa-solid fa-question-circle"></i>');
  // hours
  $(".page-node-675 .page-title").prepend('<i class="fa-solid fa-clock"></i>');

    Drupal.behaviors.bentoSearch = {
        attach: function (context, settings) {

            //$('.js-search-results__container .books').append($('#roblib-eds-books-toc'));
            //$('.js-search-results__container .articles').append($('#roblib-eds-articles-toc'));
            //$('.js-search-results__container .web').append($('#roblib-solr-search-toc-results'));
            //$('.searchtabs .tabs').append($('.admin-menu__container'));

            //move the all results link to bento block header
            var header = '.roblib-search-eds-media-header';
            var link = '#roblib-search-eds-more';
            $( header ).append($( link ));

            var header = '.roblib-search-eds-articles-header';
            var link = '#roblib-search-eds-article-more';
            $( header ).append($( link ));

            var header = '.roblib-search-solr-results-header';
            var link = '#roblib-search-solr-site-results-more';
            $( header ).append($( link ));


        }
    };



    Drupal.behaviors.pageIcons = {
        attach: function attach(context, settings) {

          //$(".page-node-2411 .page-title").prepend('<i class="fa-solid fa-laptop"></i>');
            //var pageTitle = $(".page-databases-all .page_title"); //select the element
            //var icon = 'fa-search';
            //pageTitle.prepend('<i class="fa ' + icon + '"></i>');
            ////==============================================
            //var pageTitle = $(".section-bar-askus .page_title"); //select the element
            //var icon = 'fa-weixin';
            //pageTitle.prepend('<i class="fa ' + icon + '"></i>');
            ////==============================================
            //var pageTitle = $(".section-computers .page_title"); //select the element
            //var icon = 'fa-laptop';
            //pageTitle.prepend('<i class="fa ' + icon + '"></i>');
            ////==============================================
            //var pageTitle = $(".section-printing .page_title"); //select the element
            //var icon = 'fa-print';
            //pageTitle.prepend('<i class="fa ' + icon + '"></i>');
            ////==============================================
            //var pageTitle = $(".section-peicollection .page_title"); //select the element
            //var icon = 'fa-picture-o';
            //pageTitle.prepend('<i class="fa ' + icon + '"></i>');
            ////==============================================
            //var pageTitle = $(".section-tours .page_title"); //select the element
            //var icon = 'fa-info-circle';
            //pageTitle.prepend('<i class="fa ' + icon + '"></i>');
            ////==============================================
            //var pageTitle = $(".section-sg .page_title"); //select the element
            //var icon = 'fa-book';
            //pageTitle.prepend('<i class="fa ' + icon + '"></i>');
            ////==============================================
            //var pageTitle = $(".section-faq-frequently-asked-questions-about-library-resources-and-services .page_title"); //select the element
            //var icon = 'fa-question-circle';
            //pageTitle.prepend('<i class="fa ' + icon + '"></i>');
        }
    };
/* End document
--------------------------*/
});
