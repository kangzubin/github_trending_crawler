<?php
/**
 * The example for using `github_trending_crawler.php`
 * 
 * Put the `example.php`, `github_trending_crawler.php`, and `simple_html_dom.php` to your server web root document,
 * and then visit the following example URLs to get the test results with JSON format.
 * 
 * https://path/to/example.php
 * https://path/to/example.php?lang=swift&since=daily
 * https://path/to/example.php?action=repositories&lang=swift&since=weekly
 * https://path/to/example.php?action=developers&lang=objective-c&since=monthly
 * https://path/to/example.php?action=top_languages
 * https://path/to/example.php?action=all_languages
 * ...
 * 
 * The URL query parameter default values:
 *   lang: `null`
 *   since: `daily`
 *   action: `repositories`
 * 
 * @author KANGZUBIN (https://kangzubin.com)
 * @version 0.1
 */ 

error_reporting(0);
set_time_limit(0);
header('Content-Type:application/json; charset=utf-8');
require_once(dirname(__FILE__).'/crawler/github_trending_crawler.php');

class Example {

	private $responseData = array('code' => 1000, 'msg' => 'OK', 'data' => null);

	public function run() {
		$action = $_GET['action'];
		if (!$action) {
			$action = 'repositories';
		}
		$lang = $_GET['lang']; // $lang can be `null`
		$since = $_GET['since'];
		if ($since && !in_array($since, array('daily', 'weekly', 'monthly'))) {
			$since = 'daily';
		}
		switch ($action) {
			case 'repositories':{
				$result = $this->getRedisCache('repo', $lang, $since);
				if ($result == null) {
					$result = gt_get_repositories($lang, $since);
					$this->setRedisCache('repo', $lang, $since, $result);
				}
				$this->returnResult($result);
			}
				break;
			case 'developers':{
				$result = $this->getRedisCache('deve', $lang, $since);
				if ($result == null) {
					$result = gt_get_developers($lang, $since);
					$this->setRedisCache('deve', $lang, $since, $result);
				}
				$this->returnResult($result);
			}
				break;
			case 'all_languages':{
				$result = $this->getRedisCache('all_lang', null, null);
				if ($result == null) {
					$result = gt_get_all_languages();
					$this->setRedisCache('all_lang', null, null, $result);
				}
				$this->returnResult($result);
			}
				break;
			case 'top_languages':{
				$result = $this->getRedisCache('top_lang', null, null);
				if ($result == null) {
					$result = gt_get_top_languages();
					$this->setRedisCache('top_lang', null, null, $result);
				}
				$this->returnResult($result);
			}
				break;
			default:
				$this->returnError(1002, 'Action Not Found');
				break;
		}
	}

	private function getRedisCache($type, $lang, $since) {
		if (!$type) {
			return null;
		}
		if ($type == 'all_lang' || $type == 'top_lang') {
			$key = 'k_'.$type;
		} else {
			if (!$lang) {
				$lang = 'all';
			}
			if (!$since) {
				$since = 'daily';
			}
			$key = 'k_'.$type.'_'.$lang.'_'.$since;
		}
		$redis = new Redis();
		if ($redis->connect('127.0.0.1', 6379)) {
			$str_data = $redis->get($key);
			if ($str_data) {
				$data = json_decode($str_data);
				return $data;
			}
		}
		return null;
	}

	private function setRedisCache($type, $lang, $since, $data) {
		if (!$data) {
			return;
		}
		if (!$type) {
			return;
		}
		if ($type == 'all_lang' || $type == 'top_lang') {
			$key = 'k_'.$type;
		} else {
			if (!$lang) {
				$lang = 'all';
			}
			if (!$since) {
				$since = 'daily';
			}
			$key = 'k_'.$type.'_'.$lang.'_'.$since;
		}
		$redis = new Redis();
		if ($redis->connect('127.0.0.1', 6379)) {
			$str_data = json_encode($data);
			$redis->set($key, $str_data);
		}
	}

	private function returnResult($result) {
		if (is_null($result)) {
			$this->returnError();
		} else {
			$this->responseData['data'] = $result;
			$this->jsonReturn();
		}
	}

	private function returnError($code = 1001, $msg = 'Error') {
		$this->responseData['code'] = $code;
		$this->responseData['msg'] = $msg;
		$this->jsonReturn();
	}

	private function jsonReturn() {
		exit(json_encode($this->responseData));
	}
}

$example = new Example();
$example->run();

?>