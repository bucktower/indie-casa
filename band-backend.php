<?php

  // Include the wiki.php library -- this is used to parse Wiki Markup -> HTML
  // Thank you https://github.com/lahdekorpi/Wiky.php
  require_once("wiky.inc.php");


  $band_name = $_GET["band"];
  $prepped_band_name = str_replace(' ', '_', $band_name);

  $opts = array('http' =>
    array(
      'user_agent' => 'IndieMe/0.1 (http://www.indieme.com/)'
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

  // for this variable, we only want the band's biography, none of that infobox-y
  // stuff. this will only get text after the bold band name
  $wiki_content_p = strstr($wiki_content, "'''$wiki_band_name'''");

  // Some preliminary parsing
  // preg_replace & str_replace to get rid of those internal-Wikipedia links
  $startPoint = "[[";
  $endPoint = "|";
  $wiki_content_p = str_replace("|", "", preg_replace('#('.preg_quote($startPoint).')([\w|\s|(|)|,]*)('.preg_quote($endPoint).')#si', '$1$3', $wiki_content_p));
  $wiki_content_p = str_replace("[[", "", $wiki_content_p);
  $wiki_content_p = str_replace("]]", "", $wiki_content_p);
  $wiki_content_p = preg_replace("~\<ref(.*?)\</ref\>~", "", $wiki_content_p);

  // now we must translate from wiki markup -> HTML
  $wiky = new wiky;
  $input=htmlspecialchars($wiki_content_p);
  $wiki_content_p_parsed = $wiky->parse($input)

  // Getting band's hometown/origin
  // foundOrigin -- boolean (whether was able to find origin from Wikipedia)
  //$foundOrigin = preg_match()
  ?>
