<?php
#CreatedBy;Aaron;11OCT2014;Odin-Framework
/* 
	Tonight, add in $flip_label functionality
*/
 class bolt_form
{
	var $num_instances	= 0;

	#This is an HTML form that allows you to update/edit database data.
	function create($table,$fields=NULL,$opts=NULL)
	{
		global $odin;
		$this->num_instances++;
		$instance	= (isset($o["instance"])?$o["instance"]:"inst-".$this->num_instances);
		$o			= array(
			"set_element"		=> "fieldset",
			"set_class"			=> "",
			"wrap_element"		=> "ul",
			"item_element"		=> "li",
			"legends"			=> array(),
			"new_set_on"		=> array(),
			"submit_text"		=> "Submit",
			"instance"			=> $instance,
			"field_types"		=> array(),
			"field_opts"		=> array(),
			"form_attrs"		=> array(
				"id"				=> $instance,
				"method"			=> "post",
				"action"			=> $_SERVER["REQUEST_URI"],
			),
		);
		if($opts)
			{ $o	= $odin->array->overwrite_merge_recursive($o,$opts); }
		#if fields were not passed, dynamically get the fields from the database with their defaults & data-types
		if(!$fields)
		{
			#pull fields & types from db. overwrite merge types, preferencing whatever the developer passed over the database's types.
		}
		if(!$fields)
			{ return FALSE; }

		/*
			Dear Future Aaron,
			This is where you plan to have the $_POST[$instance] check to see if this specific form was posted or not.

			If it was not, then just carry on.
		*/

		#create the dom object & elements
		$dom		= new DOMDocument();
		$form		= $dom->createElement("form");
		if(!empty($o["form_attrs"]))
		{
			foreach($o["form_attrs"] as $attr=>$value)
			{
				$form_attr			= $dom->createAttribute($attr);
				$form_attr->value	= $value;
				$form->appendChild($form_attr);
			}
		}
		#setup the elements (field)set & wrapper
		$set				= $dom->createElement($o["set_element"]);
		$set_attr			= $dom->createAttribute("class");
		$set_count			= 1;
		$set_attr->value	= "elements set-".$set_count.(!empty($o["set_class"])?" $o[set_class]":FALSE);
		$set->appendChild($set_attr);
		if($o["legends"])
		{
			$legend			= $dom->createElement("legend",is_array($o["legends"])?array_shift($o["legends"]):$o["legends"]);
			$set->appendChild($legend);
		}
		
		$wrapper			= $dom->createElement($o["wrap_element"]);
		$set->appendChild($wrapper);


		#append the submit button into this form, nested inside of its own div.buttons wrapper.
		$submit_wrap		= $dom->createElement("div");
		$attr				= $dom->createAttribute("class");
		$attr->value		= "buttons";
		$submit_wrap->appendChild($attr);

		$submit				= $dom->createElement("button", $o["submit_text"]);
		$submit_attr		= $dom->createAttribute("type");
		$submit_attr->value	= "submit";
		$submit->appendChild($submit_attr);
		
		$submit_wrap->appendChild($submit);

		foreach($fields as $name=>$default)
		{
			#do we want to add a new (field)set before this field?
			if(!empty($o["new_set_on"]) && in_array($name, $o["new_set_on"]))
			{
				$form->appendChild($set);

				$set				= $dom->createElement($o["set_element"]);
				$set_attr			= $dom->createAttribute("class");
				$set_count++;
				$set_attr->value	= "elements set-".$set_count.(!empty($o["set_class"])?" $o[set_class]":FALSE);
				$set->appendChild($set_attr);
				if($o["legends"])
				{
					$legend			= $dom->createElement("legend",is_array($o["legends"])?array_shift($o["legends"]):$o["legends"]);
					$set->appendChild($legend);
				}
				
				$wrapper			= $dom->createElement($o["wrap_element"]);
				$set->appendChild($wrapper);
			}
			#get this field's type
			$type		= (isset($o["types"][$name])?$o["types"][$name]:"text");
			#re/set this flag that says if this is one or many fields we're working with for this one "input".
			$multifields= FALSE;
			$flip_label	= FALSE;
			switch($type)
			{
				#catch-all for any field-types.
				default:
					#create a single input field
					$input				= $dom->createElement("input");
				break;
				#build out later
				case "label":
				case "hidden":
				case "disabled":
				break;
				#add radio & checkboxes
				case "radio":
				case "checkbox":
					#does this input have multiple field options?
					if(!empty($o["field_opts"][$name]))
					{
						$multifields	= TRUE;
						#start a ul & give it a class of type-group
						$input	= $dom->createElement("ul");
						$class	= $dom->createAttribute("class");
						$class->value	= "$type-group";
						$input->appendChild($class);

						#loop through all options add them to the ul
						foreach($o["field_opts"][$name] as $value=>$label)
						{
							$li	= $dom->createElement("li");

							#create the input field itself, then give it name, value, and type attributes.
							$field			= $dom->createElement("input");
							#field name
							$fname			= $dom->createAttribute("name");
							$fname->value	= $instance.'['.$name.']'.($type=="checkbox"?"[]":FALSE);
							$field->appendChild($fname);
							#field value
							$val			= $dom->createAttribute("value");
							$val->value		= $value;
							$field->appendChild($val);
							#field type
							$type_attr			= $dom->createAttribute("type");
							$type_attr->value	= $type;
							$field->appendChild($type_attr);

							#check it on if this option's value matches the field's default value.
							if($value==$default || (is_array($default) && in_array($value, $default)))
							{
								$checked		= $dom->createAttribute("checked");
								$checked->value	= "checked";
								$field->appendChild($checked);
							}

							#create a <label> for this option
							$opt_label	= $dom->createElement("label");
							$name_span	= $dom->createElement("span",$label);
							#add the input field to the label
							$opt_label->appendChild($field);
							#add the field-name (span) to the label
							$opt_label->appendChild($name_span);
							#add the label to the li
							$li->appendChild($opt_label);
							#add the li to the ul
							$input->appendChild($li);
						}
					}
					else
					{
						#This checkbox (or radio) only has one option

						#Create a single input field
						$input	= $dom->createElement("input");
						#If there is any default at all, check this field on.
						if(!empty($default))
						{
							$flip_label		= TRUE;
							$checked		= $dom->createAttribute("checked");
							$checked->value	= "checked";
							$input->appendChild($checked);
						}
					}
				break;
			}
			#create the item-level wrapping element & give it appropriate classes
			$element	= $dom->createElement($o["item_element"]);
			$el_class	= $dom->createAttribute("class");
			$el_class->value = "ft-$type f-$name";
			$element->appendChild($el_class);
			#is this a single-field attribute?
			if(!$multifields)
			{
				#wrap everything in a label
				$label				= $dom->createElement("label");
				#set a field name on the attribute
				$fname				= $dom->createAttribute("name");
				$fname->value		= $instance.'['.$name.']';
				$input->appendChild($fname);
				#add the type attribute to the input field
				$type_attr			= $dom->createAttribute("type");
				$type_attr->value	= $type;
				$input->appendChild($type_attr);

				#if there is a default value, set it here.
				if($default)
				{
					$dval			= $dom->createAttribute("value");
					$dval->value	= $default;
					$input->appendChild($dval);
				}
				#create a span with the field's name in it and add that into the label tag.
				$name_span	= $dom->createElement("span",$name);
				$label->appendChild($name_span);
				#add the input to the label
				$label->appendChild($input);
				#add the label to the element
				$element->appendChild($label);
				$wrapper->appendChild($element);
			}
			else
			{
/*
				This is a multi-field attribute, which means that $input is already done.
				Don't modify it or wrap a label around it or anything, just add its name span before you add it to the wrapper.
				Just stick it straight in!!
*/
				$name_span	= $dom->createElement("span",$name);
				$element->appendChild($name_span);
				$element->appendChild($input);
				$wrapper->appendChild($element);
			}
		}

		#Add the remaining dom elements to the output
		$form->appendChild($set);
		$form->appendChild($submit_wrap);

		#finally, write the form tag into the dom object. Then return the dom as HTML
		$dom->appendChild($form);
		return $dom->saveHTML();
	}
	
	function view()
	{
		
	}
}