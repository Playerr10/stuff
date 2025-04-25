<?php

namespace watergames;

class pagebuilder {

    public array $config =
    [
        'title'=>'Untitled',
        'footer'=>true
    ];

    public array $cssfiles = [];
    public array $metatags = [];
    public array $jsfiles = [];

    public function set_page_name($title){
        $this->config['title'] = $title;
    }

    public function toggle_footer($footer){
        if(is_bool($footer)){
            $this->config["footer"] = $footer;
        }
    }

    public function addresource($resoucelist, $resource, $push2first = false){
        if ($push2first){
            array_unshift($this->$resoucelist, $resource);
        } else {
            $this->$resoucelist[] = $resource;
        }
    }

    function addmetatag($property, $content)
	{
		$this->metatags[$property] = $content;
	}

    public function get_template($file, $data = []){
        extract($data);
        require(baseurl . "/templates/$file.php");
    }
    
    public function get_snippet($file, $data = []){
        extract($data);
        require(baseurl . "/templates/snippets/$file.php");
    }

    public function buildheader() {
        $currenturl = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $this->addmetatag("og:type", "Website");
        $this->addmetatag("og:url", $currenturl);
        
        
        if(!isset($this->metatags["og:title"]) || !isset($this->metatags["og:description"])){
            $this->addmetatag("og:title", project_name . " - " .$this->config["title"]);
            $this->addmetatag("og:description", project_description);
        }
        
        $this->addresource('cssfiles', '/assets/base.css');
        
        // this HAS to be expanded in the future 
        $this->get_snippet('header', [
            'cssfiles' => $this->cssfiles,
            'jsfiles' => $this->jsfiles,
            'metatags' => $this->metatags,
            'config' => $this->config
        ]);
        
    }
}
