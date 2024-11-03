<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Content.Gallery
 *
 * @copyright   (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Biolaxy\Plugin\Content\Gallery\Extension;

use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Uri\Uri;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Plugin to enable loading modules into content (e.g. articles)
 * This uses the {loadmodule} syntax
 *
 * @since  1.5
 */
final class Gallery extends CMSPlugin
{
    //protected static $modules = [];

    //protected static $mods = [];

    /**
     * Plugin that loads module positions within content
     *
     * @param   string   $context   The context of the content being passed to the plugin.
     * @param   object   &$article  The article object.  Note $article->text is also available
     * @param   mixed    &$params   The article params
     * @param   integer  $page      The 'page' number
     *
     * @return  void
     *
     * @since   1.6
     */
    public function onContentPrepare($context, &$article, &$params, $page = 0)
    {
        // Only execute if $article is an object and has a text property
        if (!\is_object($article) || !property_exists($article, 'text') || \is_null($article->text)) {
            return;
        }

        //----------------------------------------------------------------------------------------------------------
        
        //getters
        $doc = Factory::getApplication()->getDocument();

        //used to add scripts/styles
        $wa = $doc->getWebAssetManager();

        //in case you need it, the path to this plugin
        $pluginPath = 'plugins/content/' . $this->_name;
        
        //adding sample styles and scripts
        //remove comments if you actually want to use
        $wa->registerAndUseStyle('gallery.mainstyle',$pluginPath.'/css/style.css');
        $wa->registerAndUseScript('gallery.mainscript',$pluginPath.'/js/script.js');
        
        //----------------------------------------------------------------------------------------------------------
        
        // Regex für die Erkennung des Gallery-Shortcodes
        $regex = '/\{gallery\}(.*?)\{\/gallery\}/s';
        
        // Ersetze den Shortcode durch HTML für die Galerie
        $article->text = preg_replace_callback($regex, [$this, 'renderGallery'], $article->text);
        
        //----------------------------------------------------------------------------------------------------------
        
        // Regex für die Erkennung des hello-Shortcodes
        $regexh = '/\{hello\}(.*?)\{\/hello\}/s';
        
        // Ersetze den Shortcode durch HTML für die Galerie
        $article->text = preg_replace_callback($regexh, [$this, 'renderHello'], $article->text);
        
        //----------------------------------------------------------------------------------------------------------
        
        // Regex für die Erkennung des Gallery-Shortcodes
        $regexi = '/\{image\}(.*?)\{\/image\}/s';
        
        // Ersetze den Shortcode durch HTML für die Galerie
        $article->text = preg_replace_callback($regexi, [$this, 'renderImage'], $article->text); 
        
        //----------------------------------------------------------------------------------------------------------
        
        // Config Shortcodes
        $this->renderConfig($article);
    }
    
    private function renderGallery($matches)
    {
        // Language Plugin laden
        $this->loadLanguage();
        
        // Get a handle to the Joomla! application object
        $application = Factory::getApplication();
        
        // Parameter aus Plugin-Config auslesen
        $gallery_folder = $this->params->get('gallery-folder','/');
        
        // Images Ordner
        $folder = getcwd()."/images/";
        
        $galleryBody = $matches[1];
        
        if($galleryBody == "") {
            //echo "Body leer";
            $body_folder = $gallery_folder;
        } else {
            //echo "Body nicht leer";
            if(Folder::exists($folder.$galleryBody) == TRUE) {
                $body_folder = $galleryBody;
            } else {
                // Add a message to the message queue
                return $application->enqueueMessage(Text::_('PLG_CONTENT_GALLERY_NO_FOLDER').'<br>Ordner: '.$galleryBody, 'error');
            }
        }
        
        $images = Folder::files($folder.$body_folder);
        $body = "";
        
        //echo Folder::exists($folder);
        
        foreach($images as $value)
        {
            $full_path = $body_folder."/".$value;
            
            $body .= <<<EOT
<div class="col-12 col-sm-6 col-md-4 gallery">
    <img width="1920" height="1280" src="images/$full_path" class="img-fluid">
</div>
EOT;
         }

        $output = <<<EOT
<section class="image-grid">
    <div class="container-xxl">
        <div class="row gy-4">
        $body
        </div>
    </div>
</section>
EOT;
        
        // Hier kannst du deine Logik zur Erstellung der Galerie einfügen
        // Beispiel: Bilder aus dem Text extrahieren
        $imageUrls = explode(',', trim($galleryBody));
        
        // HTML für die Galerie generieren
        $galleryHtml = '<div class="gallery">';
        foreach ($imageUrls as $url) {
            $galleryHtml .= '<div class="gallery-item"><img src="' . htmlspecialchars(trim($url)) . '" alt=""></div>';
        }
        $galleryHtml .= '</div>';
        
        //echo '<pre>'; print_r($matches[1]); echo '</pre>';
        
        //return $galleryHtml;
        
        return $output;
    }
    
    private function renderImage($matches)
    {
        // Language Plugin laden
        $this->loadLanguage();
        
        // Get a handle to the Joomla! application object
        $application = Factory::getApplication();
        
        $folder = getcwd()."/images/";
        
        $file = $matches[1];
        
        if(File::exists($folder.$file)) {
            $full_path = "/images/".$file;
        } else {
            return $application->enqueueMessage('Die angegebene Datei existiert nicht<br>Datei: '.$file, 'error');
        }
        
        $output = <<<EOT
<section class="image-grid">
    <div class="container-xxl">
        <div class="row gy-4" style="justify-content: left;">
            <div class="col-12 col-sm-6 col-md-4 gallery">
                <img width="1920" height="1280" src="$full_path" class="img-fluid">
            </div>
        </div>
    </div>
</section>
EOT;
        
        return $output;
    }
    
    private function renderHello($matches)
    {
        // Language Plugin laden
        $this->loadLanguage();
        
        $helloBody = $matches[1];
        
        if($helloBody == "") {
            //echo "Body leer";
            $hello = "Hallo Welt!";
        } else {
            //echo "Body nicht leer";
            $hello = $helloBody;
        }

        $output = "<h1>$hello</h1>";
        
        return $output;
    }
    
    private function renderConfig($article)
    {
        $text = $article->text; // text of the article
        $config = Factory::getApplication()->getConfig()->toArray();  // config params as an array
            // (we can't do a foreach over the config params as a Registry because they're protected)
        
        // the following is just code to replace {configname} with the parameter value
        $offset = 0;
        // find opening curly brackets ...
        while (($start = strpos($text, "{", $offset)) !== false) {
            // find the corresponding closing bracket and extract the "shortcode"
            if ($end = strpos($text, "}", $start)) {
               $shortcode = substr($text, $start + 1, $end - $start - 1);
               
               // cycle through the config array looking for a match
               $match_found = false;
               foreach ($config as $key => $value) {
                   if ($key === $shortcode) {
                       $text = substr_replace($text, htmlspecialchars($value), $start, $end - $start + 1);
                       $match_found = true;
                       break;
                   }
                } 
                
                // if no match found replace it with an error string
                if (!$match_found) {
                    $this->loadLanguage();  // you need to load the plugin's language constants before using them
                    // (alternatively you can set:  protected $autoloadLanguage = true; and Joomla will load it for you)
                    $text = substr_replace($text, Text::_('PLG_CONTENT_SHORTCODES_NO_MATCH'), $start, $end - $start + 1);
                }
                
            } else {
               break;
            }
           
           $offset = $end;
        }

        // now update the article text with the processed text
        $article->text = $text;
    }
}
