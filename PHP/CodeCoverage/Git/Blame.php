<?php

class PHP_CodeCoverage_Git_Blame
{
    public $rev;
    public $author;
    public $lineNo;
    public $linesLeftInBlameGroup;
    public $isStartGroupLine;

    /**
     * Returns an array of line number to blame info
     *
     * @param $file
     * @param $lines
     * @throws PHP_CodeCoverage_Exception
     * @return array of PHP_CodeCoverage_Git_Blame
     */
    public static function getBlameInfo($file)
    {
        $blameOutput = self::getBlameOutput($file);
        $ret = array();
        $i = 0;
        $linesLeftInBlameGroup = 1;
        $blameLine = current($blameOutput);
        while (false !== $blameLine) {
            $i++;
            $linesLeftInBlameGroup--;
            $isStartGroupLine = !$linesLeftInBlameGroup;
            if ($isStartGroupLine) {
                if (!preg_match('/^(.{40}) (\d+) (\d+) (\d+)$/', $blameLine, $matches)) {
                    throw new PHP_CodeCoverage_Exception(
                        "Unexpected output from git blame for file line $i: " . $blameLine);
                }
                $linesLeftInBlameGroup = $matches[4];
            } else {
                if (!preg_match('/^(.{40}) (\d+) (\d+)$/', $blameLine, $matches)) {
                    throw new PHP_CodeCoverage_Exception(
                        "Unexpected output from git blame for file line $i: " . $blameLine);
                }
            }
            $sha = $matches[1];
            if ($i != $matches[3]) {
                throw new PHP_CodeCoverage_Exception(
                    "Unexpected output from git blame (expected line $i): " . $blameLine);
            }

            $author = '';
            do
            {
                $blameData = next($blameOutput);
                if (0 === strpos($blameData, 'author ')) {
                    $author = substr($blameData, strlen('author '));
                }
            } while (
                false !== $blameData && // EOF
                0 !== strpos($blameData, "\t") && // End of line info
                '' !== $blameData); // End of line info which has been trim()ed by exec

            $info = new PHP_CodeCoverage_Git_Blame();
            $info->rev = $sha;
            $info->author = $author;
            $info->lineNo = $i;
            $info->linesLeftInBlameGroup = $linesLeftInBlameGroup;
            $info->isStartGroupLine = $isStartGroupLine;
            $ret[$i] = $info;

            $blameLine = next($blameOutput);
        }
        return $ret;
    }

    /**
     * Runs "git blame" on the given file and returns the output.
     *
     * @param $file
     * @return array of strings - the "porcelain" output from "git blame"
     * @throws PHP_CodeCoverage_Exception
     */
    protected static function getBlameOutput($file)
    {
        if (strpos(`uname`, 'CYGWIN') !== false) {
            // Cygwin git does not like absolute Windows filenames
            $file = `cygpath "$file"`;
        }
        $file = escapeshellarg($file);
        $cmd = "git blame -p $file";
        exec($cmd, $blameOutput, $retval);
        if ($retval != 0) {
            throw new PHP_CodeCoverage_Exception(
                "Error running git blame. Perhaps you do not have git installed, or perhaps the " .
                    "project is not in git source control. Please either fix this error or don't use " .
                    "the 'excludeCommits' option.\nCommand: $cmd\nOutput: $blameOutput");
        }
        return $blameOutput;
    }

}