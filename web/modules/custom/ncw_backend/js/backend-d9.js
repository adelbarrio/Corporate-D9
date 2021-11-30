jQuery(document).ready(function ($) {
  $('tr').each(function () {
    if ($('td:nth-child(1):contains("Red")', this).length) {
      $(this).addClass('red');
    }
    if ($('td:nth-child(1):contains("Black")', this).length) {
      $(this).addClass('black');
    }
    if ($('td:nth-child(1):contains("Grey")', this).length) {
      $(this).addClass('grey');
    }
  });

  // make paste from word button work
  if (CKEDITOR) {
    CKEDITOR.on("instanceReady", function (event) {
      (function () {

        let pastetools = window.CKEDITOR.plugins.pastetools;

        function tryWrapCreateStyleStack() {
          let createStyleStack = pastetools.filters.common?.styles?.createStyleStack;
          if (createStyleStack && !createStyleStack.wrapped) {
            pastetools.filters.common.styles.createStyleStack = (element, ...args) => {
              let retVal = createStyleStack(element, ...args);
              element.attributes.style = element.attributes.style || '';
              return retVal;
            };
            pastetools.filters.common.styles.createStyleStack.wrapped = true;
          }
        }

        let createFilter = pastetools.createFilter;
        pastetools.createFilter = function (...args) {
          let retVal = createFilter.call(this, ...args);
          tryWrapCreateStyleStack();
          return retVal;
        };
        let loadFilters = pastetools.loadFilters;
        pastetools.loadFilters = function (...args) {
          let retVal = loadFilters.call(this, ...args);
          tryWrapCreateStyleStack();
          return retVal;
        };
      })();

      event.editor.on("beforeCommandExec", function (event) {
        // Show the paste dialog for the paste buttons and right-click paste
        if (event.data.name == "paste") {
          event.editor._.forcePasteDialog = true;
        }
        // Don't show the paste dialog for Ctrl+Shift+V
        if (event.data.name == "pastetext" && event.data.commandData.from == "keystrokeHandler") {
          event.cancel();
        }
      });

    });
  }

});
