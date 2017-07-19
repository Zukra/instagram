<?php

require_once './vendor/autoload.php';
require_once 'instagram.php';

use InstagramScraper\Exception\InstagramException;
use Manao\Instagram\Instagram;
use Unirest\Request;

$params = [
    'username'                   => 'banzaj_manao',
    'password'                   => 'banzajmanao',
    'tags'                       => [
        'hello',
        'banzaj'
    ],
    'postsCount'                 => 100, // 100 ** 2;
    'dateFormat'                 => "d-m-Y H:i:s",
    'startSearchStringLinkVideo' => '<meta property="og:video" content="',
    'endSearchStringLinkVideo'   => '"',
    'videoDir'                   => $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "video",
    'saveVideo'                  => false,
    'filter'                     => [
//        'type' => 'image',
        'type' => 'video',
//        'id'   => '1503542989455614952' // video id
    ]
];

$instagram = Instagram::withCredentials($params['username'], $params['password']);
$instagram->login();

$arrMedias = [];
foreach ($params['tags'] as $tag) {
    $media = $instagram->getMediasByTag($tag, $params['postsCount']);

    $arrFiltered = array_filter($media, function (&$item) use ($instagram, $params) {
        $condition = false;
        $filter = $params['filter'];
        foreach (array_keys($filter) as $key) {
            if ($item->{$key} == $filter[$key]) {
                $condition = true;
            } else {
                $condition = false;
                break;
            }
        }
        if ($condition) {
            $item->user = $instagram->getAccountById($item->ownerId); // add User object
            if ($filter['type'] === 'video') {
                $item->videoLink = $instagram::getVideoLinkByUrl($item->link, $params); // add video link
                if ($params['saveVideo']) {
                    saveVideoByLink($params['videoDir'], $item->videoLink);
                }
            }

            return true;
        }

        return false;
    });

    $arrMedias = array_merge($arrMedias, $arrFiltered);
}

$instagram::showResult($arrMedias, $params['dateFormat']);

//var_dump($arMedias);