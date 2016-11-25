<?php
$path = (strpos($_SERVER['HTTP_HOST'], "test")===false)? "": "test/";
set_include_path("/var/www/".$path."dojo/");

require 'includes/imagetools.php';

class Avatar {

	static function saveAvatar($items, $user_id){
		global $db,$user,$cdn,$apc_prefix;
		apc_delete($apc_prefix.':useravatar:'.$user_id);


		$gender = (int)$items['avatar_gender'];
		if($gender!=1 && $gender!=2) return "gender";

		$color = (int)$items['avatar_color'];
		if($color<0 || $color>16) return "color";

		if($db->query('SELECT * FROM avatar_item_definitions WHERE item_id='.(int)$items['avatar_shirt'].' AND category="shirt" AND public=1 AND (gender='.(int)$gender.' OR gender=0)')->num_rows < 1 ) return "shirt";
		if($db->query('SELECT * FROM avatar_item_definitions WHERE item_id='.(int)$items['avatar_pants'].' AND category="pants" AND public=1 AND (gender='.(int)$gender.' OR gender=0)')->num_rows < 1 ) return "pants";
		if($db->query('SELECT * FROM avatar_item_definitions WHERE item_id='.(int)$items['avatar_hair'].' AND category="hair" AND public=1 AND (gender='.(int)$gender.' OR gender=0)')->num_rows < 1 ) return "hair";
		$result = $db->query('SELECT * FROM avatar_item_definitions WHERE item_id='.(int)$items['avatar_ears'].' AND category="ears" AND public=1 AND (gender='.(int)$gender.' OR gender=0)');
			if($result->num_rows < 1 ) return "ears";
			$row = $result->fetch_assoc();
			$ear_color = (($row['x']+14*($row['y']-1))-1)%16+1;
			if($ear_color != $color) return "ear color";
		if($db->query('SELECT * FROM avatar_item_definitions WHERE item_id='.(int)$items['avatar_eyes'].' AND category="eyes" AND public=1 AND (gender='.(int)$gender.' OR gender=0)')->num_rows < 1 ) return "eyes";
		if($db->query('SELECT * FROM avatar_item_definitions WHERE item_id='.(int)$items['avatar_eyebrows'].' AND category="eyebrows" AND public=1 AND (gender='.(int)$gender.' OR gender=0)')->num_rows < 1 ) return "eyebrows";
		if($db->query('SELECT * FROM avatar_item_definitions WHERE item_id='.(int)$items['avatar_nose'].' AND category="nose" AND public=1 AND (gender='.(int)$gender.' OR gender=0)')->num_rows < 1 ) return "nose";
		if($db->query('SELECT * FROM avatar_item_definitions WHERE item_id='.(int)$items['avatar_mouth'].' AND category="mouth" AND public=1 AND (gender='.(int)$gender.' OR gender=0)')->num_rows < 1 ) return "mouth";
		if($db->query('SELECT * FROM avatar_item_definitions WHERE item_id='.(int)$items['avatar_facialfeatures'].' AND category="facialfeatures" AND public=1 AND (gender='.(int)$gender.' OR gender=0)')->num_rows < 1 ) return "facialfeatures";
		if($db->query('SELECT * FROM avatar_item_definitions WHERE item_id='.(int)$items['avatar_items'].' AND category="items" AND public=1 AND (gender='.(int)$gender.' OR gender=0)')->num_rows < 1 && (isset($items['avatar_items']) && (int)$items['avatar_items']==0)) return "items";
		if(!isset($items['avatar_items'])) $items['avatar_items']=0;
		$exists = $db->query('SELECT * FROM user_avatars WHERE user_id='.(int)$user_id)->num_rows;
		if($exists){
			$query = 'UPDATE user_avatars SET
				gender='.(int)$gender.', 
				color='.(int)$color.', 
				shirt='.(int)$items['avatar_shirt'].', 
				pants='.(int)$items['avatar_pants'].', 
				hair='.(int)$items['avatar_hair'].', 
				ears='.(int)$items['avatar_ears'].', 
				eyes='.(int)$items['avatar_eyes'].', 
				eyebrows='.(int)$items['avatar_eyebrows'].', 
				nose='.(int)$items['avatar_nose'].', 
				mouth='.(int)$items['avatar_mouth'].', 
				facialfeatures='.(int)$items['avatar_facialfeatures'].', 
				items='.(int)$items['avatar_items'].'
				WHERE user_id='.(int)$user_id;
		}else{
			$query = 'INSERT INTO user_avatars 
				(gender, color, shirt, pants, hair, ears, eyes, eyebrows, nose, mouth, facialfeatures, items, user_id) 
				VALUES (
				'.(int)$gender.', 
				'.(int)$color.', 
				'.(int)$items['avatar_shirt'].', 
				'.(int)$items['avatar_pants'].', 
				'.(int)$items['avatar_hair'].', 
				'.(int)$items['avatar_ears'].', 
				'.(int)$items['avatar_eyes'].', 
				'.(int)$items['avatar_eyebrows'].', 
				'.(int)$items['avatar_nose'].', 
				'.(int)$items['avatar_mouth'].', 
				'.(int)$items['avatar_facialfeatures'].', 
				'.(int)$items['avatar_items'].',
				'.(int)$user_id.' )';
		}
		$db->query($query);
		
		// Generate new thumbnail images
		self::drawAvatarImage($user_id, "", 1);

		return 1;
	}



	static function getBodyCoords($cat, $pose, $gender, $color){
		$cat_pose = $cat."_".$pose;
		$start = 1;
		$url = "";

		if($gender==1){
			$url = "male/male_body_01.png";
			if($cat=="head"){
				$start = 1;
			}elseif($cat=="legs"){
				$start = 17;
				if($cat_pose=="legs_2") $start = 1000;	// No legs
			}elseif($cat=="body"){
				if($cat_pose=="body_1") $start = 33;
				if($cat_pose=="body_2") $start = 49;
				if($cat_pose=="body_3") $start = 65;
				if($cat_pose=="body_4") $start = 81;
				if($cat_pose=="body_5") $start = 97;
			}elseif($cat=="ears"){
				$url = "male/male_ears_01.png";
				$start = ($pose-1)*16+1;
			}
		}
		elseif($gender==2){
			$url = "female/female_body_01.png";
			if($cat=="head"){
				$start = 1;
			}elseif($cat=="legs"){
				$start = 17;
				if($cat_pose=="legs_2") $start = 1000;	// No legs
			}elseif($cat=="body"){
				if($cat_pose=="body_1") $start = 33;
				if($cat_pose=="body_2") $start = 49;
				if($cat_pose=="body_3") $start = 65;
				if($cat_pose=="body_4") $start = 81;
			}elseif($cat=="ears"){
				$url = "female/female_ears_01.png";
				$start = ($pose-1)*16+1;
			}
		}
		$i = $color;

		$x = ($start-1 + $i-1)%14 +1;
		$y = (int)(($start-1 + $i-1)/14+1);
		return array( "x"=>$x, "y"=>$y, "url"=>$url );
	}


