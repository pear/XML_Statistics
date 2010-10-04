<?PHP
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2002 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Stephan Schmidt <schst@php.net>                             |
// |          Basic idea adapted from Luis Argerich <lrargerich@yahoo.com>|
// |          and his class xml_check                                     |
// |          (http://phpxmlclasses.sourceforge.net/)                     |
// +----------------------------------------------------------------------+
//
//    $Id$

/**
 * XML/Statistics.php
 *
 * Package to statistically analyze XML documents, i.e.
 * counting tags, CData sections, Processing instructions and more.
 *
 * @category XML
 * @package  XML_Statistics
 * @author   Stephan Schmidt <schst@php.net>
 */

/**
 * XML_Parser is needed to parse the docuemnt
 */
require_once 'XML/Parser.php';

/**
 * Tagname was not found
 */
define( "XML_STATISTICS_ERROR_TAG_NOT_FOUND", 51 );
 
/**
 * Attribute was not found
 */
define( "XML_STATISTICS_ERROR_ATTRIBUTE_NOT_FOUND", 52 );

/**
 * Invalid depth
 */
define( "XML_STATISTICS_ERROR_INVALID_DEPTH", 53 );

/**
 * Unknown processing instruction
 */
define( "XML_STATISTICS_ERROR_PI_NOT_FOUND", 54 );

/**
 * Unknown external entity
 */
define( "XML_STATISTICS_ERROR_EXTERNAL_ENTITY_NOT_FOUND", 55 );

/**
 * XML statistics class
 *
 * This class can be used to get statistic information about an XML document.
 * It is able to extract to total number of tags, occurences of a specific tag,
 * number of attributes, attributes in a specific tag, processing instruction,
 * maximum nesting level and many more.
 *
 * The class is able to analyze a file, URL or string.
 *
 * <code>
 * require_once 'XML/Statistics.php';
 * $stat = new XML_Statistics();
 * $result = $stat->analyzeFile('http://pear.php.net/rss.php');
 * if ($stat->isError($result)) {
 *     die("Error: " . $result->getMessage());
 * }
 *
 * echo "maximum nesting level: " . $stat->getMaxDepth() . "<br />";
 * echo "occurences of item   : " . $stat->countTag('item') . "<br />";
 * echo "total tags           : " . $stat->countTag() . "<br />";
 * </code>
 *
 * @category XML
 * @package  XML_Statistics
 * @version  0.1
 * @author   Stephan Schmidt <schst@php.net>
 */
class XML_Statistics extends XML_Parser {

   /**
    * options for the analyses
    * @var    array
    * @access private
    */
    var $_options = array(
                         "ignoreWhitespace" => true
                        );

   /**
    * current depth
    * @var    integer
    * @access private
    */
    var $_depth = 0;

   /**
    * count the tags
    * @var    array
    * @access private
    */
    var $_tags = array();

   /**
    * count the tags in each depth
    * @var    array
    * @access private
    */
    var $_tagsInDepth = array();

   /**
    * count attributes
    * @var    array
    * @access private
    */
    var $_attribs = array();

   /**
    * count attributes per tag
    * @var    array
    * @access private
    */
    var $_attribsPerTag = array();

   /**
    * count processing instructions
    * @var    array
    * @access private
    */
    var $_pis = array();

   /**
    * count external entities
    * @var    array
    * @access private
    */
    var $_extEntities = array();

   /**
    * all data blocks
    * @var    array
    * @access private
    */
    var $_dataChunks = array();

   /**
    * length of the data
    * @var    integer
    * @access private
    */
    var $_dataLength = 0;

   /**
    * constructor
    *
    * When instantiating a new object, you may specify an array
    * containing options for the analysis. Currently only one
    * option is possible:
    *
    * - ignoreWhitespace => true|false
    *
    * @access public
    */   
    function XML_Statistics($options = array())
    {
        $this->_options = array_merge($this->_options, $options);
        $this->folding = false;
    }

