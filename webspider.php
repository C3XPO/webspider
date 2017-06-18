<?php
/*
 * This file is part of the Goutte package.
 *
 * (c) C3XPO <c3xpo@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Spider;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

/**
 * Client.
 *
 * @author C3XPO <c3xpo@symfony.com>
 */
class Spider extends GuzzleClient
{
        private $response = array();
        private $main_url = 'http://www.label-blouse.com/';
        private $urlToScrawl = array($main_url);
        private $urlScrawled = array();
        private $urlInfos = array();
        private $row = 0;
        private $limitRow = 10;

        private $client;


        public function __construct($url = null)
        {
            if($url !== null) {
                $this->set_main_url($url);
            }

            $this->client = new GuzzleClient();
        }

        public function set_main_url($url)
        {
            $parse_url = parse_url($url);

            if(!$parse_url) {
                return false;
            }

            if($parse_url['scheme'] != 'http' OR $parse_url['scheme'] != 'https') {
                return false;
            }

            $this->main_url = $parse_url['scheme'].$parse_url['host'];
        }

        public function crawl()
        {
            while($url = each($this->urlToScrawl)) {
                $this->row++;

                $this->urlScrawled[] = $url[1];
                unset($this->urlToScrawl[$url['key']]);
                $res = $this->client->request('GET', $url[1], array('allow_redirects' => false));

                $status_code = $res->getStatusCode();
                $this->urlInfos[$url[1]]['status_code'] = $status_code;

                // Redirect ?
                if($status_code == 301 OR $status_code == 302) {
                    $location = $res->getHeader('location');
                    $redirect_url = $location[(count($location) -1)];

                    // Change $main_url in case of redirection on main domain
                    // Add to $urlToScrawl
                    // Add localtion to $urlinfos
                    $main_url = $this->urlToScrawl[] = $this->urlInfos[$url[1]]['location'] = $redirect_url;

                    continue;
                }

                // Get links
                $body = $res->getBody();
                $url_pattern = "#\b(([\w-]+://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))#";
                $url_pattern = '#'.$main_url.'[^\s()<>"\']+#';
                $nb_links = 0;

                preg_match_all($url_pattern, $body, $links);

                foreach($links[0] as $link) {
                    if(!isset($this->urlInfos[$link])) {
                        $this->urlInfos[$link] = array('linking' => array('count' => 0, 'history' => array()));
                    }
                    $this->this->urlInfos[$link]['linking']['count'] = $this->urlInfos[$link]['linking']['count'] !== null ? $this->urlInfos[$link]['linking']['count']++ : 1;

                    if(!isset($this->urlInfos[$link]['linking']['history'][$url[1]])) {
                        $this->urlInfos[$link]['linking']['history'][$url[1]] = array('count' => 1);
                    } else {
                        $this->urlInfos[$link]['linking']['history'][$url[1]]['count']++;
                    }


                    if(!in_array($link, $this->urlToScrawl) OR !in_array($link, $this->urlScrawled)) {
                        $this->urlToScrawl[] = $link;
                    }

                    $this->nb_links++;
                }

                $urlInfos[$url[1]]['nb_links'] = $nb_links;

                if($this->row >= $this->limitRow) {
                    print_r($this->urlInfos);
                    break;
                }
            }

        }

}