	static function drawAvatar($user_id=0, $for_source=0, $size=72) {
		//for_source:
				// 0=normal
				// 1=for editor
		global $db,$user,$cdn;

		$query = "
		SELECT
			user_avatars.gender as gender, user_avatars.color as color, 
			ears.item_id as ears_id, ears.title as ears_title, ears.spritesheet_url as ears_url, ears.x as ears_x, ears.y as ears_y, ears.pose as ears_pose,
			eyes.item_id as eyes_id, eyes.title as eyes_title, eyes.spritesheet_url as eyes_url, eyes.x as eyes_x, eyes.y as eyes_y, eyes.pose as eyes_pose,
			eyebrows.item_id as eyebrows_id, eyebrows.title as eyebrows_title, eyebrows.spritesheet_url as eyebrows_url, eyebrows.x as eyebrows_x, eyebrows.y as eyebrows_y, eyebrows.pose as eyebrows_pose,
			mouth.item_id as mouth_id, mouth.title as mouth_title, mouth.spritesheet_url as mouth_url, mouth.x as mouth_x, mouth.y as mouth_y, mouth.pose as mouth_pose,
			nose.item_id as nose_id, nose.title as nose_title, nose.spritesheet_url as nose_url, nose.x as nose_x, nose.y as nose_y, nose.pose as nose_pose,
			hair.item_id as hair_id, hair.title as hair_title, hair.spritesheet_url as hair_url, hair.x as hair_x, hair.y as hair_y, hair.pose as hair_pose,
			facialfeatures.item_id as facialfeatures_id, facialfeatures.title as facialfeatures_title, facialfeatures.spritesheet_url as facialfeatures_url, facialfeatures.x as facialfeatures_x, facialfeatures.y as facialfeatures_y, facialfeatures.pose as facialfeatures_pose,
			items.item_id as items_id, items.title as items_title, items.spritesheet_url as items_url, items.x as items_x, items.y as items_y, items.pose as items_pose,
			shirt.item_id as shirt_id, shirt.title as shirt_title, shirt.spritesheet_url as shirt_url, shirt.x as shirt_x, shirt.y as shirt_y, shirt.pose as shirt_pose,
			pants.item_id as pants_id, pants.title as pants_title, pants.spritesheet_url as pants_url, pants.x as pants_x, pants.y as pants_y, pants.pose as pants_pose
		FROM `user_avatars`
			LEFT JOIN `avatar_item_definitions` ears ON user_avatars.ears = ears.item_id
			LEFT JOIN `avatar_item_definitions` eyes ON user_avatars.eyes = eyes.item_id
			LEFT JOIN `avatar_item_definitions` eyebrows ON user_avatars.eyebrows = eyebrows.item_id
			LEFT JOIN `avatar_item_definitions` mouth ON user_avatars.mouth = mouth.item_id
			LEFT JOIN `avatar_item_definitions` nose ON user_avatars.nose = nose.item_id
			LEFT JOIN `avatar_item_definitions` hair ON user_avatars.hair = hair.item_id
			LEFT JOIN `avatar_item_definitions` facialfeatures ON user_avatars.facialfeatures = facialfeatures.item_id
			LEFT JOIN `avatar_item_definitions` items ON user_avatars.items = items.item_id
			LEFT JOIN `avatar_item_definitions` shirt ON user_avatars.shirt = shirt.item_id
			LEFT JOIN `avatar_item_definitions` pants ON user_avatars.pants = pants.item_id
		WHERE user_id=".(int)$user_id;

		if( $for_source==1 ){

			$result = $db->query($query);
			$avatar = $result->fetch_assoc();

			$randomize=0;
			if(!$avatar){
				$randomize=1;
				$avatar['gender'] = rand(1,2);
			}

			$legs = self::getBodyCoords("legs", $avatar['pants_pose'], $avatar['gender'], $avatar['color']);
			$body = self::getBodyCoords("body", $avatar['shirt_pose'], $avatar['gender'], $avatar['color']);
			$head = self::getBodyCoords("head", 1, $avatar['gender'], $avatar['color']);
			$ears = self::getBodyCoords("ears", $avatar['ears_pose'], $avatar['gender'], $avatar['color']);

			$avatar['legs_url']=$legs['url']; $avatar['legs_x']=$legs['x']; $avatar['legs_y']=$legs['y'];
			$avatar['body_url']=$body['url']; $avatar['body_x']=$body['x']; $avatar['body_y']=$body['y'];
			$avatar['head_url']=$head['url']; $avatar['head_x']=$head['x']; $avatar['head_y']=$head['y'];
			$avatar['ears_url']=$ears['url']; $avatar['ears_x']=$ears['x']; $avatar['ears_y']=$ears['y'];

			?>
			<div class="avatar edit">
				<div id="avatar_hairback" class="avatar_layer<?php if($for_source) echo " large"; ?>"<?php if(isset($avatar['hair_url'])){ ?> style="background: url('//<?= $cdn."/img/avatar/".str_replace("hairfront","hairback",$avatar['hair_url']); ?>') no-repeat; background-position: -<?= $size*($avatar['hair_x']-1); ?>px -<?= $size*($avatar['hair_y']-1); ?>px;"<?php } ?>></div>
				<div id="avatar_legs" class="avatar_layer<?php if($for_source) echo " large"; ?>"<?php if(isset($avatar['legs_url'])){ ?> style="background: url('//<?= $cdn."/img/avatar/".$avatar['legs_url']; ?>') no-repeat; background-position: -<?= $size*($avatar['legs_x']-1); ?>px -<?= $size*($avatar['legs_y']-1); ?>px;"<?php } ?>></div>
				<div id="avatar_pants" class="avatar_layer<?php if($for_source) echo " large"; ?>"<?php if(isset($avatar['pants_url'])){ ?> style="background: url('//<?= $cdn."/img/avatar/".$avatar['pants_url']; ?>') no-repeat; background-position: -<?= $size*($avatar['pants_x']-1); ?>px -<?= $size*($avatar['pants_y']-1); ?>px;"<?php } ?>></div>
				<div id="avatar_body" class="avatar_layer<?php if($for_source) echo " large"; ?>"<?php if(isset($avatar['body_url'])){ ?> style="background: url('//<?= $cdn."/img/avatar/".$avatar['body_url']; ?>') no-repeat; background-position: -<?= $size*($avatar['body_x']-1); ?>px -<?= $size*($avatar['body_y']-1); ?>px;"<?php } ?>></div>
				<div id="avatar_shirt" class="avatar_layer<?php if($for_source) echo " large"; ?>"<?php if(isset($avatar['shirt_url'])){ ?> style="background: url('//<?= $cdn."/img/avatar/".$avatar['shirt_url']; ?>') no-repeat; background-position: -<?= $size*($avatar['shirt_x']-1); ?>px -<?= $size*($avatar['shirt_y']-1); ?>px;"<?php } ?>></div>
				<div id="avatar_items" class="avatar_layer<?php if($for_source) echo " large"; ?>"<?php if(isset($avatar['items_url'])){ ?> style="background: url('//<?= $cdn."/img/avatar/".$avatar['items_url']; ?>') no-repeat; background-position: -<?= $size*($avatar['items_x']-1); ?>px -<?= $size*($avatar['items_y']-1); ?>px;"<?php } ?>></div>
				<div id="avatar_head" class="avatar_layer<?php if($for_source) echo " large"; ?>"<?php if(isset($avatar['head_url'])){ ?> style="background: url('//<?= $cdn."/img/avatar/".$avatar['head_url']; ?>') no-repeat; background-position: -<?= $size*($avatar['head_x']-1); ?>px -<?= $size*($avatar['head_y']-1); ?>px;"<?php } ?>></div>
				<div id="avatar_facialfeatures" class="avatar_layer<?php if($for_source) echo " large"; ?>"<?php if(isset($avatar['facialfeatures_url'])){ ?> style="background: url('//<?= $cdn."/img/avatar/".$avatar['facialfeatures_url']; ?>') no-repeat; background-position: -<?= $size*($avatar['facialfeatures_x']-1); ?>px -<?= $size*($avatar['facialfeatures_y']-1); ?>px;"<?php } ?>></div>
				<div id="avatar_hairfront" class="avatar_layer<?php if($for_source) echo " large"; ?>"<?php if(isset($avatar['hair_url'])){ ?> style="background: url('//<?= $cdn."/img/avatar/".$avatar['hair_url']; ?>') no-repeat; background-position: -<?= $size*($avatar['hair_x']-1); ?>px -<?= $size*($avatar['hair_y']-1); ?>px;"<?php } ?>></div>
				<div id="avatar_eyes" class="avatar_layer<?php if($for_source) echo " large"; ?>"<?php if(isset($avatar['eyes_url'])){ ?> style="background: url('//<?= $cdn."/img/avatar/".$avatar['eyes_url']; ?>') no-repeat; background-position: -<?= $size*($avatar['eyes_x']-1); ?>px -<?= $size*($avatar['eyes_y']-1); ?>px;"<?php } ?>></div>
				<div id="avatar_eyebrows" class="avatar_layer<?php if($for_source) echo " large"; ?>"<?php if(isset($avatar['eyebrows_url'])){ ?> style="background: url('//<?= $cdn."/img/avatar/".$avatar['eyebrows_url']; ?>') no-repeat; background-position: -<?= $size*($avatar['eyebrows_x']-1); ?>px -<?= $size*($avatar['eyebrows_y']-1); ?>px;"<?php } ?>></div>
				<div id="avatar_nose" class="avatar_layer<?php if($for_source) echo " large"; ?>"<?php if(isset($avatar['nose_url'])){ ?> style="background: url('//<?= $cdn."/img/avatar/".$avatar['nose_url']; ?>') no-repeat; background-position: -<?= $size*($avatar['nose_x']-1); ?>px -<?= $size*($avatar['nose_y']-1); ?>px;"<?php } ?>></div>
				<div id="avatar_mouth" class="avatar_layer<?php if($for_source) echo " large"; ?>"<?php if(isset($avatar['mouth_url'])){ ?> style="background: url('//<?= $cdn."/img/avatar/".$avatar['mouth_url']; ?>') no-repeat; background-position: -<?= $size*($avatar['mouth_x']-1); ?>px -<?= $size*($avatar['mouth_y']-1); ?>px;"<?php } ?>></div>
				<div id="avatar_ears" class="avatar_layer<?php if($for_source) echo " large"; ?>"<?php if(isset($avatar['ears_url'])){ ?> style="background: url('//<?= $cdn."/img/avatar/".$avatar['ears_url']; ?>') no-repeat; background-position: -<?= $size*($avatar['ears_x']-1); ?>px -<?= $size*($avatar['ears_y']-1); ?>px;"<?php } ?>></div>
				<script>
					var gender = <?= $avatar['gender']; ?>;
					var og = <?php if($avatar['gender']==1){ echo "2"; }else{ echo "1"; } ?>;
					$(document).ready(function(){
						randomizeAvatar(og, 1);
						<?php if($randomize==1){ ?>randomizeAvatar(gender);<?php } ?>

						$('#avatar_gender_input').val(gender);
					});
				</script>
			</div>
		<?php

		}elseif($for_source==0){

			if($user_id!=0){

				$avatar = self::getAvatarArray($user_id);

				if(isset($avatar) && $avatar!=0){
				?>
				<div class="avatar_layer"<?php if(isset($avatar['hair_url'])){ ?> style="background: url('//<?= $cdn."/img/avatar/".str_replace("hairfront","hairback",$avatar['hair_url']); ?>') no-repeat; background-position: -<?= $size*($avatar['hair_x']-1); ?>px -<?= $size*($avatar['hair_y']-1); ?>px;"<?php } ?>></div>
				<div class="avatar_layer"<?php if(isset($avatar['legs_url'])){ ?> style="background: url('//<?= $cdn."/img/avatar/".$avatar['legs_url']; ?>') no-repeat; background-position: -<?= $size*($avatar['legs_x']-1); ?>px -<?= $size*($avatar['legs_y']-1); ?>px;"<?php } ?>></div>
				<div class="avatar_layer"<?php if(isset($avatar['pants_url'])){ ?> style="background: url('//<?= $cdn."/img/avatar/".$avatar['pants_url']; ?>') no-repeat; background-position: -<?= $size*($avatar['pants_x']-1); ?>px -<?= $size*($avatar['pants_y']-1); ?>px;"<?php } ?>></div>
				<div class="avatar_layer"<?php if(isset($avatar['body_url'])){ ?> style="background: url('//<?= $cdn."/img/avatar/".$avatar['body_url']; ?>') no-repeat; background-position: -<?= $size*($avatar['body_x']-1); ?>px -<?= $size*($avatar['body_y']-1); ?>px;"<?php } ?>></div>
				<div class="avatar_layer"<?php if(isset($avatar['shirt_url'])){ ?> style="background: url('//<?= $cdn."/img/avatar/".$avatar['shirt_url']; ?>') no-repeat; background-position: -<?= $size*($avatar['shirt_x']-1); ?>px -<?= $size*($avatar['shirt_y']-1); ?>px;"<?php } ?>></div>
				<div class="avatar_layer"<?php if(isset($avatar['items_url'])){ ?> style="background: url('//<?= $cdn."/img/avatar/".$avatar['items_url']; ?>') no-repeat; background-position: -<?= $size*($avatar['items_x']-1); ?>px -<?= $size*($avatar['items_y']-1); ?>px;"<?php } ?>></div>
				<div class="avatar_layer"<?php if(isset($avatar['head_url'])){ ?> style="background: url('//<?= $cdn."/img/avatar/".$avatar['head_url']; ?>') no-repeat; background-position: -<?= $size*($avatar['head_x']-1); ?>px -<?= $size*($avatar['head_y']-1); ?>px;"<?php } ?>></div>
				<div class="avatar_layer"<?php if(isset($avatar['facialfeatures_url'])){ ?> style="background: url('//<?= $cdn."/img/avatar/".$avatar['facialfeatures_url']; ?>') no-repeat; background-position: -<?= $size*($avatar['facialfeatures_x']-1); ?>px -<?= $size*($avatar['facialfeatures_y']-1); ?>px;"<?php } ?>></div>
				<div class="avatar_layer"<?php if(isset($avatar['hair_url'])){ ?> style="background: url('//<?= $cdn."/img/avatar/".$avatar['hair_url']; ?>') no-repeat; background-position: -<?= $size*($avatar['hair_x']-1); ?>px -<?= $size*($avatar['hair_y']-1); ?>px;"<?php } ?>></div>
				<div class="avatar_layer"<?php if(isset($avatar['eyes_url'])){ ?> style="background: url('//<?= $cdn."/img/avatar/".$avatar['eyes_url']; ?>') no-repeat; background-position: -<?= $size*($avatar['eyes_x']-1); ?>px -<?= $size*($avatar['eyes_y']-1); ?>px;"<?php } ?>></div>
				<div class="avatar_layer"<?php if(isset($avatar['eyebrows_url'])){ ?> style="background: url('//<?= $cdn."/img/avatar/".$avatar['eyebrows_url']; ?>') no-repeat; background-position: -<?= $size*($avatar['eyebrows_x']-1); ?>px -<?= $size*($avatar['eyebrows_y']-1); ?>px;"<?php } ?>></div>
				<div class="avatar_layer"<?php if(isset($avatar['nose_url'])){ ?> style="background: url('//<?= $cdn."/img/avatar/".$avatar['nose_url']; ?>') no-repeat; background-position: -<?= $size*($avatar['nose_x']-1); ?>px -<?= $size*($avatar['nose_y']-1); ?>px;"<?php } ?>></div>
				<div class="avatar_layer"<?php if(isset($avatar['mouth_url'])){ ?> style="background: url('//<?= $cdn."/img/avatar/".$avatar['mouth_url']; ?>') no-repeat; background-position: -<?= $size*($avatar['mouth_x']-1); ?>px -<?= $size*($avatar['mouth_y']-1); ?>px;"<?php } ?>></div>
				<div class="avatar_layer"<?php if(isset($avatar['ears_url'])){ ?> style="background: url('//<?= $cdn."/img/avatar/".$avatar['ears_url']; ?>') no-repeat; background-position: -<?= $size*($avatar['ears_x']-1); ?>px -<?= $size*($avatar['ears_y']-1); ?>px;"<?php } ?>></div>
				<div class="placeholder_avatar" style="width: <?= $size; ?>px; height: <?= $size; ?>px;"></div>
				<?php

				}else{
					//No avatar found:
					?>
				<div class="guest_avatar" style="width: <?= $size; ?>px; height: <?= $size; ?>px;"></div>
					<?php
				}

			}else{
				//$user_id was 0:
				?>
				<div class="guest_avatar" style="width: <?= $size; ?>px; height: <?= $size; ?>px;"></div>
				<?php
			}
		}

	}
	
	
	static function drawAvatarImage($user_id, $size="", $force_regen=0) {
		global $db,$user,$cdn;
		$path = (strpos($_SERVER['HTTP_HOST'], "test")===false)? "": "test/";
	
		$user_id = (int)$user_id;
		// Assume we will get a valid avatar.
		$guest = 0;
	
		// Throw error if invalid user_id.
		if($user_id<1){
			return -1;
	
		}else{
			// See whether the user exists.
			$query = "SELECT * FROM users WHERE user_id=".(int)$user_id;
			$result = $db->query($query);
			if( $result->num_rows < 1 ){
				// No.
				$avatar = 0;
				$guest = 1;
			
			}else{
				// Yes. See if we can get the avatar array.
				$avatar = self::getAvatarArray($user_id);
			
			}
		}
	
		// Only show the guest avatar if $guest==1 and $avatar is 0.
		if($guest==0 && $avatar){
			$image = new Imagick();
			$image->newImage(144, 144, new ImagickPixel("transparent"));
			$image->setImageFormat('png');
		}else{
			$image = new Imagick("/var/www/".$path."dojo/img/avatar/guest-avatar.png");
			$image->setImageFormat('png');
		}
	
	
		$layers = array();
		if(isset($avatar['hair_url']))	$layers[] = array( new Imagick( "/var/www/".$path."dojo/img/avatar/".str_replace("hairfront","hairback",$avatar['hair_url']) ), $avatar['hair_x'], $avatar['hair_y'] );
		if(isset($avatar['legs_url']))	$layers[] = array( new Imagick( "/var/www/".$path."dojo/img/avatar/".$avatar['legs_url'] ), $avatar['legs_x'], $avatar['legs_y'] );
		if(isset($avatar['pants_url']))	$layers[] = array( new Imagick( "/var/www/".$path."dojo/img/avatar/".$avatar['pants_url'] ), $avatar['pants_x'], $avatar['pants_y'] );
		if(isset($avatar['body_url']))	$layers[] = array( new Imagick( "/var/www/".$path."dojo/img/avatar/".$avatar['body_url'] ), $avatar['body_x'], $avatar['body_y'] );
		if(isset($avatar['shirt_url']))	$layers[] = array( new Imagick( "/var/www/".$path."dojo/img/avatar/".$avatar['shirt_url'] ), $avatar['shirt_x'], $avatar['shirt_y'] );
		if(isset($avatar['items_url']))	$layers[] = array( new Imagick( "/var/www/".$path."dojo/img/avatar/".$avatar['items_url'] ), $avatar['items_x'], $avatar['items_y'] );
		if(isset($avatar['head_url']))	$layers[] = array( new Imagick( "/var/www/".$path."dojo/img/avatar/".$avatar['head_url'] ), $avatar['head_x'], $avatar['head_y'] );
		if(isset($avatar['facial_features_url']))	$layers[] = array( new Imagick( "/var/www/".$path."dojo/img/avatar/".$avatar['facial_features_url'] ), $avatar['facial_features_x'], $avatar['facial_features_y'] );
		if(isset($avatar['hair_url']))	$layers[] = array( new Imagick( "/var/www/".$path."dojo/img/avatar/".$avatar['hair_url'] ), $avatar['hair_x'], $avatar['hair_y'] );
		if(isset($avatar['eyes_url']))	$layers[] = array( new Imagick( "/var/www/".$path."dojo/img/avatar/".$avatar['eyes_url'] ), $avatar['eyes_x'], $avatar['eyes_y'] );
		if(isset($avatar['eyebrows_url']))	$layers[] = array( new Imagick( "/var/www/".$path."dojo/img/avatar/".$avatar['eyebrows_url'] ), $avatar['eyebrows_x'], $avatar['eyebrows_y'] );
		if(isset($avatar['nose_url']))	$layers[] = array( new Imagick( "/var/www/".$path."dojo/img/avatar/".$avatar['nose_url'] ), $avatar['nose_x'], $avatar['nose_y'] );
		if(isset($avatar['mouth_url']))	$layers[] = array( new Imagick( "/var/www/".$path."dojo/img/avatar/".$avatar['mouth_url'] ), $avatar['mouth_x'], $avatar['mouth_y'] );
		if(isset($avatar['ears_url']))	$layers[] = array( new Imagick( "/var/www/".$path."dojo/img/avatar/".$avatar['ears_url'] ), $avatar['ears_x'], $avatar['ears_y'] );
	
		for ($i=0; $i<count($layers); $i++) {
			$image->compositeImage($layers[$i][0], imagick::COMPOSITE_OVER, ($layers[$i][1]-1)*-144, ($layers[$i][2]-1)*-144);
		}
		
	
		/*
		 note: If $size isn't [tiny,small,large] then we don't need to fetch the image. We are probably forcing a regeneration.
	 
		 ===== paths ======
	 
		 * If $force_regen is 1, then we must regenerate all 3 images.
		   - If $size is [tiny,small,large] then we fetch the image AND regenerate all 3 images.
		   - Otherwise, we just generate all 3 images.
	 
		 * If $force_regen is 0, then we only generate each of the 3 images if they doesn't already exist.
		   Ex: we could generate 2 of the 3 images, if 1 already existed.
		   - If $size is [tiny,small,large] then we fetch the image AND generate any of the 3 images that don't already exist.
		   - Otherwise, we just generate any of the 3 images that don't already exist.
	   
		 */
	
	
		// Tiny:
		// ==============
		$tiny_image = clone $image;
		if($guest==0){
			$tiny_image->cropImage(96,96,20,6);
			$tiny_image->scaleImage(40, 40);
		}else{
			$tiny_image->cropImage(90,90,26,18);
			$tiny_image->scaleImage(40, 40);
		}
		$filename = "/var/www/".$path."dojo/img/avatar/user/".$user_id."_tiny.png";
		if( $force_regen || !file_exists($filename) ){
			$tiny_image->writeImage($filename);
			$compressed_tiny_image = compress_png($filename, 50, 20);
			file_put_contents($filename, $compressed_tiny_image);
		}else{
			$compressed_tiny_image = file_get_contents($filename);
		}
	
	
		// Small:
		// ==============
		$small_image = clone $image;
		if($guest==0){
			$small_image->cropImage(96,96,20,6);
			$small_image->scaleImage(48, 48);
		}else{
			$small_image->cropImage(90,90,26,18);
			$small_image->scaleImage(48, 48);
		}
		$filename = "/var/www/".$path."dojo/img/avatar/user/".$user_id."_small.png";
		if( $force_regen || !file_exists($filename) ){
			$small_image->writeImage($filename);
			$compressed_small_image = compress_png($filename, 50, 20);
			file_put_contents($filename, $compressed_small_image);
		}else{
			$compressed_small_image = file_get_contents($filename);
		}
	
	
		// Large:
		// ==============
		// just use $image, no need to clone
		$filename = "/var/www/".$path."dojo/img/avatar/user/".$user_id.".png";
		if( $force_regen || !file_exists($filename) ){
			$image->writeImage($filename);
			$compressed_large_image = compress_png($filename, 50, 20);
			file_put_contents($filename, $compressed_large_image);
		}else{
			$compressed_large_image = file_get_contents($filename);
		}
	
	
		// Return the image, but only if we need it
		if($size=="tiny")	
			return $compressed_tiny_image;
		elseif($size=="small")	
			return $compressed_small_image;
		elseif($size=="large")	
			return $compressed_large_image;
		else return 1;
	
	}


