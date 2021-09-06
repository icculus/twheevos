<?php

require_once('twheevos-common.php');

function fail($response, $msg, $url = NULL)
{
    global $title;
    header("HTTP/1.0 $response");
    if ($url != NULL) { header("Location: $url"); }
    print_header($response);
    print("<p><h1>$response</h1></p>\n\n<p>$msg</p>\n");
    print_footer();
    exit(1);
}

function fail404($msg) { fail('404 Not Found', $msg); }
function fail503($msg) { fail('503 Service Unavailable', $msg); }

function get_database()
{
    global $db, $dbname;
    if ($db == NULL) {
        $db = new SQLite3($dbname, SQLITE3_OPEN_READONLY);
        if ($db == NULL) {
            fail503("Couldn't access database. Please try again later.");
        }
    }
    return $db;
}

function print_header($subtitle)
{
    global $title, $baseurl;
    $str = <<<EOS
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <link rel="shortcut icon" type="image/ico" href="/static_files/favicon.ico" />
    <link rel="icon" type="image/png" href="/static_files/favicon.png" />
    <title>$title - $subtitle</title>
    <meta name="twitter:card" content="summary" />
    <meta name="twitter:site" content="@twheevos" />
    <meta name="twitter:image" content="$baseurl/static_files/card.png" />
    <meta name="og:image" content="$baseurl/twheevos/static_files/card.png" />
    <meta name="twitter:url" content="$baseurl/" />
    <meta property="og:url" content="$baseurl/" />
    <meta name="twitter:title" content="$title - $subtitle" />
    <meta property="og:title" content="$title - $subtitle" />
    <meta name="twitter:description" content="The achievements Twitter deserves, not the ones it needs!" />
    <meta property="og:description" content="The achievements Twitter deserves, not the ones it needs!" />
    <style>
      /* Text and background color for light mode */
      body {
        color: #333;
        max-width: 900px;
        margin: 50px;
        margin-left: auto;
        margin-right: auto;
        font-size: 16px;
        line-height: 1.3;
        font-weight: 300;
      }

      /* Text and background color for dark mode */
      @media (prefers-color-scheme: dark) {
        body {
          color: #ddd;
          background-color: #222;
        }

        a {
          color: #809fff;
        }
      }
    </style>
  </head>
  <body>

EOS;
    print($str);
}

function print_footer()
{
    $str = <<<EOS
  </body>
</html>
EOS;
    print($str);
}

function display_mainpage()
{
    print_header("coming soon");
    print(<<<EOS
<center>
<p>(coming soon.)</p>
<p>[
<a href="https://twitter.com/twheevos">twitter</a>
|
<a href="https://github.com/icculus/twheevos">source code</a>
|
<a href="https://patreon.com/icculus">DONATE</a>
]
</p>
</center>"
EOS
);
    print_footer();
}

function display_award($awardid)
{
    global $baseurl;
    $awardid = intval($awardid);
    $row = query_award($awardid);
    if ($row === false) {
        fail404("No such award, sorry.");
    }

    global $achievements;
    $ach = $achievements[$row['achievement']];
    $achname = $ach['title'];
    $achdesc = $ach['desc'];

    $username = $row['user'];
    $when = timestamp_to_string($row['timestamp']);
    $whyurl = $row['whyurl'];

    print_header('Achievement unlocked by @' . $username . "!");
    print(<<<EOS
    <center>
      <p><h1>Achievement unlocked!</h1></p>
      <p><h2>$achname</h2></p>
      <p>This achievement awarded to <a href="https://twitter.com/$username">@$username</a>
      on $when because of <a href="$whyurl">this</a>.</p>
      <p><img width="75%" src="$baseurl/image/$awardid.jpg" /></p>
      <p>$achdesc</p>
    </center>
EOS
);

    print_footer();
}

function display_image($awardid)
{
    global $achievements;
    $awardid = intval($awardid);  // converts "123.jpg" into 123.
    $row = query_award($awardid);
    $ach = $achievements[$row['achievement']];
    header('Content-Type: image/jpeg');
    echo gen_image($row['user'], $ach['title']);
}


// Mainline!

$reqargs = explode('/', preg_replace('/^\/?(.*?)\/?$/', '$1', $_SERVER['PHP_SELF']));
array_shift($reqargs);
$reqargcount = count($reqargs);
//print_r($reqargs);

$operation = ($reqargcount >= 1) ? $reqargs[0] : '';
$document = ($reqargcount >= 2) ? $reqargs[1] : '';
$extraarg = ($reqargcount >= 3) ? $reqargs[2] : '';

if (($operation == '') || ($document == '')) {
    display_mainpage();
} else if ($operation == 'award') {
    display_award($document);
} else if ($operation == 'image') {
    display_image($document);
} else {
    fail404('No such page');
}

exit(0);
?>
