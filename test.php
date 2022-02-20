<?php

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

const ERROR_PARSER = -7;

const ERROR_INTERPRET = -8;


class InputArguments {

    public string $testDirectory = "./test/";
    public string $parseScriptPath = "./parse.php";
    public string $jexamPath = "./jexamxml/jexamxml.jar";
    public bool $recursion = true;
    // TODO: change to false
    public bool $parseOnly = true;
    public bool $interpretOnly = false;
    // TODO: change to true
    public bool $cleanFiles = false;
    // TODO: check argument collisions
    public string $parserOutput = ".out";

    public function __construct() {
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
    public string $resultHtmlTestList;

    public function __construct() {
        $this->args = new InputArguments();
    }

    public function makeTest() {
        // Construct the iterator
        $it = new RecursiveDirectoryIterator($this->args->testDirectory);

        // Loop through files
        foreach(new RecursiveIteratorIterator($it) as $file) {
            $fileName = $file->getFilename();
            // skip current and parent directory to avoid looping
            if ($fileName == "." || $fileName == "..") continue;

            $filePath = $file->getPath()."/";
            $fileNoExt = preg_replace('/\\.[^.\\s]{2,4}$/', '', $fileName);
            $filePathNoExtName = $filePath.$fileNoExt;

            // iterate only through .src files
            if (!$this->args->interpretOnly) {
                if (!str_ends_with($fileName, ".src")) continue;
            }

            // add missing testing files if absent
            $this->addMissingFiles($filePathNoExtName);

            // if --int-only is not set
            if (!$this->args->interpretOnly) {
                $command = PHP_ALIAS." ".$this->args->parseScriptPath." < ".$filePathNoExtName.".src > ".$filePathNoExtName.".pout";
                // $string, $code are return results from parse.php
                exec($command, $string, $code);
                // $resultCodeFile = fopen($filePath.$fileNoExt.".rc", "r");
                // $resultCode = trim(fgets($resultCodeFile), " \n");

                if ($this->args->parseOnly) {
                    $rcFile = fopen($filePathNoExtName.".rc", "r");
                    $rcCode = trim(fgets($rcFile));
                    fclose($rcFile);

                    if ($rcCode != 0) {
                        if ($code == $rcCode) {
                            print $filePathNoExtName."  RC code matching ✅\n";
                        } else {
                            print $filePathNoExtName."  Invalid rc code! ❌ Have: ".$code." and should be: ".$rcCode."\n";
                        }
                        continue;
                    }

                    $command = "java -jar ".$this->args->jexamPath." ".$filePathNoExtName.$this->args->parserOutput." ".$filePathNoExtName.".pout";
                    exec($command, $output, $compareCode);
                    if ($compareCode == 0) {
                        print $filePathNoExtName."  XML matching ✅\n";
                    } else {
                        print $filePathNoExtName."  Invalid XML ❌\n";
                    }
                }

//                // clean .pout files if --noclean is not set
//                if ($this->args->cleanFiles) {
//                    $command = "rm -f ".$filePath.$fileNoExt.".pout";
//                    exec($command, $out, $code);
//                    // print $command."     ".$code."\n";
//                }
            }

        }
    }

    public function addMissingFiles(string $filePathNoExtName) {
        // if .rc file does not exist, create one with resultCode=0
        if (!file_exists($filePathNoExtName.".rc")) {
            file_put_contents($filePathNoExtName.".rc", "0");
        }
        // if .in file does not exist, create an empty one
        if (!file_exists($filePathNoExtName.".in")) {
            file_put_contents($filePathNoExtName.".in", "");
        }
        // if .out file does not exist, create an empty one
        if (!file_exists($filePathNoExtName.".out")) {
            file_put_contents($filePathNoExtName.".out", "");
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

