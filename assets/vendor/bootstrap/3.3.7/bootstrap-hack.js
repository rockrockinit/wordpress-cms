var bootstrapCss = 'bootstrapCss';

if (!document.getElementById(bootstrapCss))
{
    var head = document.getElementsByTagName('head')[0];
    var bootstrapWrapper = document.createElement('link');
    bootstrapWrapper.id = bootstrapCss;
    bootstrapWrapper.rel = 'stylesheet/less';
    bootstrapWrapper.type = 'text/css';
    bootstrapWrapper.href = '../wp-content/plugins/cms/assets/vendor/bootstrap/3.3.7/css/bootstrap-wrapper.less';
    bootstrapWrapper.media = 'all';
    head.appendChild(bootstrapWrapper);

    var lessjs = document.createElement('script');
    lessjs.type = 'text/javascript';
    lessjs.src = '../wp-content/plugins/cms/assets/vendor/bootstrap/3.3.7/js/less.min.js';
    head.appendChild(lessjs);

    //load other stylesheets that override bootstrap styles here, using the same technique from above

    var customStyles = document.createElement('link');
    customStyles.onload = function () {
        setTimeout(function () {
            jQuery('.bootstrap-wrapper').fadeIn();

            if (jQuery.fn.summernote) {
                jQuery('.summernote').summernote({
                    height: 200
                });
            }
        }, 300);
    }
    customStyles.id = "customStyles";
    customStyles.rel = 'stylesheet';
    customStyles.type = 'text/css';
    customStyles.href = '../wp-content/plugins/cms/assets/vendor/bootstrap/3.3.7/css/overrides.css';
    customStyles.media = 'all';
    head.appendChild(customStyles);
}