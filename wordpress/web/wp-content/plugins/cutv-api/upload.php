<?php
require_once("../../../wp-load.php");
require_once './vendor/autoload.php';
$config = new \Flow\Config();

$file = new \Flow\File($config);
$request = new \Flow\Request($config);


if ($_SERVER['REQUEST_METHOD'] === 'GET' ) {


    $chunkDir = 'app/.tmp' . DIRECTORY_SEPARATOR . $file->getIdentifier();
    if (!file_exists($chunkDir)) {
        mkdir($chunkDir);
        chmod($chunkDir, 0777);
    }
    $config->setTempDir($chunkDir);
    $file = new \Flow\File($config);

    print_r($file); echo ' ::: get request';


    if ($file->checkChunk()) {
        header("HTTP/1.1 200 Ok");
    } else {
        echo ' ::: chunk checked okay, there was no content';
        header("HTTP/1.1 204 No Content");
        return ;
    }

} else {
    echo ' ::: post request ::: > '. $file->getIdentifier() .' < ::: ';

    $chunkDir = './app/.tmp' . DIRECTORY_SEPARATOR . $file->getIdentifier();
    $config->setTempDir($chunkDir);
    $file = new \Flow\File($config);



    if ($file->validateChunk()) {
        echo ' ::: chunk validate okay ::: ';
        $file->saveChunk();


        print_r($_REQUEST); echo ' ::: saving chunk ::: ';

        // saving right away for now...
        echo ' ::: we are updating so skip this check, save channel image right away ::: ';
        saveChannelImage($file->getIdentifier());


    } else {
        // error, invalid chunk upload request, retry
        echo ' ::: chunk didn\'t validate okay';
//        header("HTTP/1.1 400 Bad Request");
        return ;
    }

//    die();
}

if (isset($_REQUEST['updateChannel'])) {
    print_r($_REQUEST);
}

echo ' ::: end';

function saveChannelImage($identifier) {
    global $wpdb;

    echo $identifier, ' ? ::: ';
    $config = new \Flow\Config();
    $chunkDir = './app/.tmp' . DIRECTORY_SEPARATOR . $identifier;
    $config->setTempDir($chunkDir);
    $file = new \Flow\File($config);

    if ($file->validateFile() ) {
        $channelImgDir = '../../uploads/channels';
//        echo ' ', $channelImgDir;
        if (!file_exists($channelImgDir)) {
            mkdir($channelImgDir);
            chmod($channelImgDir, 0777);
            // File upload was completed
            echo 'created uploads/channels folder';
        }

        $request = new \Flow\Request();

        print_r($request); echo ' ::: request in saveChannelImage ::: ', $request->getFileName();
        if (\Flow\Basic::save($channelImgDir . DIRECTORY_SEPARATOR . $request->getFileName(), $config, $request)) {
            echo "Hurray, file was saved in " .$channelImgDir . DIRECTORY_SEPARATOR . $request->getFileName();

            echo 'update this channel! ('.$_REQUEST['channel'].')';
            if ( ! add_term_meta( $_REQUEST['channel'], 'cutv_channel_img', $request->getFileName(), true ) ) {
                update_term_meta($_REQUEST['channel'], 'cutv_channel_img', $request->getFileName());
            }

        }
    } else {
        // This is not a final chunk, continue to upload
    }
}