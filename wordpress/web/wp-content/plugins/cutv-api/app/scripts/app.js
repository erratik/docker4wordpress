'use strict';

/**
 * @ngdoc overview
 * @name cutvApiAdminApp
 * @description
 * # cutvApiAdminApp
 *
 * Main module of the application.
 */
angular
    .module('cutvApiAdminApp', [
        'ngRoute',
        'flow'
    ])
    .config(function ($routeProvider, flowFactoryProvider, $httpProvider) {

        $httpProvider.defaults.transformRequest = function(data){
            if (data === undefined) {
                return data;
            }
            return $.param(data);
        };
        $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';

        $routeProvider
            .when('/', {
                templateUrl: '/wp-content/plugins/cutv-api/app/views/channels.html',
                controller: 'MainCtrl',
                controllerAs: 'main'
            })
            .when('/channel/:channelName/:channelId', {
                templateUrl: '/wp-content/plugins/cutv-api/app/views/channel.html',
                controller: 'ChannelCtrl',
                controllerAs: 'channel'
            })
            .otherwise({
                redirectTo: '/'
            });

    });

