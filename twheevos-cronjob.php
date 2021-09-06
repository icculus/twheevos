#!/usr/bin/php
<?php

require_once('TwitterAPIExchange.php');
require_once('twheevos-common.php');

foreach ($achievements as $k => $v) {
    // it's extremely useful and also completely horrifying that PHP lets you do this.
    require_once("twheevos-achievement-$k.php");
}

function get_database()
{
    global $db, $dbname;
    if ($db == NULL) {
        $db = new SQLite3($dbname, SQLITE3_OPEN_READWRITE);
        if ($db == NULL) {
            print("Couldn't access database. Please try again later.");
            exit(1);
        }
    }
    return $db;
}

function prep_database()
{
    $schema =
        "create table if not exists awards (" .
        " id integer primary key," .
        " achievement text not null," .
        " user text not null," .
        " whyurl text not null," .
        " timestamp integer unsigned not null" .
        ");" .
        "" .
        "create index if not exists awards_index on awards (user);"
    ;

    $db = get_database();
    $db->exec($schema);
}

function tweet_achievement($ach, $user, $whyurl, $rowid)
{
    print("WRITE ME tweet_achievement\n");
    return true;
}

function award_achievement($achname, $user, $whyurl)
{
    global $achievements;
    $ach = $achievements[$achname];
    $db = get_database();
    $stmt = $db->prepare('select id, timestamp, whyurl from awards where achievement = :achievement and user = :user limit 1;');
    $stmt->bindValue(':achievement', $achname);
    $stmt->bindValue(':user', $user);
    $results = $stmt->execute();
    if ($results === false) {
        print("Failed to check if user '$user' already has achievement '$achname'!");
        return false;
    }

    if ($row = $results->fetchArray()) {
        $id = $row['id'];
        $when = timestamp_to_string($row['timestamp']);
        $whyurl = $row['whyurl'];
        print("User '$user' already has the '$achname' achievement! id=$id, when=$when, why=$whyurl\n");
        return false;
    }

    $stmt = $db->prepare('insert into awards (achievement, user, whyurl, timestamp) values (:achievement, :user, :whyurl, :timestamp);');
    $stmt->bindValue(':achievement', $achname);
    $stmt->bindValue(':user', $user);
    $stmt->bindValue(':whyurl', $whyurl);
    $stmt->bindValue(':timestamp', time());
    if ($stmt->execute() === false) {
        print("Failed to award achievement '$achname' to '$user' for '$whyurl'!");
        return false;
    }

    $rowid = $db->lastInsertRowID();
    print("Awarded achievement '$achname' to '$user' for '$whyurl' (id=$rowid).\n");
    return tweet_achievement($ach, $user, $whyurl, $rowid);
}

function look_for_new_achievements($twitter)
{
    global $achievements;
    foreach ($achievements as $k => $v) {
        // it's extremely useful and also completely horrifying that PHP lets you do this.
        $fn = "look_for_new_achievements__$k";
        $fn($k, $twitter);
    }
}

function prep_twitter()
{
    return new TwitterAPIExchange([
        'consumer_key' => CONSUMER_KEY,
        'consumer_secret' => CONSUMER_SECRET,
        'oauth_access_token' => OAUTH_TOKEN,
        'oauth_access_token_secret' => OAUTH_SECRET
    ]);
}


// Mainline!
prep_database();
$twitter = prep_twitter();
look_for_new_achievements($twitter);
exit(0);
?>
