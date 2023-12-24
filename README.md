### About

A simple and "ugly" script that extract best translators from Crowdin and convert their names in a string (comma separated). Optionally you can retrive the lastest release of a Github project to filter the translations date.

### Configuration

Open the config.php file and compile the mandatory field:

#### Crowdin
+ CROWDIN_PROJECT: it's a number and can be retrived following this guide https://support.crowdin.com/enterprise/project-settings/
+ CROWDIN_TOKEN: access token with only REPORTS permission
https://crowdin.com/settings#api-key

#### Github
+ GITHUB_USER: the user who own the repository
+ GITHUB_REPO: the repository name
+ GITHUB_TOKEN: legacy access token with only PUBLIC_REPO permission https://github.com/settings/tokens

### Usage
+ Simply open the url crowdin.php in the web folder you publish this scripts (ex. https://example.org/script/crowdin.php)
+ Insert a date to filter the crowdin translations
+ [OPTIONALLY] You can retrive the date from the lastest release of a github project
+ Press "START" button and wait some seconds for the output string

### Requirements
+ PHP 8.0+ with Curl enabled
+ Works both on Apache and IIS
+ No database Needed