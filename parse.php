<?php


ini_set('display_errors','STDERR');

/* PREDEFINED ERROR CODES */
// missing parameter, invalid parameter combination
const ERROR_PARAM = 10;
// error opening input files(non-existent, insufficient privileges)
const ERROR_FILE_IN = 11;
// error opening output files(insufficient privileges, write error)
const ERROR_FILE_OUT = 12;
// internal error
const ERROR_INT = 99;
// missing or invalid header in input file
const ERROR_HEADER = 21;
// unknown or incorrect opcode in input file
const ERROR_CODE = 22;
// lexical or syntactical error in source file
const ERROR_PARSE = 23;


/* IMPLEMENTATION DEPENDENT ERROR CODES */

const ERROR_EMPTY = -3;

// strips line from comment
function lineStripComment($line): string {
    $result = strpos($line, '#');
    if($result != false){
        return substr($line, 0, $result);
    }
    return $line;
}

// clears line from comment, trims
function lineClearAndSplit($line): array {
    return preg_split('/\s+/', lineStripComment(trim($line, "\n")));
}

// checks if line is empty or a comment(begins with '#')
function lineIsEmpty($line): bool {
    if ($line == '\n' || str_starts_with($line, '#')) {
        return true;
    }
    return false;
}

// check for header correctness
function processHeader() {
    // TODO: STDIN
    global $myFile;
    $line = trim(fgets($myFile), " \n");

    // die if header is not present
    if(lineIsEmpty($line)) {
        handleError(ERROR_HEADER);
    }

    // check for regex match, die if not found
    if(!preg_match("/^\.IPPcode22$/", $line, $matches)) {
        handleError(ERROR_HEADER);
    }
}

function processInstruction($line) {
    $line[0] = strtoupper($line[0]);

    switch($line[0]) {
        /* FRAMES, FUNCTION CALLS */
        case "MOVE":
        case "CREATEFRAME":
        case "PUSHFRAME":
        case "POPFRAME":
        case "DEFVAR":
        case "CALL":
        case "RETURN":

        /* STACK */
        case "PUSHS":
        case "POPS":

        /* ARITHMETIC, RELATIONAL, BOOLEAN, CONVERSION*/
        case "ADD":
        case "SUB":
        case "MUL":
        case "IDIV":
        case "LT" || "GT" || "EQ":
        case "AND" || "OR" || "NOT":
        case "INT2CHAR":
        case "STRI2INT":

        /* INPUT, OUTPUT */
        case "CONCAT":
        case "STRLEN":
        case "GETCHAR":
        case "SETCHAR":

        /* TYPES */
        case "TYPE":

        // TODO: preklad
        /* FLOW */
        case "LABEL":
        case "JUMP":
        case "JUMPIFEQ":
        case "JUMPIFNEQ":
        case "EXIT":

        // TODO: preklad
        /* LADENIE */
        case "DPRINT":
        case "BREAK":
    }
}


function parseLines() {
    // TODO: STDIN
    global $myFile;

    // parse first line
    processHeader();


    // initialising line index for output xml order attribute
    $lineCounter = 1;

    // TODO: STDIN
    // parsing all lines
    while($line = fgets($myFile)) {

        // skip if line is empty or comment
        if (lineIsEmpty($line)) continue;

        $line = lineClearAndSplit($line);


        $lineCounter++;
    }


}


/**
 * @throws Exception
 */
function xmlInit(&$doc) {
    $doc = new DOMDocument("1.0", "UTF-8");
    $doc->formatOutput = true;
    try{
        // create root element in xml
        $root_elem = $doc->createElement("program");
        $root_elem = $doc->appendChild($root_elem);
        // create child of root element
        $lang_attr = $doc->createAttribute("language");
        $lang_attr->value = "IPPcode22";

        $root_elem->appendChild($lang_attr);

    } catch (Exception $e) {
        throw new Exception('Unable to create element PROGRAM');
    }
}

// parses input arguments, only acceptable is --help
function parseInputArguments(): int {

    $argOptions = array("help");
    $givenParams = getopt("", $argOptions);

    global $argc;
    if(array_key_exists("help", $givenParams)) {
        if ($argc == 2) {
            // TODO: complete a help message
            fputs(STDOUT, "Arbitrary help message.\n");
            return 0;
        }
        handleError(ERROR_PARAM);
    }/* else if($argc > 2) {
        handleError(ERROR_PARAM);
    }*/
    return 0;
}

// handles error, prints error message and returns the errno back
function handleError(int $errno) {
    switch ($errno) {
        case ERROR_PARAM:
            // TODO: suggest using --help
            fputs(STDERR, "Invalid number of input arguments. Use argument --help for use \n");
            exit(ERROR_PARAM);
        case ERROR_EMPTY:
            fputs(STDERR, "Empty file, nothing to read.\n");
            exit(ERROR_EMPTY);
        case ERROR_HEADER:
            fputs(STDERR, "Invalid header line.\n");
            exit(ERROR_HEADER);
    }
}


// TODO: STDIN
$myFile = fopen("prog.txt", "r");

parseInputArguments();

// global variable of output document
$doc = null;

try {
    xmlInit($doc);
} catch (Exception $e) {
    print $e->getMessage();
}

parseLines();


