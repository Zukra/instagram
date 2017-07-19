<?php

require_once './vendor/autoload.php';

use InstagramScraper\Exception\InstagramException;
use InstagramScraper\Instagram;
use Unirest\Request;

class InstaScraper
{
    private $params = [
        'username' => 'team_manao',
        'password' => 'banzajmanao',
        'postsCount' => 25, // 100 ** 2;
        'tags' => [
            'tbrgtallent',
            //'banzaj',
            //'dogs',
        ],
        'filter' => [
            'type' => [
                'video',
                'image'
            ],
            //'id' => [ // video id
            //'1501433142136684072',
            //'1506031951962044154'
            //],
        ],
        'dateFormat' => "d-m-Y H:i:s",
        'startSearchStringLinkVideo' => '<meta property="og:video" content="',
        'endSearchStringLinkVideo' => '"',
        'videoDir' => "video",
        'saveVideo' => false,
    ];

    function init() {
        if (sizeof($this->params['tags']) > 0) {
            $instagram = $this->getInstagram($this->params['username'], $this->params['password']);
            $arMedias = $this->getMedias($this->params, $instagram);
            $arMedias = $this->sortArrayByDate($arMedias, $this->params);
            //$arMedias = array_slice($arMedias, 0, $params['postsCount']);
            $this->showResult($arMedias, $this->params);
        }
    }

    function getMedias($params, $instagram)
    {
        $arMedias = [];
        foreach ($params['tags'] as $tag) {
            $media = $instagram->getMediasByTag($tag, $params['postsCount']);
            $arFiltered = array_filter($media, function (&$item) use ($instagram, $params, $tag) {
                $filter = $params['filter'];
                if ($this->getCondition($item, $filter)) {
                    $item->user = $instagram->getAccountById($item->ownerId); // add User object
                    $item->tag = $tag;
                    if (in_array('video', $filter['type'])) {
                        $this->saveMedia($item, $params);
                    }
                    return true;
                }
                return false;
            });
            $arMedias = array_merge($arMedias, $arFiltered);
        }
        return $arMedias;
    }

    function getInstagram($user, $pass)
    {
        $instagram = Instagram::withCredentials($user, $pass);
        var_dump($instagram);
        $instagram->login();

        return $instagram;
    }

    function saveMedia($item, $params)
    {
        $item->videoLink = $this->getVideoLinkByUrl($item->link, $params); // add video link
        if ($params['saveVideo']) {
            $this->saveVideoByLink($params['videoDir'], $item->videoLink);
        }
    }

    function getVideoLinkByUrl($url, $params)
    {
        if (empty($url)) {
            return false;
        }
        $response = Request::get($url);

        if ($response->code !== 200) {
            throw new InstagramException('Response code is ' . $response->code . '. Body: ' . $response->body . ' Something went wrong. Please report issue.');
        }

        $pattern = '/'
            . $params['startSearchStringLinkVideo']
            . '.*?(.*)'
            . $params['endSearchStringLinkVideo']
            . '/i';
        preg_match($pattern, $response->body, $matches);

        $link = $matches[1];

        /*
            $start = strpos($response->body, $params['startSearchStringLinkVideo']);
            $str = substr($response->body, $start, 250);

            $end = strpos($str, '/>');
            $str = substr($str, 0, $end);

            $link = explode("\"", $str)[3];
        */

        return $link;
    }

    function saveVideoByLink($dir, $link)
    {
        if (empty($link) || empty($dir)) {
            return false;
        }

        $arDirSeparator = ['/', '\\'];

        $dir .= in_array(substr($dir, 0, 1), $arDirSeparator) ? '' : '/';
        $dir .= in_array(substr($dir, -1), $arDirSeparator) ? '' : '/';
        $dir = $_SERVER['DOCUMENT_ROOT'] . '/' . $dir;

        if (!file_exists($dir)) {
            mkdir($dir);
        }

        $fileName = $dir . getFileNameFromLink($link);

        if (!file_exists($fileName)) {
            file_put_contents($fileName, file_get_contents($link));
        }

        return true;
    }

    function showResult($filteredArray, $params)
    {
        echo ' Tags : ';
        foreach ($params['tags'] as $tag) {
            echo $tag . " ";
        }
        echo '<br> All posts = ' . count($filteredArray) . "<br>";
        foreach ($filteredArray as $item) {
            echo '---------------------------------------'
                . '<br> tag - ' . $item->tag
                . '<br> id - ' . $item->id
                . '<br> date - ' . date($params['dateFormat'], $item->createdTime)
                . '<br> type - ' . $item->type
                . '<br> link - ' . $item->link
                . '<br> link video - ' . $item->videoLink
                . '<br> likes - ' . $item->likesCount
                . '<br> user id - ' . $item->user->id . ' user name - ' . $item->user->username . ' full name - ' . $item->user->fullName
                . '<br>';
        }
    }

    function getFileNameFromLink($link)
    {
        return pathinfo(parse_url($link)['path'])['basename'];
    }

    function getFileNameOld($link, $needle = '/')
    {
        return substr($link, strrpos($link, $needle));
    }

    function getCondition($item, $filter)
    {
        $condition = false;
        foreach (array_keys($filter) as $key) {   // AND - conditions
            if (count($filter[$key]) == 0 || in_array($item->{$key}, $filter[$key])) {
                $condition = true;
            } else {
                $condition = false;
                break;
            }
        }

        return $condition;
    }

    function sortArrayByDate($arMedias, $params)
    {
        if (sizeof($params['tags']) > 1) {
            usort($arMedias, function ($a, $b) {
                return ($a->createdTime < $b->createdTime);
            });
        }
        return $arMedias;
    }
}

$test = new InstaScraper();
$test->init();