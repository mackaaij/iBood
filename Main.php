<?php
/**
 * Created by PhpStorm.
 * User: Arthur
 * Date: 21-03-17
 * Time: 11:26
 * 
 * Updated 28-03-20 by Patrick to replace PushBullet (no apps available) with PushOver and add an image
 * Note I didn't rewrite the PushOver class but opted for a fast duplication of a few lines of code
 */

include_once "web.class.php";

$web = new web();

$pushbullet_user_key = "";
$pushbullet_app_token = "";
$url = "http://feeds.ibood.com/nl/nl/offer.jsonp";

$recheckTime = 3;

$seenItems = array();

$oldId = 0;

echo "iBood Hunt Checker running every $recheckTime seconds.\n";

while (true) {
	$output = $web->get($url);

	if (strlen($output) > 20) {
		$continue = true;
		$cleaned = substr($output, 6, strlen($output) - 8);

		$decoded = json_decode($cleaned);

		$id = $decoded->Id;
		$title = $decoded->Title;
		$permaLink = $decoded->Permalink;
		$prices = $decoded->Price;
		$delivery = $decoded->Delivery;
		$image = $decoded->Image;
		$priceDeliv = $prices . $delivery . $image;

		$newPrice = "";
		$oldPrice = "";
		$korting = "";

		$deliveryDate = "";

		if (array_key_exists($id, $seenItems)) {
			if ((time() - $seenItems[$id]["firstSeen"]) < 600) {
				$continue = false;
			}
		}

		/*if ($oldId == $id) {
			$continue = false;
		}*/


		if ($continue) {
			$regexArray = array(
				"newPrice" => "/<span class=\"new-price\">(?P<variabele>(.*?|\s)+)\<\/span\>/",
				"oldPrice" => "/<span class=\"strike\">\<span\>(?P<variabele>(.*?|\s)+)\<\/span\>/",
				"korting" => "/<span class=\"discount\">(?P<variabele>(.*?|\s)+)\<\/span\>/",
				"deliveryDate" => "/Verwachte verzenddatum: (?P<variabele>(.*?|\s)+)\<\/span\>/",
				"image" => "/data-mobile=\"(?P<variabele>(.*?))\"/",
			);

			foreach ($regexArray as $key => $regex) {
				if (preg_match_all($regex, $priceDeliv, $matches)) {
					$$key = $matches['variabele'][0];
				}
			}

			file_put_contents("image.jpg",file_get_contents("https:" . $image));
			
			$message = $title . "\r\n" . "Price now: $newPrice" . "\r\n" . "Old Price: $oldPrice" . "\r\n" . "Korting: $korting" . "\r\n" . $permaLink;
			$message = "$title Voor $newPrice van $oldPrice ($korting) $permaLink";
			
			echo "\n $message \n";
			
			if (preg_match("/doos/", $title)) {
				for ($i = 0; $i < 5; $i++) {
					curl_setopt_array($ch = curl_init(), array(
						CURLOPT_URL => "https://api.pushover.net/1/messages.json",
						CURLOPT_POSTFIELDS => array(
						  "token" => $pushbullet_app_token,
						  "user" => $pushbullet_user_key,
						  "message" => "IBOOD BOX MOFO, GO GET IT",
						),
						CURLOPT_SAFE_UPLOAD => true,
						CURLOPT_RETURNTRANSFER => true,
					  ));
					  curl_exec($ch);
					  curl_close($ch);
				}
			}

			//if (date("H", time()) < 2 || date("H", time()) > 10) {
				curl_setopt_array($ch = curl_init(), array(
					CURLOPT_URL => "https://api.pushover.net/1/messages.json",
					CURLOPT_POSTFIELDS => array(
					  "token" => $pushbullet_app_token,
					  "user" => $pushbullet_user_key,
					  "message" => $message,
					  "attachment" => curl_file_create("image.jpg", "image/jpeg"),
					),
					CURLOPT_SAFE_UPLOAD => true,
					CURLOPT_RETURNTRANSFER => true,
				  ));
				  curl_exec($ch);
				  curl_close($ch);
			//}

			$oldId = $id;

			$seenItems[$id] = array("title" => $title, "firstSeen" => time());
		}
	} else {
		//$pushbullet->sendPush("Error - iBOOD", "Script didn't get a valid response from the website.", "note");
	}

	sleep($recheckTime);
}
?>
