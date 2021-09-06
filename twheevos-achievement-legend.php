<?php
// If Veerender Jubbal replies "A legend in our community." award the person
//  he replied to the achievement.
function look_for_new_achievements__legend()
{
    $twitter = prep_twitter();
    $twitter->setGetfield('?screen_name=Veeren_Jubbal&count=30');
    $json = $twitter->buildOAuth('https://api.twitter.com/1.1/statuses/user_timeline.json', 'GET')->performRequest();
    $response = json_decode($json, false, JSON_INVALID_UTF8_SUBSTITUTE);
    //print($json);
    //print_r($response);
    if ($response == NULL) {
        print("legend: Failed to get Veerender's tweets AT ALL\n");
    } else if (is_object($response) && isset($response->errors)) {
        print("legend: Query to Twitter failed:\n");
        print_r($response);
    } else {
        foreach ($response as $r) {
            if ($r->in_reply_to_screen_name != NULL) {
                $u = $r->in_reply_to_screen_name;
                if ($u == 'Veeren_Jubbal') {
                    continue;  // No cheating Veerender, you can't award it to yourself.  :P
                }
                if (preg_match("/^\@$u A legend in our community\.$/", $r->text) == 1) {
                    award_achievement('legend', $u, twitter_status_url('Veeren_Jubbal', $r->id_str, $r->in_reply_to_status_id_str));
                }
            }
        }
    }
}
?>
