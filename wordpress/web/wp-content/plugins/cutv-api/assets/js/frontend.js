var ajaxurl = '/wp-admin/admin-ajax.php';

$(document).ready(function() {

    _cutv.ajax(ajaxurl, {
        action: 'cutv_get_channels',
        count: true,
        exclude_sources: true,
        json: true
    }).then(function(res) {

        var channels = JSON.parse(res);
        channels = channels.filter(function(channel) {
            return !!channel.enabled;
        });

        $('.page-wrapper .content').before('<div id="featured-channels" class="cutv-channels-top"></div>');

        new LoadTemplate(
            'featured-channels',
            'channels-top-view', {
                data: channels.filter(function(channel) {
                    return !!channel.featured
                })
            }).create();

        $('.primary-menu li:last()').append('<ul id="channels-list" class="channel-links-list"><li></li></ul>');
        new LoadTemplate(
            'channels-list',
            'channels-list', {
                data: channels
            }).create();

    });

});