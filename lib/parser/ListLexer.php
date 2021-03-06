<?php

require_once('lexer.php');

class ListLexer extends Lexer {

    public $line = 1;
    public $col = 1;
    

    const NAME      = 2;
    const COMMA     = 3;
    const COLON    = 4;
    const EQUAL    = 5;
    const TAG_START_OPENING = 6; // <!---
    const TAG_DOUBLE_CLOSING= 7; // --->
    const TAG_DOUBLE_END_OPENING= 8; // <!---/
    const TAG_SIMPLE_CLOSING= 9; // /--->
    const TPL_ATTRIBUTE= 10;
    const PHP_OPENING= 11;
    const PHP_CLOSING= 12;
    const DOUBLE_QUOTE= 13;
    const SIMPLE_QUOTE= 14;
    const TEMPLATE_ATTRIBUTE= 15;
    const TEMPLATE_CONTENT_ATTRIBUTE= 16;
    
    const HTML_START_OPENING = 17; // <
    const HTML_DOUBLE_CLOSING= 18; // >
    const HTML_DOUBLE_END_OPENING= 19; // </
    const HTML_SIMPLE_CLOSING= 20; //  />

    const MINUS= 21; //  -

    const HTML_COMMENT_OPENING= 22; //  <!--
    const HTML_COMMENT_CLOSING= 23; //  -->
    const NOT_RECOGNIZED= 24; //Not a letter, not something above
    
    static $tokenNames = array("n/a", "<EOF>",
                               "NAME", "COMMA",
                               "COLON", "EQUAL",
                               "TAG_START_OPENING",
                               "TAG_DOUBLE_CLOSING",
                               "TAG_DOUBLE_END_OPENING",
                               "TAG_SIMPLE_CLOSING",
                               "TPL_ATTRIBUTE",
                               "PHP_OPENING",
                               "PHP_CLOSING",
                               "DOUBLE_QUOTE",
                               "SIMPLE_QUOTE",
                               "TEMPLATE_ATTRIBUTE",
                               "TEMPLATE_CONTENT_ATTRIBUTE",
                                "HTML_START_OPENING",
                                "HTML_DOUBLE_CLOSING",
                                "HTML_DOUBLE_END_OPENING",
                                "HTML_SIMPLE_CLOSING",
                                "MINUS",
                                "HTML_COMMENT_OPENING",
                                "HTML_COMMENT_CLOSING",
                                "NOT_RECOGNIZED",
                                );
    
    public function getTokenName($x) {
        return ListLexer::$tokenNames[$x];
    }

    public function get_info_context(){
        return array($this->line, $this->col, $this->p);
    }

    public function ListLexer($input) {
        parent::__construct($input);
    }

    public function getContent($from, $to){

        return substr($this->input, $from, ($to-$from <=0)? 0 : $to-$from);
    }
    
    public function isLETTER() {
        return $this->c >= 'a' &&
               $this->c <= 'z' ||
               $this->c >= 'A' &&
               $this->c <= 'Z' ||
               $this->c == '_' ||
               $this->c >= '0' &&
               $this->c <= '9'

                ;
    }

    public function consume(){

        $r = parent::consume();
        if($this->c === "\n"){
            $this->line++;
            $this->col = 0;
        }else{
            $this->col++;
        }
        return $r;
    }


    function getContext(){
        return array(
            'c' => $this->c,
            'p' => $this->p,
            'line' => $this->line,
            'col' => $this->col
        );
    }

    function setContext($context){
        $this->c = $context['c'];
        $this->p = $context['p'];
        $this->line = $context['line'];
        $this->col = $context['col'];
    }

    private $frozen = array();
    function freeze(){
        $this->frozen['c'] = $this->c;
        $this->frozen['p'] = $this->p;
        $this->frozen['line'] = $this->line;
        $this->frozen['col'] = $this->col;
    }

    function rollback(){
        $this->c = $this->frozen['c'];
        $this->p = $this->frozen['p'];
        $this->line = $this->frozen['line'];
        $this->col = $this->frozen['col'];

    }

