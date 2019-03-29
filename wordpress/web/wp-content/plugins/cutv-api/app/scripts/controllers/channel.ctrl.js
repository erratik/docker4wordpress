'use strict';

/**
 * @ngdoc function
 * @name cutvApiAdminApp.controller:ChannelCtrl
 * @description
 * # MainCtrl
 * Controller of the cutvApiAdminApp
 */
angular.module('cutvApiAdminApp')

.controller('ChannelCtrl', function($scope, $http, $location, $routeParams, ChannelService) {

    // $scope.activeTab = 'sources';
    $scope.sources = null;

    $scope.channel = cutv.channels.find(({pid}) => pid == $routeParams.channelId);

    (async () => {


        const sources = await ChannelService.getSources($scope.channel.pid).then((sources) => sources);
        $scope.sources = {
            selected: sources.filter(({channel}) => channel.pid == $routeParams.channelId).map(s => {s.selected = true; return s; }),
            available: sources.filter(({channel}) => !channel.pid).map(s => {s.selected = false; return s; })
        };
        
        // $scope.channel.counts = {};
        // ChannelService.countSourceVideos($scope);

        $scope.channel = cutv.channels.find(({pid}) => pid == $routeParams.channelId);

    })();

    $scope.activeTab = !!$scope.sources && !!$scope.sources.available.length ? 'videos' : 'sources';
    
    
    $scope.$on('channelImageUpdated', (e) => $scope.channel.cutv_channel_img = e.targetScope.filename);
    $scope.$on('channelUpdated', (e) => $scope.channel = e.targetScope.channel);
    $scope.$on('sourceVideosUpdated', (e, sources) => {

        $scope.sources = sources.map(src => src);
        ChannelService.countSourceVideos($scope);
    });

});