	static function getAvatarArray($user_id, $output_json=0){
		global $db,$user,$cdn,$apc_prefix;


		$apc_key = $apc_prefix.':useravatar:'.$user_id;
		if (!($avatar = apc_fetch($apc_key))) {

			$query = "
				SELECT
					user_avatars.gender as gender, user_avatars.color as color, 
					ears.item_id as ears_id, ears.title as ears_title, ears.spritesheet_url as ears_url, ears.x as ears_x, ears.y as ears_y, ears.pose as ears_pose,
					eyes.item_id as eyes_id, eyes.title as eyes_title, eyes.spritesheet_url as eyes_url, eyes.x as eyes_x, eyes.y as eyes_y, eyes.pose as eyes_pose,
					eyebrows.item_id as eyebrows_id, eyebrows.title as eyebrows_title, eyebrows.spritesheet_url as eyebrows_url, eyebrows.x as eyebrows_x, eyebrows.y as eyebrows_y, eyebrows.pose as eyebrows_pose,
					mouth.item_id as mouth_id, mouth.title as mouth_title, mouth.spritesheet_url as mouth_url, mouth.x as mouth_x, mouth.y as mouth_y, mouth.pose as mouth_pose,
					nose.item_id as nose_id, nose.title as nose_title, nose.spritesheet_url as nose_url, nose.x as nose_x, nose.y as nose_y, nose.pose as nose_pose,
					hair.item_id as hair_id, hair.title as hair_title, hair.spritesheet_url as hair_url, hair.x as hair_x, hair.y as hair_y, hair.pose as hair_pose,
					facialfeatures.item_id as facialfeatures_id, facialfeatures.title as facialfeatures_title, facialfeatures.spritesheet_url as facialfeatures_url, facialfeatures.x as facialfeatures_x, facialfeatures.y as facialfeatures_y, facialfeatures.pose as facialfeatures_pose,
					items.item_id as items_id, items.title as items_title, items.spritesheet_url as items_url, items.x as items_x, items.y as items_y, items.pose as items_pose,
					shirt.item_id as shirt_id, shirt.title as shirt_title, shirt.spritesheet_url as shirt_url, shirt.x as shirt_x, shirt.y as shirt_y, shirt.pose as shirt_pose,
					pants.item_id as pants_id, pants.title as pants_title, pants.spritesheet_url as pants_url, pants.x as pants_x, pants.y as pants_y, pants.pose as pants_pose
				FROM `user_avatars`
					LEFT JOIN `avatar_item_definitions` ears ON user_avatars.ears = ears.item_id
					LEFT JOIN `avatar_item_definitions` eyes ON user_avatars.eyes = eyes.item_id
					LEFT JOIN `avatar_item_definitions` eyebrows ON user_avatars.eyebrows = eyebrows.item_id
					LEFT JOIN `avatar_item_definitions` mouth ON user_avatars.mouth = mouth.item_id
					LEFT JOIN `avatar_item_definitions` nose ON user_avatars.nose = nose.item_id
					LEFT JOIN `avatar_item_definitions` hair ON user_avatars.hair = hair.item_id
					LEFT JOIN `avatar_item_definitions` facialfeatures ON user_avatars.facialfeatures = facialfeatures.item_id
					LEFT JOIN `avatar_item_definitions` items ON user_avatars.items = items.item_id
					LEFT JOIN `avatar_item_definitions` shirt ON user_avatars.shirt = shirt.item_id
					LEFT JOIN `avatar_item_definitions` pants ON user_avatars.pants = pants.item_id
				WHERE user_id=".(int)$user_id;

			$result = $db->query($query);

			if(!($avatar_full = $result->fetch_assoc())){
				//Avatar lookup failed:
				return(0);

			}

			$legs = self::getBodyCoords("legs", $avatar_full['pants_pose'], $avatar_full['gender'], $avatar_full['color']);
			$body = self::getBodyCoords("body", $avatar_full['shirt_pose'], $avatar_full['gender'], $avatar_full['color']);
			$head = self::getBodyCoords("head", 1, $avatar_full['gender'], $avatar_full['color']);
			$ears = self::getBodyCoords("ears", $avatar_full['ears_pose'], $avatar_full['gender'], $avatar_full['color']);

			$avatar = [];

			$avatar['hair_url']=$avatar_full['hair_url']; $avatar['hair_x']=$avatar_full['hair_x']; $avatar['hair_y']=$avatar_full['hair_y'];
			$avatar['pants_url']=$avatar_full['pants_url']; $avatar['pants_x']=$avatar_full['pants_x']; $avatar['pants_y']=$avatar_full['pants_y'];
			$avatar['shirt_url']=$avatar_full['shirt_url']; $avatar['shirt_x']=$avatar_full['shirt_x']; $avatar['shirt_y']=$avatar_full['shirt_y'];
			$avatar['items_url']=$avatar_full['items_url']; $avatar['items_x']=$avatar_full['items_x']; $avatar['items_y']=$avatar_full['items_y'];
			$avatar['facialfeatures_url']=$avatar_full['facialfeatures_url']; $avatar['facialfeatures_x']=$avatar_full['facialfeatures_x']; $avatar['facialfeatures_y']=$avatar_full['facialfeatures_y'];
			$avatar['eyes_url']=$avatar_full['eyes_url']; $avatar['eyes_x']=$avatar_full['eyes_x']; $avatar['eyes_y']=$avatar_full['eyes_y'];
			$avatar['eyebrows_url']=$avatar_full['eyebrows_url']; $avatar['eyebrows_x']=$avatar_full['eyebrows_x']; $avatar['eyebrows_y']=$avatar_full['eyebrows_y'];
			$avatar['nose_url']=$avatar_full['nose_url']; $avatar['nose_x']=$avatar_full['nose_x']; $avatar['nose_y']=$avatar_full['nose_y'];
			$avatar['mouth_url']=$avatar_full['mouth_url']; $avatar['mouth_x']=$avatar_full['mouth_x']; $avatar['mouth_y']=$avatar_full['mouth_y'];
			$avatar['legs_url']=$legs['url']; $avatar['legs_x']=$legs['x']; $avatar['legs_y']=$legs['y'];
			$avatar['body_url']=$body['url']; $avatar['body_x']=$body['x']; $avatar['body_y']=$body['y'];
			$avatar['head_url']=$head['url']; $avatar['head_x']=$head['x']; $avatar['head_y']=$head['y'];
			$avatar['ears_url']=$ears['url']; $avatar['ears_x']=$ears['x']; $avatar['ears_y']=$ears['y'];

		}
		// Store in apc for two days
		apc_store($apc_key, $avatar, 3600*48);
		if($output_json==0){
			return $avatar;
		}else{
			echo(json_encode($avatar));
		}

	}