    public function nextToken() {
        while ( $this->c != self::EOF ) {
            switch ( $this->c ) {
                case ' ' :  case "\t": case "\n": case "\r": $this->WS();
                    
                break;
                case '<':
                    if($token = $this->TAG_DOUBLE_END_OPENING()){
                       return $token;
                    }
                    elseif($token = $this->TAG_START_OPENING()){
                       return $token;
                    }
                    elseif($token = $this->PHP_OPENING()){
                       return $token;
                    }
                    elseif($token = $this->HTML_DOUBLE_END_OPENING()){
                       return $token;
                    }else{
                        $this->consume();
                        return new Token(self::HTML_START_OPENING, '<');
                    }
                break;
                case '>':
                    $this->consume();
                    return new Token(self::HTML_DOUBLE_CLOSING, '>');
                break;
                case '/':
                    if($token = $this->TAG_SIMPLE_CLOSING()){
                       return $token;
                    }
                    elseif($token = $this->HTML_SIMPLE_CLOSING()){
                       return $token;
                    }
                    $this->consume();
                break;
                case ':':
                    $this->consume();
                    return new Token(self::COLON, ':');
                break;
                case '"':
                    $this->consume();
                    return new Token(self::DOUBLE_QUOTE, '"');
                case '=':
                    $this->consume();
                    return new Token(self::EQUAL, '=');
                break;
                case "'":
                    $this->consume();
                    return new Token(self::SIMPLE_QUOTE, "'");
                break;
                case "-":
                    if($token = $this->TAG_DOUBLE_CLOSING()){
                       return $token;
                    }else{
                        $this->consume();
                        return new Token(self::MINUS, "-");
                    }
                    $this->consume();

                break;
                case '?':
                    if($token = $this->PHP_CLOSING()){
                       return $token;
                    }
                    $this->consume();
                break;
                case $this->isLETTER():
                    if($token = $this->TEMPLATE_CONTENT_ATTRIBUTE()){
                        return $token;
                    }elseif($token = $this->TEMPLATE_ATTRIBUTE()){
                        return $token;
                    }elseif($token = $this->NAME()){
                       return $token;
                    }
                    $this->consume();
                break;
                default:
                    $this->consume();
                    return new Token(self::NOT_RECOGNIZED, $this->c);
            }
        }
        return new Token(self::EOF_TYPE,"<EOF>");
    }


    /** ---> **/
    public function TAG_DOUBLE_CLOSING(){
        $ar = array('-', '-', '-', '>');
        $this->freeze();

        foreach($ar as $actual_s){
            if($actual_s !== $this->c){
                $this->rollback();
                return;
            }
            $this->consume();
        }

        return new Token(self::TAG_DOUBLE_CLOSING, '', array(
            'line' => $this->frozen['line'],
            'col' => $this->frozen['col'],
            'char' => $this->frozen['p'],
        ));
    }


    /** <!---/ **/
    public function TAG_DOUBLE_END_OPENING(){
        $ar = array('<', '!', '-', '-', '-', '/');
        $this->freeze();

        foreach($ar as $actual_s){
            if($actual_s !== $this->c){
                $this->rollback();
                return;
            }
            $this->consume();
        }

        return new Token(self::TAG_DOUBLE_END_OPENING, '', array(
            'line' => $this->frozen['line'],
            'col' => $this->frozen['col'],
            'char' => $this->frozen['p'],
        ));
    }

    /** <!---/ **/
    public function TAG_SIMPLE_CLOSING(){
        $ar = array('/', '-', '-', '-', '>');
        $this->freeze();

        foreach($ar as $actual_s){
            if($actual_s !== $this->c){
                $this->rollback();
                return;
            }
            $this->consume();
        }

        return new Token(self::TAG_SIMPLE_CLOSING, '', array(
            'line' => $this->frozen['line'],
            'col' => $this->frozen['col'],
            'char' => $this->frozen['p'],
        ));
    }

