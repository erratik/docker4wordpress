'use strict';

/**
 * @ngdoc function
 * @name cutvApiAdminApp.controller:ChannelDisplayCtrl
 * @description
 * # MainCtrl
 * Controller of the cutvApiAdminApp
 */
angular.module('cutvApiAdminApp')

.controller('ChannelDisplayCtrl', function($scope, $http, $location, $routeParams, $timeout, ChannelService) {


    ChannelService.getChannels($routeParams.channelId).then(channel => $scope.channel = channel);

    $scope.updateSuccess = false;
    $scope.uploadSuccess = false;
    $scope.updateChannel = function() {

        var query = {
            action: 'cutv_update_channel',
            channel: $scope.channel.pid,
            name: $scope.channel.playlist_name,
            enabled: $scope.channel.enabled,
            featured: $scope.channel.featured,
            image: $scope.channel.uploadedImage || $scope.channel.cutv_channel_img
        };

        ChannelService.wpRequest(query).then(channel => {
            $scope.channel = channel;

            $scope.$emit('channelUpdated');

            $scope.updateSuccess = true;
            $timeout(() => $scope.updateSuccess = false, 2000);
        });

    };


    $scope.openDeleteDialog = (source, action) => {

        $(`#deleteSource`).modal('show');

    };

    $scope.deleteChannel = function() {
        var query = {
            action: 'cutv_delete_channel',
            channel: $scope.channel.pid
        };

        ChannelService.wpRequest(query).then(channel => {
            window.location = '/wp-admin/admin.php?page=cutv_manage_channels';
        });


    };

    $scope.$on('channelImageUploaded', (e) => {
        $scope.channel.uploadedImage = e.targetScope.filename;
    });

    $scope.$on('channelImageUpdated', (e) => {
        $scope.channel.cutv_channel_img = e.targetScope.filename;
        $scope.uploadSuccess = true;
        $timeout(() => $scope.uploadSuccess = false, 2000);
    });

});
