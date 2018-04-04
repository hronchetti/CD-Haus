<?php

class Webpage {

    protected $head;
    protected $body;
    protected $footer;
    public $title;
    public $css;
    public $context;

    /** Methods:
     * A constructor
     * The constructor for the class should accept at least two arguments: the title of the page, and an array of
     * css filenames that it will use to create the appropriate code in the head section to link to those stylesheets
     * The constructor should create the head section and footer section and give a default value for the body section
     */

    public function __construct($title = null, array $css = null) {
        $this->head = $this->makeHeader($title, $css);
        $this->footer = $this->makeFooter();
    }

    protected function makeHeader ($title, $css) {
        $cssFileList = '';
        if (is_array($css)) {
            foreach ($css as $filename) {
                $cssFileList .= "<link rel='stylesheet' href='$filename'/>";
            }
        }
        $head = <<<HEAD
<!DOCTYPE HTML>
<html lang="en">
<head>
    $cssFileList
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>$title</title>
</head>
<body>\n
HEAD;
        return $head;

    }
    /** addToBody
     * a method called 'addToBody' that will add text to the body attribute of the Webpage.  See the client to see how this method
     * will be used - it'll give you a clue as to how to implement it.
     */

    public function addToBody ($text) {
        $this->body .= $text;
    }

    protected function makeFooter () {
        $footer = <<<FOOTER
    \n\t<footer>
    </footer>
</body>
</html>
FOOTER;

        return $footer;

    }
    /** getPage
     * a getPage method which has as a return value the various sections, head, body and footer, of the webpage concatenated together.
     */
    public function getPage () {
        return $this->head . $this->body . $this->footer;
    }
}

