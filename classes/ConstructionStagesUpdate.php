<?php

class ConstructionStagesUpdate
{
    public $name;
    public $startDate;
    public $endDate;
    public $durationUnit;
    public $duration;
    public $color;
    public $externalId;
    public $status;

    /**
     * Constructor method to initialize properties from an input object.
     *
     * @param object $data Input data object.
     */

    public function __construct($data)
    {
        if (is_object($data)) {
            $vars = get_object_vars($this);

            foreach ($vars as $name => $value) {
                if (isset($data->$name)) {
                    $this->$name = $data->$name;
                }
            }
        }
    }
}