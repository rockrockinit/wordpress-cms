const Base = {
    search: undefined,
    term: undefined,
    debugLevel: 5,
    debugLevels: [
        'error',
        'warn',
        'debug',
        'log',
        'info'
    ],

    paginators: {},

    mounted: function () {
        this.info('Mounted');
    },

    beforeUpdate: function () {
        this.info('BeforeUpdate');
    },

    created: function () {
        var self = this;

        if (self.constructor.name === 'VueComponent') {
            self.$root.info('Component Created:', self.$options.name);
            self.$root.buildPagination();
            self.setup();
        } else {
            self.info('Created');

            this.init();
        }
    },

    updated: function () {
        var self = this;

        if (self.constructor.name === 'VueComponent') {
            self.$root.info('Component Updated:', self.$options.name);
            self.setup();
        } else {
            self.info('Updated');
        }
    },

    filters: {
        formatPhone: function (str) {
            if (typeof str === 'string') {
                var phone = str.replace(/[^0-9]/g, '');

                if (phone.length === 10) {
                    str = '(' + phone.slice(0, 3) + ') ' + phone.slice(3, 6) + '-' + phone.slice(6);
                }
            }

            return str;
        },

        formatDate: function (date) {
            return moment(date).format('MM/DD/YY');
        },

        formatTime: function (date) {
            return moment(date).format('h:mma');
        },

        replace: function (str, search, replace) {
            var regexp = typeof search === 'string' ? new RegExp(search, 'gi') : search;
            return str.replace(regexp, replace, str);
        },

        capitalize: function (str) {
            return str.replace(/\b\w/g, l => l.toUpperCase());
        },

        /**
         * Adds commas to numbers.
         *
         * @param number|string num The number to add commas to.
         * @return string A comma seperated numerical string.
         */
        formatNumber: function (num) {
            num = (typeof num === 'number') ? String(num) : num;
            num += '';

            let x = num.split('.'),
                x1 = x[0],
                x2 = x.length > 1 ? '.' + x[1] : '',
                rgx = /(\d+)(\d{3})/;

            while (rgx.test(x1)) {
                x1 = x1.replace(rgx, '$1' + ',' + '$2');
            }

            return x1 + x2;
        }
    },

    methods: {
        init: function () {
            var self = this;

            // Set Tabs
            self.$router.options.routes.forEach(function (route) {
                if (typeof route.tab === 'boolean' && route.tab) {
                    self.tabs.push(route);
                }
            });

            // Set Page
            if (self.$route.path === '/') {
                self.go({
                    page: self.page,
                    route: self.type
                });
            } else {
                self.fetchData();
            }
        },

        console: function () {
            if (arguments.length === 2) {
                let type = arguments[0],
                    args = Array.prototype.slice.call(arguments[1]);

                if (typeof console[type] === 'function'
                    && (Base.debugLevels.indexOf(type) + 1) <= Base.debugLevel) {

                    console[type].apply(console, args);
                }
            }
        },

        debug: function () {
            this.console('debug', arguments);
        },

        log: function () {
            this.console('log', arguments);
        },

        error: function () {
            this.console('error', arguments);
        },

        info: function () {
            this.console('info', arguments);
        },

        warn: function () {
            this.console('warn', arguments);
        },

        settings: function () {
            Base.search = $('.search select[name="search"]').val();
            Base.term = $('.search input[name="term"]').val();
        },

        getType: function () {
            return this.$options.filters.capitalize(this.$options.type);
        },

        setup: function () {
            var self = this;

            self.$root.tab = this;
            self.$root.buildPagination();

            self.info('Component Setup:', this.$options.name);

            if (Base.term) {
                $('.search select[name="search"]').val(Base.search);
                $('.search input[name="term"]').val(Base.term);
            }

            if (typeof self.afterupdate === 'function') {
                self.info('afterupdate');

                self.afterupdate.apply(self);
                self.afterupdate = undefined;
            }
        },

        showLoading: function (msg) {
            msg = msg || 'Loading...';

            this.section = 'loading';
            this.loading = msg;
        },

        debounceSearch: _.debounce(function (e) {
                var self = this;
                self.doSearch();
            }, 1250),

            doSearch: function() {
            var self = this;
            self.term = $('.pages [name="term"]').val();
            self.$root.doSearch(self.term, self.search);
        },

        buildPagination: function () {
            var self = this,
                $paginator = undefined,
                $pagination = $('.pagination-pages');

            self.info('buildPagination');

            // Find paginator for section
            if (!$.isEmptyObject(Base.paginators)) {
                for (var section in Base.paginators) {
                    if ($pagination.closest('.' + section).length) {
                        $paginator = Base.paginators[section];
                    }
                }
            } else if (typeof self.$store === 'object' && typeof self.$store.state.paginators === 'object') {
                for (var section in self.$store.state.paginators) {
                    if ($pagination.closest('.' + section).length) {
                        $paginator = self.$store.getters.paginator(section);
                    }
                }
            }

            // Build Pagination
            if ($paginator && $paginator.pages > 1) {
                var page = $paginator.page,
                    pages = $paginator.pages,
                    $prev = $('.prev', $pagination),
                    $next = $('.next', $pagination),
                    max = 12,
                    middle = Math.ceil(max / 2),
                    before = middle - 1,
                    after = (max - before) - 1,
                    last_first = pages - max;

                last_first = last_first <= 0 ? 1 : last_first;

                $('.page', $pagination).remove();

                // Real Middle
                middle = page < middle ? middle : page;

                var first = middle - before,
                    last = middle + after;

                // Last First
                first = first > last_first ? last_first : first;

                // Last Last
                last = last > pages ? pages : last;

                for (var i = max; i >= 0; i--) {
                    var num = first + i;

                    if (num <= pages) {
                        var $li = $('<li class="page" />');
                        $li.append('<a data-page="' + num + '">' + num + '</a>');

                        if (num === page) {
                            $li.addClass('selected');
                        }

                        $li.insertAfter($prev);
                    }
                }

                // Last
                if (pages > max && first < (pages - max)) {
                    // Etc
                    $li = $('<li class="page etc"><a>...</a></li>');
                    $li.insertBefore($next);

                    // Last
                    $li = $('<li class="page">');
                    $li.append('<a data-page="' + pages + '">' + pages + '</a>');
                    $li.insertBefore($next);
                }

                $('a[data-page]', $pagination).on('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    var $btn = $(e.target),
                        page = $btn.data('page');

                    $paginator.setPage(page);
                });

                $('.prev a', $pagination).off('click').on('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $paginator.prev();
                });

                $('.next a', $pagination).off('click').on('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $paginator.next();
                });

                $pagination.show();
            } else {
                $pagination.hide();
            }
        },

        /**
         * Triggers a specified route
         *
         * Examples:
         * self.go(1);
         * self.go({
         *    page: 1,
         *    company: 1234
         * });
         * self.go({
         *    page: 2,
         *    route: 'reports'
         * });
         *
         * @param options
         * @param route
         */
        go: function (options) {
            options = typeof options !== 'object' ? {page: options} : options;

            var self = this,
                route = typeof options.route === 'undefined' ? self.type : options.route;

            // Get route by other methods
            if (typeof route !== 'object') {
                var routes = self.$router.options.routes;

                if (typeof route === 'string') {
                    var found = false;

                    $.each(routes, function (i, item) {
                        var regexp = new RegExp(route, 'i');

                        if (!found && regexp.test(item.name)) {
                            route = item;
                            found = true;
                        }
                    });
                } else if (typeof route === 'number' && routes.length >= route) {
                    route = routes[route - 1];
                }
            }

            if (typeof route === 'object') {
                var uri = route.path,
                    items = uri.split(/\//);

                // Replace dynamic uri placeholders
                $.each(items, function(i, item) {
                    if (/^:/.test(item)) {
                        var value = item,
                            found = false,
                            opts = $.extend(Base, options),
                            props = item.replace(/^:/i, '').split(/_/),
                            data = opts;

                        $.each(props, function (i, prop) {
                            if (typeof data[prop] !== 'undefined') {
                                data = data[prop];
                            }
                        });

                        if (typeof data === 'object' && /^(number|string)$/i.test(typeof data.id)) {
                            value = data.id;
                        }

                        if (/^(number|string|boolean)$/i.test(typeof data)) {
                            value = data;
                        }

                        var regexp = new RegExp(item, 'i');
                        uri = uri.replace(regexp, value);
                    }
                });

                self.settings();
                self.$router.push(uri);
            }
        },

        setPage: function (e) {
            var self = this,
                $btn = $(e.target),
                page = $btn.data('page');

            self.go(page);
        },

        prev: function (e) {
            var self = this;
            self.page--;
            self.page = self.page <= 0 ? 1 : self.page;

            self.go(self.page);
        },

        next: function (e) {
            var self = this;
            self.page++;
            self.page = self.page > self.pages ? self.pages : self.page;

            self.go(self.page);
        },

        showTab: function (route) {
            var self = this,
                tab = self.tab,
                section = route.name.toLowerCase(),
                isTab = section === tab.$options.name;

            if (isTab) {
                if (tab.section !== section) {
                    tab.section = section;
                    tab.page = 1;

                    if (tab.chart) {
                        tab.chart = tab.chart.destroy();
                    }
                }
            } else {
                $('.search input[name="term"]').val('');

                Base.search = undefined;
                Base.term = undefined;

                self.go({
                    page: 1,
                    route: route
                });
            }
        },

        updateTab: function () {
            if (this.tab.page !== 'loading') {
                this.tab.page = this.tab.$options.name;
                this.buildPagination();
            }
        },

        doSearch: function (term, search) {
            var self = this;

            self = self.constructor.name === 'VueComponent' ? self.$root : self;

            self.settings();

            self.go({
                page: 1,
                route: self.type
            });

            // Fetch because the path may not have changed
            self.fetchData();
        }
    }
};