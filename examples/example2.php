<?PHP
    require_once 'XML/Statistics.php';
    
    $stat = new XML_Statistics();
	$xml  = implode("",file("http://pear.php.net/rss.php"));
	
    // analyze a string instead of a file
    $result = $stat->analyzeString($xml);
    
    if ($stat->isError($result)) {
        die("Error: " . $result->getMessage());
    }
    
    echo    "Total tags: " . $stat->countTag()."<br>";
    echo    "Occurences of item: " . $stat->countTag('item')."<br>";
    echo    "Maximum nesting level: " . $stat->getMaxDepth()."<br>";

    // these are the <item> tags => amount of news entries
    echo    "Amount of Tags in depth 3: " . $stat->countTagsInDepth(3)."<br>";
?>