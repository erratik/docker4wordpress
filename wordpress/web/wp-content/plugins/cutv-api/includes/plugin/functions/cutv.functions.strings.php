<?php

function sanitizeTitle($string) {
    return
        preg_replace(
            array('#[^A-Za-z0-9\-.\' ]#'),
            array(''),
            urldecode($string)
        );
}

function hyphenize($string) {
    return

        strtolower(
            preg_replace(
                array('#[\\s-]+#', '#[^A-Za-z0-9\-]+#'),
                array('-', ''),
                ##     cleanString(
                urldecode($string)
            ##     )
            )
        )
        ;
}

function get_numerics ($str) {
    preg_match_all('/\d+/', $str, $matches);
    return $matches[0];
}

function convert_youtube_duration($youtube_duration) {
    $re = '/(PT)(\d*)(M)(\d*)(S)/';
    $subst = '$2:$4';

    $result = preg_replace($re, $subst, $youtube_duration);

    return $result;
}
