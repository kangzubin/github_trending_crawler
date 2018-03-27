<?php
/**
 * github_trending_crawler.php
 * 
 * NOTE: This script gets trending data based on parsing the HTML DOM of the GitHub Trending Page.
 * If the GitHub Trending Page changes in the future, you would not be able to get the expected results, and you might need to change the parsing logic again.
 *
 * @author KANGZUBIN (https://kangzubin.com)
 * @version 1.0
 */

require_once(dirname(__FILE__).'/simple_html_dom.php');
define('GITHUB_ROOT_URL', 'https://github.com');
define('GITHUB_TRENDING_ROOT_URL', 'https://github.com/trending');

function gt_get_html_dom($url = GITHUB_TRENDING_ROOT_URL) {
	if (!$url) {
		return null;
	}
	// See also:
	// https://githubengineering.com/crypto-removal-notice/
	// http://php.net/manual/en/context.ssl.php
	// http://php.net/manual/en/migration56.openssl.php
	$contextOptions = array(
		'ssl' => array(
			'crypto_method'		=> STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
			'verify_peer'		=> false,
			'verify_peer_name'	=> false,
		)
	);
	$sslContext = stream_context_create($contextOptions);
	$html = file_get_html($url, false, $sslContext);
	return $html;
}

function gt_get_top_languages($dom = null) {
	if (!$dom) {
		$dom = gt_get_html_dom();
	}
	if ($dom) {
		$result = array();
		$lang_list_content = $dom->find('ul[class=filter-list small]', 0);
		if ($lang_list_content) {
			$lang_list = $lang_list_content->find('li');
			if (count($lang_list) > 0) {
				foreach($lang_list as $element) {
					$lang = array();
					$a = $element->find('a', 0);
					$lang['name'] = trim($a->plaintext);
					$id = '';
					if ($a->href != GITHUB_TRENDING_ROOT_URL) {
						$search = GITHUB_TRENDING_ROOT_URL.'/';
						$id = str_replace($search, '', $a->href);
					}
					$lang['id'] = $id;
					$result['languages'][] = $lang;
				}
				return $result;
			}
		}
	}
	return null;
}

function gt_get_all_languages($dom = null) {
	if (!$dom) {
		$dom = gt_get_html_dom();
	}
	if ($dom) {
		$result = array();
		$lang_list_content = $dom->find('div[data-filterable-for=text-filter-field]', 0);
		if ($lang_list_content) {
			$lang_list = $lang_list_content->find('a');
			if (count($lang_list) > 0) {
				foreach($lang_list as $element) {
					$lang = array();
					$lang['name'] = trim($element->plaintext);
					$id = '';
					if ($element->href != GITHUB_TRENDING_ROOT_URL) {
						$search = GITHUB_TRENDING_ROOT_URL.'/';
						$id = str_replace($search, '', $element->href);
					}
					$lang['id'] = $id;
					$result['languages'][] = $lang;
				}
				return $result;
			}
		}
	}
	return null;
}

