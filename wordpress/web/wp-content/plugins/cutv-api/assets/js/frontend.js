var ajaxurl = '/wp-admin/admin-ajax.php';

jQuery( document ).ready( function ( e ) {

    $('.video-carousel-title, .video-module-title, .video-block-container-wrapper .more_title').each(function(){
        $(this).children().wrapAll('<div></div>');
    });
    $('.main-inner .first-entry:not(:first-child)').remove();

    _cutv.ajax(ajaxurl, {
            action : 'cutv_get_channels',
            json: true
    }).then(function (res) {

/*
        $('#menu-item-7736').append('<ul class="channel-links-list"><li></li></ul>');
        $('.page-wrapper .content').before('<div class="cutv-channels-top"></div>');

        // for the top list, only show enabled channels
        data = _.map(JSON.parse(data), function(o) {
            o.enabled = o.enabled == 'true' ? true : false;
            return o;
        });

        _cutv.render({
            target: $('.cutv-channels-top'), // $el
            template: $('#cutv-channels-top'), // $el, handlebar template
            data: _.filter(data, 'enabled'), // data object
            callback: function() {
                $('.cutv-channels-top').find('ul').addClass('flex');
                console.log($('.cutv-channels-top').find('ul'), 'hello');
            }
        });

        _cutv.render({
            target: $('.channel-links-list li:first-child'), // $el
            template: $('#cutv-channels-list'), // $el, handlebar template
            data: data, // data object
            callback: function () {
                var $activeChannelItem = $('[data-channel-id="'+location.search.split('playid=')[1]+'"]');
                $activeChannelItem.addClass('active');
                $activeChannelItem.parents('.menu-item').find('> a').addClass('open active');
                if (location.search.split('playid=').length > 1) {
                    $('.channel-links-list').show()
                }
            }
        });

        // var tpl = new LoadTemplate('id', 'channels-top', {'data':'data1'});
        // var tpl = new LoadTemplate('id', 'titles-overview', {'data':'data1'});

        // tpl.create(function(){
        //     console.log('test');
        // });

        $('.primary-menu #menu-item-7736 > a').on('click', function (e) {
            e.preventDefault();
            $(this).siblings('.channel-links-list').slideToggle();
            $(this).toggleClass('open');
        });

            $('.sidebar_menu').css('height', $('.page-wrapper').height()-($('.main > header').height()+$('.cutv-channels-top').height())+'px');
    */

        $('.page-sidebar-no.page-wrapper').prepend('<div class="cutv-channels-top"></div>');
        $.get('/wp-content/plugins/cutv-api/views/channels-top-view.hbs', function (data) {
            var template = Handlebars.compile(data);
            var featuredChannels = _.filter( _.filter(JSON.parse(res), 'enabled') , 'featured');
            $('.cutv-channels-top').html(template(featuredChannels));
        }, 'html');

        $('#menu-item-7736').append('<ul class="cutv-channels"></ul>');
        $.get('/wp-content/plugins/cutv-api/views/channels-list.hbs', function (data) {
            var template = Handlebars.compile(data);
            $('.cutv-channels').html(template(JSON.parse(res)));
        }, 'html');
    });

});

_cutv.render = function (options) {
    'use strict';
    var params = {
        target: null, // $el
        template: null, // $el, handlebar template
        data: null, // data object
        callback: null
    };

    $.extend(params, options);
    var $template = params.template;
    var $target   = params.target;

    var source   = $template.html();
    var template = Handlebars.compile(source);
    var wrapper  = params.data;
    var html    = template(wrapper);

    $target.html(html);

    if (typeof params.callback === 'function') {
        params.callback();
    }
}
