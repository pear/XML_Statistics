<?PHP
/**
* This example makes use of
* XML_Statistics in conjunction with
* Math_Stats to calculate average occurences
* and other mathematical statistics
* of all tags in the documents
*/
    require_once 'XML/Statistics.php';
    require_once 'Math/Stats.php';

    $stat = new XML_Statistics();
    $result = $stat->analyzeFile("example.xml");
    
    if ($stat->isError($result)) {
        die("Error: " . $result->getMessage());
    }

    // get number of tags per tagname
	$tags	= $stat->getTagOccurences();

    // use Math_Stats
    $s = new Math_Stats();
	$s->setData($tags);
	$stats = $s->calcBasic();

	echo	"<pre>";
	print_r($stats);
	echo	"</pre>";
?>