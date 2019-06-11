'use strict';

/**
 * @ngdoc function
 * @name cutvApiAdminApp.controller:MainCtrl
 * @description
 * # MainCtrl
 * Controller of the cutvApiAdminApp
 */
angular.module('cutvApiAdminApp')

.service('ChannelService', function($http, $timeout) {
    var ChannelService = {};

    // const wpAdminAjaxpath = `/wp-admin/admin-ajax.php?action=`;

    ChannelService.getChannels = function(channelId = null) {
        return $http.get(`/wp-admin/admin-ajax.php?action=cutv_get_channels&channel=${channelId}&json=true`).then(function(res) {
            // console.log(res.data.filter(c => c.pid === channelId));
            return channelId ? res.data.filter(c => c.pid === channelId)[0] : res.data;
        });
    };

    ChannelService.getSources = () => {
        return $http.get(`/wp-admin/admin-ajax.php?action=cutv_get_sources_info&json=true`).then(function(res) {
            return res.data;
        });
    };


    ChannelService.makeSourceObj = function(sources) {
        var channel_id = scope.channelId;
        scope.channel['sources'] = scope.sources.filter((src) => {
            return src.selected
        });
    }


    ChannelService.moveSourceVideos = function(currentSrc, newSrc, movePlaylists) {
        return $http.get(`/wp-admin/admin-ajax.php?action=cutv_move_source_videos&currentSrc=${currentSrc}&newSrc=${newSrc}&movePlaylists=${movePlaylists}`).then(function(res) {
            return res.data;
        });
    };

    ChannelService.getSourceVideos = function(sources) {
        return $http.get(`/wp-admin/admin-ajax.php?action=cutv_get_sources_videos&sources=${sources.join(',')}&json=true`).then(function(res) {
            return res.data;
        });
    };
    ChannelService.updateVideos = function(videos) {
        return $http.get(`/wp-admin/admin-ajax.php?action=cutv_update_videos&video_ids=${videos.join(',')}&json=true`).then(function(res) {
            return res.data;
        });
    };

    ChannelService.wpRequest = function(query) {
        return $http.get(`/wp-admin/admin-ajax.php?${toQueryString(query)}&json=true`).then(function(res) {
            return res.data;
        });
    };

    ChannelService.handlePluginAction = (data) => {
        data.json = true;

        return $http.get(ajaxurl, {
            params: data
        }).then(res => res.data);
    };

    ChannelService.updateChannel = function($scope, update = true) {

        debugger;
        var query = {
            action: 'cutv_update_channel',
            channel: $scope.channel.pid,
            name: $scope.channel.playlist_name,
            enabled: $scope.channel.enabled,
            featured: $scope.channel.featured,
            image: $scope.channel.uploadedImage || $scope.channel.cutv_channel_img
        };

        ChannelService.wpRequest(query).then(channel => {
            if (update) {
                $scope.channel = channel;
            }

            $scope.$emit('channelUpdated');

            $scope.updateSuccess = true;
            $timeout(() => $scope.updateSuccess = false, 2000);
        });


    };

    ChannelService.countSourceVideos = function($scope, cb = null) {

        $scope.channel.counts = {};
        if ($scope.sources.length) {

            $scope.sources = $scope.sources.map(source => {

                Object.keys(source.source_video_counts).forEach(status => {
                    source.source_video_counts[status] = !source.source_video_counts[status] ? 0 : source.source_video_counts[status].length;
                })

                return source;
            });

            $scope.sources.forEach(source => {
                Object.keys(source.source_video_counts).forEach(status => {
                    $scope.channel.counts[status] = _.sumBy($scope.sources, function(o) {
                        return o.source_video_counts[status];
                    });
                });
            });

        }

        $scope.channel.isLoading = false;

        return $scope.channel.counts;
    };



    var toQueryString = function(obj) {
        return _.map(obj, function(v, k) {
            return encodeURIComponent(k) + '=' + encodeURIComponent(v);
        }).join('&');
    };
    return ChannelService;
});