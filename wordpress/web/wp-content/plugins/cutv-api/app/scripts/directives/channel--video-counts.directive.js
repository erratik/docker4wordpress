'use strict';

/**
 * @ngdoc directive
 * @name cutvApiAdminApp.directive:videoCounts
 * @description
 * # manageChannel
 */
angular.module('cutvApiAdminApp')
    .directive('videoCounts', function($http, $compile, ChannelService) {
        return {
            restrict: 'E',
            replace: true,
            scope: {
                counts: '=',
                pre: '@',
                display: '@',
                listStyle: '@'
            },
            templateUrl: '/wp-content/plugins/cutv-api/app/templates/video-counts.html',
            link: function(scope) {

                scope.status = {};
                scope.counts.forEach(count => {
                    scope.status = {
                        ...scope.status,
                        ...count
                    };
                });

                debugger;

            }
        };
    });