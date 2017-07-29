<?php

// get the huge tree JSON data for the demo
// #10 levels deep, #2 folders + #2 files each ... ~ #4K items :)

$path = dirname(__FILE__);

require_once("$path/Tree.php");

// huge tree data class :) just to get demo tree data
// it's a simple return based on the $parentId to know where we are
// and if it's a valid branch (also limiting to the #10 deep levels)

class BuildMenu extends Tree {
    /*
     * $parentId will be the path to the folder.
     */

	function searchByField($categories, $field, $value)
	{
		$retArr = array();
		foreach($categories as $key => $category)
		{
			if ($category[$field] == $value) {
				$retArr[] = $categories[$key];
			}
		}
		return $retArr;
	}
     
    public function branch($parentId = null) {
    	$categories = array (
		array("id" => "1", "label" => "Neolithic", "parent" => "0"),
		array("id" => "2", "label" => "Copper tech", "parent" => "0"),
		array("id" => "3", "label" => "Bronze tech", "parent" => "0"),
		array("id" => "4", "label" => "Iron tech", "parent" => "0"),
		array("id" => "5", "label" => "Steel tech", "parent" => "0"),
		array("id" => "6", "label" => "Clay items", "parent" => "0"),
		array("id" => "23", "label" => "Needlework", "parent" => "0"),
		array("id" => "25", "label" => "Wood tech", "parent" => "0"),
		array("id" => "8", "label" => "Tools", "parent" => "1"),
		array("id" => "9", "label" => "Weapons", "parent" => "1"),
		array("id" => "18", "label" => "Components", "parent" => "1"),
		array("id" => "10", "label" => "Tools", "parent" => "2"),
		array("id" => "11", "label" => "Weapons", "parent" => "2"),
		array("id" => "19", "label" => "Components", "parent" => "2"),
		array("id" => "12", "label" => "Tools", "parent" => "3"),
		array("id" => "13", "label" => "Weapons", "parent" => "3"),
		array("id" => "20", "label" => "Components", "parent" => "3"),
		array("id" => "14", "label" => "Tools", "parent" => "4"),
		array("id" => "15", "label" => "Weapons", "parent" => "4"),
		array("id" => "21", "label" => "Components", "parent" => "4"),
		array("id" => "16", "label" => "Tools", "parent" => "5"),
		array("id" => "17", "label" => "Weapons", "parent" => "5"),
		array("id" => "22", "label" => "Components", "parent" => "5"),
		array("id" => "7", "label" => "Clothing", "parent" => "23"),
		array("id" => "24", "label" => "Tools", "parent" => "23"),
		array("id" => "26", "label" => "Tools", "parent" => "25"),
		array("id" => "27", "label" => "Weapons", "parent" => "25"),
		array("id" => "28", "label" => "Components", "parent" => "25")
	);
    	    
        $branch = array();
        $split = explode('.', $parentId);
        $type = array_shift($split);
        if (!$type || ($type == 'folder')) {
        	if (!$type) $split = array("", "0");
        	$results = $this->searchByField($categories, "parent", $split[count($split)-1]);
        	
        	if ($results) {
        		foreach($results as $key => $result) {
        			$value = $result["id"];
        			$branch["folder..$value"] = $result["label"];
        		}
        	}
        	
        	
        }
        return $branch;
    }

    /*
     * $itemId will be the path to the file/folder.
     */

    public function itemProps($itemId) {
        $path = explode('.', $itemId);
        $type = array_shift($path);
        switch ($type) {
            case 'folder':
                return array_merge(parent::itemProps($itemId), array(
                            'inode' => true,
                            'icon' => 'folder'
                        ));
            case 'file':
                return array_merge(parent::itemProps($itemId), array(
                            'inode' => false,
                            'icon' => 'file'
                        ));
        }
        return parent::itemProps($itemId);
    }

}

$BuildMenu = new BuildMenu();

// what branch was requested?
$branch = isset($_GET['branch']) ? $_GET['branch'] : null;

$BuildMenu->json($branch);

// this will load the entire tree (comment above and uncomment this)
//$BuildMenu->json($branch, true);

// note: for large and complex tree structures
// probably the best way to do things is to return the first 2-3 levels
// starting from the requested branch instead of returning the entire tree
