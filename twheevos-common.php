<?php

$dbname = 'twheevos.sqlite3';
$db = NULL;
$title = 'twheevos';
$baseurl = 'https://icculus.org/twheevos';

$achievements = [
    'legend' => [
        'title' => '"A Legend In Our Community"',
        'desc' => 'This achievement is awarded to those that @Veeren_Jubbal replies to with the magic phrase.',
    ]
];

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

function gen_image($username, $awardname)
{
    $username = '@' . $username;
    $objImage = new Imagick('achievement.jpg');

    $imgSize = $objImage->getImageGeometry();
    $imgWidth = $imgSize['width'];
    $imgHeight = $imgSize['height'];

    $objText = new ImagickDraw();
    $objText->setFillColor(new ImagickPixel('yellow'));
    $objText->setGravity(Imagick::GRAVITY_NORTHWEST);
    $objText->setFontSize(80);
    $objImage->annotateImage($objText, 70, 50, 0, 'Achievement');
    $objImage->annotateImage($objText, 110, 150, 0, 'Unlocked!');

    $objText->setFontSize(85);
    $metrics = $objImage->queryFontMetrics($objText, $username);
    $x = intval($imgWidth - $metrics['textWidth']) / 2;
    $y = intval($imgHeight - $metrics['textHeight']) + 30;
    $objImage->annotateImage($objText, $x, $y, 0, $username);

    $objText->setFontSize(60);
    $metrics = $objImage->queryFontMetrics($objText, $awardname);
    $x = intval($imgWidth - $metrics['textWidth']) / 10;
    $y = (intval($imgHeight - $metrics['textHeight']) / 2) + 30;
    $objImage->annotateImage($objText, $x, $y, 0, $awardname);

    return $objImage;
}

function query_award($awardid)
{
    $db = get_database();
    $stmt = $db->prepare('select * from awards where id = :awardid limit 1;');
    $stmt->bindValue(':awardid', "$awardid");
    $results = $stmt->execute();
    return $results->fetchArray();
}

function timestamp_to_string($t)
{
    return strftime('%D %T %Z', $t);
}

?>