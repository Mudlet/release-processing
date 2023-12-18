<?php
  require("config.php");
?><!DOCTYPE html>
<html>
<head>
  <title>Crowdin top-member</title>
  <style>
    /* Tags */
    input[type="button"], button {
      vertical-align: top;
    }
    input[type="text"] {
      width: 80%; max-width: 400px;
    }
    textarea {
      width: 80%; max-width: 400px; min-height: 80px;
    }
    /* Class */
    .inputInsert {
      background: lightyellow;
    }
    .inputDisabled {
      background: lightgrey;
    }
    /* IDs */
    #latestDate {
      max-width: 200px;
    }
    #debug {
      border: 1px solid grey; width: 80%; max-width: 800px; min-height: 200px;
      font-family: monospace;
    }
  </style>
</head>
<body>

<h2>Crowdin top-member</h2>

  <label for="latestDate">Date latest release (yyyy-mm-dd):</label><br>
  <input type="text" id="latestDate" name="latestDate" class="inputInsert" value=""> <input id="btn-start" type="button" value="START">

<p>Just click the "Start" button and wait for the output.</p>

<h2>Output</h2>

  <label for="release">Release Name:</label><br>
  <input type="text" id="release" name="release" value="" class="inputDisabled" readonly="readonly"> <input id="btn-latest" type="button" value="Get latest"><br><br>
  <label for="reportId">ReportId:</label><br>
  <input type="text" id="reportId" name="reportId" value="" class="inputDisabled" readonly="readonly"> <input type="button" class="btn-copy" value="copy" /><br><br>
  <label for="output">Result output:</label><br>
  <textarea type="text" id="output" name="output" value="" class="inputDisabled" readonly="readonly"></textarea> <input type="button" class="btn-copy" value="copy" />

<h2>Debug windows</h2>

<div id="debug">

</div>

<script src="js/jquery.min.js"></script>
<script type="module">
  import { Octokit } from "https://esm.sh/@octokit/core";
</script>
<script>
	var intervalProgress;

	$(document).ready(function() {
    // copy to clipboard button
    $(".btn-copy").click(function(e) {
      var copyText = $(this).prev().get(0);

      copyText.select();
      copyText.setSelectionRange(0, 99999); // For mobile devices

      navigator.clipboard.writeText(copyText.value);

      alert("Copied the text: " + copyText.value);
    });

    // retrive the latest github version
    $("#btn-latest").click(function (e) {
      githublatest();
    });

    // start the crowdin integration
		$("#btn-start").click(function (e) {
      var result = $("#latestDate").val().match("\\d{4}-\\d{2}-\\d{2}");
      if (!result) {
        alert('Insert a valid date');
        return;
      }
			$('#reportId', '#btn-start').prop('disabled', true);
			crowdinCreate();
		});
	});

  /*
   * Request Github for latest release information
   */
  function githublatest() {
    makeRequest({action: "latest"}, function (status, data) {
			switch (status) {
				case "beforeSend":
					writeOutput("Github release started...");
				break;
				case "success":
          var arrOutput;
					if (data['data']['status'] == "OK") {
            writeOutput("[INFO] release created: " + data['data']['output']);
            if (data['data']['latest']) writeOutput("[INFO] latest created: " + data['data']['latest']);            
            writeOutput("Github release COMPLETED");
            arrOutput = data['data']['output'].split('|');
						$("#latestDate").val(arrOutput[0]);
            $("#release").val(arrOutput[1]);            
					} else {
						writeOutput("[ERROR] release error [2]: " + data['data']['status']);
					}
				break;
				case "error":
					writeOutput("[ERROR] release error [1]: " + data);
				break;
			};
		});

  }

  /*
   * Request Crowdin for the top-member report
   */
	function crowdinCreate() {
		makeRequest({action: "create", reportId: $("#reportId").val(), latestDate: $("#latestDate").val()}, function (status, data) {
			switch (status) {
				case "beforeSend":
					writeOutput("Crowdin top-member started...");
				break;
				case "success":
					if (data['data']['status'] == "OK") {
						$("#reportId").val(data['data']['reportId']);
						writeOutput("[INFO] reportId created: " + data['data']['reportId']);
            crowdinProgress();
						intervalProgress = setInterval(function () {
							crowdinProgress();
						}, 2000);
					} else {
						writeOutput("[ERROR] reportId error [2]: " + data['data']['status']);
					}
				break;
				case "error":
					writeOutput("[ERROR] reportId error [1]: " + data);
				break;
			};
		});
	}

  /*
   * Check report generation progress
   */
	function crowdinProgress() {
		makeRequest({action: "progress", reportId: $("#reportId").val()}, function (status, data) {
			switch (status) {
				case "beforeSend":
					writeOutput("check report progress...");
				break;
				case "success":
					if (data['data']['status'] == "OK") {
						writeOutput("[INFO] progress status: " + data['data']['progress']);
						if (data['data']['progress'] == "100") {
							clearInterval(intervalProgress);
							crowdinReport();
						}
					} else {
						writeOutput("[ERROR] progress error [2]: " + data['data']['status']);
					}
				break;
				case "error":
					writeOutput("[ERROR] progress error [1]: " + data);
				break;
			};
		});
	}

  /*
   * Get report output formatted and ordered
   */
	function crowdinReport() {
		makeRequest({action: "download", reportId: $("#reportId").val()}, function (status, data) {
			switch (status) {
				case "beforeSend":
					writeOutput("check report download...");
				break;
				case "success":
					if (data['data']['status'] == "OK") {
						writeOutput("[INFO] download status: " + data['data']['status']);
            if (data['data']['download']) writeOutput("[INFO] download status: " + data['data']['download']);
            writeOutput("Crowdin top-member COMPLETED");
            $("#output").val(data['data']['output']);
					} else {
						writeOutput("[ERROR] download error [2]: " + data['data']['status']);
					}
				break;
				case "error":
					writeOutput("[ERROR] download error [1]: " + data);
				break;
			};
		});
	}

  /*
   * Write to debug panel
   */
	function writeOutput(message, style) {
		var d = new Date();
    var h = d.getHours();
    var m = d.getMinutes();
    var s = d.getSeconds();
		$("#debug").append((h < 10 ? '0' : '') + h + ':' + (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s + ': ' + message + "<br>");
	}

  /*
   * Ajax request just a little better
   */
	function makeRequest(reqParam, callBack) {
		var req = {
			"param": reqParam
		};

		$.ajax({
			url: 'ajax.php',
			type: 'post',
			data: req,
			success: function (data) {
				var response, tmpHTML, elem, tmpObj, i;
				try {
					response = $.parseJSON(data);
				} catch (err) {
					_console(response);
				}
				switch (response['param']['type']) {
					default:
						if (callBack) callBack('success', response);
					break;
				}
			},
			beforeSend: function () {
				switch (req['param']['type']) {
					default:
						if (callBack) callBack('beforeSend');
					break;
				}
			},
			error: function (xhr, text) {
				switch (req['param']['type']) {
					default:
						if (callBack) callBack('error', text);
					break;
				}
			}
		});
	}
</script>
</body>
</html>

