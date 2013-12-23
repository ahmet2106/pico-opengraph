<?php

/**
 * OpenGraph Plugin adds auto generated OG Meta Tags for better Facebook Sharing right before closing of head
 *
 * @author Ahmet Topal
 * @link http://ahmet-topal.com
 * @license http://creativecommons.org/licenses/by-sa/2.0/
 */
class At_OpenGraph {
	// bool
	private $is_homepage;
	private $is_error = false;
	
	// array
	private $settings = array();
	private $meta = array();
	
	// string
	private $url;
	
	// string start with one elem of array
	private function at_starts_with($string, $start_with = array())
	{
		foreach ($start_with as $start)
		{
			if (!strncmp($string, $start, strlen($start))) return true;
		}
		
		return false;
	}
	
	public function request_url(&$url)
	{
		$this->url = $url;
		$this->is_homepage = ($url == '') ? true : false;
	}
	
	public function after_404_load_content(&$file, &$content)
	{
		$this->is_error = true;
	}
	
	public function config_loaded(&$settings)
	{
		$this->settings = $settings;
	}
	
	public function file_meta(&$meta)
	{
		$this->meta = $meta;
	}
	
	public function content_parsed(&$content)
	{
		$images = array();
		preg_match_all('/<img[^>]+>/i', $content, $img_tags);
		
		foreach ($img_tags[0] as $img_tag)
		{
			preg_match('/src="([^"]*)"/i', $img_tag, $match);
			$src = $match[1];
			
			$images[] = sprintf('%s%s%s',
				$this->at_starts_with($src, array('http://', 'https://')) ? '' : $this->settings['base_url'],
				(!$this->at_starts_with($src, array('http://', 'https://')) && !$this->at_starts_with($src, array('/'))) ? '/'.$this->url : '',
				$src
			);
		}
		
		if (isset($this->settings['opengraph_default_image'])) {
			$images[] = $this->settings['opengraph_default_image'];
		}
		
		$this->images = $images;
	}
	
	public function after_render(&$output)
	{
		// only if is not an error
		if (!$this->is_error)
		{
			$properties = array(
				'og:type'				=> $this->is_homepage ? 'website' : 'article',
				'og:title'				=> $this->meta['title'],
				'og:description'		=> $this->meta['description'],
				'og:url'				=> sprintf('%s/%s', $this->settings['base_url'], $this->url),
				'og:site_name'			=> $this->settings['site_title']
			);
			
			if (count($this->images))
			{
				$properties['og:image'] = $this->images[0];
			}
			
			$meta = '';
			
			foreach ($properties as $prop_k => $prop_v)
			{
				$meta .= "\t". sprintf('<meta property="%s" content="%s" />', $prop_k, $prop_v).PHP_EOL;
			}
			
			// just replace closing of head with og meta tags and close head, again
			$output = str_replace('</head>', PHP_EOL.$meta.'</head>', $output);
		}
	}
}
?>
