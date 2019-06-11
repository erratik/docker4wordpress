'use strict';

/**
 * @ngdoc function
 * @name cutvApiAdminApp.controller:MainCtrl
 * @description
 * # MainCtrl
 * Controller of the cutvApiAdminApp
 */
angular.module('cutvApiAdminApp')

.controller('MainCtrl', function($scope, $http, $location, ChannelService) {


    $scope.init = () => {
        var data = {
            count: true,
            action: 'cutv_get_channels',
        };

        ChannelService.handlePluginAction(data)
            .then(channels => {
                $scope.channels = channels.filter(({
                    enabled
                }) => !!enabled).map(channel => ({
                    ...channel,
                    isLoading: true
                }));
                return $scope.channels;
            })
            .then(channels => {

                debugger;
                channels.forEach(channel => {
                    return ChannelService.handlePluginAction({
                        channel_id: channel.pid,
                        count: true,
                        action: 'cutv_get_sources_by_channel'
                    }).then(sources => {
                        $scope.sources = sources;
                        channel.isLoading = false;
                    });
                });

            });

    };

    $scope.init();

    // todo: update .spec for addChannel()
    $scope.newChannel = {
        name: null,
        enabled: false,
        sources: []
    };

    $scope.addChannel = function() {
        var slug = $scope.newChannel.name.toLowerCase().replace(/ /g, '-');
        var createChannelRequest = {
            'action': 'cutv_add_channel',
            channelName: $scope.newChannel.name,
            enabled: $scope.newChannel.enabled,
            featured: $scope.newChannel.featured,
            slug: slug
        };


        return $http.post(ajaxurl, createChannelRequest).then(function(addedCategory) {
            $scope.channels.unshift(addedCategory.data);
        });

    };

    $scope.openAddChannelDialog = () => {

        $('#addChannel').modal('show');

    };

});