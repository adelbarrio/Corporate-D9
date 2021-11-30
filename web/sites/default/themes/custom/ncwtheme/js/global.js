/**
 * @file
 * Global utilities.
 *
 */
(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.ncwtheme = {
    attach: function (context, settings) {

    }
  };

})(jQuery, Drupal);

jQuery(document).ready(function($) {

  //Facet accordions, this should be usefull for all blocks with .block-facets-accordion-class
  $(".sidebar-first").on("click", ".block-facets-accordion h2", function(e){
    e.preventDefault();
    $(this).stop().toggleClass("active");
    $(this).closest(".block-facets-accordion").find('.content').stop().slideToggle();
  });

  $("#block-ncwtheme-main-menu .menu-item a").after("<span class='mean-expand'>&nbsp;</span>");
  $('.mean-expand').click(function(){
    $(this).parent().children('div').children('ul').slideToggle();
    $(this).toggleClass('active');
  });

  // Accordions
  if (!$('body').hasClass("node--type-thesaurus")) {
    $(".wysiwyg_accordion h3:nth-child(1)").addClass('active');
    $(".wysiwyg_accordion div:nth-child(2)").css('display', 'block');
    $(".wysiwyg_accordion h3").click(function () {
      $(this).toggleClass("active");
      $(this).next('.wysiwyg_accordion_panel').slideToggle();
      $(this).next('ol').slideToggle();
    });
  }

  if ($('body').hasClass("node--type-thesaurus")) {
    $(".wysiwyg_accordion h3").click(function () {
      $(this).toggleClass("active");
      $(this).next('.wysiwyg_accordion_panel').slideToggle();
    });
  }

  $(".sidebar-second .view-grouping .view-grouping-header").click(function(){
    $(this).toggleClass("active");
    $(this).next('.view-grouping-content').slideToggle();
  });

  // Facet with view FOPS detail
  $(".sidebar-first .view-fop-flags h3").click(function(){
    $(this).toggleClass("active");
    $(this).next('.fop-country-list').slideToggle();
  });

  //View MSD glossary accordion
  $(".view-view-glossary > div.view-content.row > h3:nth-child(7)").addClass('active');
  $(".view-view-glossary > div.view-content.row > div:nth-child(8)").css('display','block');
  $(".view-view-glossary h3").click(function(){
    $(this).toggleClass("active");
    $(this).next('.views-view-grid').slideToggle();
  });

  // View Accordions view-board-members
  $(".view-board-members .view-grouping-header").click(function(){
    $(this).toggleClass("active");
    $(this).next('.view-board-members .view-grouping-content').slideToggle();
  });

  // View Accordions view-advisory-groups
  $(".view-advisory-groups .view-grouping-header").click(function(){
    $(this).toggleClass("active");
    $(this).next('.view-advisory-groups .view-grouping-content').slideToggle();
  });

  // View Accordions view-seminar
  $(".view-seminar .view-grouping-header").click(function(){
    $(this).toggleClass("active");
    $(this).next('.view-seminar .view-grouping-content').slideToggle();
  });

  //View Careers 3th accordion opened
  if ($("body.careers")[0]) {
    $("body.user-logged-in #block-quicktabscareersaccordion #ui-id-6").trigger("click");
    $("body #block-quicktabscareersaccordion #ui-id-5").trigger("click");
  }
  //View Seminar Reports 1st accordion opened
  $( "body.tools-and-resources-seminars #block-ncwtheme-content > div > article > div.view-content > div > div > div > div:nth-child(1) > div.view-grouping-header" ).trigger( "click" );

  // Text resize
  $('#_biggify').on('click', function() {
    var fontSize = $('html').css('font-size');
    var newFontSize = parseInt(fontSize)+1;

    $('html').css('font-size', newFontSize+'px')
  })

  $('#_smallify').on('click', function() {
    var fontSize = $('html').css('font-size');
    var newFontSize = parseInt(fontSize)-1;

    $('html').css('font-size', newFontSize+'px')
  })

  $('#_reset').on('click', function() {
    $('html').css('font-size', '16px')
  })

  //Reset margin if copyright exist
  if ($(".node--type-article #main .field--name-field-caption-copyrigth-")[0]) {
    $('.node--type-article #main .field--name-field-image-caption img').addClass('custom-reset-margin');
  }

  //Move AddToAny to the bottom page
  if ($(".move-add-to-any")[0]) {
    $('.addtoany_list').appendTo('.move-add-to-any');
  }

  //Move block theasaurus in Thesaurus Detail
  if ($(".move-block-thesaurus")[0]) {
    $('#block-headerthesaurus-2').appendTo('.move-block-thesaurus');
  }

  //Move Search Blog
  if ($(".move-block-search-blog")[0]) {
    $('.block-views-exposed-filter-blocksearch-blog-page-1').appendTo('.move-block-search-blog');
  }

  //Move infographic filter
  if ($(".views-row.moved-by-jquery")[0]) {
    $('.views-row.moved-by-jquery').prependTo('.page-view-infographic .view-infographic.view-display-id-block_3 .view-content.row');
  }

  //Move Menu block Wiki block before
  if ($("div").hasClass('field--name-field-related-oshwiki-articles') && $("div").hasClass('block-views-blocklanding-menu-block-1')) {
    $('.block-views-blocklanding-menu-block-1').appendTo('article .field--name-body');
    $('.block-views-blocklanding-menu-block-1').addClass('moved-by-jquery');
  }

  //Move back button MSD
  if ($(".back-before-title")[0]) {
    $('.back-before-title').prependTo('#block-ncwtheme-page-title');
  }

  //Fix menu mobile Sticky
  if ($("#navbar-main .navbar-toggler")[0]) {
    $("#navbar-main > button").click(function(){
      $('body').toggleClass('custom-activate-menu');
      $('#header').toggleClass('custom-activate-menu-header');
    });
  }

  //Add #mail no padding in pubblications
  if ($(".related-resources-fluid")[0]) {
    $('#main').addClass('custom-no-padding');
  }

  //Add class to add margin in content publications
  if ($(".related-resources-fluid")[0]) {
    $('.publications-row').addClass('custom-add-margin');
  }

  //Add class to add border in content publications if Additional publications on this topic exist
  if ($(".wrapper-view-aditional-publications")[0]) {
    $('.publications-row').addClass('add-border');
  }

  //Hide Related publications if Twin publications exist
  if ($(".related-resources-fluid .twin-publications")[0]) {
    $('.related-resources-fluid  .related-resources-publications').hide();
    $('.related-resources-fluid  .content-headings-related').hide();
  }

  //Fix display pages footer view if we haven't pagination
  if ($(".pagerer-container")[0]) {
    $('.pager-total').addClass('with-pager');
  }else{
    $('.pager-total').addClass('without-pager');
  }

  //Show input search when click in Search button responsive menu
  $("#block-searchsite").click(function(){
    $('#block-searchsite .btn-primary').addClass('activate');
    $('#block-searchsite .form-search').addClass('activate');
  });

  //Hide the blocks in responsive until the components are loading
  if ($(window).width() < 992) {
    $('#block-languagedropdownswitchercontent').hide();
    $('#block-searchsite').hide();
    $('#block-headermenu').hide();
  }

  //Move the Blocks in responsive and desktop when we resize the page
  $(window).on('resize', function(){
    if ($(window).width() < 992) {
      $('#block-languagedropdownswitchercontent').appendTo('#navbar-main');
      $('#block-languagedropdownswitchercontent').show();
      $('#block-searchsite').appendTo('#navbar-main');
      $('#block-searchsite').show();
      $('#block-headermenu').appendTo('#navbar-main');
      $('#block-headermenu').show();
    }
    if ($(window).width() > 992) {
      $('#block-languagedropdownswitchercontent').appendTo('.region-header-form');
      $('#block-languagedropdownswitchercontent').show();
      $('#block-searchsite').appendTo('.region-header-form');
      $('#block-searchsite').show();
      $('#block-headermenu').appendTo('.region-header-links');
      $('#block-headermenu').show();
    }
  });

  //Left menu in sidebar first
  $(".sidebar-first .menu-level-1 .menu-item--expanded > a").after("<span class='mean-expand-custom no-active'>&nbsp;</span>");
  $('.sidebar-first .menu-level-1 .mean-expand-custom').click(function(){
    $(this).parent().children('div').children('ul').slideToggle();
    $(this).toggleClass('active');
    $(this).toggleClass('no-active');
  });

  //Breadcumbs - Fix &amp;
  $('.breadcrumb-item a').each(function() {
    var text = $(this).text();
    $(this).text(text.replace('&amp;', '&'));
  });

  // Breadcrumbs - Hide duplicate items
  var text_breadcrumb_item_2 = $("#block-ncwtheme-breadcrumbs > div.content > nav > ol > li:nth-child(2) > a").text();
  var text_breadcrumb_item_3 = $("#block-ncwtheme-breadcrumbs > div.content > nav > ol > li:nth-child(3) > a").text();

  if ( text_breadcrumb_item_2 == text_breadcrumb_item_3){
    $('#block-ncwtheme-breadcrumbs > div.content > nav > ol > li:nth-child(3)').hide();
  }
  // Breadcrumbs - Hide "Links" crumb in elements from footer
  if(text_breadcrumb_item_2 == "Links"){
    $('#block-ncwtheme-breadcrumbs > div.content > nav > ol > li:nth-child(2)').hide();
  }

  $('.breadcrumb-fluid ol.breadcrumb').addClass('custom-visible');

  //Archivied calls - Add class custom-active in year
  if ($(".view-id-calls.view-display-id-page_1")[0]) {
    let url = $(location).attr('href');
    //The year is in the URL
    let urlKey = url.replace(/\/\s*$/, "").split('/').pop();
    $('.view-display-id-page_1 .call__item a').filter(function () {
      return $(this).text() == urlKey;
    }).addClass('active-custom');
    //Add the active class to the left menu 'Procurement'
    $('.menu-level-1 > li:nth-child(6)').addClass('menu-item--active-trail');
    $('.menu-level-1 > li:nth-child(6) a').addClass('is-active');
  }

  //View clear filter MSD DB - MDR-4617
  if ($(".clear-filter-view")[0]) {
    let url = window.location.href;
    if(url.includes('?')){
      $('.clear-filter-view').show();
    }else{
      $('.clear-filter-view').hide();
    }
  }

  //Add the active class to the left menu 'Infographics'
  if ($(".node--type-infographic")[0]) {
    $('#block-mainnavigation-2--2 > ul > li:nth-child(8) > div > ul > li > div > ul > li:nth-child(8)').addClass('menu-item--active-trail');
    $('#block-mainnavigation-2--2 > ul > li:nth-child(8) > div > ul > li > div > ul > li:nth-child(8) > a').addClass('is-active');
  }

  //Add the active class to the left menu 'Focal points'
  if ($(".node--type-fop-page")[0]) {
    $( "#block-mainnavigation-2--2 > ul > li:nth-child(9) > div > ul > li > div > ul > li:nth-child(4) > span" ).trigger( "click" );
    $('#block-mainnavigation-2--2 > ul > li:nth-child(9) > div > ul > li > div > ul > li:nth-child(4) > div > ul > li').addClass('menu-item--active-trail');
    $('#block-mainnavigation-2--2 > ul > li:nth-child(9) > div > ul > li > div > ul > li:nth-child(4) > div > ul > li > a').addClass('is-active');
  }

  //Add the active class to the left menu Procurement / 'Calls'
  if ($(".node--type-calls")[0]) {
    $('#block-mainnavigation-2--2 > ul > li:nth-child(9) > div > ul > li > div > ul > li:nth-child(7)').addClass('menu-item--active-trail');
    $('#block-mainnavigation-2--2 > ul > li:nth-child(9) > div > ul > li > div > ul > li:nth-child(7) > a').addClass('is-active');
  }

  //Add the active class to the left menu Job vacancies
  if ($(".node--type-job-vacancies")[0]) {
    $('#block-mainnavigation-2--2 > ul > li:nth-child(9) > div > ul > li > div > ul > li:nth-child(6)').addClass('menu-item--active-trail');
    $('#block-mainnavigation-2--2 > ul > li:nth-child(9) > div > ul > li > div > ul > li:nth-child(6) > a').addClass('is-active');
  }

  //Add the active class to the left menu Directives
  if ($(".node--type-directive")[0]) {
    $('#block-mainnavigation-2--2 > ul > li:nth-child(6) > div > ul > li > div > ul > li:nth-child(2)').addClass('menu-item--active-trail');
    $('#block-mainnavigation-2--2 > ul > li:nth-child(6) > div > ul > li > div > ul > li:nth-child(2) > a').addClass('is-active');
  }

  //Add the active class to the left menu Directives
  if ($(".node--type-guideline")[0]) {
    $('#block-mainnavigation-2--2 > ul > li:nth-child(6) > div > ul > li > div > ul > li:nth-child(3)').addClass('menu-item--active-trail');
    $('#block-mainnavigation-2--2 > ul > li:nth-child(6) > div > ul > li > div > ul > li:nth-child(3) > a').addClass('is-active');
  }

  //Hierarchical view
  $('#tree ul').css('display', 'none');
  $("#tree .has-child > .expand_menu").click(function(){
    $(this).toggleClass('expanded');
    $(this).parent('.has-child').find('> .item-list > ul').slideToggle('slow');
  });

  //Tooltip Thesaurus
  if ($(".content-tooltip img")[0]) {
    $('.content-tooltip img').click(function() {
      $(".thesaurus-tooltip").fadeIn(300);
    });
    $('.close-thes-tooltip').click(function() {
      $(".thesaurus-tooltip").fadeOut(300);
    });
  }

  //Display clear filter button if url has parameter
  if ($("#edit-reset")[0]) {
    let url = window.location.href;
    if(url.includes('?')){
      $('#edit-reset').addClass('custom-active');
      $('.views-exposed-form').addClass('custom-active-filter');
    }
  }

  // Our story show more or less anniversaries
  if ($(".about-eu-osha-eu-osha-2004-2019-our-story")[0]) {
    $(".about-eu-osha-eu-osha-2004-2019-our-story").ready(function () {
      // Hide items on start
      let elements = document.getElementsByClassName("anniversary-elements");
      let isHidingElements = true
      showOrHideNumberOfElements(elements, isHidingElements)
      //Get view all button from the page footer
      const viewAllButton = document.getElementsByClassName("field-content see-more-arrow pull-right")[0]
      const viewAllText = viewAllButton.children.item(0).innerHTML
      viewAllButton.children.item(0).innerHTML = viewAllText.split("/")[0]

      viewAllButton.addEventListener("click", function () {
        isHidingElements = !isHidingElements
        showOrHideNumberOfElements(elements, isHidingElements)
        viewAllButton.children.item(0).innerHTML = isHidingElements
            ? viewAllText.split("/")[0]
            : viewAllText.split("/")[1]
      })
    });

    function showOrHideNumberOfElements(elements, toHide) {
      // When it hides elements, only three should be displayed
      const ELEMENTS_TO_SHOW = toHide ? 3 : 0;
      for(let i = ELEMENTS_TO_SHOW; i < elements.length; ++i) {
        elements[i].style.display = toHide ? "none" : "block"
      }
    }
  }

  //Remove equal elements in MSD Glosaary filter
  if ($(".view-msd-glossary")[0]) {
    var seen = {};
    $('.term-glosssary-msd-letter').each(function() {
      var txt = $(this).text();
      if (seen[txt])
        $(this).remove();
      else
        seen[txt] = true;
    });
    //Scroll up the anchor
    $(".term-glosssary-msd-letter").click(function(event){
      setTimeout(function() {
        $('html,body').animate({
          scrollTop: $(window).scrollTop() -500
        });
      }, 100);
    });
  }

  // Add class active in menu Glossary when filtered by letter
  if (window.location.href.indexOf("alphabetical") > -1) {
    $('#block-thesaurus > ul > li:nth-child(2)').addClass("menu-item--active-trail");
  }

  // Add class in letter A when load the page Glossary
  if (!$(".page-view-glossary .view-glossary .views-summary a.is-active")[0]) {
    $('.page-view-glossary .view-glossary .views-summary:nth-child(4) a').addClass('is-active');
  }

  // Show original language description on musculoskeletal disorders
  if ($(".view-description-original-language")[0]) {
    $('#block-ncwtheme-content > div > div > div.description-wrapper > .field--name-field-body-original').show();
    $('#block-ncwtheme-content > div > div > div.description-wrapper > .field--name-body').hide();
  }

  // Hide aprobe form
  if ($("#approve-form")[0]) {
    $('#content-moderation-entity-moderation-form').hide();
  }else{
    $('#content-moderation-entity-moderation-form').show();
  }
  if($(".osha-workflow-list-widget p.active") && $(".osha-workflow-list-widget p.active").text() == "To Be Approved" && $("input[name='approving']").val()=="approver") {
    $('#content-moderation-entity-moderation-form').hide();
  }else{
    $('#content-moderation-entity-moderation-form').show();
  }

  // Accesskey for custom elements
  $('#edit-lang-dropdown-select').attr('accessKey','L');
  $('#edit-search-api-fulltext').attr('accessKey','Q');


  // Move block-facet-blockdate-of-directive over the searh button

  $(".sidebar-first").each(function(){
    if($(this).find(".block-facet-blockdate-of-directive").length>0){
      let $dateDirective=$(this).find(".block-facet-blockdate-of-directive").html();
      $(this).find("#views-exposed-form-search-directives-search-directory-page").find(".form-actions js-form-wrapper mb-3").before($dateDirective);
    };

  })

});

