<?php
/*
 * Copyright (C) 2015 Marcel Bollmann <bollmann@linguistics.rub.de>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 * the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 * FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/
?>
<?php
/** @file Lemmatizer.php
 * Perl-based lemmatizer similar to what RFTagger uses.
 *
 * @author Marcel Bollmann
 * @date March 2014
 */
require_once ("AutomaticAnnotator.php");

/** Annotates lemma tagsets by wrapping an external lemmatization script.
 *
 * Has options to lowercase all tokens, use POS information in addition to the
 * token, or use the normalized wordform instead of the token.
 */
class Lemmatizer extends AutomaticAnnotator {
    private $tmpfiles = array();
    private $dictionary = array();
    private $lowercase_all = false;
    private $use_pos = true;
    private $use_norm = false;
    private $filter_unknown = true;
    private $lines = null;
    // lemmatizer return value if lemma could not be found:
    private $unknown_lemma = "<unknown>";

    public function __construct($prfx, $opts) {
        parent::__construct($prfx, $opts);
        if (!array_key_exists("par", $this->options)) {
            $this->options["par"] = $this->prefix . "Lemmatizer.par";
        }
        if (array_key_exists("use_pos", $this->options)) {
            $this->use_pos = ($this->options["use_pos"] == 1) ? true : false;
        }
        if (array_key_exists("use_norm", $this->options)) {
            $this->use_norm = (bool)$this->options["use_norm"];
        }
        if (array_key_exists("lowercase_all", $this->options)) {
            $this->lowercase_all = (bool)$this->options["lowercase_all"];
        }
        if (array_key_exists("filter_unknown", $this->options)) {
            $this->filter_unknown = (bool)$this->options["filter_unknown"];
        }
    }

    public function __destruct() {
        foreach ($this->tmpfiles as $tmpfile) {
            unlink($tmpfile);
        }
    }

    /** Writes an input file for the lemmatization script.
     *
     * @param array $tokens Tokens to write
     *
     * @return Name of the newly created file
     */
    private function writeLemmatizerInput($tokens) {
        $filename = tempnam(sys_get_temp_dir(), "cora_lem");
        $this->tmpfiles[] = $filename;
        $handle = fopen($filename, "w");
        foreach ($tokens as $tok) {
            fwrite($handle, $tok['ascii']);
            if ($this->use_pos) {
                fwrite($handle, "\t" . $tok['tags']['pos']);
            }
            fwrite($handle, "\n");
        }
        fclose($handle);
        return $filename;
    }

    private function lowercaseAscii(&$tokens) {
        foreach ($tokens as & $tok) {
            if (isset($tok['ascii'])) {
                $tok['ascii'] = mb_strtolower($tok['ascii'], 'UTF-8');
            }
        }
        unset($tok);
    }

    /** Converts lemmatizer output to an array for saving it back to CorA.
     *
     * @param array $mod The original input token
     * @param string $line Output line from lemmatizer
     *
     * @return An array element suitable for DBInterface::saveLines()
     */
    private function makeAnnotationArray($mod, $line) {
        if ($this->filter_unknown && ($line == $this->unknown_lemma)) {
            return array();
        }
        return array("id" => $mod['id'], "ascii" => $mod['ascii'], "anno_lemma" => $line);
    }

    public function annotate($tokens) {
        if ($this->use_norm) {
            $tokens = array_map(array($this, 'mapNormToAscii'), $tokens);
        }
        if ($this->lowercase_all) {
            $this->lowercaseAscii($tokens);
        }
        $tmpfname = $this->writeLemmatizerInput($tokens);
        // call lemmatizer
        $output = array();
        $retval = 0;
        $cmd = implode(" ", array($this->options["bin"], $this->options["par"], $tmpfname));
        exec($cmd, $output, $retval);
        if ($retval) {
            error_log("CorA: Lemmatizer.php: Lemmatizer returned status code {$retval}; call was: {$cmd}");
            throw new Exception("Lemmatizer returned status code {$retval}."); //$LOCALE
        }
        return array_map(array($this, 'makeAnnotationArray'), $tokens, $output);
    }

    public function startTrain() {
        $this->lines = array();
    }

    public function bufferTrain($tokens) {
        if ($this->use_norm) {
            $tokens = array_map(array($this, 'mapNormToAscii'), $tokens);
        }
        if ($this->lowercase_all) {
            $this->lowercaseAscii($tokens);
        }
        foreach ($tokens as $tok) {
            if (!$tok['verified'] || !isset($tok['tags']['pos'])
                || empty($tok['tags']['pos']) || !isset($tok['tags']['lemma'])
                || empty($tok['tags']['lemma'])) {
                continue;
            }
            $newline = $tok['ascii'] . "\t";
            if ($this->use_pos) {
                $newline = $newline . $tok['tags']['pos'] . "\t";
            }
            $newline = $newline . $tok['tags']['lemma'];
            $this->lines[] = $newline;
        }
        $this->lines = array_unique($this->lines);
    }

    public function performTrain() {
        $handle = fopen($this->options['par'], "w");
        if (!$handle) {
            throw new Exception("Couldn't open parameter file: " . $this->options['par']); //$LOCALE
        }
        foreach ($this->lines as $line) {
            fwrite($handle, $line);
            fwrite($handle, "\n");
        }
        fclose($handle);
        $this->lines = null;
    }
}
?>
