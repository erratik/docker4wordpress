'use strict';

/**
 * @ngdoc directive
 * @name cutvApiAdminApp.directive:channelItems
 * @description
 * # manageChannel
 */
angular.module('cutvApiAdminApp')
    .directive('channelItems', function($templateRequest, $http, $compile, ChannelService) {
        return {
            restrict: 'E',
            replace: true,
            scope: {
                channel: '=',
                sources: '='
            },
            template: '<div>No sources added for channel</div>',
            link: function(scope, element, attrs) {

                ChannelService.getChannelSources(scope.channel.pid).then((sources) => {
                    debugger;
                    scope.sources = sources.map(source => {
                        return source;
                    });

                    const sourceVideoCount = ChannelService.countSourceVideos(scope);

                    // if (!scope.channel.counts.size()) {
                    //     return;
                    // }

                    $templateRequest('/wp-content/plugins/cutv-api/app/templates/channel-item.html').then(function(html) {
                        var template = angular.element(html);
                        element.html(template);
                        $compile(template)(scope);

                    });
                });


                scope.updateChannel = () => ChannelService.updateChannel(scope, false);

            }
        };
    });