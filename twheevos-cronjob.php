<?php

require_once('twheevos-common.php');

function award_achievement($achname, $user, $whyurl)
{
    global $achievements;
    $ach = $achievements[$achname];
    $db = get_database();
    $stmt = $db->prepare('insert into awards (achievement, user, whyurl, timestamp) values (:achievement, :user, :whyurl, NOW());');
    $stmt->bindValue(':achievement', $achname);
    $stmt->bindValue(':user', $user);
    $stmt->bindValue(':whyurl', $whyurl);
    return $stmt->execute() === false ? false : $db->lastInsertRowID();
}

exit(0);
?>
