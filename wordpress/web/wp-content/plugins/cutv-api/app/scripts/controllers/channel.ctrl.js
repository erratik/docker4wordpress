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


    // debugger;

    // (async() => {
    //     $scope.channel = await cutv.channels.find(c => c.pid == $routeParams.channelId);
    //     const sources = await ChannelService.getSources($scope.channel.pid).then((sources) => sources);
    //     $scope.sources = {
    //         selected: sources.filter(s => s.channel.pid == $routeParams.channelId).map(s => {
    //             s.selected = true;
    //             return s;
    //         }),
    //         available: sources.filter(s => !s.channel.pid).map(s => {
    //             s.selected = false;
    //             return s;
    //         })
    //     };
    //     // debugger;

    //     // $scope.channel = cutv.channels.find(c => c.pid == $routeParams.channelId);
    //     // const sources = $scope.;
    //     // if () {
    //     // $scope.videos = await ChannelService.getSourceVideos($scope.sources.selected.map(s => s.source_id)).then((videos) => videos);
    //     // }

    // })();


    // $scope.activeTab = !!$scope.sources && $scope.sources.selected.length ? 'videos' : 'sources';
    $scope.activeTab = 'sources';

    $scope.$on('channelImageUpdated', (e) => $scope.channel.cutv_channel_img = e.targetScope.filename);
    $scope.$on('channelUpdated', (e) => $scope.channel = e.targetScope.channel);


    $scope.$on('sourcesUpdated', (e, data) => {
        $scope.sources = data[0];
    });

    // $scope.$on('onManageChannelVideos', async(e) => {
    //     const sources = e.targetScope.sources.map(s => s.source_id);
    //     $scope.videos = await ChannelService.getSourceVideos(sources).then((videos) => videos);
    // });

    // $scope.$on('onRetrieveSources', async(e) => {

    //     debugger;
    //     const sources = await ChannelService.getSources($scope.channel.pid).then((sources) => sources);
    //     $scope.sources = {
    //         selected: sources.filter(s => s.channel.pid == $routeParams.channelId).map(s => {
    //             s.selected = true;
    //             return s;
    //         }),
    //         available: sources.filter(s => !s.channel.pid).map(s => {
    //             s.selected = false;
    //             return s;
    //         })
    //     };
    // });

    $scope.$on('sourceVideosUpdated', (e, sources) => {
        $scope.sources = sources.map(src => src);
        ChannelService.countSourceVideos($scope);
    });

});