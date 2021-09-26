<?php 
namespace App\Helpers;

Class Functions
{

	public static function indexTableMaker($indexTableInfo)
	{
		return view('component.index_table',
			[
				'indexTableInfo' => $indexTableInfo,
			]
		);
	}

	public static function indexTableContanMaker($titleKey,$value)
	{
		//dd($titleKey["toAddString"].$value);
		switch ($titleKey['type']) 
		{
			case 'linkStringFontAdd':
				return "<a href='".$titleKey["toAddString"].$value."'>".$titleKey["displayString"]."</a>"; 
			
		}
	}

	public static function tableEditInputMaker($attribute)
	{
		$toReturnHtml="";
		$defaultAttribute =array(
								'type' => '',
								'label' => '',
								'name' =>'',
								'value' =>'',
								'class' => '',
								'id' => '',
								'placeholder'=>''
							);

		foreach ($defaultAttribute as $key => $value) {
			if(empty($attribute[$key]))
			{
				$attribute[$key]='';
			}
		}
		
		switch ($attribute['type']) 
		{
			case 'text':
				$toReturnHtml.='<div class="control-group">
							<label class="control-label" for="basicinput">
								'.$attribute["label"].'
							</label>
							<div class="controls">
								<input type="text" name="'.$attribute["name"].'" value="'.$attribute["value"].'" placeholder="'.$attribute["placeholder"].'" id="'.$attribute["id"].'" class="span7 '.$attribute["label"].'">
							</div>
						</div>';
						break;
			case 'hidden':
				$toReturnHtml.='
					<input type="hidden" name="'.$attribute["name"].'" id="'.$attribute["id"].'" class="span7 '.$attribute["label"].'" value="'.$attribute["value"].'">';
						break;
				
		}
		return $toReturnHtml;

	}
}