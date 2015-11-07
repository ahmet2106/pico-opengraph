<?php

/**
* PicoATOpenGraph - auto generated OG Meta Tags for better Facebook Sharing!
*
* This plugin adds automatically generated OpenGraph Meta tags to
* the end of the </head> section on your pages. The Front-Page is mentioned
* as a "website" object, while every Sub-Page as an "article" object.
*
* - Copy `PicoATOpenGraph.php` to your `plugins` directory
* - Copy the below configuration array to your `config/config.php`
*
* //PicoATOpenGraph Configuration
* $config['PicoATOpenGraph'] = array(
*     'enabled' => true,
*     'default_image' => 'path/to/default/image.jpg'
* );
*
* - Set your default image path as a backup when images cannot be found on a page
*
* @author Ahmet Topal
* @author Tyler Heshka
* @link http://ahmet-topal.com
* @license http://creativecommons.org/licenses/by-sa/2.0/
* @version 1.0
*/
class PicoATOpenGraph extends AbstractPicoPlugin
{
    /**
    * This plugin is enabled by default?
    *
    * @see AbstractPicoPlugin::$enabled
    * @var boolean
    */
    protected $enabled = false;

    /**
    * This plugin depends on ...
    *
    * @see AbstractPicoPlugin::$dependsOn
    * @var string[]
    */
    protected $dependsOn = null;

    /**
    * Is the requested page the homepage?
    *
    * @var boolean
    */
    private $is_homepage;

    /**
    * Has a 404 error occured?
    *
    * @var boolean
    */
    private $is_error = false;

    /**
    * Copy of the site config
    *
    * @var array[] the site config.php
    */
    private $config = array();

    /**
    * Copy of the site meta
    *
    * @var array[] the page meta
    */
    private $meta = array();

    /**
    * The requested URL
    *
    * @var string[] the requested URL
    */
    private $url;

    /**
    * Triggered after Pico has read its configuration
    *
    * @see    Pico::getConfig()
    * @param  mixed[] &$config array of config variables
    * @return void
    */
    public function onConfigLoaded(&$config)
    {
        //Make a local copy of the site config
        $this->config = $config;
    }

    /**
    * Triggered after Pico has evaluated the request URL
    *
    * @see    Pico::getRequestUrl()
    * @param  string &$url part of the URL describing the requested contents
    * @return void
    */
    public function onRequestUrl(&$url)
    {
        //Make a local copy of the requested URL
        $this->url = $url;

        //Determine if this is the homepage
        $this->is_homepage = ($url == '' || $url == 'index') ? true : false;
    }

    /**
    * Triggered after Pico has read the contents of the 404 file
    *
    * @see    Pico::getRawContent()
    * @param  string &$rawContent raw file contents
    * @return void
    */
    public function on404ContentLoaded(&$rawContent)
    {
        //Note that a 404 error has occured
        $this->is_error = true;
    }

    /**
    * Triggered after Pico has parsed the meta header
    *
    * @see    Pico::getFileMeta()
    * @param  string[] &$meta parsed meta data
    * @return void
    */
    public function onMetaParsed(&$meta)
    {
        //Make a local copy of the site meta
        $this->meta = $meta;
    }

    /**
    * Triggered after Pico has parsed the contents of the file to serve
    *
    * @see    Pico::getFileContent()
    * @param  string &$content parsed contents
    * @return void
    */
    public function onContentParsed(&$content)
    {
        //Find images in the parsed content
        $images = array();

        //Search pattern
        preg_match_all('/<img[^>]+>/i', $content, $img_tags);

        //Grab the first image found
        foreach ($img_tags[0] as $img_tag)
        {
            //Search pattern
            preg_match('/src="([^"]*)"/i', $img_tag, $match);

            //First match
            $src = $match[1];

            //Build the image URL
            $images[] = sprintf('%s%s%s',
                $this->at_starts_with($src, array('http://', 'https://')) ? '' : $this->config['base_url'],
                (!$this->at_starts_with($src, array('http://', 'https://')) && !$this->at_starts_with($src, array('/'))) ? '/'.$this->url : '',
                $src
            );
    }

    //No images found! Use the default...
    if (isset($this->config['PicoATOpenGraph']['defaultImage'])) {
        $images[] = $this->config['PicoATOpenGraph']['defaultImage'];
    }

    //Return the result
    $this->images = $images;
}

/**
* Triggered after Pico has rendered the page
*
* @param  string &$output contents which will be sent to the user
* @return void
*/
public function onPageRendered(&$output)
{
    //Only if not error page
    if (!$this->is_error)
    {
        //Setup the OpenGraph properties
        $properties = array(
            'og:type'           => $this->is_homepage ? 'website' : 'article',
            'og:title'          => $this->meta['title'],
            'og:description'	=> $this->meta['description'],
            'og:url'            => $this->config['rewrite_url'] ? sprintf('%s/%s', $this->config['base_url'], $this->url) : sprintf('%s?%s', $this->config['base_url'], $this->url),
            'og:site_name'      => $this->config['site_title']
        );

        //Include any found images
        if (count($this->images))
        {
            $properties['og:image'] = $this->images[0];
        }

        //Clear the meta variable for output
        $meta = '';

        //Loop through properties, and create meta tags
        foreach ($properties as $key => $value)
        {
            $meta .= "\t". sprintf('<meta property="%s" content="%s" />', $key, $value).PHP_EOL;
        }

        //Replace closing of head with og meta tags then close the head again
        $output = str_replace('</head>', PHP_EOL.$meta.'</head>', $output);
    }
}

/**
* string start with one elem of array
*
* @param   string $string
* @param   array $starts_with
* @return  boolean true|false
*/
private function at_starts_with($string, $start_with = array())
{
    foreach ($start_with as $start)
    {
        if (!strncmp($string, $start, strlen($start))) return true;
    }

    return false;
}

}
