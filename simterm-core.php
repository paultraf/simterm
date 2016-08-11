<?php

require_once('simterm-settings.php');
require_once('simterm-line.php');

class SimTerm
{
    protected $settings;

    function __construct()
    {
	/* Inicialización básica de mi plugin 
	   (la que no tiene que ver con WordPress) */
	$this->settings = SimTermSettings::getInstance();
    }

    function settings()
    {
	return $this->settings;
    }

    function simterm_shortcode($atts, $content="")
    {
      $_lines = preg_split("/\r\n|\n|\r/", trim($content));
      if (count($_lines)==0)
	return;
      add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
      wp_enqueue_script('simterm-showyourterms', plugins_url('js/show-your-terms.min.js',__FILE__), array(), '20160705', true);
      wp_enqueue_script('simterm-launcher', plugins_url('js/simterm.js',__FILE__), array('simterm-showyourterms'), '20160705', true);
      wp_enqueue_style('simterm-showyourtermscss', plugins_url('css/show-your-terms.min.css', __FILE__), array(), '20160705', 'all');
      wp_enqueue_style('simterm-extracss', plugins_url('css/simterm.css', __FILE__), array(), '20160705', 'all');

      $data=array('lines'=> array());
      $commandPrep = get_option('simterm-command-prepend');
      $typePrep = get_option('simterm-type-prepend');
      $defaultDelay = get_option('simterm-default-delay');
      $lastLineDelay = get_option('simterm-last-delay-time');
      $defaultTheme = get_option('simterm-default-theme');
      $plainOutputDelay = get_option('simterm-output-delay-time');
      $defaultTypingSpeed = get_option('simterm-typing-speed');
      $filters = array();
      if (get_option('simterm-transform-chars'))
	$filters[] = array($this, 'fixDashes');
      $data['theme'] = ( (isset($atts['theme'])) && ($this->settings()->validTheme($atts['theme'])) )?$atts['theme']:$defaultTheme;
      $data['title'] = (isset($atts['title']))?$atts['title']:get_option('simterm-window-title');

      $lines = array();
      foreach ($_lines as $l)
	{
	  /* Replace strip_tags for preg_replace. strip_tags removes orphan < symbols and they're often
	     used in terminals. */
	  /* This expression must leave orphan <, > but supports sort <input >output and things like that. */
	  $l = trim(preg_replace( '/<[^<>\s]+(\s+[^<>\s]+)*?>/', '', $l));
	  if (empty($l))
	    continue;
	  $lines[] = $l;
	}
      $lineCount = count($lines);
      for ($i = 0; $i<$lineCount; ++$i)
	{
	  $linedata = array();
	  $thisline = new SimTermLine($lines[$i], $commandPrep, $typePrep, ($i<$lineCount-1)?$defaultDelay:$lastLineDelay, $defaultTypingSpeed);
	  $data['lines'][] = $thisline->getData($filters);
	}

      /* One more loop to fix delays */
      for ($i = 0; $i<$lineCount-1; ++$i)
	{
	  if ( ($data['lines'][$i]['type'] == 'line') && ($data['lines'][$i+1]['type'] == 'line') && (!$data['lines'][$i]['customDelay']) )
	    $data['lines'][$i]['delay'] = $plainOutputDelay;
	}

      return SimTermView::render('live/syt', array('data' => $data));
    }

    function fixDashes($content)
    {
      $content = str_replace( '&#8211;' , '--' , $content );
      $content = str_replace( '&#8212;' , '---' , $content );
      $content = str_replace( '<' , '&lt;' , $content );
      $content = str_replace( '>' , '&gt;' , $content );
      $content = str_replace (' ', '&nbsp;', $content);
      return $content; 
    }

};
