<?php
abstract class TemplateBaseNode implements TemplateNode{

        protected $mandatory_attributes = array();
        protected $allowed_attributes = array();
        protected $attributes_aliases = array();

        protected $attributes = array();

        protected $info;
        protected $k=0;
        public $file, $line, $col;

        protected $is_nested = false;

        public function setup($info){
                $this->info = $info;

                $name = $this->getName();



                foreach($this->attributes_aliases as $real => $arr){
                        foreach($arr as $ali){
                                if(array_key_exists($ali, $this->attributes)){
                                        $this->attributes[$real] = $this->attributes[$ali];
                                        unset($this->attributes[$ali]);
                                }
                        }
                }

                foreach($this->attributes as $attr => $vv){
                        if(!in_array($attr, $this->allowed_attributes)){
                               print "\n'$attr' is not an allowed attribute for '$name' Tag. In File: '" . $this->file.". Line: ". $this->line .":" . $this->col. "'\n";
                        }
                }

                foreach($this->mandatory_attributes as $vv){
                        if(!array_key_exists($vv, $this->attributes)){
                                print "\nYou must add the attribute $vv for $name Tag.  In File: '" . $this->file.". Line: ". $this->line .":" . $this->col. "'\n";
                        }
                }

                foreach($this->allowed_attributes as $v)
                        if(!array_key_exists($v, $this->attributes))
                                $this->attributes[$v] = "";
        }

        function setIsNested($bool){
                $this->is_nested = $bool;
        }

        function getInfo(){
                return $this->info;
        }

        private function getName(){
                $c = get_class($this);
                $c = str_replace("Template", "", $c);
                return  str_replace("Node", "", $c);
        }

        public function getAttribute($name){
            return $this->attributes[$name];
        }




}
