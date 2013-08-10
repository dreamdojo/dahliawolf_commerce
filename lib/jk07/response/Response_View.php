<?php

class Response_View extends Jk_Base
{
    protected $title;
    protected $file;
    protected $template;

    protected $tags         = array();
    protected $js_includes  = array();
    protected $js_modules   = array();
    protected $css_includes = array();
    protected $css_modules  = array();

    protected $content_type  = 'text/html';


    public function __construct($file = 'default/default.phtml', $title = 'genereic title', $template = 'templates/default.phtml', $tags = null)
    {
        $this->file        = $file;
        $this->title       = $title;
        $this->tags        = $tags;
        $this->template    = $template;
    }

    public function setFile ($file = '_general.phtml')
    {
        $this->file = $file;
    }

    public function setContentType($ctype = 'text/html')
    {
        $this->content_type = $ctype;
    }

    public function getContentType()
    {
        return $this->content_type;
    }


    public function getFile()
    {
        return $this->file;
    }

    public function setTitle ($title = 'generic title')
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTemplate($t = 'templates/default.phtml')
    {
        $this->template = $t;
    }

    public function getTemplate()
    {
        $tmp = APP_VIEWS . $this->template;

		if( file_exists($tmp) ) return $this->template;

        return null;
    }

    public function setCSSModules($a)
    {
        is_array($a) ? $this->css_modules = array_merge($this->css_modules, $a) : array_push($this->css_modules, $a);
    }

    public function getCSSModules()
    {
        return $this->css_modules;
    }

    public function setJSModules(array $a)
    {
        is_array($a) ? $this->js_modules = array_merge($this->js_modules, $a) : array_push($this->js_modules, $a);
    }

    public function getJSModules()
    {
        return $this->js_modules;
    }


    public function setCSS( array $a )
    {
        is_array($a) ? $this->css_includes = array_merge($this->css_includes, $a) : array_push($this->css_includes, $a);
    }

    public function getCSS()
    {
        return $this->css_includes;
    }

    public function setJS( $a)
    {
        is_array($a) ? $this->js_includes = array_merge($this->js_includes, $a) : array_push($this->js_includes, $a);
    }


    public function getJS()
    {
        return $this->js_includes;
    }


    public function setTags(array $a)
    {
        is_array($a) ? array_merge($this->tags, $a) : array_push($this->tags, $a);
    }

    public function getTags()
    {
        return $this->tags;
    }



}

?>