<?php

namespace Manao\Instagram;

use InstagramScraper\Exception\InstagramException;
use InstagramScraper\Instagram as BaseInstagram;
use Unirest\Request;

class Instagram extends BaseInstagram {
    public $sessionUsername;
    public $sessionPassword;
    public $userSession;

    public function __construct() {
    }

    public static function withCredentials($username, $password, $sessionFolder = null) {
        parent::withCredentials($username, $password, $sessionFolder);
        $instance = new self();
        $instance->sessionUsername = $username;
        $instance->sessionPassword = $password;

        return $instance;
    }

    public static function getVideoLinkByUrl($url, $params) {
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

    public static function saveVideoByLink($dir, $link) {
        if (empty($link) || empty($dir)) {
            return false;
        }
        $fileName = getFileNameFromLink($link);

        if (!file_exists($dir)) {
            mkdir($dir);
        }
        if (!file_exists($dir . $fileName)) {
            $content = file_get_contents($link);
            file_put_contents($dir . $fileName, $content);
        }

        return true;
    }

    public static function showResult($filteredArray, $dateFormat) {
        echo count($filteredArray) . "<br>";
        foreach ($filteredArray as $item) {
            echo '---------------------------------------'
                . '<br> id - ' . $item->id
                . '<br> date - ' . date($dateFormat, $item->createdTime)
                . '<br> type - ' . $item->type
                . '<br> link - ' . $item->link
                . '<br> link video - ' . $item->videoLink
                . '<br> likes - ' . $item->likesCount
                . '<br> user id - ' . $item->user->id . ' user name - ' . $item->user->username . ' full name - ' . $item->user->fullName
                . '<br>';
        }
    }

    public static function getFileName($link, $needle = '/') {
        return substr($link, strrpos($link, $needle));
    }
}