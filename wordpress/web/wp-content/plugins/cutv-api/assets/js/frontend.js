var ajaxurl = '/wp-admin/admin-ajax.php';

var data = [];
$(document).ready(function() {

    $('.page-wrapper .content').before('<div id="featured-channels" class="cutv-channels-top"></div>');

    $('.primary-menu li:last()').append('<ul id="channel-links" class="channel-links-list"><li></li></ul>');

    _cutv.ajax(ajaxurl, {
        action: 'cutv_get_channels',
        count: 1,
        exclude_sources: 1,
        json: true
    }).then(function(res) {

        data = JSON.parse(res);

        new LoadTemplate(
            'featured-channels',
            'channels-top-view', {
                data: data.filter(function(x) {
                    return !!x.featured
                })
            }).create();

        new LoadTemplate(
            'channel-links',
            'channel-list', {
                data: data.filter(function(x) {
                    return !!x.enabled;
                })
            }).create();
    });

});