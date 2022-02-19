<?php

use JetBrains\PhpStorm\NoReturn;
// directory error
const ERROR_DIRECTORY = 41;
// directory error parsing parameter --directory
const ERROR_DIR_DIRECTORY = -3;
// jexampath path give in param --jexamscript does not exist
const ERROR_JEXAMPATH = -4;
// parse script path give in param --parse-script does not exist
const ERROR_PARSE_PATH = -5;
// testing files' directory given in param --directory does not exist
const ERROR_TEST_PATH = -6;

// for local testing
const PHP_ALIAS = "php";

class InputArguments {

    public string $testDirectory = "./test/";
    public string $parseScriptPath = "./parse.php";
    public string $jexamPath = "./jexamxml/jexamxml.jar";
    public bool $recursion = false;
    // TODO: change to false
    public bool $parseOnly = true;
    public bool $interpretOnly = false;
    // TODO: change to true
    public bool $cleanFiles = false;

    #[NoReturn] public function __construct() {
        $argOptions = array("help", "directory:", "recursive", "parse-script:", "int-script:", "parse-only", "int-only", "jexampath:", "noclean");
        $givenParams = getopt("", $argOptions);

        global $argc;

        if (array_key_exists("help", $givenParams)) {
            if ($argc == 2) {
                fputs(STDOUT, "Arbitrary help message.\n");
                exit(0);
            } else {
                handleError(ERROR_PARAM);
            }
        }

        if (array_key_exists("directory", $givenParams)) {
            $directory = $givenParams["directory"];
            if ($directory[strlen($directory) - 1] != "/") {
                handleError(ERROR_DIR_DIRECTORY);
            }
            if (!file_exists($directory)) {
                handleError(ERROR_TEST_PATH);
            }

            $this->testDirectory = $givenParams["directory"];
        }

        if (array_key_exists("recursive", $givenParams)) {
            $this->recursion = true;
        }

        if (array_key_exists("parse-script", $givenParams)) {
            $parseScriptPath = $givenParams["parse-script"];
            if (!file_exists($parseScriptPath)) {
                handleError(ERROR_PARSE_PATH);
            }
            $this->parseScriptPath = $givenParams["parse-script"];
        }

        if (array_key_exists("parse-only", $givenParams)) {
            $this->parseOnly = true;
        }

        if (array_key_exists("int-only", $givenParams)) {
            $this->interpretOnly = true;
        }

        if (array_key_exists("jexampath", $givenParams)) {
            $jexamPath = $givenParams["jexamPath"];
            if (!file_exists($jexamPath)) {
                handleError(ERROR_JEXAMPATH);
            }
            $this->jexamPath = $givenParams["jexampath"];
        }

        if (array_key_exists("noclean", $givenParams)) {
            $this->cleanFiles = false;
        }

        return 0;
    }
}

class Tester {
    private InputArguments $args;
    private array $foldersToTest;
    public string $resultHtmlTestList;

    public function __construct() {
        $this->args = new InputArguments();
    }

    public function scanFolders () {

    }

    public function makeTest() {
        $testFiles = scandir($this->args->testDirectory);

        $currentWorkDir = $this->args->testDirectory;

        foreach ($testFiles as $file) {
            // remove .DS_Store
            if (!is_dir($file) && $file != ".DS_Store" && str_ends_with($file, ".src")) {
                $fileNoExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $file);
                $command = PHP_ALIAS." ".$this->args->parseScriptPath." < ".$this->args->testDirectory.$file." > ".$currentWorkDir.$fileNoExt.".pout";
                exec($command);

                $command = "java -jar ".$this->args->jexamPath." ".$this->args->testDirectory.$fileNoExt.".in ".$this->args->testDirectory.$fileNoExt.".pout";
                print exec($command);
                print "\n";
            }
        }
    }


}

function handleError(int $errno) {
    switch ($errno) {
        case ERROR_DIR_DIRECTORY:
            fputs(STDERR, "Directory given in parameter --directory must end with '\'.\n");
            exit(ERROR_DIRECTORY);
        case ERROR_TEST_PATH:
            fputs(STDERR, "Invalid directory of testing files given in parameter --directory.\n");
            exit(ERROR_DIRECTORY);
        case ERROR_JEXAMPATH:
            fputs(STDERR, "Invalid directory given in parameter --jexampath.\n");
            exit(ERROR_DIRECTORY);
        case ERROR_PARSE_PATH:
            fputs(STDERR, "Invalid directory given in parameter --jexampath.\n");
            exit(ERROR_DIRECTORY);
    }
}



$tester = new Tester();
$tester->makeTest();

