'use strict';

// require '../services/channel.svc';

/**
 * @ngdoc directive
 * @name cutvApiAdminApp.directive:manageSources
 * @description
 * # manageChannel
 */
angular.module('cutvApiAdminApp')
    .directive('manageVideos', ($http, $routeParams, ChannelService) => {
        return {
            restrict: 'E',
            // replace: true,
            scope: {
                channel: '=',
                sources: '='
            },
            templateUrl: '/wp-content/plugins/cutv-api/app/templates/manage-videos.html',
            controller: async function($scope, $element, $attrs) {

                debugger;
                $scope.options = [{
                        name: 'Publish',
                        value: 'publish'
                    },
                    {
                        name: 'Unpublish',
                        value: 'draft'
                    },
                    {
                        name: 'Trash',
                        value: 'trash'
                    }
                ];
                $scope.selectedAction = $scope.options[0];

                $scope.counts = {};
                $scope.videos = {};
                $scope.loaders = {};
                $scope.selected = new Set();

                $scope.toggles = {};
                $scope.loading = true;
                $scope.query = '';
                const sources = $scope.sources.map(s => {
                    $scope.counts[s.source_id] = {};
                    $scope.loaders[s.source_id] = {};
                    $scope.toggles[s.source_id] = {};

                    return s.source_id;
                });

                $scope.fetchVideos = async() => await ChannelService.getSourceVideos(sources).then((videos) => {
                    // $scope.videos = videos;
                    Object.keys(videos).forEach(source => {
                        const sourceVideos = videos[source];
                        const pending = Object.keys(videos[source]).filter(id => videos[source][id].status === 'pending').map(id => videos[source][id]);
                        const published = Object.keys(videos[source]).filter(id => videos[source][id].status === 'publish').map(id => videos[source][id]);
                        const unpublished = Object.keys(videos[source]).filter(id => videos[source][id].status === 'draft').map(id => videos[source][id]);

                        $scope.videos[source] = {
                            pending: pending,
                            published: published,
                            unpublished: unpublished,
                            all: pending.concat(published, unpublished),
                            total: Object.keys(videos[source]).length
                        };
                        // $scope.videos.all = $scope.videos.all.concat($scope.videos[source].all);
                        $scope.loaders[source] = false;
                    });

                    $scope.loading = false;
                });
                $scope.fetchVideos();

                $scope.toggleVideoSelected = (video) => {
                    if ($scope.selected.has(video.id)) {
                        $scope.selected.delete(video.id);
                    } else {
                        $scope.selected.add(video.id);
                    }
                    video.selected = !video.selected;
                    $scope.selectedVideos = Array.from($scope.selected);
                };
                $scope.isVideoSelected = (id) => $scope.selected.has(id);

                $scope.toggleSourceVideos = (source, state) => {
                    $scope.toggles[source][state] = !$scope.toggles[source][state];
                    $scope.videos[source][state].forEach(id => {
                        let video = $scope.videos[source].all.find(vid => id === vid);

                        $scope.toggleVideoSelected(video);
                    });
                };


                $scope.updateVideos = (method) => {
                    var data = {
                        method: method.value,
                        action: 'cutv_convert_snaptube',
                        video_ids: Array.from($scope.selected).join(','),
                    };

                    debugger;
                    ChannelService.handlePluginAction(data).then(res => {
                        $scope.updateSuccess = true;
                        $scope.fetchVideos();
                    });

                };

                $scope.openSourceDialog = (source, action) => {

                    switch (action) {
                        case 'edit':
                            $http.get(`/wp-admin/post.php?post=${source.source_id}&action=edit`).then((res) => {
                                console.log(JSON.parse(res))
                            });
                            $(`#${action}Sources_${source.source_id}`).find('.content').html(`<iframe src="/wp-admin/post.php?post=${source.source_id}&action=edit"></iframe>`);
                            break;
                        case 'run':
                            $http.get(`/wp-admin/admin.php?page=wpvr&run_sources&ids=${source.source_id}`).then((res) => {
                                var el = document.createElement('html');
                                el.innerHTML = res.data;
                                $(`#${action}Sources_${source.source_id}`).find('.content').html($(el.getElementsByClassName('wpvr_source_insights')).html());

                            });
                            break;
                        default:


                    }
                    $(`#${action}Sources_${source.source_id}`).modal('show');


                };
                $scope.closeSourceDialog = (source, action) => {
                    // todo: refetch channel videos
                    $(`#${action}Sources_${source.source_id}`).modal('hide');
                }



            }
        };
    })
    .filter('orderObjectBy', function() {
        return function(input, attribute) {
            if (!angular.isObject(input)) return input;

            var array = [];
            for (var objectKey in input) {
                array.push(input[objectKey]);
            }
            console.log();
            array.sort(function(a, b) {
                a = a[attribute];
                b = b[attribute];
                return a - b;
            });
            return array;
        }
    });