<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AlphaLemon\Block\FileBundle\Core\ElFinder;

use AlphaLemon\ElFinderBundle\Core\Connector\AlphaLemonElFinderBaseConnector;
use AlphaLemon\ThemeEngineBundle\Core\Asset\AlAsset;

/**
 * Description of ElFinderMarkdownConnector
 *
 * @author alphalemon <webmaster@alphalemon.com>
 */
class ElFinderFileConnector extends AlphaLemonElFinderBaseConnector
{
    protected function configure()
    {
        $request = $this->container->get('request');
        $asset = new AlAsset($this->container->get('kernel'), '@AlphaLemonCmsBundle');
        $absolutePath = $asset->getAbsolutePath() . '/' . $this->container->getParameter('alphalemon_cms.upload_assets_dir') . '/' . $this->container->getParameter('app_file.base_folder') . '/';
        $filesPath = $this->container->getParameter('kernel.root_dir') . '/../' . $this->container->getParameter('alphalemon_cms.web_folder') . '/' . $absolutePath;

        $options = array(
            'locale' => '',
            'roots' => array(
                array(
                    'driver'        => 'LocalFileSystem',   // driver for accessing file system (REQUIRED)
                    'path'          => $filesPath,         // path to files (REQUIRED)
                    'URL'           => $request->getScheme().'://'.$request->getHttpHost() . '/' . $absolutePath, // URL to files (REQUIRED)
                    'accessControl' => 'access',             // disable and hide dot starting files (OPTIONAL)
                    'rootAlias'     => 'Javascripts',
                )
            )
        );

        return $options;
    }
}