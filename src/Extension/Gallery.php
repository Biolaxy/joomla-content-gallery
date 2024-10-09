<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Content.Gallery
 *
 * @copyright   (C) 2024 Biolaxy
 * @license     MIT License
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
        $folder = "/var/www/vhosts/hosting143814.a2e85.netcup.net/test.biolaxy.de/httpdocs/images/";
        
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
}
