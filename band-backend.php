<?php

  // Include the wiki.php library -- this is used to parse Wiki Markup -> HTML
  // Thank you https://github.com/lahdekorpi/Wiky.php
  require_once("wiky.inc.php");


  $band_name = ucwords($_GET["band"]);
  $prepped_band_name = str_replace(' ', '_', $band_name);

  $opts = array('http' =>
    array(
      'user_agent' => 'IndieCasa/0.1 (http://www.indie.casa/)'
    )
  );
  $context = stream_context_create($opts);

  $url = "http://en.wikipedia.org/w/api.php?action=query&titles={$prepped_band_name}&prop=revisions&rvprop=content&rvsection=0&format=json";
  $band_info_raw = file_get_contents($url, FALSE, $context);

  // Creates an array of wikipedia page info
  $band_info = json_decode(file_get_contents($url, FALSE, $context), true);

  // we're going through ["query"] -> ["pages"] -> ["PAGE ID"] -> ALL OUR INFO
  // Problem is, the page id is always changing, so we must get it dynamically.
  // Fortunately, ["PAGE ID"] is always the only key in the ["pages"] array, so
  // we can reference it using $band_key[0]
  $band_key = array_keys($band_info["query"]["pages"]);

  $wiki_band_name = $band_info["query"]["pages"][$band_key[0]]["title"];

  // get the actual wiki markup from the first part of the page
  $wiki_content = $band_info["query"]["pages"][$band_key[0]]["revisions"][0]["*"];

  // TODO: Case for redirects (for example, if you "The Alabama Shakes" instead of "Alabama Shakes")

  /**
   * BIOGRAPHY
   * Getting the band's biography
   */
    // for this variable, we only want the band's biography, none of that infobox-y
    // stuff. this will only get text after the bold band name
    $wiki_content_p = stristr($wiki_content, "'''$wiki_band_name'''");

    // Some preliminary parsing
    // preg_replace & str_replace to get rid of those internal-Wikipedia links, <ref> tags, etc.
    $startPoint = "[[";
    $endPoint = "|";
    $wiki_content_p = str_replace("|", "", preg_replace('#('.preg_quote($startPoint).')([\w|\s|(|)|,]*)('.preg_quote($endPoint).')#si', '$1$3', $wiki_content_p));
    $wiki_content_p = str_replace("[[", "", $wiki_content_p);
    $wiki_content_p = str_replace("]]", "", $wiki_content_p);
    $wiki_content_p = preg_replace("~\<ref(.*?)\</ref\>~", "", $wiki_content_p);

    // now we must translate from wiki markup -> HTML
    $wiky = new wiky;
    $input=htmlspecialchars($wiki_content_p);
    $wiki_content_p_parsed = $wiky->parse($input);

  /**
   * ORIGIN
   * Getting the band's origin
   */
    // Getting band's hometown/origin
    // foundOrigin -- boolean (whether was able to find origin from Wikipedia)
    $delimiter = '#';
    $startTag = 'origin';
    $endTag = ']]';
    $regex = $delimiter . preg_quote($startTag, $delimiter). '(.*?)'
                        . preg_quote($endTag, $delimiter)
                        . $delimiter
                        . 's';
    $foundOrigin = preg_match($regex,$wiki_content,$matches);
    $origin = $matches[0];

    // some parsing
    $origin = str_replace("origin", "", $origin);
    $origin = str_replace("=", "", $origin);
    $origin = str_replace("[[", "", $origin);
    $origin = str_replace("]]", "", $origin);

  /**
   * LABEL
   * Getting the band's origin
   */
    // Getting band's current label (listed last)
    // foundLabel -- boolean (whether was able to find origin from Wikipedia)
    $delimiter = '#';
    $startTag = 'label';
    $endTag = PHP_EOL;
    $regex = $delimiter . preg_quote($startTag, $delimiter). '(.*?)'
                        . preg_quote($endTag, $delimiter)
                        . $delimiter
                        . 's';
    $foundlabel = preg_match($regex,$wiki_content,$matches);
    // putting each record label into its own array element
    $lalArray = $matches[1];
    $labelArray = explode(",",$matches[1]);
    // so, right now we have something like "Warner Bros Records|Warner Bros." -- now we filter out the part after "|"
    $label = substr_replace(end($labelArray),"",strpos(end($labelArray),"|"));

    // just getting rid of the nusiance
    $label = str_replace("label", "", $label);
    $label = str_replace("=", "", $label);
    $label = str_replace("[[", "", $label);
    $label = str_replace("]]", "", $label);

    // What if the label is a flatlist? We shall reprocess it
    if($label == "{{flatlist") {
      $label = "ERROR";
    }

    // now we gotta do a second call to see if this label is associated with any of the big 3
    $prepped_label_name = str_replace(' ', '_', $label);
    $url = "http://en.wikipedia.org/w/api.php?action=query&titles={$prepped_label_name}&prop=revisions&rvprop=content&rvsection=0&format=json";
    $label_info = json_decode(file_get_contents($url, FALSE, $context), true);
    $label_key = array_keys($label_info["query"]["pages"]);
    $wiki_content_label = $label_info["query"]["pages"][$label_key[0]]["revisions"][0]["*"];

    $delimiter = '#';
    $startTag = '{{';
    $endTag = "'''";
    $regex = $delimiter . preg_quote($startTag, $delimiter). '(.*?)'
                        . preg_quote($endTag, $delimiter)
                        . $delimiter
                        . 's';
    $foundOrigin = preg_match($regex,$wiki_content_label,$matches);
    $label_infobox = $matches[0];

    // search for key terms in the record label's wikipedia page infobox
    if(strpos($label_infobox,'Warner Bros') !== false) {
      $isBandIndie = 'a record label owned by <b>Warner Music Group</b>';
    } elseif(strpos($label_infobox,'Sony') !== false) {
      $isBandIndie = 'a record label owned by <b>Sony Music Entertainment</b>';
    } elseif(strpos($label_infobox,'Universal Music') !== false) {
      $isBandIndie = 'a record label owned by <b>Universal Music Group</b>';
    } else {
      $isBandIndie = 'a <b>truly independent record label</b>';
    }
?>
