<?php

class CommandLineInterface
{

	protected $command="";

	protected $keywords = array(
	"help",
	"put",
	"take",
	"look",
	"drop"
	);

	public function interpret($str)
	{
		$historyStr = "";//This will record the output so it can be copied and repeated;
		
		$this->command = strip_tags($str);
		$first_space = strpos($this->command, " ");
		
		if ($first_space === FALSE) $first_word = $this->command;
		else $first_word = substr($this->command, 0, $first_space);
		
		if (!in_array($first_word, $this->keywords))
		{
			$historyStr .= para("Unknown keyword '$first_word'.");
		}
		else
		{
			switch ($first_word)
			{
				case "help":
					$historyStr .= para("OK, I'll help once I figure out how.");
					break;
				case "put":
					$historyStr .= para("Put what in where?");
					break;
				case "take":
					$historyStr .= para("You can't take it, it's MINE!");
					break;
				case "look":
					$historyStr .= para("Yes, I see you, Honey, that's very nice.");
					break;
				case "drop":
					$historyStr .= para("Are you sure? These things are fragile.");
					break;
			}//end switch
		}
		return $historyStr;
	}

}

$cli = new CommandLineInterface();

if (isset($_POST["prev_txt"]) && $_POST["prev_txt"]!="")
{
	ptag ("h2", "History");
	$returnStr = urldecode($_POST["prev_txt"]);
	echo $returnStr;
}
else $returnStr = "";

if (isset($_POST["submit_btn"])&&isset($_POST["text_txt"]))
{
	$returnStr .= $cli->interpret( $_POST["text_txt"] );
}
$returnStr = urlencode($returnStr);

echo "<form action='index.php?page=cli' method='post'";
ptag ("input", "", "name='prev_txt' id='prev_txt' type='hidden' value='$returnStr'");
ptag ("label", "Enter command below.", "for='text_txt'");
ptag ("input", "", "name='text_txt' id='text_txt' type='text' value='' size=80");
ptag ("input", "", "name='submit_btn' id='submit_btn' type='submit' value='Submit'");
echo "</form>";

?>