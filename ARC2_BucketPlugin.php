<?php
/*
 homepage: http://www.enridaga.net/
 license:  undefined

 class:    ARC2_BucketPlugin
 author:   Enrico Daga
 version:  2011-04-25
 */

ARC2::inc ( 'Class' );

class ARC2_BucketPlugin extends ARC2_Class {

	function __construct($a = '', &$caller) {
		parent::__construct ( $a, $caller );
	}

	function __init() {
		parent::__init ();
	}

	function getBucket($triples = NULL) {
		return new Bucket ( $triples, $this );
	}

	function getShape($mappings = array()) {
		return new Shape ( $mappings, $this );
	}
}
/**
 * This is a Bucket, a utility class to manage branches of RDF data
 *
 * All methods support both compact and expanded syntax (except when specified in the comment):
 * http://www.w3.org/2002/07/owl#Thing is equal to owl:Thing
 *
 * Supported namespaces are the ones included in the ARC2_class given as second parameter to the constructor.
 *
 */
class Bucket {

	/**
	 * This private variable hosts the triples
	 * @var array
	 */
	private $triples = array ();

	/**
	 * Pointer to the ARC2 instance
	 * @var ARC2
	 */
	public $arc = null;

	/**
	 * String constant for IRIs
	 * @var string
	 */
	public $IRI = 'uri';

	/**
	 * String constant for blank nodes
	 * @var string
	 */
	public $BNODE = 'bnode';

	/**
	 * String ocnstant for literals
	 * @var string
	 */
	public $LITERAL = 'literal';

	/**
	 * Default constructor
	 *
	 * @param array $triples - the array of ARC2 triples
	 * @param object $arcClass - the factory class, must be a ARC2_class
	 * @return Bucket
	 */
	function Bucket($triples = array(), $arcClass) {
		$this->triples = $triples;
		$this->arc = $arcClass;
	}

	/**
	 * A clone of the current Bucket object.
	 * This method creates a new Bucket which contains the same triples.
	 * This method does not change the state of the object.
	 * @return Bucket
	 */
	function bucketClone() {
		return new Bucket ( $this->getTriples (), $this->arc );
	}

	/**
	 * Gets the ARC2 triples contained in this bucket
	 * This method does not change the state of the object.
	 * @return array - The ARC2 triples contained in this bucket
	 */
	function getTriples() {
		return $this->triples;
	}

	/**
	 * Returns a bucket with all triples in which $resource is involved as subject
	 * This is a 'getter method'.
	 *
	 * @param string $resource - the 's' of the triples to put in the returned bucket
	 * @param boolean $inverse - add also triples where $resource is 'o'
	 * @return Bucket
	 */
	function describe($resource, $inverse = false) {
		// We add a filter
		$this->filterAdd ( $resource );
		$bres = $this->filter ();

		if ( $inverse ){
			$this->filterAdd ( NULL, NULL, $resource );
			$bres = $this->filter()->merge($bres);
		}

		return $bres;

	}

	/**
	 * Creates a bucket object which contains the merge of the current bucket object with the parameter object/array of triples
	 * This method does not change the state of the object.
	 *
	 * @param mixed $bucket - can be an array of triples or a bucket object
	 * @return Bucket
	 */
	function merge($bucket) {
		if (is_array ( $bucket )) {
			return new Bucket ( array_merge ( $this->getTriples (), $bucket ), $this->arc );
		} else if (is_object ( $bucket )) {
			return new Bucket ( array_merge ( $this->getTriples (), $bucket->getTriples () ), $this->arc );
		} else
		return null;
	}

	/**
	 * Gets the number of triples in this bucket
	 * This method does not change the state of the object.
	 * @return int
	 */
	function size() {
		return count ( $this->triples );
	}