    /** <!--- **/
    public function TAG_START_OPENING(){
        $ar = array('<', '!', '-', '-', '-');
        $this->freeze();

        foreach($ar as $actual_s){
            if($actual_s !== $this->c){
                $this->rollback();
                return;
            }
            $this->consume();
        }
        return new Token(self::TAG_START_OPENING, '', array(
            'line' => $this->frozen['line'],
            'col' => $this->frozen['col'],
            'char' => $this->frozen['p'],
        ));
    }

    /** tplcontent: **/
    public function TEMPLATE_CONTENT_ATTRIBUTE(){
        $ar = array('t', 'p', 'l', 'c','o', 'n', 't','e','n','t', ':');
        $this->freeze();

        foreach($ar as $actual_s){
            if($actual_s !== $this->c){
                $this->rollback();
                return;
            }
            $this->consume();
        }
        return new Token(self::TEMPLATE_CONTENT_ATTRIBUTE, '', array(
            'line' => $this->frozen['line'],
            'col' => $this->frozen['col'],
            'char' => $this->frozen['p'],
        ));
    }

    /** tpl: **/
    public function TEMPLATE_ATTRIBUTE(){
        $ar = array('t', 'p', 'l', ':');
        $this->freeze();

        foreach($ar as $actual_s){
            if($actual_s !== $this->c){
                $this->rollback();
                return;
            }
            $this->consume();
        }
        return new Token(self::TEMPLATE_ATTRIBUTE, '', array(
            'line' => $this->frozen['line'],
            'col' => $this->frozen['col'],
            'char' => $this->frozen['p'],
        ));
    }


    /** </ **/
    public function HTML_DOUBLE_END_OPENING(){
        $ar = array('<', '/');
        $this->freeze();

        foreach($ar as $actual_s){
            if($actual_s !== $this->c){
                $this->rollback();
                return;
            }
            $this->consume();
        }
        return new Token(self::HTML_DOUBLE_END_OPENING, '', array(
            'line' => $this->frozen['line'],
            'col' => $this->frozen['col'],
            'char' => $this->frozen['p'],
        ));
    }

    /** /> **/
    public function HTML_SIMPLE_CLOSING(){
        $ar = array('/', '>');
        $this->freeze();

        foreach($ar as $actual_s){
            if($actual_s !== $this->c){
                $this->rollback();
                return;
            }
            $this->consume();
        }
        return new Token(self::HTML_SIMPLE_CLOSING, '', array(
            'line' => $this->frozen['line'],
            'col' => $this->frozen['col'],
            'char' => $this->frozen['p'],
        ));
    }



    /** <? **/
    public function PHP_OPENING(){
        $ar = array('<', '?');
        $this->freeze();

        foreach($ar as $actual_s){
            if($actual_s !== $this->c){
                $this->rollback();
                return;
            }
            $this->consume();
        }
        return new Token(self::PHP_OPENING, '', array(
            'line' => $this->frozen['line'],
            'col' => $this->frozen['col'],
            'char' => $this->frozen['p'],
        ));
    }


    /** ?> **/
    public function PHP_CLOSING(){
        $ar = array('?', '>');
        $this->freeze();

        foreach($ar as $actual_s){
            if($actual_s !== $this->c){
                $this->rollback();
                return;
            }
            $this->consume();
        }
        return new Token(self::PHP_CLOSING, '', array(
            'line' => $this->frozen['line'],
            'col' => $this->frozen['col'],
            'char' => $this->frozen['p'],
        ));
    }



    /** NAME : ('a'..'z'|'A'..'Z')+; // NAME is sequence of >=1 letter */
    public function NAME() {
        $buf = '';
        do {
            $buf .= $this->c;
            $this->consume();
        } while ($this->isLETTER());
        
        return new Token(self::NAME, $buf);
    }


    /** WS : (' '|'\t'|'\n'|'\r')* ; // ignore any whitespace */
    public function WS() {
        while(ctype_space($this->c)) {
            $this->consume();
        }
    }
}
