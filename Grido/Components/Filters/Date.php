<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 */

namespace Grido\Components\Filters;

/**
 * Date input filter.
 *
 * @package     Grido
 * @subpackage  Components\Filters
 * @author      Petr Bugyík
 */
class Date extends Text
{
    /** @var string for ->where('<column> LIKE %s', <value>) */
    protected $condition = 'LIKE %s';

    /** @var string for ->where('<column> LIKE %s', '%'.<value>.'%') */
    protected $formatValue = '%%value%';

    /** @var string JS date format */
    protected $dateFormat = 'Y-m-d';

    /**
     * @param \Grido\Grid $grid
     * @param string $name
     * @param string $label
     * @param array $items for select
     */
    public function __construct($grid, $name, $label, $dateFormat = 'Y-m-d')
    {
        $this->setDateFormat($dateFormat);
        parent::__construct($grid, $name, $label);
    }

    /**
     * @return \Nette\Forms\Controls\TextInput
     */
    protected function getFormControl()
    {
        $control = parent::getFormControl();
        $control->controlPrototype->class[] = 'date';
        $control->controlPrototype->attrs['autocomplete'] = 'off';
        $control->controlPrototype->attrs['data-format'] = $this->dateFormat;
        return $control;
    }

    /**
     * Set date format
     *
     * @param string Date format
     * @return \Grido\Components\Filters\Date $this
     */
    public function setDateFormat($format)
    {
        $this->dateFormat = $format;
        return $this;
    }

    /**
     * Get date format
     *
     * @return string Date format
     */
    public function getDateFormat()
    {
        return $this->dateFormat;
    }

    /**
     * {@inheritdoc}
     */
    protected function formatValue($value)
    {
        // Convert $value to the database format
        $datetime = \DateTime::createFromFormat($this->dateFormat, $value);

        if ($this->formatValue !== NULL) {
            return str_replace(self::VALUE_IDENTIFIER, $datetime->format('Y-m-d'), $this->formatValue);
        } else {
            return $datetime->format('Y-m-d');
        }
    }

}
