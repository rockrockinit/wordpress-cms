/**
 * Paginator class attempts to generically handle different types of custom pagination
 *
 * @param opts
 * @constructor
 */
var Paginator = function (opts) {
    var options = {
        router: undefined,
        count: 30,
        page: 1,
        pages: 1,
        total: 0
    };

    this.route = function (page) {
        var self = this;

        if (typeof self.go === 'function') {
            self.go(self.page);
        } else if (self.router && typeof self.router.go === 'function') {
            self.router.go(self.page);
        }
    };

    this.setPage = function (page) {
        var self = this;
        self.page = page;

        self.route();
    };

    this.prev = function () {
        var self = this;
        self.page--;
        self.page = self.page <= 0 ? 1 : self.page;

        self.route();
    };

    this.next = function () {
        var self = this;
        self.page++;
        self.page = self.page > self.pages ? self.pages : self.page;

        self.route();
    };

    return $.extend(this, options, opts);
};