	static function drawEditor($user_id=0, $lite=0) {
		global $db;
		global $user;
		global $cdn;

		if(isset($user_id) && $user_id!=0){
			$query = "SELECT * FROM user_avatars WHERE user_id = ".(int)$user_id;
			$result = $db->query($query);
			$avatar = $result->fetch_assoc();
		}


		//Get definitions:
		$result = $db->query("SELECT item_id as id, title, category, gender, pose, spritesheet_url as url, x, y FROM `avatar_item_definitions` WHERE public=1 ORDER BY created ASC, item_id ASC");
		while($row = $result->fetch_assoc()){
			$row['x']=($row['x']-1)*144;
			$row['y']=($row['y']-1)*144;

			//Male or neutral
			if(($row['gender']==1 || $row['gender']==0)){
				if($row['category']!='color'){
					${"male_".$row['category']}[] = $row;

					if(isset($avatar) && $avatar[$row['category']]==$row['id']){
						$i_position["male_".$row['category']]=count(${"male_".$row['category']})-1;
					}
				}
			}

			//Female or neutral
			if(($row['gender']==2 || $row['gender']==0)){
				if($row['category']!='color'){
					${"female_".$row['category']}[] = $row;
					if(isset($avatar) && $avatar[$row['category']]==$row['id']){
						$i_position["female_".$row['category']]=count(${"female_".$row['category']})-1;
					}
				}else{

				}
			}
		}
		//Set color separately:
		if(isset($avatar) && $avatar['gender']==1){
			$i_position["male_color"] = (int)$avatar['color'];
		}else{
			$i_position["male_color"] = 1;
		}
		if(isset($avatar) && $avatar['gender']==2){
			$i_position["female_color"] = (int)$avatar['color'];
		}else{
			$i_position["female_color"] = 1;
		}
		?>
		<script>
			var i_category = "hair";
			var i_position = {
				"male_hair":<?= isset($i_position['male_hair'])?$i_position['male_hair']:0; ?>,				"female_hair":<?= isset($i_position['female_hair'])?$i_position['female_hair']:0; ?>,
				"male_ears":<?= isset($i_position['male_ears'])?$i_position['male_ears']:0; ?>,				"female_ears":<?= isset($i_position['female_ears'])?$i_position['female_ears']:0; ?>,
				"male_eyebrows":<?= isset($i_position['male_eyebrows'])?$i_position['male_eyebrows']:0; ?>,			"female_eyebrows":<?= isset($i_position['female_eyebrows'])?$i_position['female_eyebrows']:0; ?>,
				"male_eyes":<?= isset($i_position['male_eyes'])?$i_position['male_eyes']:0; ?>,				"female_eyes":<?= isset($i_position['female_eyes'])?$i_position['female_eyes']:0; ?>,
				"male_facialfeatures":<?= isset($i_position['male_facialfeatures'])?$i_position['male_facialfeatures']:0; ?>,	"female_facialfeatures":<?= isset($i_position['female_facialfeatures'])?$i_position['female_facialfeatures']:0; ?>,
				"male_color":<?= isset($i_position['male_color'])?$i_position['male_color']:1; ?>,				"female_color":<?= isset($i_position['female_color'])?$i_position['female_color']:1; ?>,
				"male_mouth":<?= isset($i_position['male_mouth'])?$i_position['male_mouth']:0; ?>,				"female_mouth":<?= isset($i_position['female_mouth'])?$i_position['female_mouth']:0; ?>,
				"male_nose":<?= isset($i_position['male_nose'])?$i_position['male_nose']:0; ?>,				"female_nose":<?= isset($i_position['female_nose'])?$i_position['female_nose']:0; ?>,
				"male_items":<?= isset($i_position['male_items'])?$i_position['male_items']:0; ?>,				"female_items":<?= isset($i_position['female_items'])?$i_position['female_items']:0; ?>,
				"male_pants":<?= isset($i_position['male_pants'])?$i_position['male_pants']:0; ?>,				"female_pants":<?= isset($i_position['female_pants'])?$i_position['female_pants']:0; ?>,
				"male_shirt":<?= isset($i_position['male_shirt'])?$i_position['male_shirt']:0; ?>,				"female_shirt":<?= isset($i_position['female_shirt'])?$i_position['female_shirt']:0; ?>
			};

			var items = {};

			items['male_eyes'] = <?= json_encode($male_eyes); ?>;
			items['male_eyebrows'] = <?= json_encode($male_eyebrows); ?>;
			items['male_ears'] = <?= json_encode($male_ears); ?>;
			items['male_mouth'] = <?= json_encode($male_mouth); ?>;
			items['male_nose'] = <?= json_encode($male_nose); ?>;
			items['male_hair'] = <?= json_encode($male_hair); ?>;
			items['male_facialfeatures'] = <?= json_encode($male_facialfeatures); ?>;
			items['male_items'] = <?= json_encode($male_items); ?>;
			items['male_shirt'] = <?= json_encode($male_shirt); ?>;
			items['male_pants'] = <?= json_encode($male_pants); ?>;

			items['female_eyes'] = <?= json_encode($female_eyes); ?>;
			items['female_eyebrows'] = <?= json_encode($female_eyebrows); ?>;
			items['female_ears'] = <?= json_encode($female_ears); ?>;
			items['female_mouth'] = <?= json_encode($female_mouth); ?>;
			items['female_nose'] = <?= json_encode($female_nose); ?>;
			items['female_hair'] = <?= json_encode($female_hair); ?>;
			items['female_facialfeatures'] = <?= json_encode($female_facialfeatures); ?>;
			items['female_items'] = <?= json_encode($female_items); ?>;
			items['female_shirt'] = <?= json_encode($female_shirt); ?>;
			items['female_pants'] = <?= json_encode($female_pants); ?>;

			<?php if(isset($avatar)){ ?>
			var legs = <?= json_encode(self::getBodyCoords("legs", $avatar['pants_pose'], $avatar['gender'], $avatar['color'])); ?>;
			var body = <?= json_encode(self::getBodyCoords("body", $avatar['shirt_pose'], $avatar['gender'], $avatar['color'])); ?>;
			var head = <?= json_encode(self::getBodyCoords("head", 1, $avatar['gender'], $avatar['color'])); ?>;
			var ears = <?= json_encode(self::getBodyCoords("ears", $avatar['ears_pose'], $avatar['gender'], $avatar['color'])); ?>;
			<?php }else{ ?>
			var legs = {};
			var body = {};
			var head = {};
			var ears = {};
			<?php } ?>


			function avatar_previous(category){
				if(category!='color'){
					if(category!='ears'){
						var key = ((gender==1)?"male_":(gender==2)?"female_":"")+category;
						i_position[key]--;
						if(i_position[key]<0){
							i_position[key] = items[key].length-1;
						}
						if(key in items && items[key]!==null){
							var newitem = items[key][i_position[key]];
							updateAvatarItem(newitem, category);
							$('.avatar_item_info').html(htmlEntities(newitem['title']));
						}else{
							$('.avatar_item_info').html('');
						}
					}else{
						var key = ((gender==1)?"male_":(gender==2)?"female_":"")+category;
						i_position[key]-=16;
						if(i_position[key]<0){
							i_position[key] = items[key].length+i_position[key];
						}
						if(key in items && items[key]!==null){
							var newitem = items[key][i_position[key]];
							updateAvatarItem(newitem, category);
							$('.avatar_item_info').html(htmlEntities(newitem['title']));
						}else{
							$('.avatar_item_info').html('');
						}
					}

				}else{
					var key = ((gender==1)?"male_":(gender==2)?"female_":"")+category;
					i_position[key]--;
					if(i_position[key]<1){
						i_position[key] = 16;
					}
					change_color(i_position[key], key);
				}
			}

			function avatar_next(category){
				if(category!='color'){
					if(category!='ears'){
						var key = ((gender==1)?"male_":(gender==2)?"female_":"")+category;
						i_position[key]++;
						if(i_position[key]>items[key].length-1){
							i_position[key] = 0;
						}
						if(key in items && items[key]!==null){
							var newitem = items[key][i_position[key]];
							updateAvatarItem(newitem, category);
							$('.avatar_item_info').html(htmlEntities(newitem['title']));
						}else{
							$('.avatar_item_info').html('');
						}
					}else{
						var key = ((gender==1)?"male_":(gender==2)?"female_":"")+category;
						i_position[key]+=16;
						if(i_position[key]>items[key].length-1){
							i_position[key] = i_position[key]-(items[key].length);
						}
						if(key in items && items[key]!==null){
							var newitem = items[key][i_position[key]];
							updateAvatarItem(newitem, category);
							$('.avatar_item_info').html(htmlEntities(newitem['title']));
						}else{
							$('.avatar_item_info').html('');
						}

					}
				}else{
					var key = ((gender==1)?"male_":(gender==2)?"female_":"")+category;
					i_position[key]++;
					if(i_position[key]>16){
						i_position[key] = 1;
					}
					change_color(i_position[key], key);
				}
			}

			function change_color(color, key, quiet){
				quiet = quiet || 0;
				var g = key.split("_")[0];

				var legs = getBodyCoords('legs', items[g+'_pants'][i_position[g+'_pants']]['pose'], gender, color);
				var body = getBodyCoords('body', items[g+'_shirt'][i_position[g+'_shirt']]['pose'], gender, color);
				var head = getBodyCoords('head', 1, gender, color);
				var ears = getBodyCoords('ears', items[g+'_ears'][i_position[g+'_ears']]['pose'], gender, color);

				var ear_key = g+'_ears';
				var ear_pose = Math.floor( i_position[ear_key]/16 ) + 1;
				i_position[ear_key] = (ear_pose-1)*16 + i_position[g+'_color'] - 1;

				if(quiet==0){
					$('#avatar_legs').css({
						"background":"url('//<?= $cdn."/img/avatar/"; ?>"+legs["url"]+"') no-repeat",
						"background-position":"-"+(144*(legs["x"]-1))+"px -"+(144*(legs["y"]-1))+"px"
					});
					$('#avatar_body').css({
						"background":"url('//<?= $cdn."/img/avatar/"; ?>"+body["url"]+"') no-repeat",
						"background-position":"-"+(144*(body["x"]-1))+"px -"+(144*(body["y"]-1))+"px"
					});
					$('#avatar_head').css({
						"background":"url('//<?= $cdn."/img/avatar/"; ?>"+head["url"]+"') no-repeat",
						"background-position":"-"+(144*(head["x"]-1))+"px -"+(144*(head["y"]-1))+"px"
					});
					$('#avatar_ears').css({
						"background":"url('//<?= $cdn."/img/avatar/"; ?>"+ears["url"]+"') no-repeat",
						"background-position":"-"+(144*(ears["x"]-1))+"px -"+(144*(ears["y"]-1))+"px"
					});
					$('#avatar_color_input').val(color);
					$('#avatar_ears_input').val( items[g+'_ears'][i_position[g+'_ears']]['id'] );
				}
			}

			function updateAvatarItem(newitem, category){
				$('#avatar_'+category.replace("female_","").replace("male_","")+'_input').val(newitem['id']);
				if(category!='color'){
					if(category=="hair"){
						$('#avatar_'+category.replace("hair","hairback").replace("female_","").replace("male_","")).css({
							"background":"url('//<?= $cdn."/img/avatar/"; ?>"+newitem["url"].replace("front","back")+"') no-repeat",
							"background-position":"-"+newitem["x"]+"px -"+newitem["y"]+"px"
						});
						$('#avatar_'+category.replace("hair","hairfront").replace("female_","").replace("male_","")).css({
							"background":"url('//<?= $cdn."/img/avatar/"; ?>"+newitem["url"]+"') no-repeat",
							"background-position":"-"+newitem["x"]+"px -"+newitem["y"]+"px"
						});
					}else{
						$('#avatar_'+category.replace("female_","").replace("male_","")).css({
							"background":"url('//<?= $cdn."/img/avatar/"; ?>"+newitem["url"]+"') no-repeat",
							"background-position":"-"+newitem["x"]+"px -"+newitem["y"]+"px"
						});
						if(category=="pants"){
							var g = (gender==1)?"male":(gender==2)?"female":"";
							var legs = getBodyCoords('legs', items[g+'_pants'][i_position[g+'_pants']]['pose'], gender, i_position[g+'_color']);
							$('#avatar_legs').css({
								"background":"url('//<?= $cdn."/img/avatar/"; ?>"+legs["url"]+"') no-repeat",
								"background-position":"-"+(144*(legs["x"]-1))+"px -"+(144*(legs["y"]-1))+"px"
							});
						}else if(category=="shirt"){
							var g = (gender==1)?"male":(gender==2)?"female":"";
							var body = getBodyCoords('body', items[g+'_shirt'][i_position[g+'_shirt']]['pose'], gender, i_position[g+'_color']);
							$('#avatar_body').css({
								"background":"url('//<?= $cdn."/img/avatar/"; ?>"+body["url"]+"') no-repeat",
								"background-position":"-"+(144*(body["x"]-1))+"px -"+(144*(body["y"]-1))+"px"
							});
						}
					}
				}
			}

			function switchCategory(cat){
				i_category = cat;
				var key = (gender==1?'male_':gender==2?'female_':'')+i_category;
				$('.avatar_cat_button').removeClass('active'); $('.'+i_category).addClass('active');
				if(cat!='color'){
					if(key in items && items[key]!==null){
						$('.avatar_item_info').html(items[key][i_position[key]]['title']);
					}else{
						$('.avatar_item_info').html('');
					}
				}else{
					$('.avatar_item_info').html('');
				}
			}

			function switchGender(setgender){
				if(setgender===undefined){
					gender = (gender==1)?2:(gender==2)?1:gender;
				}else{
					gender = setgender;
				}
				$('#avatar_gender_input').val(gender);
				redrawAvatar();

				var g = (gender==1?'male_':gender==2?'female_':'');
				var key = g+i_category;
				if(i_category!='color'){
					if(key in items && items[key]!==null){
						$('.avatar_item_info').html(items[key][i_position[key]]['title']);
					}else{
						$('.avatar_item_info').html('');
					}
				}else{
					$('.avatar_item_info').html('');
				}
				if(g=="male_"){
					$('.avatar_gender_male_button').addClass('active');
					$('.avatar_gender_female_button').removeClass('active');
					$('.avatar_cat_button').removeClass('female');
					$('.avatar_cat_button').addClass('male');
				}else if(g=="female_"){
					$('.avatar_gender_female_button').addClass('active');
					$('.avatar_gender_male_button').removeClass('active');
					$('.avatar_cat_button').removeClass('male');
					$('.avatar_cat_button').addClass('female');
				}
			}

			function randomizeAvatar(g, quiet){
				quiet = quiet || 0;
				if(quiet==0){
					gender = g;
				}
				var gendertext = ((g==1)?"male_":(g==2)?"female_":"");
				var keys = [ gendertext+"color", gendertext+"eyes", gendertext+"eyebrows", gendertext+"mouth", gendertext+"nose", gendertext+"hair", gendertext+"facialfeatures", gendertext+"items", gendertext+"shirt", gendertext+"pants" ];
				var i;
				for(i=0; i<keys.length; i++){
					var key = keys[i];
					var category = key.replace("female_","").replace("male_","");
					if((key in items && items[key]) && key!="color"){
						if(category!="ears"){
							if((category=="facialfeatures" || category=="items") && Math.random()<.5){
								i_position[key] = 0;
							}else{
								i_position[key] = Math.floor(Math.random()*(items[key].length-1)) + 1;
							}
						}
						if(quiet==0){
							var newitem = items[key][i_position[key]];
							updateAvatarItem(newitem, category);
						}
					}else if(category=="color"){
						i_position[key]=Math.floor(Math.random()*16)+1;
						var ear_key = key.replace("color","ears");
						var ear_pose = Math.floor( Math.random() * Math.floor((items[ear_key].length)/16) ) + 1;
						i_position[ear_key] = (ear_pose-1)*16 + i_position[key]-1;
						change_color(i_position[key], key, quiet);
					}else{
						i_position[key] = 0;
						if(quiet==0){
							$('#avatar_'+key.replace("female_","").replace("male_","")).css({
								"background":"",
								"background-position":""
							});
							$('#avatar_'+key.replace("female_","").replace("male_","")+'_input').val('');
						}
					}
				}
				if(quiet==0){
					var g = (g==1?'male_':g==2?'female_':'');
					var key = g+i_category;
					if(i_category!='color'){
						if(key in items && items[key]!==null){
							$('.avatar_item_info').html(items[key][i_position[key]]['title']);
						}else{
							$('.avatar_item_info').html('');
						}
					}else{
						$('.avatar_item_info').html('');
					}
					if(g=="male_"){
						$('.avatar_gender_male_button').addClass('active');
						$('.avatar_gender_female_button').removeClass('active');
					}else if(g=="female_"){
						$('.avatar_gender_female_button').addClass('active');
						$('.avatar_gender_male_button').removeClass('active');
					}
				}
			}

			function redrawAvatar(){
				var gendertext = ((gender==1)?"male_":(gender==2)?"female_":"");
				var keys = [ gendertext+"color", gendertext+"eyes", gendertext+"eyebrows", gendertext+"mouth", gendertext+"nose", gendertext+"hair", gendertext+"facialfeatures", gendertext+"items", gendertext+"shirt", gendertext+"pants" ];
				var i;
				for(i=0; i<keys.length; i++){
					var key = keys[i];
					var category = key.replace("female_","").replace("male_","");
					if((key in items && items[key] && i_position[key]<=items[key].length-1) && category!="color"){
						var newitem = items[key][i_position[key]];
						updateAvatarItem(newitem, category);
					}else if(category=="color"){
						change_color(i_position[key], key);
					}else{
						$('#avatar_'+key.replace("female_","").replace("male_","")).css({
							"background":"",
							"background-position":""
						});
						$('#avatar_'+key.replace("female_","").replace("male_","")+'_input').val('');
					}
				}
			}



			function getBodyCoords(cat, pose, gender, color){
				var cat_pose = cat+"_"+pose;
				var start = 1;
				var url = "";
				//console.log(cat+'  '+pose+'  '+gender+'  '+color);

				if(gender==1){
					url = "male/male_body_01.png";
					if(cat=="head"){
						start = 1;
					}else if(cat=="legs"){
						start = 17;
						if(cat_pose=="legs_2") start = 1000;	// No legs
					}else if(cat=="body"){
						if(cat_pose=="body_1") start = 33;
						if(cat_pose=="body_2") start = 49;
						if(cat_pose=="body_3") start = 65;
						if(cat_pose=="body_4") start = 81;
						if(cat_pose=="body_5") start = 97;
					}else if(cat=="ears"){
						url = "male/male_ears_01.png";
						start = (pose-1)*16+1;
					}
				}
				else if(gender==2){
					url = "female/female_body_01.png";
					if(cat=="head"){
						start = 1;
					}else if(cat=="legs"){
						start = 17;
						if(cat_pose=="legs_2") start = 1000;	// No legs
					}else if(cat=="body"){
						if(cat_pose=="body_1") start = 33;
						if(cat_pose=="body_2") start = 49;
						if(cat_pose=="body_3") start = 65;
						if(cat_pose=="body_4") start = 81;
					}else if(cat=="ears"){
						url = "female/female_ears_01.png";
						start = (pose-1)*16+1;
					}
				}
				var i = color;

				var x = (start-1 + i-1)%14 +1;
				var y = parseInt((start-1 + i-1)/14+1);
				return { x:x, y:y, url:url };
			}

		</script>

		<div style="text-align: center; margin: 0 auto 5px auto;">
			<a class="avatar_gender_male_button" href="javascript:void(0)" onclick="switchGender(1);"><div class="img"></div><span>Male</span></a> 
			<a class="avatar_gender_female_button" href="javascript:void(0)" onclick="switchGender(2);"><div class="img"></div><span>Female</span></a> 
			<a class="avatar_random_button" href="javascript:void(0)" onclick="randomizeAvatar(gender);">Random!</a> 
		</div>

		<?php self::drawAvatar($user_id, 1, 144); ?>
		<?php if($lite==1){ ?>
		<div id="show_full_controls" style="text-align: center; margin-top: 15px;">
			<a class="avatar_button" style="position: relative; bottom: auto; right: auto;" href="javascript:void(0);" onclick="showControls();">Customize it!</a>
		</div>
		<div id="full_controls" style="display: none;">
		<?php } ?>

		<div style="text-align: center; margin: -10px auto -5px auto;">
			<a class="avatar_prev" href="javascript:void(0)" onclick="avatar_previous(i_category);"></a>
			<div class="avatar_item_info"></div>
			<a class="avatar_next" href="javascript:void(0)" onclick="avatar_next(i_category);"></a>
		</div>

		<div style="text-align: left; line-height: 0px; margin: 0 auto; width: <?php if($lite==0){ echo "228"; }else{ echo "190"; } ?>px;">
			<a class="avatar_cat_button shirt" href="javascript:void(0)" onclick="switchCategory('shirt');"></a><a class="avatar_cat_button pants" href="javascript:void(0)" onclick="switchCategory('pants');"></a><a class="avatar_cat_button color" href="javascript:void(0)" onclick="switchCategory('color');"></a><a class="avatar_cat_button hair male active" href="javascript:void(0)" onclick="switchCategory('hair');"></a><a class="avatar_cat_button ears" href="javascript:void(0)" onclick="switchCategory('ears');"></a><a class="avatar_cat_button eyes" href="javascript:void(0)" onclick="switchCategory('eyes');"></a><a class="avatar_cat_button eyebrows" href="javascript:void(0)" onclick="switchCategory('eyebrows');"></a><a class="avatar_cat_button nose" href="javascript:void(0)" onclick="switchCategory('nose');"></a><a class="avatar_cat_button mouth" href="javascript:void(0)" onclick="switchCategory('mouth');"></a><a class="avatar_cat_button facialfeatures male" href="javascript:void(0)" onclick="switchCategory('facialfeatures');"></a><?php if($lite==0){ ?><a class="avatar_cat_button items" href="javascript:void(0)" onclick="switchCategory('items');"></a><?php } ?>
		</div>
		
		<?php if($lite==1){ ?>
		</div>
		<script>
			function showControls(){
				$('#editor_header').slideUp(300);
				$('#show_full_controls').fadeOut(300,function(){
					$('#show_full_controls').remove();
					$('#full_controls').fadeIn(500);
				});	
			}
		</script>
		<?php } ?>

		<input type="hidden" name="avatar_gender" id="avatar_gender_input" value="<?= $avatar['gender']; ?>">
		<input type="hidden" name="avatar_shirt" id="avatar_shirt_input" value="<?= $avatar['shirt']; ?>">
		<input type="hidden" name="avatar_pants" id="avatar_pants_input" value="<?= $avatar['pants']; ?>">
		<input type="hidden" name="avatar_color" id="avatar_color_input" value="<?= $avatar['color']; ?>">
		<input type="hidden" name="avatar_hair" id="avatar_hair_input" value="<?= $avatar['hair']; ?>">
		<input type="hidden" name="avatar_ears" id="avatar_ears_input" value="<?= $avatar['ears']; ?>">
		<input type="hidden" name="avatar_eyes" id="avatar_eyes_input" value="<?= $avatar['eyes']; ?>">
		<input type="hidden" name="avatar_eyebrows" id="avatar_eyebrows_input" value="<?= $avatar['eyebrows']; ?>">
		<input type="hidden" name="avatar_nose" id="avatar_nose_input" value="<?= $avatar['nose']; ?>">
		<input type="hidden" name="avatar_mouth" id="avatar_mouth_input" value="<?= $avatar['mouth']; ?>">
		<input type="hidden" name="avatar_facialfeatures" id="avatar_facialfeatures_input" value="<?= $avatar['facialfeatures']; ?>">
		<input type="hidden" name="avatar_items" id="avatar_items_input" value="<?= $avatar['items']; ?>">

		<script>
			var g = (gender==1?'male_':gender==2?'female_':'');
			var key = g+i_category;
			key = key.replace('color','head');
			$('.avatar_item_info').html(items[key][i_position[key]]['title']);
			$('.avatar_gender_'+g+'button').addClass('active');
		</script>
		<?php
	}
}
?>
