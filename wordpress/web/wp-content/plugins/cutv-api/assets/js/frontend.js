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


        let channels = JSON.parse(res);

        channels = channels.filter(function(channel) {
            return !!channel.enabled;
        });

        $('#menu-item-7736').append('<ul id="channel-links-list"><li></li></ul>');
        $('.page-wrapper .content').before('<div id="cutv-channels-top"></div>');

        const $top_channels = new LoadTemplate(
            'cutv-channels-top',
            'channels-top-view', {
                data: channels.filter(function(channel) {
                    return !!channel.featured
                })
            }).create(function() {
            console.log($top_channels);
            debugger;
        });

        const $list_channels = new LoadTemplate(
            'channel-links-list',
            'channels-list', {
                data: channels
            }).create(function() {
            console.log($list_channels);
            debugger;
        });

        // $.get('/wp-content/plugins/cutv-api/views/channels-top-view.hbs', function(data) {
        //     var template = Handlebars.compile(data);
        //     $('.cutv-channels').html(template(JSON.parse(res)));
        // }, 'html');

        // $('#menu-item-7736').append('<ul class="cutv-channels"></ul>');
        // $.get('/wp-content/plugins/cutv-api/views/channels-list.hbs', function(data) {
        //     var template = Handlebars.compile(data);
        //     $('#cutv-channels-list').html(template(JSON.parse(res)));
        // }, 'html');

        $('.sidebar_menu').css('height', $('.page-wrapper').height() - ($('.main > header').height() + $('.cutv-channels-top').height()) + 'px');



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