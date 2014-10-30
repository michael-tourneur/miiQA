require(['jquery', 'uikit!autocomplete,pagination','domReady!'], function($, uikit) {
  var section, table, filters, spinnerFilters, buttonFiltersActive, orders, spinnerOrders, buttonOrdersActive, search, pagination, page, baseUrl, currentUrl, data, autocomplete, reloadQuestionsTable;

  section               = $('#miiQA-index');
  table                 = $('#questions-table', section);
  filters               = $('#questions-filters', section);
  spinnerFilters        = $('.js-spinner', filters),
  buttonFiltersActive   = $('a.active', filters);
  orders                = $('#questions-orders', section);
  spinnerOrders         = $('.js-spinner', orders),
  buttonOrdersActive     = $('.uk-button.active', orders);
  search                = $('[name="filter[search]"]', section),
  pagination            = $('[data-uk-pagination]', section);
  page                  = $('[name="page"]', section);
  baseUrl               = '//' + window.location.host;
	currentUrl            = baseUrl + window.location.pathname;

  console.log(search);
  data                  = {
    filter: {
      search  : search.val(),
      status  : filters.find('.active[data-status]').data('status'),
      orderby : orders.find('.active[data-orderby]').data('orderby'),
    },
    page  : page.val(),
  };

	autocomplete = uikit.autocomplete(search.parent(), {
    source: function(release) {
      $.getJSON(currentUrl, { filter: {autocomplete: search.val()} }, function(Getdata) {
        data.filter.search = search.val();
        release(Getdata.list);
      });
    },
    template: '<ul class="uk-nav uk-nav-autocomplete uk-autocomplete-results">{{~items}}<li data-value="{{$item.title}}" data-url="{{$item.url}}" data-id="{{$item.id}}"><a>{{$item.title}}</a></li>{{/items}}</ul>'
  });

	autocomplete.element.on('autocomplete-select', function(e, data){
		if(typeof data.url !== 'undefined' && data.url !== '')
    	window.location.href = baseUrl + data.url;
  });

  reloadQuestionsTable = function() {
    $.get(currentUrl, data, function(data) {
      table.html(data.table);
      pagination.toggleClass('uk-hidden', data.total < 2).data('pagination').render(data.total);
      $('.js-spinner').addClass('uk-hidden');
    });
  }

  // pagination
  pagination.on('uk-select-page', function(e, index) {
      page.val(index);
      data.page = page.val();
      reloadQuestionsTable();
  });

  // table order filters
  orders.on('click', '.uk-button', function (e) {
      var button = $(this);

      e.preventDefault();
      e.stopImmediatePropagation();

      if(!button.is(buttonOrdersActive)) {
        data.filter.orderby = button.data('orderby');
        spinnerOrders.removeClass('uk-hidden');
        reloadQuestionsTable();
        button.addClass('active').addClass('uk-button-danger');
        buttonOrdersActive.removeClass('active').removeClass('uk-button-danger');

        buttonOrdersActive = button;
      }
  });

  // table status filters
  filters.on('click', 'a', function (e) {
      var button = $(this);

      e.preventDefault();
      e.stopImmediatePropagation();

      if(!button.is(buttonFiltersActive)) {
        data.filter.status = button.data('status');
        spinnerFilters.removeClass('uk-hidden');
        reloadQuestionsTable();
        button.addClass('active');
        buttonFiltersActive.removeClass('active');

        buttonFiltersActive = button;
      }
  });

});
