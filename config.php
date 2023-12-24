<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);

define("DEBUG", false);

// Look in the project details: https://support.crowdin.com/enterprise/project-settings/
define("CROWDIN_PROJECT", "");

// Token generated https://crowdin.com/settings#api-key
// Needed only "REPORTS" permission
define("CROWDIN_TOKEN", "");


// Public repo information
define("GITHUB_USER", "");
define("GITHUB_REPO", "");
// Token generated https://github.com/settings/tokens
// Needed only "PUBLIC_REPO" permission
define("GITHUB_TOKEN", "");

// DO NOT TOUCH BELOW

define("CROWDIN_API", "https://api.crowdin.com/api/v2/projects/" . CROWDIN_PROJECT . "/reports");
define("GITHUB_API", "https://api.github.com/repos/" . GITHUB_USER . "/" . GITHUB_REPO . "/releases/latest");

