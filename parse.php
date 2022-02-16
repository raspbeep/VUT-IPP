<?php


ini_set('display_errors','STDERR');



const ERROR_PARAM = -2;
const ERROR_EMPTY = -3;
$myFile = fopen("prog.txt", "r");

parseInputArguments();

$doc = null;
try {
    xmlInit($doc);
} catch (Exception $e) {
    print $e->getMessage();
}

parseLines();


function parseLines() {

    global $myFile;
    $line = fgets($myFile);

    // when input is empty, exits with ERROR_EMPTY
    if(!$line) {
        handleError(ERROR_EMPTY);
    }


    preg_match("^\.(IPPcode22)(IPPcode22)\n$", $line, $matches);
    if($matches == NULL) {
        print "no matches bruh";
    } else {
        print $matches;
    }

}


/**
 * @throws Exception
 */
function xmlInit(&$doc) {
    $doc = new DOMDocument("1.0", "UTF-8");
    $doc->formatOutput = true;
    try{
        $root_elem = $doc->createElement("program");
        $root_elem = $doc->appendChild($root_elem);

        $lang_attr = $doc->createAttribute("language");
        $lang_attr->value = "IPPcode22";

        $root_elem->appendChild($lang_attr);

    } catch (Exception $e) {
        throw new Exception('Unable to create element PROGRAM');
    }
}

// parses input arguments, only acceptable is --help
function parseInputArguments(): int {
    global $argc;
    $argOptions = array("help");
    $givenParams = getopt(null, $argOptions);

    print $argc;
    if($argc == 2 && isset($args["help"])) {
        // TODO: complete a help message
        fputs(STDOUT, "Arbitrary help message.\n");
        return 0;
    } else if($argc < 2) {
        handleError(ERROR_PARAM);
    }
    return 0;
}

// handles error, prints error message and returns the errno back
function handleError(int $errno) {
    switch ($errno) {
        case ERROR_PARAM:
            fputs(STDERR, "Invalid number of input arguments.\n");
            die(ERROR_PARAM);
        case ERROR_EMPTY:
            fputs(STDERR, "Empty file, nothing to read.\n");
            exit(ERROR_EMPTY);
    }
}


