<?PHP
    require_once 'XML/Statistics.php';
    
    $stat = new XML_Statistics(array("ignoreWhitespace" => true));
    $result = $stat->analyzeFile("example.xml");
    
    if ($stat->isError($result)) {
        die("Error: " . $result->getMessage());
    }
    
    // total amount of tags:
    echo    "Total tags: " . $stat->countTag()."<br>";
    
    // count amount of 'title' attribute, in all tags
    echo    "Occurences of attribute title: " . $stat->countAttribute("title")."<br>";

    // count amount of 'title' attribute, only in <section> tags
    echo    "Occurences of attribute title in tag section: " . $stat->countAttribute("title", "section")."<br>";

    // count total number of tags in depth 4
    echo    "Amount of Tags in depth 4: " . $stat->countTagsInDepth(4)."<br>";

    echo    "Occurences of PHP Blocks: " . $stat->countPI("PHP")."<br>";
    
    echo    "Occurences of external entity 'bar': " . $stat->countExternalEntity("bar")."<br>";
    
    echo    "Data chunks: " . $stat->countDataChunks()."<br>";

    echo    "Length of all data chunks: " . $stat->getCDataLength()."<br>";
?>