   /**
    * analyze a file or URL
    *
    * After analyzing a file, use the count*() and get*()
    * methods to retrieve the result of the analysis.
    *
    * @access public
    * @param  string    $filename
    * @return boolean   true on success
    * @throws PEAR_Error
    * @see    analyzeString()
    */   
    function analyzeFile($file)
    {
        $this->XML_Parser();
        $this->_resetVars();
        $this->setInputFile($file);
        $result = $this->parse();
        if ($this->isError($result)) {
            return $result;
        }
        return true;
    }

   /**
    * analyze an XML string
    *
    * After analyzing a file, use the count*() and get*()
    * methods to retrieve the result of the analysis.
    *
    * @access public
    * @param  string    $xml        the XML string to analyze
    * @return boolean   true on success
    * @throws PEAR_Error
    * @see    analyzeFile()
    */   
    function analyzeString( $xml )
    {
        $this->XML_Parser();
        $this->_resetVars();
        $result = $this->parseString($xml);
        if ($this->isError($result)) {
            return $result;
        }
        return true;
    }

   /**
    * return API version
    *
    * @access   public
    * @static
    * @return   string  $version API version
    */
    function apiVersion()
    {
        return "0.1";
    }

   /**
    * get a list of tag occurences
    *
    * This will count the occurences of each tag in the document.
    * It returns an associative array with the tag names in the keys
    * and the number of occurences in the corresponding values.
    *
    * @access public
    * @return array               array with tag names in the index and occurences as the value
    * @see    getAttributeOccurences()
    */
    function getTagOccurences()
    {
        $list = $this->_tags;
        unset($list["__total"]);
        return $list;
    }

   /**
    * get a list of attribute occurences
    *
    * This will count the occurences of each attribute (by its unique name) in the document.
    * It returns an associative array with the tag names in the keys
    * and the number of occurences in the corresponding values.
    *
    * If you specify a tagname this method will only count the occurences
    * of attributes in this tag.
    *
    * @access public
    * @param  string    $tag      restrict to a tag
    * @return array               array with attribute names in the index and occurences as the value
    * @see    getTagOccurences()
    */
    function getAttributeOccurences($tagname = null)
    {
        if ($tagname != null) {
            if (!isset($this->_attribsPerTag[$tagname])) {
                return $this->raiseError('Tag {$tagname} not found.', XML_STATISTICS_ERROR_TAG_NOT_FOUND);
            }
            $list = $this->_attribsPerTag[$tagname];
        } else {
            $list = $this->_attribs;
        }
        unset($list["__total"]);
        return $list;
    }

   /**
    * count the occurences of a tag
    *
    * Counts how often a certain tag is used in the document.
    * If no tagname is specified ot will count the total
    * number of tags.
    *
    * @access public
    * @param  string    $tagname    if no tag name is supplied, all tags are counted
    * @return integer               occurences of the tag
    * @see    countTagsInDepth()
    */
    function countTag($tagname = null)
    {
        if ($tagname == null) {
            $tagname = "__total";
        }
        if (isset($this->_tags[$tagname])) {
            return $this->_tags[$tagname];
        }
        return $this->raiseError('Tag {$tagname} not found.', XML_STATISTICS_ERROR_TAG_NOT_FOUND);
    }

   /**
    * count the tags in a certain depth
    *
    * Thru nesting a tag can be at a certain depth. The root
    * tag is at depth zero. By counting the amount of tags in a
    * depth you can count the number of recordsets in an XML
    * document.
    *
    * @access public
    * @param  integer    $depth     tag depth (0 is root)
    * @return integer               number of tags in this depth
    * @see    countTag()
    */
    function countTagsInDepth($depth)
    {
        if (isset($this->_tagsInDepth[$depth])) {
            return $this->_tagsInDepth[$depth];
        }
        return $this->raiseError('Invalid depth {$depth}.', XML_STATISTICS_ERROR_INVALID_DEPTH);
    }

