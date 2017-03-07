if ($('#modal-cms-ids').length) {
    var CmsIds = new Vue({
        delimiters: ['${', '}'],

        el: '#modal-cms-ids',

        data: {
            page: 1,
            count: 30,
            total: 0,
            loading: false,
            pages: undefined,
            records: undefined,
            columns: undefined
        },

        watch: {
            '$route': 'fetchData'
        },

        created: function () {
            var self = this;

            self.info('Created');

            self.init();
        },

        mounted: Base.mounted,

        beforeUpdate: Base.beforeUpdate,

        updated: function () {
            var self = this;

            self.buildPagination();
        },

        filters: $.extend(Base.filters, {}),

        methods: $.extend(Base.methods, {
            getValue: function (value) {
                value = typeof value === 'string' ? value : '';
                value = value.trim();
                value = value.replace(/(<([^>]+)>)/ig, "");

                if (value.length > 50) {
                    value = value.substring(0, 50).trim() + '...';
                }

                return value;
            },

            init: function () {
                var self = this;
            },

            doSearch: function () {
                var self = this;

                self.page = 1;

                self.getRecords();
            },

            showPage: function (page) {
                var self = this;

                self.page = page;
                self.getRecords();
            },

            getRecords: function () {
                var self = CmsIds,
                    search = $('.search [name="search"]').val(),
                    url = cms.site_url + '/index.php/wp-json/cms/get/' + cms.modal.data('table'),
                    query = [];

                console.log('Modal:', cms.modal);

                self.loading = true;

                url += '/page/' + self.page + '/count/' + self.count;

                // Handle Searches
                if (search) {
                    search = encodeURI(search.trim());

                    if (search.length) {
                        url += '/search/' + search;
                    }
                }

                if (query.length) {
                    url += '?' + query.join('&');
                }

                self.log('Url:', url);

                $.ajax({
                    url: url,
                    success: function (data) {
                        self.columns = [];
                        self.records = data.successes[0].records;
                        self.pages = data.successes[0].pages;
                        self.total = data.successes[0].total;

                        if (self.records) {
                            var record = self.records[0];

                            for (var column in record) {
                                self.columns.push(column);
                            }
                        }

                        Base.paginators['section-forms'] = new Paginator({
                            go: self.showPage,
                            count: data.successes[0].count,
                            page: data.successes[0].page,
                            pages: data.successes[0].pages,
                            total: data.successes[0].total
                        });

                        self.loading = false;
                    }
                });
            },

            fetchData: function () {

            },

            addSelected: function () {
                var self = this,
                    $component = cms.modal,
                    ids = [];

                $.each($('input[name="forms"]:checked'), function () {
                    ids.push($(this).val());
                });

                if (!ids.length) {
                    alert('Please select a item to add.');
                } else {
                    var records = [];

                    //console.log(self.records);

                    $.each(self.records, function (i, record) {
                        if ($.inArray(record.id, ids) >= 0) {
                            records.push(record);
                        }
                    });

                    if (records.length) {
                        var $table = $('table', $component);

                        $('.no-records', $table).remove();

                        $.each(records, function (i, record) {
                            if (!$('[data-id="' + record.id + '"]').length) {
                                var id = record.id,
                                    $frag = $('<tr data-id="' + id + '"></tr>'),
                                    $ids = $('.ids', $component),
                                    ids = $ids.val(),
                                    index = ids.indexOf(String(id));

                                ids = ids ? ids.split(/,|\|/) : [];

                                $('th', $table).each(function () {
                                    var column = $(this).data('column'),
                                        value = self.getValue(record[column]);

                                    $frag.append('<td>' + value + '</td>');
                                });

                                var $btn = $($('#tmpl-btn-remove').html());

                                $('td:last', $frag).append($btn).addClass('text-right');

                                $('tbody', $table).append($frag);

                                if (index < 0) {
                                    ids.push(id);
                                    $ids.val(ids.join(','));
                                }

                                self.$forceUpdate();
                            }
                        });
                    }
                }
            }
        })
    });
}