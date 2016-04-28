<?php
namespace Search\Model\Filter;

class Like extends Base
{

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'before' => false,
        'after' => false,
        'mode' => 'or',
        'comparison' => 'LIKE',
        'wildcardAny' => '*',
        'wildcardOne' => '?',
    ];

    /**
     * Process a LIKE condition ($x LIKE $y).
     *
     * @return void
     */
    public function process()
    {
        if ($this->skip()) {
            return;
        }

        $conditions = [];
        foreach ($this->fields() as $field) {
            $left = $field . ' ' . $this->config('comparison');
            $right = $this->_wildCards($this->value());

            $conditions[] = [$left => $right];
        }

        $this->query()->andWhere([$this->config('mode') => $conditions]);
    }

    /**
     * Wrap wild cards around the value.
     *
     * @param  string $value Value.
     * @return string
     */
    protected function _wildCards($value)
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->_wildCards($v);
            }
            return $value;
        }

        $value = $this->_formatWildCards($value);
        if ($this->config('before')) {
            $value = $this->_formatWildCards($this->config('wildcardAny')) . $value;
        }

        if ($this->config('after')) {
            $value = $value . $this->_formatWildCards($this->config('wildcardAny'));
        }

        return $value;
    }

    /**
     * Escape wild cards in value.
     *
     * @param string $value Value.
     * @return string
     */
    protected function _escapeWildCards($value)
    {
        if (!$this->config('escapeWildcards')) {
            return $value;
        }

        $from = ['%', '_'];
        $to = ['\%', '\_'];
        return str_replace($from, $to, $value);
    }

    /**
     * Replace substitutions with original wildcards
     * but first, escape the original wildcards in the text to use them as normal search text
     *
     * @param string $value Value
     * @return string Value
     */
    protected function _formatWildCards($value)
    {
        $from = $to = $substFrom = $substTo = [];
        if ($this->config('wildcardAny') !== '%') {
            $from[] = '%';
            $to[] = '\%';
            $substFrom[] = $this->config('wildcardAny');
            $substTo[] = '%';
        }
        if ($this->config('wildcardOne') !== '_') {
            $from[] = '_';
            $to[] = '\_';
            $substFrom[] = $this->config('wildcardOne');
            $substTo[] = '_';
        }
        if (!empty($from)) {
            // Escape first
            $value = str_replace($from, $to, $value);
            // Replace wildcards
            $value = str_replace($substFrom, $substTo, $value);
        }
        return $value;
    }
}
