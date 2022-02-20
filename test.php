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
    // TODO: change to .src
    // if --parse-only is set, reference output to compare parser xml output with is .out instead of .in
    public string $testingOutput = ".out";

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
            $this->testingOutput = ".out";
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
    public htmlPrinter $htmlPrinter;

    public function __construct() {
        $this->args = new InputArguments();
        $this->htmlPrinter = new htmlPrinter();
        $this->makeTest();
        $this->htmlPrinter->generateHtmlFile();
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

            $testCase = new Test();
            $testCase->pathToTest = $filePathNoExtName;
            $testCase->testFileName = $fileNoExt;

            // if --int-only is not set
            if (!$this->args->interpretOnly) {
                $command = PHP_ALIAS." ".$this->args->parseScriptPath." < ".$filePathNoExtName.".src > ".$filePathNoExtName.".pout";
                // $string, $code are return results from parse.php
                exec($command, $parserOutput, $parserCode);
                $testCase->parserMessage = $parserOutput;


                if ($this->args->parseOnly) {
                    $rcFile = fopen($filePathNoExtName.".rc", "r");
                    $rcCode = trim(fgets($rcFile));
                    fclose($rcFile);

                    $testCase->expectedCode = $rcCode;
                    $testCase->parserCode = $parserCode;

                    if ($rcCode != 0) {
                        if ($parserCode == $rcCode) {
                            print $filePathNoExtName."  RC code matching ✅\n";
                        } else {
                            print $filePathNoExtName."  Invalid rc code! ❌ Have: ".$parserCode." and should be: ".$rcCode."\n";
                        }
                    }
                    if ($rcCode == 0 && $parserCode == 0) {
                        $command = "java -jar ".$this->args->jexamPath." ".$filePathNoExtName.$this->args->testingOutput." ".$filePathNoExtName.".pout";
                        exec($command, $output, $compareCode);
                        if ($compareCode == 0) {
                            print $filePathNoExtName."  XML matching ✅\n";
                        } else {
                            print $filePathNoExtName."  Invalid XML ❌\n";
                        }
                    }
                }

                // clean .pout files if --noclean is not set
                if ($this->args->cleanFiles) {
                    $command = "rm -f ".$filePath.$fileNoExt.".pout";
                    exec($command, $out, $code);
                    // print $command."     ".$code."\n";
                }

                $this->htmlPrinter->addTest($testCase);
            }

        }
    }

    // add missing .in, .out, .rc if absent
    public function addMissingFiles(string $filePathNoExtName) {
        // if .rc file does not exist, create one with resultCode = 0
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

class htmlPrinter
{
    public string $templateBegin = "<html lang=\"en\">
                                        <head>
                                            <title>
                                                IPP Test Report
                                            </title>
                                            <style>
                                                .test-result-table {
                                                    border: 1px solid black;
                                                    width: auto;
                                                }
                                                .test-result-table-header-cell {
                                                    border-bottom: 1px solid black;
                                                    background-color: silver;
                                                }
                                                .test-result-step-command-cell {
                                                    border-bottom: 1px solid gray;
                                                }
                                                .test-result-step-description-cell {
                                                    border-bottom: 1px solid gray;
                                                }
                                                .test-result-step-result-cell-ok {
                                        
                                                    border-bottom: 1px solid gray;
                                                    background-color: green;
                                                }
                                                .test-result-step-result-cell-failure {
                                                    border-bottom: 1px solid gray;
                                                    background-color: red;
                                                }
                                                .test-result-step-result-cell-notperformed {
                                                    border-bottom: 1px solid gray;
                                                    background-color: white;
                                                }
                                                .test-result-describe-cell {
                                                    background-color: tan;
                                                    font-style: italic;
                                                }
                                                .test-cast-status-box-ok {
                                                    border: 1px solid black;
                                                    float: left;
                                                    margin-right: 10px;
                                                    width: 45px;
                                                    height: 25px;
                                                    background-color: green;
                                                }
                                            </style>
                                        </head>
                                        <body>
                                        <h1 class=\"test-results-header\">
                                            Test Report
                                        </h1>";
    public string $tableBegin = "<table class=\"test-result-table\">
                                    <thead>
                                    <tr>
                                        <td class=\"test-result-table-header-cell\">
                                            Test File Name
                                        </td>
                                        <td class=\"test-result-table-header-cell\">
                                            Path to test file
                                        </td>
                                        <td class=\"test-result-table-header-cell\">
                                            Result
                                        </td>
                                        <td class=\"test-result-table-header-cell\">
                                            Result code from parser
                                        </td>
                                        <td class=\"test-result-table-header-cell\">
                                            Error message from parser
                                        </td>
                                        <td class=\"test-result-table-header-cell\">
                                            Result code from interpret
                                        </td>
                                        <td class=\"test-result-table-header-cell\">
                                            Error message from interpret
                                        </td>
                                    </tr>
                                    </thead>
                                    <tbody>";
    public string $templateEnd = "</tbody></table></body></html>";
    public string $tests = "";
    public string $testsSummary = "";
    public int $totalTestCount = 0;
    public int $successTestCount = 0;

    public function addTest(Test $test) {
        $this->totalTestCount++;

        $this->tests .= "<tr class=\"test-result-step-row test-result-step-row-altone\">
                            <td class=\"test-result-step-command-cell\">
                                ".$test->testFileName."
                            </td>
                            <td class=\"test-result-step-description-cell\">
                                ".$test->pathToTest."
                            </td>
                            <td class=\"".(($test->parserCode == $test->expectedCode)?"test-result-step-result-cell-ok":"test-result-step-result-cell-failure")."\">
                                ".(($test->parserCode == $test->expectedCode) ? "OK" : "ERR")."
                            </td>
                            <td class=\"".(($test->parserCode == $test->expectedCode)?"test-result-step-result-cell-ok":"test-result-step-result-cell-failure")."\">
                                ".$test->parserCode."
                            </td>
                            <td class=\"test-result-step-result-cell-ok\">
                                ".(implode('', $test->parserMessage))."
                            </td>
                            <td class=\"test-result-step-result-cell-ok\">
                                ".$test->interpretCode."
                            </td>
                            <td class=\"test-result-step-result-cell-ok\">
                                ".(implode('', $test->interpretMessage))."
                            </td>
                        </tr>";
    }

    public function generateSummary() {
        // TODO: svg graph?
        $this->testsSummary .= "<table class=\"test-result-table\">
                                    <thead>
                                    <tr>
                                        <td class=\"test-result-table-header-cell\">
                                            Total number of tests
                                        </td>
                                        <td class=\"test-result-table-header-cell\">
                                            Successful tests
                                        </td>
                                        <td class=\"test-result-table-header-cell\">
                                            Failed tests
                                        </td>
                                        <td class=\"test-result-table-header-cell\">
                                            Percentage successful
                                        </td>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr class=\"test-result-step-row test-result-step-row-altone\">
                                        <td class=\"test-result-step-command-cell\">
                                            ".$this->totalTestCount."
                                        </td>
                                        <td class=\"test-result-step-description-cell\">
                                            ".$this->successTestCount."
                                        </td>
                                        <td class=\"test-result-step-result-cell-ok\">
                                            ".$this->totalTestCount-$this->successTestCount."
                                        </td>
                                        <td class=\"test-result-step-result-cell-ok\">
                                            ".$this->successTestCount/$this->totalTestCount."
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>";
    }

    public function generateHtmlFile() {
        $htmlContent = $this->templateBegin.$this->testsSummary.$this->tableBegin.$this->tests.$this->templateEnd;
        $outputHtmlFile = fopen("testResult.html", "w");
        fwrite($outputHtmlFile, $htmlContent);
        fclose($outputHtmlFile);
    }
}

class Test {
    public string $testFileName = "";
    public string $pathToTest = "";
    public int $parserCode;
    public int $expectedCode;
    public array $parserMessage = array();
    public int $interpretCode = 0;
    public array $interpretMessage = array();
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


