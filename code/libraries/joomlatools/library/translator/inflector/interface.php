<?php
/**
 * Joomlatools Framework - https://www.joomlatools.com/developer/framework/
 *
 * @copyright   Copyright (C) 2007 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework for the canonical source repository
 */

/**
 * Translator Inflector Interface
 *
 * @author  Ercan Ozkaya <https://github.com/ercanozkaya>
 * @package Koowa\Library\Translator\Inflector
 */
interface KTranslatorInflectorInterface
{
    /**
     * Returns the plural position to use for the given locale and number.
     *
     * @param integer $number The number
     * @param string  $locale The locale
     * @return integer The plural position
     */
    public static function getPluralPosition($number, $locale);

    /**
     * Overrides the default plural rule for a given locale.
     *
     * @param callable $rule   A PHP callable
     * @param string $locale   The locale
     * @throws LogicException
     * @return void
     */
    public static function setPluralRule($rule, $locale);
}