//Load function
(function($) {
  $(window).on('load', function() {

    //Selected term Hierarchical View
    if ($(".hierarchical-tree")[0]) {
      var getUrlParameter = function getUrlParameter(sParam) {
        var sPageURL = window.location.search.substring(1),
          sURLVariables = sPageURL.split('&'),
          sParameterName,
          i;

        for (i = 0; i < sURLVariables.length; i++) {
          sParameterName = sURLVariables[i].split('=');

          if (sParameterName[0] === sParam) {
            return typeof sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
          }
        }
        return false;
      };
      var thesaurusTermId = getUrlParameter('term');
      var thesaurusClass = '.thesaurus-term-' + thesaurusTermId;

      // Add the class to highlight the term
      $(thesaurusClass).addClass('term-selected');

      // Open the accordion for the current term
      $(thesaurusClass).parent().siblings("span.expand_menu").click();

      // Open the accordion for the parent elements
      var elem = $(thesaurusClass).closest("div.item-list");
      while(elem.length > 0)
      {
        elem.siblings("span.expand_menu").click();
        elem = elem.parent().closest("div.item-list");
      }
    }

    //Move dateofdirective filter (Legislation - EU Directives)
    if ($("#block-dateofdirective")[0]) {
      $('#block-dateofdirective').insertBefore('#views-exposed-form-search-directives-search-directory-page .form-actions');
    }

    //Move Language Selector to the menu responsive
    if ($(window).width() < 992) {
      $('#block-languagedropdownswitchercontent').appendTo('#navbar-main');
      $('#block-languagedropdownswitchercontent').show();
      $('#block-searchsite').appendTo('#navbar-main');
      $('#block-searchsite').show();
      $('#block-headermenu').appendTo('#navbar-main');
      $('#block-headermenu').show();
    }

    //Move alert wrapper
    if ($(".alert-wrapper")[0]) {
      $('.alert-wrapper').insertBefore('nav.tabs');
    }

  });
})(jQuery);
