<?php
require("config.php");
/*
 * Parse all input parameters
 */
$qs_action   = @$_POST['param']['action'];
$qs_reportId = @$_POST['param']['reportId'];
$qs_latest   = @$_POST['param']['latestDate'];

$response = $_POST;
$response['data'] = array();

/*
 * Handle github/crowdin action
 */

switch ($qs_action) {
  case "latest":
    $oGithub = new cGithub();
    $response['data']['status'] = $oGithub->getlatest();
    $response['data']['output'] = $oGithub->output;
    if (DEBUG) $response['data']['latest'] = $oGithub->latest;
  break;
	case "create":
		$oCrowdin = new cCrowdin();
		if ($qs_reportId != "") {
			$oCrowdin->setReport($qs_reportId);
      $response['data']['status'] = "OK";
		} else {
      $oCrowdin->setlatest($qs_latest);
			$response['data']['status'] = $oCrowdin->createReport();
		}
		$response['data']['reportId'] = $oCrowdin->reportId;
	break;
	case "progress":
		$oCrowdin = new cCrowdin();
		if ($qs_reportId != "") {
			$oCrowdin->setReport($qs_reportId);
			$response['data']['status'] = $oCrowdin->getProgress();
		} else {
			$response['data']['status'] = "reportId is mandatory";
		}
		$response['data']['progress'] = $oCrowdin->progress;
	break;
	case "download":
		$oCrowdin = new cCrowdin();
		if ($qs_reportId != "") {
			$oCrowdin->setReport($qs_reportId);
			$response['data']['status'] = $oCrowdin->getDownload();
		} else {
			$response['data']['status'] = "reportId is mandatory";
		}
    $response['data']['output'] = $oCrowdin->output;
		if (DEBUG) $response['data']['download'] = $oCrowdin->download;
	break;
}

print json_encode($response);

/*
 * Class to handle all Crowdin integration
 */


class cCrowdin {
  public $output = "";
	public $download = "";
	public $progress = 0;
  public $reportId = "";
  public $latest = "";

  private $token = CROWDIN_TOKEN;
	private $api_url = CROWDIN_API;

	function __construct() {
    $this->latest = date("Y-01-01");
	}

	function setReport($reportId) {
		$this->reportId = $reportId;
	}

  function setlatest($latest) {
    $this->latest = $latest;
  }

  /*
   * Request Crowdin for the top-member report
   */
	function createReport() {
		$arrJSON = json_encode(array(
			"name" => "top-members",
			"schema" => array(
				"unit" => "strings",
                // "languageId" => "it",
				"format" => "json",
				"dateFrom" => $this->latest . "T00:00:00+00:00",
				"dateTo" => date("Y") . "-12-31T23:59:59+00:00"
			)
		));

		$response = get_web_page($this->api_url, $arrJSON, false, array(
			CURLOPT_HTTPHEADER => array (
				"Content-Type: application/json; charset=utf-8",
				"Authorization: Bearer " . $this->token
		)));

		$ret = $this->isJson($response["content"]);
		if ($ret == "OK") {
			$responseJSON = json_decode($response["content"], true);
			$ret = $this->isError($responseJSON);
			if ($ret == "OK") {
				$this->setReport($responseJSON["data"]["identifier"]);
			}
		}
		return $ret;
	}

  /*
   * Check report generation progress
   */
	function getProgress() {
		$response = get_web_page($this->api_url . "/" . $this->reportId, array(), true, array(
			CURLOPT_HTTPHEADER => array (
				"Content-Type: application/json; charset=utf-8",
				"Authorization: Bearer " . $this->token
		)));

		$ret = $this->isJson($response["content"]);
		if ($ret == "OK") {
			$responseJSON = json_decode($response["content"], true);
			$ret = $this->isError($responseJSON);
			if ($ret == "OK") {
				$this->progress = $responseJSON["data"]["progress"];
			}
		}
		return $ret;
	}

  /*
   * Download report in json format
   */
	function getDownload() {
		$response = get_web_page($this->api_url . "/" . $this->reportId . "/download", array(), true, array(
			CURLOPT_HTTPHEADER => array (
				"Content-Type: application/json; charset=utf-8",
				"Authorization: Bearer " . $this->token
		)));

		$ret = $this->isJson($response["content"]);
		if ($ret == "OK") {
			$responseJSON = json_decode($response["content"], true);
			$ret = $this->isError($responseJSON);
			if ($ret == "OK") {
				$this->download = file_get_contents($responseJSON["data"]["url"]);
        $ret = $this->handleDownload();
			}
		}
		return $ret;
	}

