'use strict';

/**
 * @ngdoc function
 * @name cutvApiAdminApp.controller:MainCtrl
 * @description
 * # MainCtrl
 * Controller of the cutvApiAdminApp
 */
angular.module('cutvApiAdminApp')

    .controller('MainCtrl', function ($scope, $http, $location, ChannelService) {


        ChannelService.getChannels().then((data) => {
            $scope.channels = data;

            $scope.channels = $scope.channels.map(channel => {
                channel.isLoading = true;
                return channel;
            });
            return $scope.channels;
        });


        // todo: update .spec for addChannel()
        $scope.newChannel = {
            name: null,
            enabled: false,
            sources: []
        };

        $scope.addChannel = function() {
            var slug = $scope.newChannel.name.toLowerCase().replace(/ /g, '-');
            var createChannelRequest = {
                'action' : 'cutv_add_channel',
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
