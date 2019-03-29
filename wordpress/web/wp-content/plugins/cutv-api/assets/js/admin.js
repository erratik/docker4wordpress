var $ = jQuery;

jQuery(function($) {

    if (typeof angular != 'undefined') angular.bootstrap(document, ['cutvApiAdminApp']);

    $('body')
        .on('mouseover', '.ui.checkbox', function () {

            $(this).checkbox();
        });


})

// UTILS
var _cutv = {
    ajax : function(url, data, options) {
        return new MakePromise({ url: url, data: data, options: options });
    }
};
function MakePromise(options){

    //log({msg: "A promise is being made...", color: 'purple' });

    var params = {
        method: 'POST',
        cache: true,
        showErrors: true,
        success: function(result) {
            //log({msg:"Promise went through!", color: 'purple' });
            //console.groupEnd();
            promise.resolve(result);
        },
        error: function(jqXHR, textStatus, error) {

            if ( jqXHR.status == 400 ) {
                errorMessage = jqXHR.responseText;
                log({msg: "%c(╯°□°）╯ should be accompanied by custom message to display", color: 'red' });
                log({msg: errorMessage , color: 'red' });


            } else {
                log({msg: "%c(╯°□°）╯", color: 'red' });
                errorMessage = { error: jqXHR.message, statusCode: jqXHR.code};
            }

            promise.reject(errorMessage);
        }
    };

    $.extend(params, options.options);

    var promise = $.Deferred();
    $.ajax({
        type: params.method,
        url: options.url,
        data: options.data,
        success: params.success,
        error: params.error
    });
    // console.log(promise)
    return promise;
}
DEBUG_LEVEL = window.location.hostname == 'cutv.erratik.ca' ? 3 : 0;
if (navigator.appName == "Microsoft Internet Explorer") DEBUG_LEVEL = 0;
function log(options) {

    var defaults = {
        msg: null,
        level: DEBUG_LEVEL,
        group: false,
        color: 'blue'
    };
    $.extend(defaults, options);

    if ( DEBUG_LEVEL > 2 && navigator.appName != "Microsoft Internet Explorer") {
        console.log("%c" + options.msg, "color:"+options.color+";");
    }
}