function gt_get_repositories($lang = null, $since = null, $dom = null) {
	if (!$dom) {
		$url = GITHUB_TRENDING_ROOT_URL;
		if ($lang) {
			$url = $url.'/'.$lang;
		}
		if ($since) {
			$url = $url.'?since='.$since;
		}
		$dom = gt_get_html_dom($url);
	}
	if ($dom) {
		$result = array();
		$repo_list_content = $dom->find('ol[class=repo-list]', 0);
		if ($repo_list_content) {
			$repo_list = $repo_list_content->find('li[class=col-12 d-block width-full py-4 border-bottom]');
			if (count($repo_list) > 0) {
				foreach($repo_list as $element) {
					$repo = array();
					$a = $element->find('div[class=d-inline-block col-9 mb-1]', 0)->find('a', 0);
					$author_title = explode('/', $a->plaintext);
					// author
					$repo['author'] = trim($author_title[0]);
					// title
					$repo['title'] = trim($author_title[1]);
					// link
					$repo['url'] = GITHUB_ROOT_URL.$a->href;
					// description
					$p = $element->find('div[class=py-1]', 0)->find('p', 0);
					$repo['description'] = trim($p->plaintext);
					// language
					$div = $element->find('div[class=f6 text-gray mt-2]', 0);
					$lang = $div->find('span[itemprop=programmingLanguage]', 0);
					$repo['language'] = trim($lang->plaintext);
					// stars
					$stars = $div->find('a[class=muted-link d-inline-block mr-3]', 0);
					$repo['stars'] = trim($stars->plaintext);
					// forks
					$forks = $div->find('a[class=muted-link d-inline-block mr-3]', 1);
					$repo['forks'] = trim($forks->plaintext);
					// new stars
					$new_stars = $div->find('span[class=d-inline-block float-sm-right]', 0);
					if ($new_stars) {
						$new_stars = trim($new_stars->plaintext);
						$repo['newStars'] = $new_stars;
					} else {
						$repo['newStars'] = null;
					}
					// built by
					$contributors = array();
					$aImgs = $div->find('a[class=no-underline]', 0);
					if ($aImgs) {
						$imgs = $aImgs->find('img');
						foreach($imgs as $img) {
							$user = array();
							$user['id'] = $img->title;
							$temp = array();
							preg_match_all('/^(.*)(?:\?s=)/i', $img->src, $temp);
							$user['avatar'] = $temp[1][0];
							$contributors[] = $user;
						}
					}
					$repo['contributors'] = $contributors;
					$result['repositories'][] = $repo;
				}
				return $result;
			}
		}
	}
	return null;
}

function gt_get_developers($lang = null, $since = null, $dom = null) {
	if (!$dom) {
		$url = GITHUB_TRENDING_ROOT_URL.'/developers';
		if ($lang) {
			$url = $url.'/'.$lang;
		}
		if ($since) {
			$url = $url.'?since='.$since;
		}
		$dom = gt_get_html_dom($url);
	}
	if ($dom) {
		$result = array();
		$deve_list_content = $dom->find('ol[class=list-style-none]', 0);
		if ($deve_list_content) {
			$deve_list = $deve_list_content->find('li[class=d-sm-flex flex-justify-between border-bottom border-gray-light py-3]');
			if (count($deve_list) > 0) {
				foreach($deve_list as $element) {
					$deve = array();
					// user homepage url
					$div = $element->find('div[class=d-flex]', 0);
					$a = $div->find('div[class=mx-2]', 0)->find('a', 0);
					$deve['url'] = GITHUB_ROOT_URL.$a->href;
					// user avatar url
					$temp = array();
					$avatar = $a->find('img', 0)->src;
					preg_match_all('/^(.*)(?:\?s=)/i', $avatar, $temp);
					$deve['avatar'] = $temp[1][0];
					// user name & id
					$h2 = $div->find('div[class=mx-2]', 1)->find('h2', 0);
					$h2_text = $h2->plaintext;
					preg_match_all('/(?:\()(.*)(?:\))/i', $h2_text, $temp);
					$name = trim($temp[1][0]);
					$id = null;
					if ($name) {
						preg_match_all('/^(.*)(?:\()/i', $h2_text, $temp);
						$id = trim($temp[1][0]);
					} else {
						$id = trim($h2_text);
					}
					$deve['name'] = $name;
					$deve['id'] = $id;
					// user's representative repository
					$repo = $div->find('div[class=mx-2]', 1)->find('a[class=repo-snipit css-truncate]', 0);
					$deve['repository']['url'] = GITHUB_ROOT_URL.$repo->href;
					$deve['repository']['title'] = trim($repo->find('span[class=repo-snipit-name css-truncate-target]', 0)->plaintext);
					$deve['repository']['desp'] = trim($repo->find('span[class=repo-snipit-description css-truncate-target]', 0)->plaintext);
					$result['developers'][] = $deve;
				}
				return $result;
			}
		}
	}
	return null;
}

?>