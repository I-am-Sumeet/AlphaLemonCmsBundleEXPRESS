<?php
/*
 * This file is part of the AlphaLemon CMS Application and it is distributed
 * under the GPL LICENSE Version 2.0. To use this application you must leave
 * intact this copyright notice.
 *
 * Copyright (c) AlphaLemon <webmaster@alphalemon.com>
 *
 * For the full copyright and license infpageModelation, please view the LICENSE
 * file that was distributed with this source code.
 *
 * For extra documentation and help please visit http://www.alphalemon.com
 *
 * @license    GPL LICENSE Version 2.0
 *
 */

namespace AlphaLemon\AlphaLemonCmsBundle\Core\Content\Validator;

use AlphaLemon\AlphaLemonCmsBundle\Core\Model\Orm\LanguageModelInterface;

/**
 * Extends the base parameters validator object to add specific validations for
 * the languages
 *
 * @author alphalemon <webmaster@alphalemon.com>
 */
class AlParametersValidatorLanguageManager extends AlParametersValidator
{
    protected $languageModel;

    /**
     * Constructor
     *
     * @param LanguageModelInterface $languageModel
     */
    public function __construct(LanguageModelInterface $languageModel)
    {
        $this->languageModel = $languageModel;
    }

    /**
     * Sets the language model
     *
     * @param LanguageModelInterface $v
     */
    public function setLanguageModel(LanguageModelInterface $v)
    {
        $this->languageModel = $v;
    }

    /**
     * Gets the language model
     *
     * @return LanguageModelInterface
     */
    public function getLanguageModel()
    {
        return $this->languageModel;
    }

    /**
     * Checks when the give language name is already saved
     *
     * @param string $laguageName
     * @return  boolean
     */
    public function languageExists($laguageName)
    {
        return (count($this->languageModel->fromLanguageName($laguageName)) > 0) ? true : false;
    }
}