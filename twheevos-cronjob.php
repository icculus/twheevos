#!/usr/bin/php
<?php

require_once('TwitterAPIExchange.php');
require_once('twheevos-common.php');

foreach ($achievements as $k => $v) {
    // it's extremely useful and also completely horrifying that PHP lets you do this.
    require_once("twheevos-achievement-$k.php");
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

function tweet_achievement($ach, $user, $whyurl, $rowid, $in_reply_to_status_id=NULL)
{
    global $baseurl;
    $achname = $ach['title'];

    $mediaid = NULL;

    // upload the image...
    $twitter = prep_twitter();
    $json = $twitter->buildOAuth('https://upload.twitter.com/1.1/media/upload.json', 'POST')->
        setPostfields([
            'media_category' => 'tweet_image',
            'media_data' => base64_encode(gen_image($user, $achname))
        ])->performRequest();
    $response = json_decode($json, false, JSON_INVALID_UTF8_SUBSTITUTE);
    //print($json);
    //print_r($response);
    if ($response == NULL) {
        print("Failed to post image to Twitter AT ALL\n");
        return false;
    } else if (is_object($response) && isset($response->errors)) {
        print("media: Post of image to Twitter failed:\n");
        print_r($response);
        return false;
    } else {
        $mediaid = $response->media_id_string;
    }

    // ...and tweet!
    $status = "@$user Achievement unlocked: $achname.\n\n$baseurl/award/$rowid";

    $twitter = prep_twitter();
    $postfields = [
        'status' => $status,
        'media_ids' => "$mediaid",
        'trim_user' => true
    ];
    if (isset($in_reply_to_status_id)) {
        $postfields['in_reply_to_status_id'] = $in_reply_to_status_id;
    }
    $json = $twitter->buildOAuth('https://api.twitter.com/1.1/statuses/update.json', 'POST')->
        setPostfields($postfields)->performRequest();
    $response = json_decode($json, false, JSON_INVALID_UTF8_SUBSTITUTE);
    //print($json);
    //print_r($response);
    if ($response == NULL) {
        print("Failed to post tweet to Twitter AT ALL\n");
        return false;
    } else if (is_object($response) && isset($response->errors)) {
        print("media: Post of tweet to Twitter failed:\n");
        print_r($response);
        return false;
    }

    print("Posted tweet!\n");
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
        //print("User '$user' already has the '$achname' achievement! id=$id, when=$when, why=$whyurl\n");
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

function look_for_new_achievements()
{
    global $achievements;
    foreach ($achievements as $k => $v) {
        // it's extremely useful and also completely horrifying that PHP lets you do this.
        $fn = "look_for_new_achievements__$k";
        $fn();
    }
}


// Mainline!
prep_database();
look_for_new_achievements();
exit(0);
?>
