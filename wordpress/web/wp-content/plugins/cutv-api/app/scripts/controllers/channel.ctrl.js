'use strict';

/**
 * @ngdoc function
 * @name cutvApiAdminApp.controller:ChannelCtrl
 * @description
 * # MainCtrl
 * Controller of the cutvApiAdminApp
 */
angular.module('cutvApiAdminApp')

.controller('ChannelCtrl', function($scope, $rootScope, $http, $location, $routeParams, ChannelService) {

    // $scope.sources = null;
    $scope.activeTab = 'sources';


    $scope.channel = {
        isLoading: true
    };
    $rootScope.init = async(event = null) => {

        var data = {
            count: 0,
            exclude_sources: 0,
            channel_id: $routeParams.channelId,
            action: 'cutv_get_channels',
        };

        await ChannelService.handlePluginAction(data).then(async res => {

            $scope.channel = res.channel;
            const sources = await ChannelService.getSources().then(sources => sources.map(s => {
                s.selected = s.channel.pid === $scope.channel.pid;
                return s;
            }));

            $scope.channel.sources = res.sources;

            $scope.sources = {
                selected: sources.filter(s => s.selected),
                available: sources.filter(s => !s.selected)
            };

            if (event) {
                // debugger;
                $scope.$broadcast('reload');
            }

        });
    };

    $scope.init();

    $scope.$on('videosUpdated', (e) => $scope.init(e));


});