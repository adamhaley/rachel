<? 
$feed = 'http://localhost/rachel/wordpress/?feed=rss2&cat=4';

        $doc = new DOMDocument();
        $doc->load($feed);
        $arrFeeds = array();
        $i=0;
        foreach ($doc->getElementsByTagName('item') as $node) {
                $itemRSS = array (
                        'title' => $node->getElementsByTagName('title')->item(0)->nodeValue,
                        'desc' => $node->getElementsByTagName('description')->item(0)->nodeValue,
                        'link' => $node->getElementsByTagName('link')->item(0)->nodeValue,
                        'date' => $node->getElementsByTagName('pubDate')->item(0)->nodeValue
                        );
                array_push($arrFeeds, $itemRSS);
                if($i==2){
                        break;
                }
                $i++;
        }
        foreach($arrFeeds as $entry){
                echo "<b>" . date("M j, Y",strtotime($entry['date'])) . "</b><br /><br />";
                echo "<a href='" . $entry['link'] . "' target='_new'><b>" . $entry['title'] . "</b></a><br /><br />";
                echo str_replace('[...]','[<a href="' . $entry['link'] . '" target="_new">...</a>]',$entry['desc']);
                echo "<br /><br />";
        }
        echo "<br /><br />";
?>
