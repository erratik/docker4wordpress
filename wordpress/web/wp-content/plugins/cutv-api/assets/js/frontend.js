var ajaxurl = '/wp-admin/admin-ajax.php';

jQuery(document).ready(function(e) {

    $('.video-carousel-title, .video-module-title, .video-block-container-wrapper .more_title').each(function() {
        $(this).children().wrapAll('<div></div>');
    });
    $('.main-inner .first-entry:not(:first-child)').remove();

    _cutv.ajax(ajaxurl, {
        action: 'cutv_get_channels',
        json: true
    }).then(function(res) {

        const channels = JSON.parse(res).filter(({
            is_publish
        }) => is_publish === '1');


        $('#menu-item-7736').append('<ul class="channel-links-list"><li></li></ul>');
        $('.page-wrapper .content').before('<div id="cutv-channels-top"></div>');
        // debugger;

        // _cutv.render({
        //     data: channels, // data object
        //     target: $('.cutv-channels-top'), // $el
        //     template: $('#cutv-channels-top'), // $el, handlebar template
        //     callback: function() {
        //         $('.cutv-channels-top').find('ul').addClass('flex');
        //         debugger;
        //         // console.log($('.cutv-channels-top').find('ul'), 'hello');
        //     }
        // });

        // _cutv.render({
        //     target: $('.channel-links-list li:first-child'), // $el
        //     template: $('#cutv-channels-list'), // $el, handlebar template
        //     data: data, // data object
        //     callback: function() {
        //         var $activeChannelItem = $('[data-channel-id="' + location.search.split('playid=')[1] + '"]');
        //         $activeChannelItem.addClass('active');
        //         $activeChannelItem.parents('.menu-item').find('> a').addClass('open active');
        //         if (location.search.split('playid=').length > 1) {
        //             $('.channel-links-list').show()
        //         }
        //     }
        // });

        // var tpl = new LoadTemplate('cutv-channels-top', 'channels-top-view', {
        //     'data': channels
        // });
        // // var tpl = new LoadTemplate('id', 'titles-overview', {'data':'data1'});

        // tpl.create(function() {
        //     console.log('test');
        // });

        // console.log(tpl);

        // debugger;

        // _cutv.render({
        //     data: channels, // data object
        //     target: $('.cutv-channels-top'), // $el
        //     template: tpl.el, // $el, handlebar template
        //     callback: function() {
        //         $('.cutv-channels-top').find('ul').addClass('flex');
        //         debugger;
        //         // console.log($('.cutv-channels-top').find('ul'), 'hello');
        //     }
        // });

        // $('.primary-menu #menu-item-7736 > a').on('click', function(e) {
        //     e.preventDefault();
        //     $(this).siblings('.channel-links-list').slideToggle();
        //     $(this).toggleClass('open');
        // });

        // $('.sidebar_menu').css('height', $('.page-wrapper').height() - ($('.main > header').height() + $('.cutv-channels-top').height()) + 'px');


        $('.page-sidebar-no.page-wrapper').prepend('<div class="cutv-channels-top"></div>');
        $.get('/wp-content/plugins/cutv-api/views/channels-top-view.hbs', function(data) {
            var template = Handlebars.compile(data);
            var featuredChannels = channels.filter(({
                featured
            }) => !!featured);
            $('.cutv-channels-top').html(template(featuredChannels));
        }, 'html');

        $('#menu-item-7736').append('<ul class="cutv-channels"></ul>');
        $.get('/wp-content/plugins/cutv-api/views/channels-list.hbs', function(data) {
            var template = Handlebars.compile(data);
            $('.cutv-channels').html(template(JSON.parse(res)));
        }, 'html');
    });

});

_cutv.render = function(options) {
    'use strict';
    var params = {
        target: null, // $el
        template: null, // $el, handlebar template
        data: null, // data object
        callback: null
    };

    $.extend(params, options);
    var $template = params.template;
    var $target = params.target;

    var source = $template.html();
    var template = Handlebars.compile(source);
    var wrapper = params.data;
    var html = template(wrapper);

    $target.html(html);

    if (typeof params.callback === 'function') {
        params.callback();
    }
}

var LoadTemplate = function(element, template, data) {
    // Check if parenet element is defined as string or object.
    if (typeof element == 'string') {
        this.el = document.getElementById(element);
    } else {
        this.el = element;
    }

    // Store template name and data.
    this.tempName = template;
    this.data = data || null;

    // You can change this to path of your template folder.
    this.folderPath = 'assets/templates/';
};

LoadTemplate.prototype.create = function(callback) {
    var req = new XMLHttpRequest();
    var that = this;

    // Define parameters for request.
    req.open('get', this.folderPath + this.tempName + '.handlebars', true);

    // Wait for request to complete.
    req.onreadystatechange = function() {
        if (req.readyState == 4 && req.status == 200) {
            //Compile HB template, add data (if defined) and place in parent element.
            var compiled = Handlebars.compile(req.response);
            that.el.innerHTML = compiled(that.data);

            // Execute callback function
            callback();
        }
    };

    // Send request.
    req.send();
};

LoadTemplate.prototype.createAndWait = function(callback) {
    var req = new XMLHttpRequest();
    var that = this;

    // Define parameters for request.
    req.open('get', this.folderPath + this.tempName + '.handlebars', true);

    // Wait for request to complete.
    req.onreadystatechange = function() {
        if (req.readyState == 4 && req.status == 200) {
            //Compile HB template, but wait..
            var compiled = Handlebars.compile(req.response);

            // Execute callback function and parse variables.
            callback(compiled, that.el);
        }
    };

    // Send request.
    req.send();
};