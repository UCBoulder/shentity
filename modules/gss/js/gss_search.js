(function ($) {
  $(document).ready(function () {
    if ( $( ".gdoc-search" ).length ) {
      $('.gdoc-search').before('<label for="searchtable"><strong>Enter keyword to search </strong></label><input id="searchtable" type="search" autosave="csrsearch" results="1" placeholder="search" name="s">');
      $('#searchtable').keyup(function () {
        searchTable($(this).val());
      });
    }
});
function searchTable(inputVal) {
  var table = $('#gdoc-table');
  table.find('tr').each(function (index, row) {
    var allCells = $(row).find('td');
    if(allCells.length > 0)
    {
      var found = FALSE;
      allCells.each(function (index, td) {
        var regExp = new RegExp(inputVal, 'i');
        if(regExp.test($(td).text()))
        {
          found = TRUE;
          return FALSE;
        }
      });
      if(found == TRUE) { $(row).show();} else { $(row).hide();
      }
    }
  });
}
}(jQuery));
