<?php
require_once 'functions_wastecalendar.php';
class Streets {
	private $result = "??";
	private $location_id; //default location_id for select if one exists in db for this client
	private $default_option_content; //default for select  if one exists in db for this client
	
	function __construct($properties_array)	{
		if (!(method_exists('WasteColCalendarContainer', 'create_object')))
		{
			exit;
		}
	}

	public function get_streets(){
		//debug: disable next line to test without preselection address
		$this->location_id= get_user_location_id(); //if location_id was saved before from this client
		$mysqli=connect_mydb();
		$query="SELECT id,name_street,type_street,house_nos FROM location";
		$result = mysqli_query($mysqli,$query) or die ('Couldn’t execute query: '.$query);
		if ($result->num_rows > 0) {
			while($row = $result->fetch_assoc()) {
				$streets[$row["id"]]= $row["name_street"]." (".$row["type_street"].") ".$row["house_nos"];
			}
		}	
		if ($this->location_id) {
			$this->default_option_content=$streets[$this->location_id];
		}
		return $streets;
	}
  
	public function get_type_streets(){
		$mysqli=connect_mydb();
		$query="SELECT DISTINCT type_street FROM location ORDER BY type_street";
		$result = mysqli_query($mysqli,$query) or die ('Couldn’t execute query: '.$query);
		if ($result->num_rows > 0) {
			while($row = $result->fetch_assoc()) {
				$streets[]= $row["type_street"];
			}
		}	
		return $streets;
	}

	/*	BvS
	*	public function get_select($dog_app)
	*	Note 1. $dog_app was the location of the xml file with all options for the select box
	*	Now we fill select by using $this->get_streets(). The options are now in a MySql DB.
	*	Probably better fitting with the used container model would be to use $?_app to get to
	*	the db somehow??
	*	Note 2. In the interface the method get_select() is called by getting the last method 
	*	of the class. So it is essential that this is the last method (??? not very clear design).
	*/
	 
	public function get_select()  //BvS NB parameter ?_app removed
	{
		 /* BvS This works but will cause immediate submit after clicking a letter and thus
			not permitting to choose another street beginning with the same letter:
			$this->result = "<select name='street' id='street' onchange='this.form.submit()'>";
		 */
		$streets=$this->get_streets();

		$this->result = "<select name='street' id='street'>";
		$hidden='';
		if (!$this->location_id)
		{
			$this->location_id=-1;
			$this->default_option_content="Sélectionnez votre adresse";
			$hidden=' hidden'; //hidden prevents placeholder to be added to dropdown list (in case select2 is not used e.g. when javascript is disabled)
		}
		$this->result = $this->result .
			"<option value='{$this->location_id}' selected".$hidden.">{$this->default_option_content}</option>";

		foreach ( $streets as $id => $full_name)
		{
			if ($id!==$this->location_id){  //prevent double entry if $id is in selected option
				$this->result = $this->result . "<option value=$id>$full_name</option>";
			}			
		}
		$this->result = $this->result . "</select>";
		return $this->result;
	}	
}