   /**
    * count the occurences of an attribute
    *
    * Counts how often a certain attribute is used in the document.
    * If no attribute is specified ot will count the total
    * number of tags.
    *
    * With the second parameter you may limit the search to
    * a special tag.
    *
    * @access public
    * @param  string    $attribute  if no attribute name is supplied, all attributes are counted
    * @param  string    $tagname    this allows you to limit your search to a tag
    * @return integer               occurences of the attribute
    */
    function countAttribute($attribute = null, $tagname = null)
    {
        if ($attribute == null) {
            $attribute = "__total";
        }

        if ($tagname == null) {
            if (isset($this->_attribs[$attribute])) {
                return $this->_attribs[$attribute];
            }
            return $this->raiseError('Attribute {$attribute} not found.', XML_STATISTICS_ERROR_TAG_NOT_FOUND);
        }

        if (!isset($this->_attribsPerTag[$tagname])) {
            return $this->raiseError('Tag {$tagname} not found.', XML_STATISTICS_ERROR_TAG_NOT_FOUND);
        }
        if (isset($this->_attribsPerTag[$tagname][$attribute])) {
            return $this->_attribsPerTag[$tagname][$attribute];
        }
        return $this->raiseError('Attribute {$attribute} not found.', XML_STATISTICS_ERROR_TAG_NOT_FOUND);
    }
    
   /**
    * count the occurences of a processing instruction
    *
    * Counts how often a certain processing instruction (e.g. <?PHP) is used in the document.
    * The target is the 'language' of the processing instruction. You only
    * need to specify the name, without the leading question mark.
    * If no target is specified ot will count the total
    * number of processing instructions.
    *
    * @access public
    * @param  string    $target     if no tag name is supplied, all PIs are counted
    * @return integer               occurences of the processing instruction
    */
    function countPI($target = null)
    {
        if ($target == null) {
            $target = "__total";
        }
        if (isset($this->_pis[$target])) {
            return $this->_pis[$target];
        }
        return $this->raiseError('Processing Instruction with specified target {$target} not found.', XML_STATISTICS_ERROR_PI_NOT_FOUND);
    }

   /**
    * count the occurences of a external entitity
    *
    * Counts how often a certain external is used in the document.
    * If no name is specified ot will count the total
    * number of external entities.
    *
    * External entities have to be declared in your DTD:
    * <pre>
    * &lt;!DOCTYPE page [
    *   &lt;!ENTITY foo SYSTEM "foo.xml"&gt;
    *   &lt;!ENTITY bar SYSTEM "bar.xml"&gt;
    * ]&gt;
    * </pre>
    *
    * @access public
    * @param  string    $name       the name of the entity
    * @return integer               occurences of the entity
    */
    function countExternalEntity($name = null)
    {
        if ($name == null) {
            $name = "__total";
        }
        if (isset($this->_extEntities[$name])) {
            return $this->_extEntities[$name];
        }
        return $this->raiseError('External entity target {$name} not found.', XML_STATISTICS_ERROR_EXTERNAL_ENTITY_NOT_FOUND);
    }

   /**
    * count the occurences of a data blocks
    *
    * This is influenced by the 'ignoreWhitespace' option.
    * Furthermore a new chunk is counted when the parser find a linebreak or
    * internal entity.
    *
    * @access public
    * @return integer               number of data chunks
    * @see    getCDataLength()
    */
    function countDataChunks()
    {
        return count($this->_dataChunks);
    }

   /**
    * get the combined length of all CData sections
    *
    * If you need to know how many chars you have between
    * your tags, this is the method that gets it.
    *
    * @access public
    * @return integer               number of data chunks
    * @see    countDataChunks()
    */
    function getCDataLength()
    {
        return $this->_dataLength;
    }

   /**
    * get the maximum nesting level
    *
    * This method will count how deep your document
    * is nested.
    *
    * <pre>
    * &lt;root&gt;
    *   &lt;foo&gt;
    *     &lt;bar/&gt;
    *   &lt;/foo&gt;
    * &lt;root&gt;
    * </pre>
    *
    * The nesting level of this document is 3.
    *
    * @access public
    * @return integer               maximum nesting level
    */
    function getMaxDepth()
    {
        return    max(array_keys($this->_tagsInDepth));
    }

