<?php
class FieldSorter {
    public $field;

    function __construct($field) {
        $this->field = $field;
    }

    function cmp($a, $b) {
        if ($a[$this->field] == $b[$this->field]) return 0;
        return ($a[$this->field] > $b[$this->field]) ? 1 : -1;
    }
}

//to be used with usort
//$sorter = new FieldSorter('field_to_sort_by');    
//usort($arrayToBeSorted, array($sorter, "cmp"));

//source: http://stackoverflow.com/questions/2426917/how-do-i-sort-a-multidimensional-array-by-one-of-the-fields-of-the-inner-array-i
?>