	/**
	 * Returns wheather the triple exists in this bucket.
	 * All parameters must be present in the call.
	 * This method does not change the state of the object.
	 *
	 * @param string $s
	 * @param string $p
	 * @param string $o
	 * @return boolean
	 */
	function isTrue($s, $p, $o) {
		$input = $this->getTriples ();
		foreach ( $input as $triple ) {
			if ($this->match ( $triple, $s, $p, $o )) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns wheather this bucket is empty.
	 * This method does not change the state of the object.
	 *
	 * @return boolean
	 */
	function isEmpty() {
		return count ( $this->getTriples () ) == 0;
	}

	/**
	 * Returns wheather the ARC2 triple given as first parameter matches the conditions given as parameters.
	 * All of the parameters except the first can be NULL, this means that the condition must be matched anyway.
	 * This method does not change the state of the object.
	 *
	 * @todo support for array(), giving more options to be matched
	 * @param array $triple - the ARC2 representation of the triple
	 * @param array $s - the 's' value of the ARC2 triple
	 * @param array $p - the 'p' value of the ARC2 triple
	 * @param array $o - the 'o' value of the ARC2 triple
	 * @param string $s_type - the 's_type' value of the ARC2 triple
	 * @param string $o_type - the 'o_type' value of the ARC2 triple
	 * @param string $o_datatype - the 'o_datatype' value of the ARC2 triple
	 * @param string $o_lang - the 'o_lang' value of the ARC2 triple
	 * @return boolean
	 */
	function match($triple,array $s = NULL,array $p = NULL,array $o = NULL, $s_type = NULL, $o_type = NULL, $o_datatype = NULL, $o_lang = NULL) {
		if (isset ( $triple ['s'] ) && isset ( $triple ['p'] ) && isset ( $triple ['o'] )) {
			$sm = false;
			if ($s == NULL) {
				$sm = true;
			} else if ($s != NULL) {
				foreach( $s as $si){
					if ($triple ['s'] == $si) {
						$sm = true;
						break;
					}
				}
				if(!$sm) return false;
			} else if ($s_type != NULL) {
				if ($triple ['s_type'] == $s_type) {
					$sm = true;
				} else
				return false;
			}
			$pm = false;
			if ($p == NULL) {
				$pm = true;
			} else if ($p != NULL) {
				foreach( $p as $pi){
					if ($triple ['p'] == $pi) {
						$pm = true;
						break;
					}
				}
				if(!$pm)
				return false;
			}
			$omv = false;
			$omt = false;
			$omd = false;
			$oml = false;
			if ($o == NULL) {
				$omv = true;
			} else {
				foreach($o as $oi){
					if ($triple ['o'] == $oi) {
						$omv = true;
						break;
					}
				}
				if(!$omv)
				return false;

			}
			if ($o_type == NULL) {
				$omt = true;
			} else if ($triple ['o_type'] == $o_type) {
				$omt = true;
			} else
			return false;
			if ($o_datatype == NULL) {
				$omd = true;
			} else {
				$o_datatype = $this->arc->expandPName ( $o_datatype );
				if ($triple ['o_datatype'] == $o_datatype) {
					$omd = true;
				} else
				return false;
			}
			if ($o_lang == NULL) {
				$oml = true;
			} else if ($triple ['o_lang'] == $o_lang) {
				$oml = true;
			} else
			return false;

			$om = ($omv && $omt && $omd && $oml);

			if ($sm && $pm && $om) {
				return true;
			}
		}
		return false;
	}

	/**
	 * This method returns a new Bucket which contains all the triples that match the given conditions
	 * This is a 'getter method'.
	 * If some filter has bee added through some 'setter method' - such 'onProperty' - it is applyed.
	 * Then the filter stack is cleaned.
	 * If no paramters are passed, the filter will only apply filters from the filter stack. If no fitlers have been set, it returns a new Bucket which is the clone of this.
	 *
	 * @see match()
	 * @todo support for array(), giving more options to be matched
	 * @param string $s - the 's' value of the ARC2 triple
	 * @param string $p - the 's' value of the ARC2 triple
	 * @param string $o - the 's' value of the ARC2 triple
	 * @param string $s_type - the 's_type' value of the ARC2 triple
	 * @param string $o_type - the 'o_type' value of the ARC2 triple
	 * @param string $o_datatype - the 'o_datatype' value of the ARC2 triple
	 * @param string $o_lang - the 'o_lang' value of the ARC2 triple
	 * @return Bucket
	 */
	function filter($s = NULL, $p = NULL, $o = NULL, $s_type = NULL, $o_type = NULL, $o_datatype = NULL, $o_lang = NULL) {
		$input = $this->getTriples ();
		$output = array ();
		// We add the given filter
		$this->filterAdd ( $s, $p, $o, $s_type, $o_type, $o_datatype, $o_lang );

		// We cycle throught the triples
		foreach ( $input as $triple ) {
			// We cycle through the filter stack
			$matches = true;
			foreach ( $this->filterStack as $filter ) {
				// If the triple does not match one filter, do not add it the output set
				if (! $this->match ( $triple, $filter [0], $filter [1], $filter [2], $filter [3], $filter [4], $filter [5], $filter [6] )) {
					$matches = false;
					break;
				}
			}
			if ($matches)
			array_push ( $output, $triple );
		}
		// We clean the filter
		$this->filterInit ();
		return new Bucket ( $output, $this->arc );
	}

	/**
	 * Wheather the param is an IRI
	 * This method does not change the state of the object.
	 *
	 * Supported schemas:
	 * - http
	 * - https
	 * - ftp
	 * - urn
	 * - svn
	 * - svn+ssh
	 * - doi
	 * - isbn
	 * - tel
	 *
	 * @param string $o
	 * @return boolean
	 */
	function isIRI($o) {
		$expanded = $this->arc->expandPName ( $o );
		//return (preg_match ( '/^(http|ftp|urn):[a-zA-z0-9\_\+\-\:\%\s\(\)]+$/i', $expanded ));
		//return (preg_match ( '@[a-zA-Z]:.+@', $o ));
		return (preg_match ( '@^(http|https|ftp|urn|svn|svn+ssh|doi|isbn|tel):.+@', $expanded ));
	}

	/**
	 * Wheather the param is a Literal
	 * This method does not change the state of the object.
	 *
	 * @param string $o
	 * @return boolean
	 */
	function isLiteral($o) {
		return ((! $this->isIRI ( $o )) && (! $this->isBNode ( $o )));
	}

	/**
	 * Wheather the param is a BNode.
	 * This method does not change the state of the object.
	 *
	 * @param string $o
	 * @return boolean
	 */
	function isBNode($o) {
		return (substr ( $o, 0, 2 ) == '_:');
	}

	/**
	 * Gets the type of the parameter.
	 * This method does not change the state of the object.
	 *
	 * @param string $o
	 * @return string -
	 *  Returned values are:
	 *  - Bucket::IRI ('iri')
	 *  - Bucket::LITERAL ('literal')
	 *  - Bucket::BNODE ('bnode')
	 *
	 */
	function getObjectType($o) {
		if ($this->isIRI ( $o )) {
			return $this->IRI;
		}
		if ($this->isBNode ( $o )) {
			return $this->BNODE;
		} else
		return $this->LITERAL;
	}

	/**
	 *
	 * Returns a bucket which contains all the triples with the given string as subject.
	 * This has the same behaviour of $bucket->filter ( $s ).
	 * This is a 'setter method'.
	 * This method returns the object itself.
	 *
	 * @param string $s
	 * @return Bucket
	 */
	function onSubject($s) {
		$this->filterAdd ( $s );
		return $this;
	}

	/**
	 * Returns a bucket wich contains all the triples with the given string as property.
	 * This has the same behaviour of $bucket->filter ( NULL, $p )
	 * This is a 'setter method'.
	 * This method returns the object itself.
	 *
	 * @param string $p
	 * @return Bucket
	 */
	function onProperty($p) {
		$this->filterAdd ( NULL, $p );
		return $this;
	}

	/**
	 * Returns a bucket wich contains all the triples with the given string as object.
	 * This has the same behaviour of $bucket->filter ( NULL, NULL, $o )
	 * This is a 'setter method'.
	 * This method returns the object itself.
	 *
	 * @param string $o
	 * @return Bucket
	 */
	function onObject($o) {
		$this->filterAdd ( NULL, NULL, $o );
		return $this;
	}

	/**
	 * Returns a simple array with all subjects (without duplicates)
	 * This is a 'getter method'.
	 * This method returns an array of strings (subjects)
	 * return array()
	 *
	 */
	function subjects($s_type = NULL) {
		$this->filterAdd ( NULL, NULL, NULL, $s_type );
		$triples = $this->filter ()->getTriples ();
		$subjects = array ();
		foreach ( $triples as $triple ) {
			array_push ( $subjects, $triple ['s'] );
		}
		return array_unique ( $subjects );
	}

	/**
	 * Returns a simple array with all properties (without duplicates)
	 * This is a 'getter method'.
	 * This method returns an array of strings (properties)
	 * return array()
	 *
	 */
	function properties() {
		$triples = $this->filter ()->getTriples ();
		$properties = array ();
		foreach ( $triples as $triple ) {
			array_push ( $properties, $triple ['p'] );
		}
		return array_unique ( $properties );
	}

	/**
	 * Returns a simple array with all matching values (without duplicates)
	 * This is a 'getter method'.
	 * This method returns an array of strings (values)
	 *
	 * return array()
	 *
	 */
	function values($o_type = NULL, $o_datatype = NULL, $o_lang = NULL) {
		$this->filterAdd ( NULL, NULL, NULL, NULL, $o_type, $o_datatype, $o_lang );
		$triples = $this->filter ()->getTriples ();

		$objects = array ();
		foreach ( $triples as $triple ) {
			array_push ( $objects, $triple ['o'] );
		}
		return array_unique ( $objects );
	}

	/**
	 * Returns the first value of the first triple.
	 * This makes sense if you are sure that the bucket contains only 1 triple that matches the parameters (and the filter stack) and you do not want to spend time by using the return of getTriples() method!
	 * This is a 'getter method'.
	 * This method returns a string (the value)
	 *
	 * @return string
	 */
	function firstValue($o_type = NULL, $o_datatype = NULL, $o_lang = NULL) {
		$this->filterAdd ( NULL, NULL, NULL, NULL, $o_type, $o_datatype, $o_lang );
		$triples = $this->filter ()->getTriples ();
		foreach ( $triples as $triple ) {
			return $triple ['o'];
		}
		return NULL;
	}

	/**
	 * Returns a new bucket which contains the triples of this bucket plus the triples fetched from the passed uri
	 * This method does not change the state of the object.
	 *
	 * @param string $uri - the uri from which fetch the triples
	 * @return Bucket
	 */
	function fetch($uri) {
		$conf = $this->arc->a;
		$parser = ARC2::getRDFParser ( $conf );
		$parser->parse ( $uri );
		$triples = $parser->getTriples ();
		return $this->merge ( new Bucket ( $triples, $this->arc ) );
	}

	/**
	 * Wheather the give IRIs are equal.
	 * Support compact syntax: http://www.w3.org/2002/07/owl# is equal to owl:Thing
	 * This method does not change the state of the object.
	 *
	 * @param string $a
	 * @param string $b
	 * @return boolean
	 */
	function same($a, $b) {
		return ($this->arc->expandPName ( $a ) == $this->arc->expandPName ( $b ));
	}

	/**
	 * The compact version of the string
	 * This method does not change the state of the object.
	 *
	 * @param string $o
	 * @return string
	 */
	function compact($o) {
		return $this->arc->getPName ( $o );
	}

	/**
	 * The expanded version of the string
	 * This method does not change the state of the object.
	 *
	 * @param string $o
	 * @return string
	 */
	function expanded($o) {
		return $this->arc->expandPName ( $o );
	}
	/**
	 * Returns the prefix of the given expanded uri string
	 * This method does not change the state of the object.
	 *
	 * @param string $o
	 * @return string
	 */
	function prefix($o) {
		$ar = split ( ':', $this->arc->getPName ( $o ) );
		return $ar [0];
	}
	/**
	 * Returns the namespace of the given expanded uri string
	 * ie removes local name from the string
	 * This method does not change the state of the object.
	 *
	 * Note: this method was previously called 'namespace'.
	 * This looks to be a reserved keyword.
	 *
	 * @param string $o
	 * @return string
	 */
	function nspace($o) {
		return $this->arc->getPNameNamespace ( $this->arc->getPName ( $o ) );
	}
	/**
	 * Returns the local name from the full uri string
	 * This method does not change the state of the object.
	 *
	 * @param string $o
	 * @return string
	 */
	function local($o) {
		$ar = split ( ':', $this->arc->getPName ( $o ) );
		return $ar [1];
	}

	/**
	 * Returns the local name of the given compact uri string.
	 * This method does not change the state of the object.
	 *
	 * @param string $o
	 * @param connector $o
	 * @return string
	 */
	function name($o) {
		$ar = split ( ':', $this->arc->getPName ( $o ) );
		return $ar [1];
	}

	/**
	 * Private filed that holds the stack of filters.
	 *
	 * @var array
	 */
	private $filterStack = array ();

	/**
	 * Prepare a parameter to be used in a filter.
	 * The input is intended to be an IRI, to be used as
	 * subject or property filter.
	 * The function returns an array.
	 *
	 * @param string|array $iri
	 * @return array
	 */
	private function prepareFilterIri($iri = NULL){
		if(is_null($iri)) return NULL;
		if(!is_array($iri)){
			$iri = array($this->arc->expandPName ( $iri ));
		}else{
			foreach($iri as &$irii){
				$irii = $this->arc->expandPName ( $irii );
			}
		}
		return $iri;
	}

	/**
	 * Prepare a parameter to be used in a filter.
	 * The input is intended to be the object clause of a filter.
	 * @param string|array $obj
	 * @return array
	 */
	private function prepareFilterObject($obj = NULL){
		if(is_null($obj)) return NULL;
		if(!is_array($obj)){
			if($this->isIRI($obj)){
				$obj = $this->arc->expandPName ( $obj );
			}
			$obj = array($obj);
		}else{
			foreach($obj as &$obji){
				if($this->isIRI($obji)){
					$obji = $this->arc->expandPName ( $obji );
				}
			}
		}
		return $obj;
	}

	/**
	 * This function prepare the filter array.
	 * This is needed to have ech parameter in a uniform fashion
	 * to avoid unefficient checks in the match() function.
	 *
	 * @param string|array $s
	 * @param string|array $p
	 * @param string|array $o
	 * @param string $s_type
	 * @param string $o_type
	 * @param string $o_datatype
	 * @param string $o_lang
	 */
	private function prepareFilter($s = NULL, $p = NULL, $o = NULL, $s_type = NULL, $o_type = NULL, $o_datatype = NULL, $o_lang = NULL){
		return array(
		$this->prepareFilterIri($s),
		$this->prepareFilterIri($p),
		$this->prepareFilterObject($o),
		$s_type,
		$o_type,
		$o_datatype,
		$o_lang
		);

	}
	/**
	 * This method adds a filter to the stack of filters.
	 * This method changes the state of the object.
	 *
	 * @param string|array $s
	 * @param string|array $p
	 * @param string|array $o
	 * @param string $s_type
	 * @param string $o_type
	 * @param string $o_datatype
	 * @param string $o_lang
	 */
	private function filterAdd($s = NULL, $p = NULL, $o = NULL, $s_type = NULL, $o_type = NULL, $o_datatype = NULL, $o_lang = NULL) {
		$this->filterStack [] = $this->prepareFilter (
		$s,
		$p,
		$o,
		$s_type,
		$o_type,
		$o_datatype,
		$o_lang
		);
	}

	/**
	 * Initialize the filter stack.
	 * This method changes the state of the object.
	 *
	 */
	private function filterInit() {
		$this->filterStack = array ();
	}

	/**
	 * Removes a filter from the filter stack.
	 * This method changes the state of the object.
	 *
	 * @param string $s
	 * @param string $p
	 * @param string $o
	 * @param string $s_type
	 * @param string $o_type
	 * @param string $o_datatype
	 * @param string $o_lang
	 * @return mixed (The removed filter or false if requested filter does not exists in the stack)
	 */
	private function filterRemove($s = NULL, $p = NULL, $o = NULL, $s_type = NULL, $o_type = NULL, $o_datatype = NULL, $o_lang = NULL) {
		foreach ( $this->filterStack as $kk => $fil ) {
			if ($this->filterEqual ( $fil, array (
			$s,
			$p,
			$o,
			$s_type,
			$o_type,
			$o_datatype,
			$o_lang
			) )) {
				$removedFilter = $this->filterStack [$kk];
				unser ( $this->filterStack [$kk] );
				return $removedFilter;
			}
		}
		return false;
	}
	/**
	 * Check if two filters (a kind of array) are equals.
	 * This method does not change the state of the object.
	 *
	 * @param array $filter1
	 * @param array $filter2
	 * @return boolean
	 */
	private function filterEqual($filter1, $filter2) {
		if ($filter1 [0] == $filter2 [0] && $filter1 [1] == $filter2 [1] && $filter1 [2] == $filter2 [2] && $filter1 [3] == $filter2 [3] && $filter1 [4] == $filter2 [4] && $filter1 [5] == $filter2 [5] && $filter1 [6] == $filter2 [6]) {
			return true;
		} else {
			return false;
		}
	}
	/**
	 * Returns a Bucket flushing the filter stack
	 * This method changes the state of the object.
	 * This is equivalent to $bucket->filter() .
	 *
	 * @return Bucket
	 */
	private function flush() {
		return $this->filter ();
	}
}

/**
 * *** THIS IS ONGOING WORK ***
 *
 * TODO
 * This is a Shape, a utility class to build ready-made objects that aggregates data from a bucket to be used,
 * for instance, as Models in a MVC environment.
 * Mappings must be of the form:
 *
 * $mappings => array(
 * 		// the attribute 'label' will contain the object of property 'p', forced as single value
 * 		'label'  => array( 'p' => 'rdfs:label' , 'flags' => Shape::SINGLE ),
 * 		// Is it possible to give shape objects as the shape to fill using the object uri as subject
 * 		// In this case an array of $objects with attributes defined by $classShape will be returned
 * 		'type'   => array('p'=>'rdf:type','shape'=>$classShape)
 *
 * );
 *
 * Supported parameters are:
 * 'p' : the property uri (mandatory)
 * 'o_type' : the object type
 * 'o_datatype' : the object datatype
 * 'o_lang' : the object lang
 * 'flags' : Shape::$SINGLE or Shape::$MULTIPLE
 * 'shape' : a shape to use for filling an object (will use object URI as Subject)
 */
class Shape {
	public static $SINGLE = 1;
	public static $MULTIPLE = 2;
	private $mappings = NULL;
	/**
	 * Create a Shape with the give mappings
	 * @param string $subject
	 *   The URI of the subject (if any)
	 * @param array $mappings
	 *   The mappings to be applied to the output
	 * @return Shape
	 */
	function Shape($mappings = array()) {
		$this->mappings = $mappings;
	}
	/**
	 * Returns a PHP object with attributes according to the defined mapping of this Shape.
	 *
	 * @param Bucket $bucket
	 * @param string $s
	 */
	function fill($bucket = NULL, $s) {
		$object = new stdClass ( );

		// Evaluate each mapping
		foreach ( $this->mappings as $attribute => $mapping ) {
			$object->$attribute = $this->evalMapping ( $s, $mapping, $bucket );
		}

		return $object;
	}
	/**
	 * Enter description here...
	 * @param string $s
	 * @param array $mapping
	 * @param Bucket $bucket
	 */
	private function evalMapping($s, $mapping, $bucket) {

		if ($s == NULL || ! is_array ( $mapping ) || ! ($bucket instanceof Bucket))
		return 'uffa';

		$forceSingle = (isset ( $mapping ['flags'] ) && ($mapping ['flags'] & Shape::$SINGLE)) ? true : false;
		$forceMulti = (isset ( $mapping ['flags'] ) && ($mapping ['flags'] & Shape::$MULTIPLE)) ? true : false;

		$p = (isset ( $mapping ['p'] )) ? $mapping ['p'] : NULL;
		$o_type = (isset ( $mapping ['o_type'] )) ? $mapping ['o_type'] : NULL;
		$o_datatype = (isset ( $mapping ['o_datatype'] )) ? $mapping ['o_datatype'] : NULL;
		$o_lang = (isset ( $mapping ['o_lang'] )) ? $mapping ['o_lang'] : NULL;

		$shape = (isset ( $mapping ['shape'] ) && $mapping ['shape'] instanceof Shape) ? $mapping ['shape'] : NULL;

		// If $p is not evaluated, return;
		if ($p == NULL)
		return 'no property';

		$bucketResult = $bucket->filter ( $s, $p, NULL, NULL, $o_type, $o_datatype, $o_lang );

		// Now we fill the object
		$value = NULL;
		if ($forceSingle) {
			$value = $bucketResult->firstValue ();
		} else if ($forceMulti) {
			$value = $bucketResult->values ();
		} else {
			$value = ($bucketResult->size () > 1) ? $bucketResult->values () : $bucketResult->firstValue ();
		}

		// If there is a shape apply it (only to URI objects)
		if ($shape != NULL) {
			if (is_array ( $value )) {
				foreach ( $value as &$v ) {
					if ($bucketResult->isIRI ( $v )) {
						$iri = $v;
						$v = $shape->fill ( $bucket, $iri );
					}
				}
			} else {
				if ($bucketResult->isIRI ( $value )) {
					$iri = $value;
					$v = $shape->fill ( $bucket, $iri );
				}
			}
		}
		return $value;

	}
	/**
	 * Enter description here...
	 *
	 * @return array
	 */
	function getMappings() {
		return $this->mappings;
	}
}
?>