<?php

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

  $wiki_content = $band_info["query"]["pages"][$band_key[0]]["revisions"][0]["*"];
  $wiki_content_p = strstr($wiki_content, "'''$wiki_band_name'''");
?>
