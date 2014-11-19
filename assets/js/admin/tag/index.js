require(['jquery', 'uikit!pagination', 'rowselect', 'domReady!'], function($, uikit, RowSelect) {

    var form         = $('#js-tags'),
        showOnSelect = form.find('.js-show-on-select').addClass('uk-hidden'),
        table        = $('.js-table', form).on('selected-rows', function(e, rows) { showOnSelect.toggleClass('uk-hidden', !rows.length); }),
        rowselect    = new RowSelect(table),
        pagination   = $('[data-uk-pagination]', form),
        page         = $('[name="page"]', form),
        addTag       = $('#addTag'),
        addTagForm   = $('#addTagForm');

    // delete button
    form.on('click', '[data-action]', function(e) {
        e.preventDefault();

        $.post($(this).data('action'), form.serialize(), function(data) {
            uikit.notify(data.message, data.error ? 'danger' : 'success');
            selectPage(page.val());
        });
    })

    // submit filters
    .on('keyup', 'input[name^="filter"]', uikit.Utils.debounce(function() {
        selectPage(0);
    }, 200))
    .on('change', 'select[name^="filter"]', function() {
        selectPage(0);
    })
    .on('submit', function(e) {
        e.preventDefault();
    });

    // pagination
    pagination.on('uk.pagination.select', function(e, index) {
        page.val(index);

        $.post(form.data('action'), form.serialize(), function(data) {
            table.html(data.table);
            pagination.toggleClass('uk-hidden', data.total < 2).data('pagination').render(data.total);
            $('.uk-alert', form).toggleClass('uk-hidden', data.total > 0);
            rowselect.fetchRows();
        });
    });

    function selectPage(index) {
        console.log(index);
        pagination.data('pagination').selectPage(index);
    }

    addTag.on('click', function(e){
      e.preventDefault();
      addTagForm.toggleClass('uk-hidden');
    });

    // add button
    addTagForm.on('submit', function(e){
      e.preventDefault();
      $.post($(this).attr('action'), $(this).serialize(), function(data) {
          uikit.notify(data.message, data.error ? 'danger' : 'success');
          selectPage(page.val());
      });
    });
});