    /**
     * Start element handler for XML parser
     *
     * @access protected
     * @param  object $parser  XML parser object
     * @param  string $element XML element
     * @param  array  $attribs attributes of XML tag
     * @return void
     */
    function startHandler($parser, $element, $attribs)
    {
        // count the total number of tags
        $this->_tags["__total"]++;
        
        // count occurences of this tag
        if (!isset($this->_tags[$element])) {
            $this->_tags[$element] = 0;
        }
        $this->_tags[$element]++;

        // count tags in the current depth
        if (!isset($this->_tagsInDepth[$this->depth])) {
            $this->_tagsInDepth[$this->depth] = 0;
        }
        $this->_tagsInDepth[$this->depth]++;

        // count the number of attributes
        $attribCount = count($attribs);
        $this->_attribs["__total"] = $this->_attribs["__total"] + $attribCount;

        // count the number of attributes for this tag
        if (!isset($this->_attribsPerTag[$element])) {
            $this->_attribsPerTag[$element] = array(
                                                    "__total" => 0
                                                   );
        }
        $this->_attribsPerTag[$element]["__total"] = $this->_attribsPerTag[$element]["__total"] + $attribCount;

        // count occurences of each attribute
        foreach (array_keys($attribs) as $attribName) {
            if (!isset($this->_attribs[$attribName])) {
                $this->_attribs[$attribName] = 0;
            }
            $this->_attribs[$attribName]++;

            // count the occurences of an attribute per tag
            if (!isset($this->_attribsPerTag[$element][$attribName])) {
                $this->_attribsPerTag[$element][$attribName] = 0;
            }
            $this->_attribsPerTag[$element][$attribName]++;
        }
        
        
        $this->depth++;    
    }

    /**
     * End element handler for XML parser
     *
     * @access protected
     * @param  object XML parser object
     * @param  string
     * @return void
     */
    function endHandler($parser, $element)
    {
        $this->depth--;
    }

    /**
     * Handler for character data
     *
     * @access protected
     * @param  object XML parser object
     * @param  string CDATA
     * @return void
     */
    function cdataHandler($parser, $cdata)
    {
        if ($this->_options["ignoreWhitespace"]) {
            $cdata = trim($cdata);
            if (empty($cdata)) {
                return true;
            }
        }
        
        // store the data
        array_push($this->_dataChunks, $cdata);

        // update the total length
        $this->_dataLength = $this->_dataLength + strlen($cdata);
    }

    /**
     * Handler for processing instructions
     *
     * @access protected
     * @param  object XML parser object
     * @param  string target
     * @param  string data
     * @return void
     */
    function piHandler($parser, $target, $data)
    {
        // count total amount of processing instructions
        $this->_pis["__total"]++;

        // count PIs per target
        if (!isset($this->_pis[$target])) {
            $this->_pis[$target] = 0;
        }
        
        $this->_pis[$target]++;
    }
    
    /**
     * Handler for external entities
     *
     * @access protected
     * @param  object XML parser object
     * @param  string target
     * @param  string data
     * @return void
     */
    function entityrefHandler($parser, $open_entity_names, $base, $system_id, $public_id)
    {
        // count total amount of external entities
        $this->_extEntities["__total"]++;

        // count external entities per entity name
        if (!isset($this->_extEntities[$open_entity_names])) {
            $this->_extEntities[$open_entity_names] = 0;
        }
        
        $this->_extEntities[$open_entity_names]++;
        return true;
    }

   /**
    * reset all used object properties
    *
    * This method is called before parsing a new document
    *
    * @access private
    */
    function _resetVars()
    {
        $this->depth          = 1;
        
        $this->_tags          = array(
                                      "__total" => 0
                                     );
        $this->_attribs       = array(
                                      "__total" => 0
                                     );

        $this->_tagsInDepth   = array();

        $this->_attribsPerTag = array();

        $this->_dataChunks    = array();
        $this->_dataLength    = 0;

        $this->_pis           = array(
                                      "__total" => 0
                                     );
        $this->_extEntities   = array(
                                      "__total" => 0
                                     );
    }
}
?>