  /*
   * Handle report json data and order the results
   */
	function handleDownload() {
    $arrUser = array();
		$ret = $this->isJson($this->download);
		if ($ret == "OK") {
			$responseJSON = json_decode($this->download, true);
      foreach ($responseJSON['data'] as $user) {
        if ($user['translated'] > 0) {
          $arrUser[] = $user['user']['fullName'];
        }
      }
      natsort($arrUser);
      $this->output = implode(", ", $arrUser);
		}
    return $ret;
	}

  /*
   * check if JSON is valid
   */
	private function isJson($json) {
		$ret = "OK";
		json_decode($json);
		if (json_last_error() !== JSON_ERROR_NONE) {
			// Errore JSON
			$ret = json_last_error();
		}
		return $ret;
	}

  /*
   * Check if JSON result is a crowdin error
   */
	function isError($json) {
    $ret = "OK";
    if (isset($json['error'])) {
        $ret = $json['error']['code'] . ' - ' . $json['error']['message'];
    }
		return $ret;
	}
}

/*
 * Class to handle all Github integration
 */

class cGithub {
  public $output = "";
  public $latest = "";

  private $token = GITHUB_TOKEN;
	private $api_url = GITHUB_API;

  function getlatest() {
    $response = get_web_page($this->api_url, array(), true, array(
			CURLOPT_HTTPHEADER => array (
				"Accept: application/vnd.github+json",
				"Authorization: Bearer " . $this->token,
        "X-GitHub-Api-Version: 2022-11-28"
		)));

		$ret = $this->isJson($response["content"]);
		if ($ret == "OK") {
			$responseJSON = json_decode($response["content"], true);
			$ret = $this->isError($responseJSON);
			if ($ret == "OK") {
				$this->latest = $response["content"];
        $ret = $this->handlelatest();
			}
		}
		return $ret;
  }

  function handlelatest() {
		$ret = $this->isJson($this->latest);
		if ($ret == "OK") {
			$responseJSON = json_decode($this->latest, true);
      $this->output = substr($responseJSON['published_at'], 0, 10) . '|' . $responseJSON['name'];      
		}
    return $ret;
  }

  /*
   * check if JSON is valid
   */
	private function isJson($json) {
		$ret = "OK";
		json_decode($json);
		if (json_last_error() !== JSON_ERROR_NONE) {
			// Errore JSON
			$ret = json_last_error();
		}
		return $ret;
	}

  /*
   * Check if JSON result is a crowdin error
   */
	function isError($json) {
    $ret = "OK";
    if (isset($json['message'])) {
        $ret = $json['message'] . ' - ' . $json['documentation_url'];
    }
		return $ret;
	}
}

/*
 * Curl request... just a little more verbose
 */
function get_web_page( $url, $post = array(), $bolGET = false, $options = array()) {
    $options = array_replace(array(
        CURLOPT_RETURNTRANSFER => true,     // return web page
        CURLOPT_HEADER         => false,     // don't return headers
        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
        CURLOPT_ENCODING       => "",       // handle all encodings
        CURLOPT_USERAGENT      => "spider", // who am i
        //CURLOPT_AUTOREFERER    => true,     // set referer on redirect
        CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
        CURLOPT_TIMEOUT        => 120,      // timeout on response
        CURLOPT_MAXREDIRS      => 10,        // stop after 10 redirects
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false
    ), $options);

    if (!$bolGET) {
      $options[CURLOPT_POST      ] = 1;
      $options[CURLOPT_POSTFIELDS] = $post;
    } else {
      $options[CURLOPT_HTTPGET   ] = true;
    }

    $ch      = curl_init( $url );
    foreach ($options as $key => $value) {
      curl_setopt( $ch, $key, $value );
    }
    $content = curl_exec( $ch );
    $err     = curl_errno( $ch );
    $errmsg  = curl_error( $ch );
    $header  = curl_getinfo( $ch );
    curl_close( $ch );

    $header['errno']   = $err;
    $header['errmsg']  = $errmsg;
    $header['content'] = $content;
    return $header;
}