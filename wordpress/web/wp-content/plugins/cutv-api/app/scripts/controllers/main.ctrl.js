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
            count: 1,
            exclude_sources: 0,
            action: 'cutv_get_channels',
        };

        ChannelService.handlePluginAction(data)
            .then(res => {
                const datum = res.filter(x => !!x.channel.enabled);
                $scope.channels = datum.map(({
                    channel,
                    sources
                }) => ({
                    ...channel,
                    sources,
                    isLoading: true
                }));
                return datum.map(x => x.sources);
            })
            .then(sources => {
                const data = Array.prototype.concat.apply([], sources.map(x => x));
                Array.prototype.concat.apply([], data.map(x => x.source))
                    .map((source, i) => {
                        $scope.channels[i].counts = ['publish', 'draft', 'pending'].map(status => {
                            const count = {};
                            count[status] = data[i].videos[status];
                            data.reduce((_, curr) => count[status] += curr.videos[status]);
                            return count;
                        });
                        $scope.channels[i].isLoading = false;
                        return source;
                    });
            }).finally(() => $scope.channels = $scope.channels.map(channel => {
                channel.isLoading = false;
                return channel;
            }));

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